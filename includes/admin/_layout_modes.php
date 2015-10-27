<?php
//Prevents direct access to the script
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/*TODO:
 - write JS to check if layouts library are installed using AJAx
 - write PHP code to download libraries and move them to the js/layouts folder
 */
?>

<div class="wrap">
<h2><?php _e( 'Isotope Layout Modes', 'otopsi-domain' ); ?></h2>

<?php

if( isset( $_POST['credentials'] ) ):
	//The user needs to provide FTP credentials so that the layout mode library can be installed safely
	$url = wp_nonce_url( admin_url( 'admin.php?page=otopsi_layout_modes' ), 'otopsi-layout-modes' );
	//This method call will check the credentials submitted (if any) and generate a form for the user to provide FTP credentials if the check fails
	if( false === ($creds = request_filesystem_credentials( $url, '', false, false, array(), '', false, __OTOPSI_ROOT__ . '/js/layout-modes/', array( 'otopsi_layout_mode', 'credentials' ) ) ) ){
		//Could not verify credentials
		return;
	}

	//Let's download the layout mode library and install it securely!
	$result = Otopsi::install_layout_mode( $creds );

	if( is_wp_error( $result ) ){
?>
<p class="error"><span class="dashicons dashicons-welcome-comments"></span>
<?php
		if( 'download_failed' === $result->get_error_code() ){
			echo __('Download failed', 'otopsi-domain') . ': ' . $result->get_error_message( 'download_failed' );
		}

		if( 'installation_failed' === $result->get_error_code() ){
			echo __('Installation failed', 'otopsi-domain') . ': ' . $result->get_error_message( 'installation_failed' );
		}

	}
?>
</p>
<?php
endif;
?>

<p>Here you can check what Isotope layout modes are installed and available for your WordPress installation. See the <a href="http://isotope.metafizzy.co/layout-modes.html" target="_blank">Isotope documentation for more information</a>.</p>
<p>
The layout modes displayed with an <span class="dashicons dashicons-thumbs-up blue"></span> icon are already installed and available to use in your Isotope widgets,
while the ones showing the <span class="dashicons dashicons-download"></span> icon can be downloaded and installed by clicking on them.<br/>
</p>
<ul>
<?php
	foreach(Otopsi::get_isotope_layout_modes() as $layoutName => $libraryURL){
		echo sprintf('<li class="layout-mode%s" data-name="%s">%2$s %s</li>', '' === $libraryURL ? ' installed blue' : '', $layoutName, '' === $libraryURL ? '<span class="note">' . __('part of the core Isotope library', 'otopsi-domain') . '</span>' : '');
	}
?>
</ul>
