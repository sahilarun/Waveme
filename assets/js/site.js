(function ($) {
  'use strict';

  // touch screen
  if( ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0) ){
      $('html').addClass('is-touch');
  }
  
  var init = function(){
    // for w3c valid
    var setting = store('setting');
    $('.theme-color').replaceWith('<label class="theme-color" for="theme-color"><input type="color" id="theme-color" '+(setting.color ? ('value="'+setting.color+'"') : '')+'>' + $('.theme-color').html() +'</label>');
    // scroll menu to center
    $('.page-navigation ul, .user-navigation ul').each(function(){
      var c = $(this).find('.active, .current_page_item');
      if(c.length){
        var p = c.offset().left - $(this).offset().left + c.outerWidth(true)/2 + $(this).scrollLeft() - $(this).width()/2;
        $(this).scrollLeft(p);
      }
    });
    $(document).trigger('init:site');
  }
  
  init();
  
  // start pjax
  var elements = 'a:not(.no-ajax):not([target]):not(.sub-ajax):not(.ajax_add_to_cart):not(.comment-reply-link):not(#cancel-comment-reply-link):not(.edd_cart_remove_item_btn):not(.edd_download_file_link), form.search-form';
  var ajax_elements = (typeof wp.hooks !== 'undefined' ) ? wp.hooks.applyFilters('hook_ajax_elements',  elements ) : elements;
  var selectors = ['title', '#header nav', '#content', '#aside', '#footer', '#mobile-menu'];
  var ajax_selectors= (typeof wp.hooks !== 'undefined' ) ? wp.hooks.applyFilters('hook_ajax_selectors', selectors) : selectors;
  var pjax = new Pjax({
    cacheBust: false,
    elements: ajax_elements,
    selectors: ajax_selectors,
    switches: {
      '#content': function(oldEl, newEl, options) {
        var that = this;
        $('html').addClass('page-animating');
        setTimeout(function(){
          oldEl.outerHTML = newEl.outerHTML;
          that.onSwitch();
          $('html').removeClass('page-animating');
        }, 500);
      }
    }
  });

  pjax._handleResponse = pjax.handleResponse;
  pjax.handleResponse = function(responseText, request, href) {
    if (request.responseText.match("<html")) {
      if(!responseText || (responseText && responseText.match(/class="site-content no-ajax/)) ){
        window.location.href = href;
        return;
      }
      var classNames = responseText.match(/body([^>]*)class=['|"]([^'|"]*)['|"]/);
      $(document).trigger('pjax:loaded');
      pjax._handleResponse(responseText, request, href);
      $(document).trigger('pjax:handled');
      setTimeout(function(){
        classNames && classNames[2] && $('body').attr('class', classNames[2]);
      }, 500);
    } else {
      window.location.href = href;
    }
  }

  var sub_pjax = new Pjax({
    cacheBust: false,
    elements: 'a.sub-ajax',
    selectors: ['#sub-ajax-content', '#sub-ajax-menu']
  });
  
  $(document).on('refresh', function() {
    pjax.refresh();
    sub_pjax.refresh();
  });

  $(document).on('reload', function(event, url) {
    pjax.loadUrl( (event && event.detail && event.detail.url) || url || window.location.href);
  });
  
  // ajax send
  $(document).on('pjax:send', function() {
    $('html').addClass('page-loading');
  });

  // ajax success
  $(document).on('pjax:complete', function() {
    init();
    $('#search-state').prop('checked', false);
    $('.modal-backdrop').remove();
    $('html').removeClass('page-loading scrolled');
    $(document).trigger('refresh');
    if($('body').width() < 782){
      $('#menu-state').prop('checked', false);
    }
  });

  // no-ajax
  $(document).on('click', '.no-ajax > a:not(.no-ajax)', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var href = $(this).attr('href') ? $(this).attr('href') : $(this).find('a').attr('href');
    window.location = href;
    return false;
  });

  // nav
  $(document).on('click', '.menu-item-has-children, .secondary-menu', function(e){
    e.preventDefault();
    e.stopPropagation();
    $(this).toggleClass('current-menu-ancestor');
  });

  // comment
  $(document).on('keyup', '#commentform textarea', function(e){
    if (this.clientHeight < this.scrollHeight) {
      this.style.height = this.scrollHeight + 'px';
    }
  });

  // theme switch
  $(document).on('click', '.theme-switch', function(e){
    e.preventDefault();
    $('html').toggleClass('dark');
    var data = store('setting');
    data.theme = $('html').hasClass('dark') ? 'dark': 'light';
    store('setting', data);
  });
  $('html').addClass( store('site-theme') );

  // theme color
  $(document).on('change', '.theme-color input', function(e){
    e.preventDefault();
    var color = $(this).val();
    var data = store('setting');
    data.color = color;
    store('setting', data);
    setColor();
  });

  $(document).on('click', '.accordion h3, .accordion h4', function(e){
    e.preventDefault();
    $(this).toggleClass('open');
    $(this).nextUntil('h3, h4').toggle();
  });

  function setTheme(){
    var data = store('setting'), theme = '';
    if(data.theme){
      theme = data.theme;
    }
    if(location.href.indexOf('theme=dark') > -1){
      theme = 'dark';
    }
    if(location.href.indexOf('theme=light') > -1){
      theme = 'light';
    }
    if($('.theme-switch').length == 0 || theme == '') return;
    $('html').removeClass('dark light').addClass(theme);
  }

  function setColor(){
    var data = store('setting');
    if(!data.color) return;
    $('html').attr('style', '--color-primary:'+data.color);
    $('.theme-color input').val(data.color);
  }
  setColor();
  setTheme();

  function toggleFullScreen(){
    var is_full_screen = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement || null;
  
    if(is_full_screen === null){
      var element = document.documentElement;
      if(element.requestFullscreen)
        element.requestFullscreen();
      else if(element.mozRequestFullScreen)
        element.mozRequestFullScreen();
      else if(element.webkitRequestFullscreen)
        element.webkitRequestFullscreen();
      else if(element.msRequestFullscreen)
        element.msRequestFullscreen();
    }else{
      if(document.exitFullscreen)
        document.exitFullscreen();
      else if(document.mozCancelFullScreen)
        document.mozCancelFullScreen();
      else if(document.webkitExitFullscreen)
        document.webkitExitFullscreen();
      else if(document.msExitFullscreen)
        document.msExitFullscreen();
    }
  }
  $(document).on('click', '.fullscreen', function(e){
    e.preventDefault();
    toggleFullScreen();
  });

  // save setting to localstorage
  function store(namespace, data) {
    var site = $('.site-title:first').text().replace(/\s/g, '');
    namespace = site+'--'+namespace;
    try{
      if (arguments.length > 1) {
        return localStorage.setItem(namespace, JSON.stringify(data));
      } else {
        var store = localStorage.getItem(namespace);
        return (store && JSON.parse(store)) || {};
      }
    }catch(err){
      
    }
  }

})(jQuery);
