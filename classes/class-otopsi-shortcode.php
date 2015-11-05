<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Manage the edition of Otopsi shortcodes.
 */
class Otopsi_Shortcode{

	/*
	 * Render an Isotope from the given the shortcode
	 */
	public static function short_code_hook( $atts ) {
		if ( array_key_exists( 'id', $atts ) && ! empty( $atts['id'] ) ) { // Check if shortcode ID is passed
			$sc_data = Otopsi_Shortcode::get_shortcodes_data();
			if( array_key_exists( $atts['id'], $sc_data ) ) {
				$current_sc = $sc_data[ $atts['id'] ];
				$current_sc['enable'] = 1;
				return Otopsi_Renderer::render_instance( $current_sc );
			}

			return sprintf( __( 'Otopsi : Could not find a shortcode with the ID %s' , 'otopsi-domain' ), $atts['id'] );
		}
		
		return __( 'Otopsi : Shortcode ID empty or undefined.', 'otopsi-domain' );
	}

	/*
	 * Commit a shortcode to the database
	 */
	public static function save_shortcode( &$sc_data, $my_data ) {
		if( !isset( $my_data['sc_id'] ) ) { //obtain a new shortcode id
			$my_data['sc_id'] = get_option( OTOPSI_SC_KEY );
			//update the shortcode id for next one
			update_option( OTOPSI_SC_KEY, $my_data['sc_id'] + 1 );
		}

		$sc_data[ $my_data['sc_id'] ] = $my_data;
		Otopsi_Shortcode::save_shortcodes_data( $sc_data );
		return $my_data['sc_id'];
	}

	/*
	 * Return the array containing data about all the registered shortcodes
	 */
	public static function get_shortcodes_data() {
		$sc_data = json_decode( get_option( OTOPSI_SC_DATA ), true );
		if ( empty( $sc_data ) ) {
			return array();
		}

		return $sc_data;
	}

	/*
	 * Save the shortcodes data
	 */
	public static function save_shortcodes_data( $sc_data ) {
		update_option( OTOPSI_SC_DATA, json_encode( $sc_data, JSON_HEX_APOS | JSON_HEX_QUOT ) );
	}

}
