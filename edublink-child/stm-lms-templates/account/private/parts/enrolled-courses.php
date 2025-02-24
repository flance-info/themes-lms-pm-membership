<?php

stm_lms_register_style( 'user-courses' );
stm_lms_register_style( 'instructor_courses' );
stm_lms_register_style( 'expiration/main' );

wp_enqueue_script( 'masterstudy-enrolled-courses_edublink' );
$is_pro_plus                = STM_LMS_Helpers::is_pro_plus();
$options                    = get_option( 'stm_lms_settings' );
$options['student_reports'] = $options['student_reports'] ?? true;
$course_bundle              = is_ms_lms_addon_enabled( 'course_bundle' );
$enterprise                 = is_ms_lms_addon_enabled( 'enterprise_courses' );
$certificate                = is_ms_lms_addon_enabled( 'certificate_builder' );
$point                      = is_ms_lms_addon_enabled( 'point_system' );
$reviews                    = STM_LMS_Options::get_option( 'course_tab_reviews', true );
$filter_enabled             = STM_LMS_Courses::filter_enabled();
$reviews                    = $point = $certificate = $enterprise = $course_bundle = true;
$term                       = get_queried_object();
$filter_enabled             = STM_LMS_Courses::filter_enabled();
stm_lms_register_style( 'taxonomy_archive' );

// Initialize term object if null
if (empty($term)) {
	$term = new stdClass();
}

// Initialize term_id with empty array as default
$term->term_id = (!empty($term->term_id)) ? $term->term_id : [];

$args            = array(
		'per_row'        => STM_LMS_Options::get_option( 'courses_per_row', 4 ),
		'posts_per_page' => STM_LMS_Options::get_option( 'courses_per_page', get_option( 'posts_per_page' ) ),
);

// Only add tax_query if term_id is not empty
if (!empty($term->term_id)) {
	$args['tax_query'] = array(
			array(
					'taxonomy' => 'stm_lms_course_taxonomy',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
			),
	);
}

$args['class'] = 'archive_grid';

$not_empty_stats = $reviews || $point || $certificate || $enterprise || $course_bundle;
$is_pro_plus     = true;
stm_lms_register_style( 'taxonomy_archive' );
?>

