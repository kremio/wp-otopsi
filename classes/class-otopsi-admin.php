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
		add_menu_page(  __('Otopsi Admin', 'otopsi-domain'), 'Otopsi', 'manage_options', 'otopsi_admin_menu', array( $this, 'render_shortcodes_admin_page' ), 'dashicons-layout' );
		add_submenu_page( 'otopsi_admin_menu', __('Shortcodes', 'otopsi-domain'), __('Manage Shortcodes', 'otopsi-domain'), 'manage_options', 'otopsi_admin_menu', array( $this, 'render_shortcodes_admin_page' ) );
		add_submenu_page( 'otopsi_admin_menu', __('Layout modes', 'otopsi-domain'), __('Layout modes', 'otopsi-domain'), 'manage_options', 'otopsi_layout_modes', array( $this, 'render_layout_modes_page' ) );
	}

	public static function enqueue_script($hook){
		if( 'post.php' !== $hook && FALSE === strpos($hook, 'otopsi') ){
			return;
		}
		wp_register_script( 'otopsi-admin-js', plugins_url( 'js/otopsi-admin.js',  dirname(__FILE__) ), array( 'jquery', 'jquery-ui-accordion', 'jquery-ui-sortable', 'jquery-ui-tabs'), OTOPSI_VERSION, true );

		//Let our frontend JS know about the base URL
		$urls =array( 
			'layoutsJsURL'   => plugins_url( 'js/layout-modes/',  dirname(__FILE__) ),
			'layoutsAdminURL' => admin_url( 'admin.php?page=otopsi_layout_modes' ),
 	   	);
		wp_localize_script( 'otopsi-admin-js', 'OtopsiUrls', $urls);
		wp_enqueue_script( 'otopsi-admin-js' );

	}

	/*
	 * Admin page
	 */
	public function render_shortcodes_admin_page() {
		wp_enqueue_style( 'otopsi-admin-style', plugins_url( 'css/otopsi-admin.css',  dirname(__FILE__) ) );
				include( __OTOPSI_ROOT__ . '/includes/admin/_shortcodes.php' );
		include( __OTOPSI_ROOT__ . '/includes/admin/_footer.php' );
	}

	/*
	 * Documentation page
	 */
	public function render_layout_modes_page() {
		wp_enqueue_style( 'otopsi-admin-style', plugins_url( 'css/otopsi-admin.css',  dirname(__FILE__) ) );
		include( __OTOPSI_ROOT__ . '/includes/admin/_layout_modes.php' );
		include( __OTOPSI_ROOT__ . '/includes/admin/_footer.php' );
	}

}
