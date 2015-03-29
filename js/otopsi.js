(function(){

  var Otopsi = function(){
    jQuery(".otopsi-init").each( function(){
      jQuery(this).removeClass("otopsi-init");
      var isotopeOptions = JSON.parse( "{"+jQuery(this).attr('data-otopsi')+"}" );
      var $container = jQuery(this).find(".otopsi-container").isotope( isotopeOptions );

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

})();
