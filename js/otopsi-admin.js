(function(){

	var Otopsi = {

		//Called to reveal the Otopsi meta box on the page edit page
	ontoggleOtopsi: function ( value ) {
		if( value ) {
			jQuery( '.otopsi_show_if_enabled' ).show();
		}else{
			jQuery( '.otopsi_show_if_enabled' ).hide();
		}
	},

	//Called when the selected taxonomies change in the taxonomy field
	onchangeTerm: function( term_field, value) {
		var data = {
			action: 'otopsi_get_taxonomy_terms',
			otopsi_term: value
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function( response ) {
			jQuery( '#' + term_field ).html( response );
		});
	},

	//Check if each layout modes not already marked as installed is in fact installed
	checkForInstalledLayouts: function(){
		jQuery( '.layout-mode' ).not( '.installed' ).each( Otopsi.checkIfLayoutModeIsInstalled );
	},

	//Display a layout mode as installed
	showAsInstalledLayout: function(){
		$that = jQuery(this);
		//Already installed
		$that.addClass( 'installed blue' );
	},

	//Perfom an AJAX get query to check if the layout mode JavaScript file is accessible
	checkIfLayoutModeIsInstalled: function(){
		var $that = jQuery(this);
		var layoutName = $that.attr( 'data-name' );
		jQuery.get( OtopsiUrls.layoutsJsURL + layoutName + '.js').success(function(response){
			//Already installed
			$that.each( Otopsi.showAsInstalledLayout() );
			
		}).fail(function(response){
			if( '200' == response.status || response.statusText === 'OK' ){
				//Already installed
				$that.each( Otopsi.showAsInstalledLayout );
				return;
			}
			if( '404' != response.status ){
				alert( 'Unexpected HTTP status ' + response.status + ' when checking for layout library ' +  OtopsiUrls.layoutsJsURL + layoutName + '.js');
				return;
			}
			//Not installed yet
			$that.addClass( 'download' );
			$that.click( Otopsi.downloadLayoutLib );
		});
	},

	//Sends an AJAX request to the plugin to download and install the layout mode library
	downloadLayoutLib: function(){
		var $that = jQuery( this );
	   $that.removeClass( 'download installed blue' );

		var layoutName = $that.attr( 'data-name' );
		var data = {
			action: 'otopsi_check_credentials_for_download',
			otopsi_layout_mode: layoutName
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			console.log( response );
			if( 'ok' === response ){ //Installation successful
				$that.removeClass( 'download' );
				$that.each( Otopsi.checkIfLayoutModeIsInstalled  ); //Check it again to trigger the refresh of the layout modes list
				return;
			}

			if( 'credentials needed' === response ){ //FTP credentials required to perform the operation
				data = {
					credentials: true,
					otopsi_layout_mode: layoutName
				};

				jQuery.post( OtopsiUrls.layoutsAdminURL, data, function( response ){
					var wrapHTML = jQuery( response ).find('.wrap').html();
					jQuery( '.wrap' ).html( wrapHTML ); //replace the content of the screen with the credentials input form
				    Otopsi.checkForInstalledLayouts();
				});
				return;
			}

			//Other errors
			alert( response );
		});
	}
};

window.Otopsi = Otopsi;
jQuery( document ).ready( Otopsi.checkForInstalledLayouts );

})();
