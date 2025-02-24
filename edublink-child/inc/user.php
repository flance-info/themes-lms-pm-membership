<?php

use MasterStudy\Lms\Plugin\PostType;

//ini_set( 'display_errors', 1 );ini_set( 'display_startup_errors', 1 );error_reporting( E_ALL );
if ( class_exists( 'STM_LMS_User' ) ) {


	class STM_LMS_User_Edublink extends STM_LMS_User {

		public static function init() {

			remove_action( 'wp_ajax_stm_lms_get_user_courses', 'STM_LMS_User::get_user_courses' );
			add_action( 'wp_ajax_stm_lms_get_user_courses', 'STM_LMS_User_Edublink::get_user_courses' );
			self::enqueue_scripts();
		}

		public static function enqueue_scripts() {

			wp_register_script( 'masterstudy-enrolled-courses_edublink', get_stylesheet_directory_uri() . '/assets/js/enrolled-courses.js', array( 'jquery', 'vue.js', 'vue-resource.js' ), time(), true );
			wp_localize_script(
				'masterstudy-enrolled-courses_edublink',
				'student_data',
				array(
					'id'         => get_current_user_id(),
					'hide_stats' => __( 'Hide Statistics', 'masterstudy-lms-learning-management-system' ),
					'show_stats' => __( 'Show Statistics', 'masterstudy-lms-learning-management-system' ),
				)
			);

		}

		public static function _get_user_courses( $offset, $status = 'all' ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
			$user = self::get_current_user();
			if ( empty( $user['id'] ) ) {
				die;
			}
			$user_id = $user['id'];
			$response           = array(
				'posts' => array(),
				'total' => false,
			);
			$pp                 = get_option( 'posts_per_page' );
			$offset             = $offset * $pp;
			$response['offset'] = $offset;
			$total              = 0;
			$all_courses        = stm_lms_get_user_courses( $user_id, '', '', array() );

			// Get the user's current membership level
			$membership_level = pmpro_getMembershipLevelForUser($user_id);
			//print_r($membership_level->id);
			//print_r($all_courses);
			
			foreach ( $all_courses as $course_user ) {
				if ( get_post_type( $course_user['course_id'] ) !== 'stm-courses' ) {
					stm_lms_get_delete_courses( $course_user['course_id'] );
					continue;
				}
				++ $total;
			}
			$columns                 = array(
				'course_id',
				'current_lesson_id',
				'progress_percent',
				'subscription_id',
				'start_time',
				'status',
				'enterprise_id',
				'bundle_id',
				'for_points'
			);

			$courses                 = stm_lms_get_user_courses(
				$user_id,
				$pp,
				$offset,
				$columns,
				null,
				null,
			);

			// Get all courses of user membership plan
			$all_courses = array();
			if ($membership_level) {
				$level_id = $membership_level->id;
				$all_courses = get_option('initial_courses_' . $level_id, array());
			}

			// Process the courses
			$all_courses = get_posts(array(
                'post_type' => 'stm-courses',
                'numberposts' => -1,
            ));
			$course_ids = array_column($courses, 'course_id');
			foreach ($all_courses as $course_id) {
				$course = get_post($course_id);
				if ($course && $course->post_type === 'stm-courses') {
					if (in_array($course->ID, $course_ids)) {
						continue;
					}

					$plans_courses = STM_LMS_Course::course_in_plan( $course_id );
					if (!empty($plans_courses)) {
						continue;
					}
					$course_mod = array(
						'course_id' => $course->ID,
						'current_lesson_id' => 0,
						'progress_percent' => 0,
						'subscription_id' => '',
						'status' => 'not_enrolled',
						'enterprise_id' => 0,
						'bundle_id' => 0,
						'for_points' => '',
					);

					$courses[] = $course_mod;
				}
			}

			$response['total_posts'] = $total;
			$response['total']       = $total <= $offset + $pp;
			$response['pages']       = ceil( $total / $pp );
			if ( ! empty( $courses ) ) {
				foreach ( $courses as $course ) {
					$id = $course['course_id'];
					if ( get_post_type( $id ) !== 'stm-courses' ) {
						stm_lms_get_delete_courses( $id );
						continue;
					}
					if ( ! get_post_status( $id ) ) {
						continue;
					}
					$price      = get_post_meta( $id, 'price', true );
					$sale_price = STM_LMS_Course::get_sale_price( $id );
					if ( empty( $price ) && ! empty( $sale_price ) ) {
						$price      = $sale_price;
						$sale_price = '';
					}
					$post_status                = STM_LMS_Course::get_post_status( $id );
					$image                      = ( function_exists( 'stm_get_VC_img' ) ) ? stm_get_VC_img( get_post_thumbnail_id( $id ), '272x161' ) : get_the_post_thumbnail( $id, 'img-300-225' );
					$course['progress_percent'] = ( $course['progress_percent'] > 100 ) ? 100 : $course['progress_percent'];
					if ( 'completed' === $course['status'] ) {
						$course['progress_percent'] = '100';
					}
					$current_lesson = ( ! empty( $course['current_lesson_id'] ) ) ? $course['current_lesson_id'] : STM_LMS_Lesson::get_first_lesson( $id );
					/* Check membership status*/
					$subscription_enabled = STM_LMS_Subscriptions::subscription_enabled();
					$in_enterprise        = STM_LMS_Order::is_purchased_by_enterprise( $course, $user_id );
					$my_course            = ( intval( get_post_field( 'post_author', $id ) ) === intval( $user_id ) );
					$is_free              = ( ! get_post_meta( $id, 'not_single_sale', true ) && empty( STM_LMS_Course::get_course_price( $id ) ) );
					$is_bought            = STM_LMS_Order::has_purchased_courses( $user_id, $id );
					$in_bundle            = isset( $course['bundle_id'] ) && ! empty( $course['bundle_id'] );
					$bought_by_membership = ! empty( $course['subscription_id'] );
					$for_points           = isset( $course['for_points'] ) && ! empty( $course['for_points'] );
					$not_in_membership    = get_post_meta( $id, 'not_membership', true );
					$only_for_membership  = ! $not_in_membership && ! $is_bought && ! $is_free && ! $in_enterprise && ! $in_bundle && ! $for_points;
					$membership_level     = $subscription_enabled && STM_LMS_Subscriptions::user_has_subscription( $user_id );
					$membership_status    = ( $subscription_enabled ) ? STM_LMS_Subscriptions::get_membership_status( get_current_user_id() ) : 'inactive';
					$membership_expired   = $subscription_enabled && 'expired' === $membership_status && $only_for_membership && ! $my_course && $bought_by_membership;
					$membership_inactive  = $subscription_enabled && ! $membership_level && 'active' !== $membership_status && 'expired' !== $membership_status && $only_for_membership && ! $my_course && $bought_by_membership;
					ob_start();
					STM_LMS_Templates::show_lms_template(
						'global/expired_course',
						array(
							'course_id'     => $id,
							'expired_popup' => false,
						)
					);
					$expiration = ob_get_clean();
					$post       = array(
						'id'                  => $id,
						'url'                 => get_the_permalink( $id ),
						'image_id'            => get_post_thumbnail_id( $id ),
						'title'               => get_the_title( $id ),
						'link'                => get_the_permalink( $id ),
						'image'               => $image,
						'terms'               => wp_get_post_terms( $id, 'stm_lms_course_taxonomy' ),
						'terms_list'          => stm_lms_get_terms_array( $id, 'stm_lms_course_taxonomy', 'name' ),
						'views'               => STM_LMS_Course::get_course_views( $id ),
						'price'               => STM_LMS_Helpers::display_price( $price ),
						'sale_price'          => STM_LMS_Helpers::display_price( $sale_price ),
						'post_status'         => $post_status,
						'availability'        => get_post_meta( $id, 'coming_soon_status', true ),
						'progress'            => strval( $course['progress_percent'] ),
						/* translators: %s: course complete */
						'progress_label'      => sprintf( esc_html__( '%s%% Complete', 'masterstudy-lms-learning-management-system' ), $course['progress_percent'] ),
						'current_lesson_id'   => STM_LMS_Lesson::get_lesson_url( $id, $current_lesson ),
						'course_id'           => $id,
						'lesson_id'           => $current_lesson,
						/* translators: %s: start time */
						'start_time'          => sprintf( esc_html__( 'Started %s', 'masterstudy-lms-learning-management-system' ), date_i18n( get_option( 'date_format' ), $course['start_time'] ) ),
						'duration'            => get_post_meta( $id, 'duration_info', true ),
						'expiration'          => $expiration,
						'is_expired'          => STM_LMS_Course::is_course_time_expired( get_current_user_id(), $id ),
						'membership_expired'  => $membership_expired,
						'membership_inactive' => $membership_inactive,
						'no_membership_plan'  => $subscription_enabled && ! $membership_level && $only_for_membership && ! $my_course && $bought_by_membership,
						'course_status' => $course['status']
					);
					/* Check course complete status*/
					$curriculum       = ( new MasterStudy\Lms\Repositories\CurriculumRepository() )->get_curriculum( $id, true );
					$course_materials = array_reduce(
						$curriculum,
						function ( $carry, $section ) {
							return array_merge( $carry, $section['materials'] ?? array() );
						},
						array()
					);
					$material_ids     = array_column( $course_materials, 'post_id' );
					$last_lesson      = ! empty( $material_ids ) ? end( $material_ids ) : 0;
					$lesson_post_type = get_post_type( $last_lesson );
					if ( PostType::QUIZ === $lesson_post_type ) {
						$last_quiz        = STM_LMS_Helpers::simplify_db_array( stm_lms_get_user_last_quiz( $user_id, $last_lesson ) );
						$passing_grade    = get_post_meta( $last_lesson, 'passing_grade', true );
						$lesson_completed = ! empty( $last_quiz['progress'] ) && $last_quiz['progress'] >= ( $passing_grade ?? 0 ) ? 'completed' : '';
					} else {
						$lesson_completed = STM_LMS_Lesson::is_lesson_completed( $user_id, $id, $last_lesson ) ? 'completed' : '';
					}
					$course_passed = intval( STM_LMS_Options::get_option( 'certificate_threshold', 70 ) ) <= intval( $course['progress_percent'] );
					if ( ! empty( $lesson_completed ) && ! $course_passed ) {
						$post['complete_status'] = 'failed';
					} elseif ( intval( $course['progress_percent'] ) > 0 ) {
						$post['complete_status'] = $course_passed ? 'completed' : 'in_progress';
					} else {
						$post['complete_status'] = 'not_started';
					}


					$matches_filters = true;
					$filters = array();
					if (!empty($_GET)) {
						foreach ($_GET as $key => $value) {
							if (strpos($key, 'filter_') === 0) {
								$filters[$key] = $value;
							}
						}
					}

					if (!empty($filters)) {
						foreach ( $filters as $filter_key => $filter_value ) {
							switch ( $filter_key ) {
								case 'filter_category':
									$course_categories = wp_get_post_terms($id, 'stm_lms_course_taxonomy');
									$category_ids = array_map(function($term) {
										return $term->term_id;
									}, $course_categories);
									$matches_filters = false;
									// Convert filter_value to array if it's not already
									foreach ($filter_value as $value) {
										$value= (is_array($value)) ? $value[0] : $value;

										if ( in_array($value, $category_ids)) {
											$matches_filters = true;
											
										}
									}

									break;
								case 'filter_level':
									$course_level = get_post_meta( $id, 'level', true );
									if ( $course_level != $filter_value ) {
										$matches_filters = false;
									}
									break;
								case 'filter_rating':
									$course_rating = STM_LMS_Course::course_average_rate( $id );
									if ( $course_rating < $filter_value ) {
										$matches_filters = false;
									}
									break;
								case 'filter_price':
									$course_price = get_post_meta( $id, 'price', true );
									if ( $filter_value == 'free' && ! empty( $course_price ) ) {
										$matches_filters = false;
									} elseif ( $filter_value == 'paid' && empty( $course_price ) ) {
										$matches_filters = false;
									}
									break;
							}
							if ( ! $matches_filters ) {
								break;
							}
						}
					}


					if ( ($status === $post['complete_status'] || 'all' === $status) && $matches_filters  ) {
						$response['posts'][] = $post;
					}

					if ( empty( $response['posts'] ) ) {
						$response['total'] = true;
					}

					$response['quote_left'] = self::count_quote_left_onmembership( $user_id );
				}
			}


			return $response;
		}

		public static function get_user_courses() {
			check_ajax_referer( 'stm_lms_get_user_courses', 'nonce' );
			$offset = ( ! empty( $_GET['offset'] ) ) ? intval( $_GET['offset'] ) : 0;
			$status = ( ! empty( $_GET['status'] ) ) ? sanitize_text_field( $_GET['status'] ) : 'all';
			$r      = self::_get_user_courses( $offset, $status );
			wp_send_json( apply_filters( 'stm_lms_get_user_courses_filter', $r ) );
		}

		public static function count_quote_left_onmembership( $user_id, $membership_id =null ) {
			$r = array();
			// Get subscription info
			$sub      = STM_LMS_Subscriptions_Edublink::user_subscriptions( null, null, $membership_id );
			$r['sub'] = $sub;
			if ( ! empty( $membership_id ) ) {
				$sub  = object;
				$subs = STM_LMS_Subscriptions_Edublink::user_subscription_levels();
				if ( ! empty( $subs ) ) {
					foreach ( $subs as $subscription ) {
						if ( $subscription->subscription_id === $membership_id && $subscription->quotas_left ) {
							$sub = $subscription;
						}
					}
				}
			}
			$membership_id = $sub->subscription_id;
			$level_id      = $sub->ID;
			if ( ! empty( $membership_id ) ) {

				// Get monthly course limit from membership level settings
				$monthly_course_limit = STM_LMS_Subscriptions_Edublink::get_course_number( $level_id );
				if ( $monthly_course_limit === '0' || empty( $monthly_course_limit ) ) {
					$r['error'] = __( 'This membership level does not allow course enrollment.', 'edublink-child' );

					return $r;
				}
				// Get subscription start date
				$subscription_start = get_user_meta( $user_id, 'subscription_start_date_' . $membership_id, true );
				if ( empty( $subscription_start ) ) {
					// First time subscription - set start date
					$subscription_start = current_time( 'timestamp' );
					update_user_meta( $user_id, 'subscription_start_date_' . $membership_id, $subscription_start );
				}
				// Calculate current month's quota
				$current_month_quota = STM_LMS_Subscriptions_Edublink::calculate_current_month_quota( $subscription_start, $monthly_course_limit );
				$enrolled_courses    = stm_lms_get_user_courses_by_subscription(
					$user_id,
					$membership_id,
					array( 'user_course_id', 'start_time' ),
					null,
					'start_time DESC'
				);
				$enrolled_count = STM_LMS_Subscriptions_Edublink::count_subscription_month_enrollments( $enrolled_courses, $subscription_start );
				$quote_left = $current_month_quota - $enrolled_count ;

				// Calculate days until next quota
				$next_quota_date = STM_LMS_Subscriptions_Edublink::get_next_quota_date( $subscription_start );
				$days_remaining  = ceil( ( $next_quota_date - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
				$r['year-message']       = sprintf(
					__( 'You can unlock and study up to %d courses each month. During your 1-year subscription, all previously unlocked courses will remain available to you.', 'edublink-child' ),
					$monthly_course_limit
				);
				if ($quote_left > 0){
					$r['next-month-message'] = sprintf(
						__( 'You can unlock the  %d courses in current month.', 'edublink-child' ),
						$quote_left
					);
				}else{
					$r['next-month-message'] = sprintf(
						__( 'You can unlock the next %d courses in %d days.', 'edublink-child' ),
						$sub->quotas_left,
						$days_remaining
					);
				}
			}

			return $r;
		}

	}

	STM_LMS_User_Edublink::init();
}
