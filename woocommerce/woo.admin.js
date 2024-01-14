(function ($, window) {
  'use strict';

  var init = function(){
    $(window).on( 'load', function () {
      var $wcpd = $( '#woocommerce-product-data' );
      $wcpd.off( 'click', '.hndle' );
      $wcpd.find( '.hndle' ).unbind( 'click.postboxes' );
      $wcpd.on( 'click', '.hndle', function( event ) {
          if ( $( event.target ).filter( 'input, option, label, select' ).length ) {
              return;
          }
          $wcpd.toggleClass( 'closed' );
          postboxes.save_state( 'product' );
      });
    });
  }

  init();

})(jQuery, window);
