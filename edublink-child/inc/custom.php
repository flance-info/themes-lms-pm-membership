<?php

if ( ! function_exists( 'stm_module_styles' ) ) {
	function stm_module_styles( $handle, $style = 'style_1', $deps = array(), $inline_styles = '' ) {
		if ( empty( $handle ) || empty( $style ) ) {
			return;
		}

		$path   = get_template_directory_uri() . '/assets/css/vc_modules/' . $handle . '/' . $style . '.css';
		$handle = 'stm-' . $handle . '-' . $style;

		wp_enqueue_style( $handle, $path, $deps, STM_THEME_VERSION, 'all' );

		if ( ! empty( $inline_styles ) ) {
			wp_add_inline_style( $handle, $inline_styles );
		}
	}
}