<div id="enrolled-courses" class="enrolled-courses">
	<div class="stm_lms_user_info_top">
		<div class="masterstudy-enrolled-courses">
			<div class="masterstudy-enrolled-courses__title-wrapper">
				<h3 class="masterstudy-enrolled-courses__title">
					<?php echo esc_html__( 'Enrolled courses', 'masterstudy-lms-learning-management-system' ); ?>
				</h3>
				<?php if ( $is_pro_plus && $options['student_reports'] ) { ?>
					<span class="masterstudy-enrolled-courses__toggle" @click="showStats()">
						{{ statsVisible ? student_data.hide_stats : student_data.show_stats }}
					</span>
				<?php } ?>
			</div>
			<span class="masterstudy-enrolled-courses-separator">
				<span class="masterstudy-enrolled-courses-separator__short"></span>
				<span class="masterstudy-enrolled-courses-separator__long"></span>
			</span>
			<?php
			if ( $is_pro_plus && $options['student_reports'] ) {
				if ( $not_empty_stats ) {
					?>
					<div v-show="statsVisible" class="masterstudy-enrolled-courses-sorting">
						<?php if ( $course_bundle ) { ?>
							<div class="masterstudy-enrolled-courses-sorting__block">
								<div class="masterstudy-enrolled-courses-sorting__block-icon masterstudy-enrolled-courses-sorting__block-icon_bundles"></div>
								<div class="masterstudy-enrolled-courses-sorting__block-content">
									<span class="masterstudy-enrolled-courses-sorting__block-title">
										<?php echo esc_html__( 'Bundles', 'masterstudy-lms-learning-management-system' ); ?>
									</span>
									<span v-if="stats.courses_types" class="masterstudy-enrolled-courses-sorting__block-value">
										{{ stats.courses_types.bundle_count }}
									</span>
								</div>
							</div>
							<?php
						}
						if ( $enterprise ) {
							?>
							<div class="masterstudy-enrolled-courses-sorting__block">
								<div class="masterstudy-enrolled-courses-sorting__block-icon masterstudy-enrolled-courses-sorting__block-icon_groups"></div>
								<div class="masterstudy-enrolled-courses-sorting__block-content">
									<span class="masterstudy-enrolled-courses-sorting__block-title">
										<?php echo esc_html__( 'Groups', 'masterstudy-lms-learning-management-system' ); ?>
									</span>
									<span v-if="stats.courses_types" class="masterstudy-enrolled-courses-sorting__block-value">
										{{ stats.courses_types.enterprise_count }}
									</span>
								</div>
							</div>
							<?php
						}
						if ( $reviews ) {
							?>
							<div class="masterstudy-enrolled-courses-sorting__block">
								<div class="masterstudy-enrolled-courses-sorting__block-icon masterstudy-enrolled-courses-sorting__block-icon_reviews"></div>
								<div class="masterstudy-enrolled-courses-sorting__block-content">
									<span class="masterstudy-enrolled-courses-sorting__block-title">
										<?php echo esc_html__( 'Reviews', 'masterstudy-lms-learning-management-system' ); ?>
									</span>
									<span class="masterstudy-enrolled-courses-sorting__block-value">
										{{ stats.reviews }}
									</span>
								</div>
							</div>
							<?php
						}
						if ( $certificate ) {
							?>
							<div class="masterstudy-enrolled-courses-sorting__block">
								<div class="masterstudy-enrolled-courses-sorting__block-icon masterstudy-enrolled-courses-sorting__block-icon_certificates"></div>
								<div class="masterstudy-enrolled-courses-sorting__block-content">
									<span class="masterstudy-enrolled-courses-sorting__block-title">
										<?php echo esc_html__( 'Certificates', 'masterstudy-lms-learning-management-system' ); ?>
									</span>
									<span class="masterstudy-enrolled-courses-sorting__block-value">
										{{ stats.certificates }}
									</span>
								</div>
							</div>
							<?php
						}
						if ( $point ) {
							?>
							<div class="masterstudy-enrolled-courses-sorting__block">
								<div class="masterstudy-enrolled-courses-sorting__block-icon masterstudy-enrolled-courses-sorting__block-icon_points"></div>
								<div class="masterstudy-enrolled-courses-sorting__block-content">
									<span class="masterstudy-enrolled-courses-sorting__block-title">
										<?php echo esc_html__( 'Points', 'masterstudy-lms-learning-management-system' ); ?>
									</span>
									<span class="masterstudy-enrolled-courses-sorting__block-value">
										{{ stats.total_points }}
									</span>
								</div>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
				<div class="masterstudy-enrolled-courses-tabs">
					<div
							@click="getCourses('all')"
							class="masterstudy-enrolled-courses-tabs__block"
							:class="{'masterstudy-enrolled-courses-tabs__block_active': activeTab === 'all'}"
					>
						<div class="masterstudy-enrolled-courses-tabs__block-icon masterstudy-enrolled-courses-tabs__block-icon_all"></div>
						<div class="masterstudy-enrolled-courses-tabs__block-content">
							<span class="masterstudy-enrolled-courses-tabs__block-title">
								<?php echo esc_html__( 'All', 'masterstudy-lms-learning-management-system' ); ?>
							</span>
							<span v-if="stats.courses_statuses" class="masterstudy-enrolled-courses-tabs__block-value">
								{{ stats.courses_statuses.summary }}
							</span>
						</div>
					</div>
					<div
							@click="getCourses('completed')"
							class="masterstudy-enrolled-courses-tabs__block"
							:class="{'masterstudy-enrolled-courses-tabs__block_active': activeTab === 'completed'}"
					>
						<div class="masterstudy-enrolled-courses-tabs__block-icon masterstudy-enrolled-courses-tabs__block-icon_completed">
							<span class="masterstudy-enrolled-courses-tabs__block-icon-wrapper"></span>
						</div>
						<div class="masterstudy-enrolled-courses-tabs__block-content">
							<span class="masterstudy-enrolled-courses-tabs__block-title">
								<?php echo esc_html__( 'Completed', 'masterstudy-lms-learning-management-system' ); ?>
							</span>
							<span v-if="stats.courses_statuses" class="masterstudy-enrolled-courses-tabs__block-value">
								{{ stats.courses_statuses.completed }}
							</span>
						</div>
					</div>
					<div
							@click="getCourses('in_progress')"
							class="masterstudy-enrolled-courses-tabs__block"
							:class="{'masterstudy-enrolled-courses-tabs__block_active': activeTab === 'in_progress'}"
					>
						<div class="masterstudy-enrolled-courses-tabs__block-icon masterstudy-enrolled-courses-tabs__block-icon_progress"></div>
						<div class="masterstudy-enrolled-courses-tabs__block-content">
							<span class="masterstudy-enrolled-courses-tabs__block-title">
								<?php echo esc_html__( 'In progress', 'masterstudy-lms-learning-management-system' ); ?>
							</span>
							<span v-if="stats.courses_statuses" class="masterstudy-enrolled-courses-tabs__block-value">
								{{ stats.courses_statuses.in_progress }}
							</span>
						</div>
					</div>
					<div
							@click="getCourses('failed')"
							class="masterstudy-enrolled-courses-tabs__block"
							:class="{'masterstudy-enrolled-courses-tabs__block_active': activeTab === 'failed'}"
					>
						<div class="masterstudy-enrolled-courses-tabs__block-icon masterstudy-enrolled-courses-tabs__block-icon_failed">
							<span class="masterstudy-enrolled-courses-tabs__block-icon-wrapper"></span>
						</div>
						<div class="masterstudy-enrolled-courses-tabs__block-content">
							<span class="masterstudy-enrolled-courses-tabs__block-title">
								<?php echo esc_html__( 'Failed', 'masterstudy-lms-learning-management-system' ); ?>
							</span>
							<span v-if="stats.courses_statuses" class="masterstudy-enrolled-courses-tabs__block-value">
								{{ stats.courses_statuses.failed }}
							</span>
						</div>
					</div>
				</div>
			<?php } ?>
			<div id="error-message-container" class="alert alert-danger"></div>

			<div id="unlock-message-container" class="alert alert-info" style="display:block;">
				<p class="ym" v-if="yearMessage">{{ yearMessage }}</p>
				<p class="nm" v-if="nextMonthMessage">{{ nextMonthMessage }}</p>
			</div>
		</div>
	</div>
	<div class="stm_lms_courses__archive_wrapper">
		<div class="stm_lms_courses stm_lms_courses__archive">
			<div class="stm-lms-user-courses">
				<div class="multiseparator"></div>
				<div v-if="!loading" class="stm_lms_instructor_courses__grid">
					<div class="stm_lms_instructor_courses__single" v-for="course in courses"
						 v-bind:class="{'expired' : course.expiration.length && course.is_expired || course.membership_expired || course.membership_inactive}">
						<div class="stm_lms_instructor_courses__single__inner">
							<div class="stm_lms_instructor_courses__single--image">
								<div class="stm_lms_post_status heading_font"
									 v-if="course.post_status"
									 v-bind:class="course.post_status.status">
									{{ course.post_status.label }}
								</div>
								<div v-html="course.image" class="image_wrapper"></div>
								<?php STM_LMS_Templates::show_lms_template( 'account/private/parts/expiration' ); ?>
							</div>
							<div class="stm_lms_instructor_courses__single--inner">
								<div class="stm_lms_instructor_courses__single--terms" v-if="course.terms">
									<div class="stm_lms_instructor_courses__single--term" v-for="(term, key) in course.terms">
										<a :href="'<?php echo esc_url( STM_LMS_Course::courses_page_url() ); ?>' + '?terms[]=' + term.term_id + '&category[]=' + term.term_id" v-if="key === 0">
											{{ term.name }}
										</a>
									</div>
								</div>
								<div class="stm_lms_instructor_courses__single--title">
									<a v-bind:href="course.link">
										<h5 v-html="course.title"></h5>
									</a>
								</div>
								<div class="stm_lms_instructor_courses__single--progress">
									<div class="stm_lms_instructor_courses__single--progress_top">
										<div class="stm_lms_instructor_courses__single--duration" v-if="course.duration">
											<i class="far fa-clock"></i>
											{{ course.duration }}
										</div>
										<div class="stm_lms_instructor_courses__single--completed">
											{{ course.progress_label }}
										</div>
									</div>
									<div class="stm_lms_instructor_courses__single--progress_bar">
										<div class="stm_lms_instructor_courses__single--progress_filled"
											 v-bind:style="{'width' : course.progress + '%'}"></div>
									</div>
								</div>
								<div class="stm_lms_instructor_courses__single--enroll">
									<a v-if="course.expiration.length && course.is_expired || course.membership_expired || course.membership_inactive || course.no_membership_plan" class="btn btn-default"
									   :href="course.url" target="_blank">
										<span><?php esc_html_e( 'Preview Course', 'masterstudy-lms-learning-management-system' ); ?></span>
									</a>
									<?php
									if ( is_ms_lms_addon_enabled( 'coming_soon' ) ) {
										?>
										<a v-bind:href="course.current_lesson_id" class="btn btn-default"
										   v-bind:class="{
									'continue': course.progress !== '0',
									'disabled coming-soon-not-allowed': course.availability === '1'
								}"
										   v-else>
											<span v-if="course.progress === '0' && course.availability === ''"><?php esc_html_e( 'Start Course', 'masterstudy-lms-learning-management-system' ); ?></span>
											<?php
											if ( is_ms_lms_addon_enabled( 'coming_soon' ) ) {
												?>
												<span
														v-else-if="course.availability === '1'"><?php esc_html_e( 'Coming soon', 'masterstudy-lms-learning-management-system' ); ?></span>
												<?php
											}
											?>
											<span v-else-if="course.progress === '100'"><?php esc_html_e( 'Completed', 'masterstudy-lms-learning-management-system' ); ?></span>
											<span v-else><?php esc_html_e( 'Continue', 'masterstudy-lms-learning-management-system' ); ?></span>
										</a>
										<?php
									} else {
										?>
										
											<a v-bind:href="course.current_lesson_id" class="btn btn-default"
										   v-bind:class="{
									'continue': course.progress !== '0',
								}"
										   v-else-if="course.course_status === 'enrolled'">
											<span v-if="course.progress === '0' && course.availability === ''"><?php esc_html_e( 'Start Course', 'masterstudy-lms-learning-management-system' ); ?></span>
											<?php
											if ( is_ms_lms_addon_enabled( 'coming_soon' ) ) {
												?>
												<span
														v-else-if="course.availability === '1'"><?php esc_html_e( 'Coming soon', 'masterstudy-lms-learning-management-system' ); ?></span>
												<?php
											}
											?>
											<span v-else-if="course.progress === '100'"><?php esc_html_e( 'Completed', 'masterstudy-lms-learning-management-system' ); ?></span>
											<span v-else><?php esc_html_e( 'Continue', 'masterstudy-lms-learning-management-system' ); ?></span>
										</a>
										<a v-else="course.course_status === 'enrolled'"  class="btn btn-default"
										data-masterstudy-modal="masterstudy-membership-modal"
										 @click.prevent="unlockCourse(course.course_id)">
										<span>	
											<?php esc_html_e( 'Unlock', 'edublink-child' ); ?>
										</span>
										</a>

        
										<?php
									}
									?>
								</div>
								<div class="stm_lms_instructor_courses__single--started">
									{{ course.start_time }}
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="stm-lms-course-no-result" id="stm-lms-course-no-result" v-if="!courses.length && !loading">
					<div class="no-found">
						<div class="no-result-background">
							<span class="no-result-icon"></span>
						</div>
						<div class="no-found-icon">
							<i class="stmlms-not_found_courses"></i>
						</div>
					</div>
					<p>
						<?php echo esc_html__( "You haven't enrolled in courses yet.", 'masterstudy-lms-learning-management-system' ); ?>
					</p>
				</div>
				<div v-if="loading" class="stm-lms-course-spinner-container">
					<div class="stm-lms-spinner">
						<div></div>
						<div></div>
						<div></div>
						<div></div>
					</div>
				</div>
			</div>
		</div>

		<?php
		if ( $filter_enabled ) {
			stm_lms_register_style( 'courses_filter' );
			stm_lms_register_script_edublink( 'courses_filter' );
			STM_LMS_Templates::show_lms_template(
					'courses/advanced_filters/mainenroll',
					array(
							'args'     => $args,
							'category' => $term->term_id,
					)
			);
		}
		?>
	</div>
	<div class="text-center load-my-courses">
			<a @click="getCourses(activeTab, true, true)" v-if="!total && courses.length" class="btn btn-default" v-bind:class="{'loading' : loadingButton}">
				<span><?php esc_html_e( 'Show more', 'masterstudy-lms-learning-management-system' ); ?></span>
			</a>
		</div>
</div>


