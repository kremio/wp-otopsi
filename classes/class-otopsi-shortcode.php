<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Manage the edition of Otopsi shortcodes.
 */
class Otopsi_Shortcode{

	/*
 	 * Create a new OtopsiShortCode instance from the given the shortcode
 	 */
	public static function short_code_hook( $atts ) {
		if ( array_key_exists( 'id' ,$atts ) && !empty( $atts['id'] ) ) { // Check if shortcode ID is passed
			$scData = Otopsi_Shortcode::get_shortcodes_data();
			if( array_key_exists( $atts['id'], $scData ) ) {
				$currentSc = $scData[ $atts['id'] ];
				$currentSc['enable'] = 1;
				return Otopsi_Renderer::render_instance( $currentSc );
			}else{
				return 'Otopsi : Could not find a shortcode with the ID ' . $atts['id'];
			}
		}else{
			return 'Otopsi : Shortcode ID empty or undefined.';
		}
	}

	/*
	 * Commit a shortcode to the database
	 */
	public static function save_shortcode( &$scData, $mydata ) {
		if( !isset( $mydata['sc_id'] ) ) { //obtain a new shortcode id
			$mydata['sc_id'] = get_option( OTOPSI_SC_KEY );
			//update the shortcode id for next one
			update_option( OTOPSI_SC_KEY, $mydata['sc_id'] + 1 );
		}

		$scData[ $mydata['sc_id'] ] = $mydata;
		Otopsi_Shortcode::save_shortcodes_data( $scData );
	}

	/*
	 * Return the array containing data about all the registered shortcodes
	 */
	public static function get_shortcodes_data() {
		$scData	= json_decode( get_option(OTOPSI_SC_DATA), true );
		if ( empty($scData) ) {
			return array();
		}

		return $scData;
	}

	/*
	 * Save the shortcodes data
	 */
	public static function save_shortcodes_data( $scData ) {
		update_option( OTOPSI_SC_DATA, json_encode( $scData, JSON_HEX_APOS | JSON_HEX_QUOT ) );
	}

}
