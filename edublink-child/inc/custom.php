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