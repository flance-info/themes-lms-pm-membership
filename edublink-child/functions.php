<?php
define('STM_THEME_VERSION', time());

require_once 'inc/custom.php';
require_once 'inc/subscriptions.php';

function edublink_child_enqueue_styles() {
	wp_enqueue_style( 'edublink-child-style', get_stylesheet_uri() );
}

add_action( 'wp_enqueue_scripts', 'edublink_child_enqueue_styles', 100 );

