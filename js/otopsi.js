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
      var $container = jQuery(this).find(".otopsi-container").isotope( isotopeOptionsJSON );

      jQuery(this).find('.otopsi-filters').on( 'click', 'button', function() {
        var filterValue = jQuery( this ).attr('data-filter');

        $container.isotope({ filter: filterValue });
      });

      // change is-checked class on buttons
      jQuery(this).find('.button-group').each( function( i, buttonGroup ) {
        var $buttonGroup = jQuery( buttonGroup );
        $buttonGroup.on( 'click', 'button', function() {
          $buttonGroup.find('.is-checked').removeClass('is-checked');
          jQuery( this ).addClass('is-checked');
        });
      });

    });
  };

  window.Otopsi = Otopsi;
  jQuery(window).load( Otopsi );

})();
