<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Otopsi Widget
Plugin URI: http://krem.io/wp/otopsi/
Description: Add Isotope.js filtering and layout functionality to a page.
Author: Jonathan Cremieux
Version: 0.1
Author URI: http://krem.io/
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

/*
TODO:
Add option to control the number of post returned and the sorting on DB query
Documentation
Template Custom CSS
 */

define('OTOPSI_VERSION',  '0.1');
define('OTOPSI_META_KEY', 'otopsi_meta');
define('OTOPSI_SC_NAME',  'otopsi');
define('OTOPSI_SC_KEY',   'otopsi_sc_key');
define('OTOPSI_SC_DATA',  'otopsi_sc_data');
define('__OTOPSI_ROOT__',  dirname( dirname(__FILE__) ) );


//Include the classes that make up the widget
require_once( __OTOPSI_ROOT__ . '/classes/class-otopsi.php');
require_once( __OTOPSI_ROOT__ . '/classes/class-otopsi-admin.php'); 
require_once( __OTOPSI_ROOT__ . '/classes/class-otopsi-shortcode.php'); 
require_once( __OTOPSI_ROOT__ . '/classes/class-otopsi-renderer.php'); 


/**
 * Load the plugin class for the post edit screen.
 */
function load_otopsi() {
	new Otopsi();
}

/**
 * Load the plugin class for the admin menu.
 */
function load_otopsi_admin() {
	new OtopsiAdmin();
}

//Setup initial state when the plugin is activated
register_activation_hook( __FILE__, array( 'Otopsi', 'on_activation' ) );

if ( is_admin() ) {
	add_action( 'load-post.php', 'load_otopsi' );
	add_action( 'load-post-new.php', 'load_otopsi' );
	//Setup AJAX handler to retrieve the terms of a taxonomy on the client side
	add_action( 'wp_ajax_otopsi_get_taxonomy_terms', array( 'Otopsi', 'get_taxonomy_terms' ) );
	//Add an admin menu
	add_action( 'admin_menu', 'load_otopsi_admin' );
}
//render the grid and filters if enabled for the page
add_filter( 'the_content', array( 'Otopsi_Renderer', 'render_in_post' ), 100000 );

add_shortcode( OTOPSI_SC_NAME,  array( 'Otopsi_Shortcode', 'short_code_hook' ) );
