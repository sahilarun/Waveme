(function($) {
  'use strict';

  var init = function() {

    $('.edd-no-js').hide();
    $('a.edd-add-to-cart').addClass('edd-has-js');

  }
  // ajax success
  $(document).on('pjax:complete', function() {
    init();
  });

  init();

})(jQuery);
