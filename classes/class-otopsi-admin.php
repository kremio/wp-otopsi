<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Manage the edition of Otopsi shortcodes.
 */
class Otopsi_Admin{
	/*
	 * Create the Administration menu
	 */
	public function __construct() {
	add_menu_page( 'Otopsi Admin', 'Otopsi', 'manage_options', 'otopsi_admin_menu', array( $this, 'render_shortcodes_admin_page' ), '' );
	add_submenu_page( 'otopsi_admin_menu', 'Shortcodes', 'Manage Shortcodes', 'manage_options', 'otopsi_admin_menu', array( $this, 'render_shortcodes_admin_page' ) );
	add_submenu_page( 'otopsi_admin_menu', 'Documentation', 'Documentation', 'manage_options', 'otopsi_documentation', array( $this, 'render_documentation_page' ) );
	}

	/*
	 * Admin page
	 */
	public function render_shortcodes_admin_page() { 
	include( __OTOPSI_ROOT__ . 'includes/admin/_header.php' );
	include( __OTOPSI_ROOT__ . 'includes/admin/_shortcodes.php' );
	include( __OTOPSI_ROOT__ . 'includes/admin/_footer.php' );
	}

	/*
	 * Documentation page
	 */
	public function render_documentation_page() {
	include( __OTOPSI_ROOT__ . 'includes/admin/_header.php' );
	include( __OTOPSI_ROOT__ . 'includes/admin/_documentation.php' );
	include( __OTOPSI_ROOT__ . 'includes/admin/_footer.php' );
	}

}
