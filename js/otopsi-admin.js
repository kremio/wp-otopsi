(function(){

	var Otopsi = {

		initAdmin: function(){
			Otopsi.initAccordions();
			Otopsi.initSortOptions();
			Otopsi.initFilterGroups();
			Otopsi.checkForInstalledLayouts();

			//Prevent submission of the form when pressing ENTER in a text field
			jQuery( '.otopsi-no-submit' ).keypress(function (e) {
				if (e.which == 13) {
					jQuery( this ).blur();
					return false;
				}
			});
		},

	initFilterGroups: function(){
		var $filterGroupAccordion = jQuery( '#filter-groups-accordion' );
		$filterGroupAccordion.accordion({
			collapsible: true,
			heightStyle: 'content',
			header:'> div > h3',
			//Reflect the open/closed state in the header arrow
			create: function( event, ui ) {
				ui.header.parent().removeClass( 'closed' );
			},
			activate: function( event, ui ) { //change the header arrow direction
				ui.newHeader.parent().removeClass( 'closed' );
				ui.oldHeader.parent().addClass( 'closed' );
			}
		}).sortable({
			axis: 'y',
			handle: 'h3',
			stop: function( event, ui ) {
				// IE doesn't register the blur when sorting
				// so trigger focusout handlers to remove .ui-state-focus
				ui.item.children( 'h3' ).triggerHandler( 'focusout' );

				// Refresh accordion to handle new order
				jQuery( this ).accordion( 'refresh' );
				Otopsi.updateOtopsiFilters();
			}
		});

		jQuery( 'input[name="new-filter-group"]' ).keypress(function (e) {
			if (e.which == 13) {
				jQuery( '#add-filter-group' ).click();
				return false;
			}
		});

		jQuery( '#add-filter-group' ).click(function(e){
			e.preventDefault();
			//Check that a name was provided
			var $newFilterGroupInput = jQuery( 'input[name="new-filter-group"]' );
			var filterGroupName = $newFilterGroupInput.val();
			if( filterGroupName.match(/^\s*$/g) ){
				$newFilterGroupInput.css( 'visibility', 'hidden' );
				setTimeout( function(){
					$newFilterGroupInput.css( 'visibility', 'visible' );
				}, 250 );
				return false;
			}

			$newFilterGroupInput.val( '' ); //empty the input

			//Insert a new group filter box a the end of the accordion
			var $filterGroup = Otopsi.createFilterGroup( filterGroupName );

			//Setup events handlers and state
			Otopsi.initFilterGroup( $filterGroup );
			
			//Make it the active item of the accordion
			$filterGroupAccordion.accordion( "refresh" );
			$filterGroupAccordion.accordion( "option", "active", -1 );

			//Update filter settings hidden field
			Otopsi.updateOtopsiFilters();
		});

		//Init the group already in the DOM
		var $filterGroups = jQuery('#filter-groups-accordion .hndle, #filter-groups-accordion .inside');
		//Go through each handle/inside pairs of elements
		for(var i = 0; i < $filterGroups.length; i += 2){
			var $filterGroup = Otopsi.addCustomFunctionsToGroup( $filterGroups.slice(i, i+2) );
			Otopsi.initFilterGroup( $filterGroup );
		}

		//Update filter select when terms and post type change
		jQuery( '#otopsi_term, #otopsi_posttype' ).change(function(){
  			//Retrieve and set the initial filter types
			Otopsi.updateFiltersSelect();
		});

		//Retrieve and set the initial filter types
		Otopsi.updateFiltersSelect();
		
/*
		jQuery( '.filter-group-box' ).tabs({
			active: 0,
			activate: function( event, ui ) { //change the header arrow direction
				ui.newTab.addClass( 'tabs' );
				ui.oldTab.removeClass( 'tabs' );
			}
		});

		jQuery( '.filter-group-box .filters' ).sortable();

		jQuery( '.filter-adder a').click( function(e){
			e.preventDefault();
			$this = jQuery(this);
			$this.parents('.filter-adder').toggleClass( 'wp-hidden-children' );
		});

		Otopsi.updateFiltersSelect();

		jQuery( '.add-filter-button' ).click( Otopsi.addFilter );

		jQuery( '.filter-group-name-input' ).blur(function(){
			var $this = jQuery( this );
			var $filterGroupBox = $this.parents( '.filter-group-box' );
			var $filterGroupBoxHandle = jQuery( 'h3.hndle.group-' + $filterGroupBox.attr( 'data-group' ) );
			$filterGroupBoxHandle.text( $this.val() );
		});
*/
	},

	initAccordions: function(){
		jQuery( '.otopsi_show_if_enabled' ).accordion({
			collapsible: true,
		heightStyle: 'content',
		header:'.settings > .hndle',
		//Reflect the open/closed state in the header arrow
		create: function( event, ui ) {
			ui.header.parent( '.postbox' ).removeClass( 'closed' );
		},
		activate: function( event, ui ) { //change the header arrow direction
			ui.newHeader.parent( '.postbox' ).removeClass( 'closed' );
			ui.oldHeader.parent( '.postbox' ).addClass( 'closed' );
		}
		});
	},

	initSortOptions: function(){
		jQuery( '.sort-option span' ).click(function(){
			$this = jQuery(this);
			$this.parent( '.sort-option' ).removeClass( 'off ASC DESC' ).addClass( $this.attr( 'data-state' ) ).attr('data-direction', $this.attr( 'data-state' ));
			Otopsi.updateSortValue();
		});
		Otopsi.updateSortValue();
	},

	//Update the value of the otopsi[sort] input field
	updateSortValue: function(){
		var sortCodes = '';
		var sortDirections = '';
		jQuery( '.sort-option' ).not('.off').each(function(){
			$this = jQuery(this);
			sortCodes += sortCodes !== '' ? ',' : '';
			sortCodes += $this.attr( 'data-code' );

			sortDirections += sortDirections !== '' ? ',' : '';
			sortDirections += $this.attr( 'data-direction' );
		});
		jQuery( 'input[name="otopsi[sort]"]' ).val( sortCodes + '|' + sortDirections );
	},

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

	addCustomFunctionsToGroup: function( $handleInsideArray ){
		$handleInsideArray.handle = function(){
			for( var i = 0; i < this.length; i++){
				if( jQuery( this[i] ).hasClass('hndle') ){
					return jQuery( this[i] );
				}
			}
		};

		$handleInsideArray.inside = function(){
			for( var i = 0; i < this.length; i++){
				if( jQuery( this[i] ).hasClass('inside') ){
					return jQuery( this[i] );
				}
			}
		};

		return $handleInsideArray;
	},

	createFilterGroup: function( filterGroupName ){
		//Copy the template HTML code
		var $template = Otopsi.addCustomFunctionsToGroup( jQuery( jQuery( '.otopsi_show_if_enabled .filter-group-template' ).html() ) );

		//Set the group name
		$template.handle().text( filterGroupName );
		$template.find( 'input[name="group-tab-name"]' ).val( filterGroupName );

		var $wrappingDiv = jQuery( '<div class="filters-group-wrap"></div>' ); //Necessary to make the accordion sortable
		$wrappingDiv.append( $template );
		jQuery( '#filter-groups-accordion' ).append( $wrappingDiv );
		return $template;
	},

	updateFiltersSelect: function(){
		var data = {
			action: 'otopsi_get_possible_filters',
			search:{
				term: jQuery('#otopsi_term').val(),
				posttype:  jQuery('#otopsi_posttype').val(),
				sort:  jQuery('input[name="otopsi[sort]"').val(),
				limit: jQuery('input[name="otopsi[limit]"').val()
			}
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function( response ) {
			jQuery( '#filters-select-source, #filter-groups-accordion .filters-select' ).html( response );
			//Check the validity of the filters and warn the user if one filter became useless
			Otopsi.checkFiltersValidity();
		});

	},

	getMessage: function( messageClass ){
		return jQuery('.otopsi_show_if_enabled  .filters-js-messages .'+messageClass).text();
	},

	/*
	 * Empty and hide the given filter add/edit form
	 */
	resetFilterForm: function( $filterAdder ){
		var $labelInput = $filterAdder.find( 'input.filter-label-input' );
		var $filtersSelect = $filterAdder.find( 'select.filters-select' );
		$labelInput.val('');
		$filtersSelect.find('option').prop('selected', false);
		$filterAdder.addClass( 'wp-hidden-children' ).removeClass( 'edit' );
		$filterAdder.find('.cancel-filter-button, .update-filter-button').off('click'); //reset click handlers
	},

	validateFilterForm: function( $filterAdder ){
		var labelName = $filterAdder.find( 'input.filter-label-input' ).val();
		var filters = $filterAdder.find( 'select.filters-select' ).val();

		if( labelName.match(/^\s*$/g) ){
			$filterAdder.find( 'input.filter-label-input' ).css( 'visibility', 'hidden' );
			setTimeout( function(){
				$filterAdder.find( 'input.filter-label-input' ).css( 'visibility', 'visible' );
			}, 250 );
			return false;
		}

		if( !filters ){
			$filterAdder.find( 'select.filters-select' ).css( 'visibility', 'hidden' );
			setTimeout( function(){
				$filterAdder.find( 'select.filters-select' ).css( 'visibility', 'visible' );
			}, 250 );
			return false;
		}
		
		return { labelName: labelName, filters: filters };
	},

	//Check that all set filters have a valid filter type
	checkFiltersValidity: function(){
		var $warningMessage = jQuery( '.filter-warning' );
		$warningMessage.removeClass( 'on' );
		var showWarning = false;
		var $filtersSelect = jQuery( '#filters-select-source' );
		var $filters = jQuery('#filter-groups-accordion .filters li');
		$filters.removeClass( 'warning' );

		jQuery( '#filter-groups-accordion .filters-group-wrap' ).removeClass( 'warning' );

		
		$filters.each(function(){
			var filterIsOK = 0;
			var $filter = jQuery( this );
			
			var selectedOptions = $filter.attr( 'data-filters' ).split( ',' );
			jQuery.each( selectedOptions, function( i, e ){
				filterIsOK += $filtersSelect.find( "option[value='" + e + "']" ).length;
			});

			if( filterIsOK > 0 ){
				return;
			}

			$filter.addClass( 'warning' );
			$filter.parents( '.filters-group-wrap' ).addClass( 'warning' );
			showWarning = true;
		});

		if( showWarning ){
			$warningMessage.addClass( 'on' );
		}
	},

	updateOtopsiFilters: function(){

		var settingsString = '';
		var $filterGroups = jQuery('#filter-groups-accordion .hndle, #filter-groups-accordion .inside');
		//Go through each handle/inside pairs of elements
		for(var i = 0; i < $filterGroups.length; i += 2){
			var $filterGroup = Otopsi.addCustomFunctionsToGroup( $filterGroups.slice(i, i+2) );
			settingsString += Otopsi.getFilterGroupSettingsString( $filterGroup ) + '|';
		}

		jQuery( 'input[name="otopsi[filters]"]' ).val( settingsString.substring( 0, settingsString.length-1 ) );
		console.log( jQuery( 'input[name="otopsi[filters]"]' ).val() );
	},

	getFilterGroupSettingsString: function( $filterGroup ){
		var settingsString = $filterGroup.handle().text(); //group name
		settingsString += $filterGroup.inside().find( 'input[name="group-tab-display"]:checked' ).length > 0 ? ";1" : ";0"; //display group name?
		//filters
		$filterGroup.inside().find('.filters li').each(function(){
			var $this = jQuery( this );
		   settingsString += ';' + $this.attr( 'data-name' ) + ';' + $this.attr( 'data-filters' );
		});

		return settingsString;
	},

	initFilterGroup: function( $filterGroup ){
		$filterGroup.inside().tabs({
			active: 0,
			activate: function( event, ui ) { //change the header arrow direction
				ui.newTab.addClass( 'tabs' );
				ui.oldTab.removeClass( 'tabs' );
			}
		});
		
		//Update the settings after sortfing filters
		$filterGroup.inside().find( '.filters' ).sortable({
			stop: Otopsi.updateOtopsiFilters
		});
		
		$filterGroup.inside().find( '.filter-adder a').click( function(e){
			e.preventDefault();
			$this = jQuery(this);
			$this.parents('.filter-adder').toggleClass( 'wp-hidden-children' );
		});

		$filterGroup.inside().find( '.filter-group-name-input' ).blur(function(){
			var $this = jQuery( this );
			$filterGroup.handle().text( $this.val() );
			//Update filter settings hidden field
			Otopsi.updateOtopsiFilters();
		}).keypress(function (e) {
			if (e.which == 13) {
				jQuery( this ).blur();
				return false;
			}
		});

		$filterGroup.inside().find( 'input[name="group-tab-display"]' ).change(function(){
			//Update filter settings hidden field
			Otopsi.updateOtopsiFilters();
		});

		$filterGroup.inside().find( 'input[name="filters-tab-label"]' ).keypress(function (e) {
			if (e.which == 13) {
				jQuery( this ).blur();
				return false;
			}
		});


		$filterGroup.inside().find( '.add-filter-button' ).click( Otopsi.addFilter );

		//Set the content of the filter type select
		$filterGroup.inside().find( '.filters-select' ).html( jQuery( '#filters-select-source' ).html() );

		//Set up events handler on the filters
		$filterGroup.inside().find( '.filters li' ).each( function(){
			Otopsi.initFilter( jQuery( this ) );
		});


		$filterGroup.inside().find( '.delete-filter-group' ).click(function(e){
			e.preventDefault();
			if( ! confirm( Otopsi.getMessage( 'confirm-deletion' ) ) ){
				return;
			}
			$filterGroup.inside().remove();
			if( $filterGroup.handle().parent().hasClass( 'warning' ) ){
				//Check if the change resolves the warning
				Otopsi.checkFiltersValidity();
			}
			
			$filterGroup.handle().parent().remove();
			//Update filter settings hidden field
			Otopsi.updateOtopsiFilters();

		});

	},

	addFilter: function(e){
		e.preventDefault();
		var $this = jQuery( this );
		var $filterAdder = $this.parents( '.filter-adder' );

		var settings = Otopsi.validateFilterForm( $filterAdder );
		if( false === settings ){
			return;
		}
		//Reset the form
		Otopsi.resetFilterForm( $filterAdder );

		var $filter = jQuery( '<li data-filters="' + settings.filters + '" data-name="' + settings.labelName + '"><b class="name">' + settings.labelName + '</b><span class="dashicons dashicons-edit edit"></span><span class="dashicons dashicons-trash delete"></span></li>' );
		//Add it to the DOM
		$this.parents( '.filter-group-box' ).find( '.filters' ).append( $filter );

		Otopsi.initFilter( $filter );
		
		//Switch to the filters tab
		$this.parents( '.filter-group-box' ).tabs( 'option', 'active', 1 );

		//Update filter settings hidden field
		Otopsi.updateOtopsiFilters();

	},

	initFilter: function( $filter ){
		//Set click handlers for edit and delete
		$filter.find('.delete').click( function(e){
			e.preventDefault();
			if( ! confirm( Otopsi.getMessage( 'confirm-deletion' ) ) ){
				return;
			}
			$filter.remove();
			//Update filter settings hidden field
			Otopsi.updateOtopsiFilters();

			//Check if the change resolves the warning
			if( $filter.hasClass( 'warning' ) ){
				Otopsi.checkFiltersValidity();
			}

		});

		$filter.find('.edit').click( Otopsi.editFilter );

	},

	editFilter: function(e){
		e.preventDefault();
		var $filter = jQuery( this ).parent();
		$filter.parent().find( 'li' ).removeClass( 'edit' );
		$filter.addClass( 'edit' );

		var $filterAdder = $filter.parents( '.filter-group-box' ).find('.filter-adder');
		//Make sure the form is clean
		Otopsi.resetFilterForm( $filterAdder );

		//Populate the form with the filter's settings
		var $labelInput = $filterAdder.find( 'input.filter-label-input' );
		$labelInput.val( $filter.attr( 'data-name' ) );
		var $filtersSelect = $filterAdder.find( 'select.filters-select' );
		var selectedOptions = $filter.attr( 'data-filters' ).split( ',' );
		jQuery.each( selectedOptions, function( i, e ){
			$filtersSelect.find( "option[value='" + e + "']" ).prop( 'selected', true );
		});

		//Set button click handlers
		$filterAdder.find( '.cancel-filter-button' ).click(function(e){
			e.preventDefault();
			$filter.removeClass( 'edit' );
			Otopsi.resetFilterForm( $filterAdder );
		});

		$filterAdder.find( '.update-filter-button' ).click(function(e){
			e.preventDefault();
			var settings = Otopsi.validateFilterForm( $filterAdder );
			if( false === settings ){
				return;
			}
			//Update
			$filter.find( '.name' ).text( settings.labelName );
			$filter.attr( 'data-name', settings.labelName );
			$filter.attr( 'data-filters', settings.filters );
			$filter.removeClass( 'edit' );
			Otopsi.resetFilterForm( $filterAdder );

			//Update filter settings hidden field
			Otopsi.updateOtopsiFilters();
			
			//Check if the change resolves the warning
			if( $filter.hasClass( 'warning' ) ){
				Otopsi.checkFiltersValidity();
			}
		});

		//Display the form
		$filterAdder.removeClass( 'wp-hidden-children' ).addClass( 'edit' );

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
jQuery( document ).ready( Otopsi.initAdmin );

})();
