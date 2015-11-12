(function(){


	var Otopsi = function(){
		jQuery(".otopsi-init").each( function(){
			jQuery(this).removeClass("otopsi-init");
			var isotopeOptionsString = jQuery(this).attr('data-otopsi');
			/*
			 * To remove wrapping curly braces
			 * var reg = /^\{((?:.|\s)*)\}$/g;
			 * isotopeOptions = isotopeOptions.replace( reg, "$1");
			 */
			var isotopeOptionsJSON = JSON.parse( isotopeOptionsString );
			var $container = jQuery(this).find( '.otopsi-container' ).isotope( isotopeOptionsJSON );
			var $otopsiWrapper = jQuery(this);
			/*
			   jQuery(this).find('.otopsi-filters').on( 'click', 'button', function() {
			   });
			   */

			function applyFilters(){
								//first find the filters that are to be combined (AND)
				var combineFilters = [];
				$otopsiWrapper.find( '.button-group.combine' ).each( function(){
					var $buttonGroup = jQuery( this );
					$buttonGroup.find( 'button.is-checked' ).each(function(){
						var dataFilter = jQuery( this ).attr( 'data-filter' );
						if( '' === dataFilter ){
							return;
						}
						combineFilters.push( dataFilter );
					});
				});

				//next get the filters that are to added (OR)
				var addFilters = [];
				$otopsiWrapper.find( '.button-group.add' ).each( function(){
					var $buttonGroup = jQuery( this );
					$buttonGroup.find( 'button.is-checked' ).each(function(){
						var dataFilter = jQuery( this ).attr( 'data-filter' );
						if( '' === dataFilter ){
							return;
						}
						addFilters.push( dataFilter );
					});
				});

				//apply
				if( combineFilters.length === 0 ){
					combineFilters.push(''); //ensure the first for runs once
				}
    			if( addFilters.length === 0 ){
					addFilters.push(''); //ensure the first for runs once
				}
				var filters = [];
				for( var i = 0; i < combineFilters.length; i++ ){
					for( var j = 0; j < addFilters.length; j++ ){
						filters.push( combineFilters[i] + addFilters[j] );
					}
				}

				//Build the filter string
				var filterValue = '';
				for(var k = 0; k < filters.length; k++){
					filterValue += ( filterValue !== '' ? ',' : '' ) + filters[k];
				}

				$container.isotope({ filter: filterValue });

			}


			// change is-checked class on buttons
			$otopsiWrapper.find('.button-group').each( function( i, buttonGroup ) {
				var $buttonGroup = jQuery( buttonGroup );
				$buttonGroup.on( 'click', 'button', function() {
					$buttonGroup.find( '.is-checked' ).removeClass( 'is-checked' );
					jQuery( this ).addClass( 'is-checked' );
					applyFilters();
				});

			});
		});

	};

	window.Otopsi = Otopsi;
	jQuery(window).load( Otopsi );

})();
