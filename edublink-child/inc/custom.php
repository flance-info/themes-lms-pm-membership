<?php

if ( ! function_exists( 'stm_module_styles' ) ) {
	function stm_module_styles( $handle, $style = 'style_1', $deps = array(), $inline_styles = '' ) {
		if ( empty( $handle ) || empty( $style ) ) {
			return;
		}

		// Check child theme first
		$child_path = get_stylesheet_directory_uri() . '/assets/css/vc_modules/' . $handle . '/' . $style . '.css';
		$child_file = get_stylesheet_directory() . '/assets/css/vc_modules/' . $handle . '/' . $style . '.css';

		// If file exists in child theme, use that, otherwise use parent theme
		$path = file_exists($child_file) ? $child_path : get_template_directory_uri() . '/assets/css/vc_modules/' . $handle . '/' . $style . '.css';
		$handle = 'stm-' . $handle . '-' . $style;

		wp_enqueue_style( $handle, $path, $deps, STM_THEME_VERSION, 'all' );

		if ( ! empty( $inline_styles ) ) {
			wp_add_inline_style( $handle, $inline_styles );
		}
	}
}

// Add custom field to membership level settings page
function add_custom_field_to_membership_level() {
	?>
	<h3 class="topborder"><?php esc_html_e('Custom Fields', 'edublink-child'); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="custom_field"><?php esc_html_e('Custom Field:', 'edublink-child'); ?></label>
				</th>
				<td>
					<input type="text" id="custom_field" name="custom_field" value="<?php echo esc_attr(get_option('custom_field')); ?>" />
					<p class="description"><?php esc_html_e('Enter your custom field value here.', 'edublink-child'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
//add_action('pmpro_membership_level_after_other_settings', 'add_custom_field_to_membership_level');

function save_custom_field($level_id) {
	if(isset($_POST['custom_field'])) {
		update_option('custom_field_' . $level_id, sanitize_text_field($_POST['custom_field']));
	}
}
//add_action('pmpro_save_membership_level', 'save_custom_field');



function stm_lms_register_script_edublink($script, $deps = array(), $footer = false, $inline_scripts = '') {
	if (!stm_lms_is_masterstudy_theme()) {
		wp_enqueue_script('jquery');
	}

	$handle = "stm-lms-{$script}";
	$child_theme_url = get_stylesheet_directory_uri(); // Get child theme URL
	$script_path = "/assets/js/{$script}.js";
	$script_file = get_stylesheet_directory() . $script_path;

	// Check if script exists in child theme
	if (file_exists($script_file)) {
		// Use script from child theme
		wp_enqueue_script(
			$handle, 
			$child_theme_url . $script_path, 
			$deps, 
			time(), 
			$footer
		);
	} else {
		// Fallback to parent theme script
		wp_enqueue_script(
			$handle, 
			STM_LMS_URL . 'assets/js/' . $script . '.js', 
			$deps, 
			time(), 
			$footer
		);
	}

	if (!empty($inline_scripts)) {
		wp_add_inline_script($handle, $inline_scripts);
	}
}