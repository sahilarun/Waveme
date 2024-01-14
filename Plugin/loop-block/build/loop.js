/**
 * 1.0
 * loop
 * 
 * @ flatfull.com All Rights Reserved.
 * Author url: flatfull.com
 */

!function(r){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=r();else if("function"==typeof define&&define.amd)define([],r);else{("undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this).BezierEasing=r()}}(function(){return function f(u,i,a){function c(n,r){if(!i[n]){if(!u[n]){var e="function"==typeof require&&require;if(!r&&e)return e(n,!0);if(d)return d(n,!0);var t=new Error("Cannot find module '"+n+"'");throw t.code="MODULE_NOT_FOUND",t}var o=i[n]={exports:{}};u[n][0].call(o.exports,function(r){return c(u[n][1][r]||r)},o,o.exports,f,u,i,a)}return i[n].exports}for(var d="function"==typeof require&&require,r=0;r<a.length;r++)c(a[r]);return c}({1:[function(r,n,e){var a=4,c=1e-7,d=10,o="function"==typeof Float32Array;function t(r,n){return 1-3*n+3*r}function f(r,n){return 3*n-6*r}function u(r){return 3*r}function l(r,n,e){return((t(n,e)*r+f(n,e))*r+u(n))*r}function p(r,n,e){return 3*t(n,e)*r*r+2*f(n,e)*r+u(n)}function s(r){return r}n.exports=function(f,n,u,e){if(!(0<=f&&f<=1&&0<=u&&u<=1))throw new Error("bezier x values must be in [0, 1] range");if(f===n&&u===e)return s;for(var i=o?new Float32Array(11):new Array(11),r=0;r<11;++r)i[r]=l(.1*r,f,u);function t(r){for(var n=0,e=1;10!==e&&i[e]<=r;++e)n+=.1;var t=n+.1*((r-i[--e])/(i[e+1]-i[e])),o=p(t,f,u);return.001<=o?function(r,n,e,t){for(var o=0;o<a;++o){var f=p(n,e,t);if(0===f)return n;n-=(l(n,e,t)-r)/f}return n}(r,t,f,u):0===o?t:function(r,n,e,t,o){for(var f,u,i=0;0<(f=l(u=n+(e-n)/2,t,o)-r)?e=u:n=u,Math.abs(f)>c&&++i<d;);return u}(r,n,n+.1,f,u)}return function(r){return 0===r?0:1===r?1:l(t(r),n,e)}}},{}]},{},[1])(1)});

(function () {
  "use strict";

  window.slider = function(el, options){
    var el = el
    , rtl = (getComputedStyle(el).direction == 'rtl') ? true : false
    , arrows = options.arrows == false ? false : true
    , dots = options.dots ? true : false
    , slides = options.slides
    , autoplay = options.autoplay ? true : false
    , autoplaySpeed = options.autoplaySpeed || 2000
    , loop = options.loop ? true : false
    , gap = parseInt( getComputedStyle(el)['gridColumnGap'], 10 ) || 20
    , ua = (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) || (/Edge\/\d./i.test(navigator.userAgent))
    , l_c = options.l_c || 'slider-left-btn'
    , r_c = options.r_c || 'slider-right-btn'
    , l_svg = options.l_svg || '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>'
    , r_svg = options.r_svg || '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>'
    , space = 8
    , scrollingClass = 'block-slider-scrolling'
    , nav, dot, l, r, it ;

    el.className += ' block-loop-slider';
    arrows && _nav();
    dots && _dot();
    autoplay && _play();
    _active();

    function _nav(){
      nav = document.createElement('div');
      nav.className = 'block-loop-nav';
      el.parentNode.appendChild(nav);
      _nav_();
    }

    function _nav_(){
      var n = _n()
      , html = '';
      html = '<button class="'+l_c+'">'+l_svg+'</button><span></span><button class="'+r_c+'">'+r_svg+'</button>';
      nav.innerHTML = html;
      l = nav.getElementsByClassName(l_c)[0];
      r = nav.getElementsByClassName(r_c)[0];
      nav.addEventListener("click", function(e){
        _stop();
        var btn = e.target
        , w = slides ? slides * (el.children[0].offsetWidth + gap) : (el.offsetWidth + gap)
        , m = btn.className.indexOf(l_c) > -1 ? -1 : 1
        , v ;
        v = el.scrollLeft + w*m*(rtl ? -1 : 1);
        _scroll(v);
      });
    }

    function _dot(){
      dot = document.createElement('div');
      dot.className = 'block-loop-dot';
      el.parentNode.appendChild(dot);
      _dot_();
    }

    function _dot_(){
      var n = _n()
      , html = '';
      if(!dot || n < 2) return;
      for (var i = 0; i < n; i++){
        html += '<button data-index="'+i+'"></button>';
      }
      dot.innerHTML = html;
      dot.addEventListener("click", function(e){
        _stop();
        var btn = e.target
        , w = el.offsetWidth + gap
        , i = btn.dataset.index
        , v = w*i*(rtl ? -1 : 1);
        _scroll(v);
      });
    }

    function _play(){
      if(options.autoplay !== true || autoplay == false) return;
      autoplay = true;
      it = setInterval(throttle(_play_, 30), autoplaySpeed);
    }

    function _pause(){
      autoplay = false;
      it && clearInterval(it);
    }

    function _stop(){
      autoplay = false;
      it && clearInterval(it);
    }

    function _play_(){
      var w = el.scrollWidth
      , m = slides || 1
      , i = el.children[0].offsetWidth + gap
      , v = el.scrollLeft + i*m*(rtl ? -1 : 1);
      if( (v + el.offsetWidth) > w) {
        if(loop){
          v = 0;
        }else{
          _stop();
        }
      }
      autoplay && _scroll(v);
      _active();
    }

    function _n(){
      return Math.round( el.scrollWidth / el.offsetWidth );
    }

    el.addEventListener(('ontouchstart' in window) ? 'touchstart' : 'mouseenter', _pause);
    el.addEventListener(('ontouchstart' in window) ? 'touchend' : 'mouseleave', _play);
    el.addEventListener('scroll', debounce(_active, 25));
    window.addEventListener('resize', debounce(_resize, 25));

    function _resize(){
      _dot_();
      _active();
    }

    function _active(){
      var n = _n()
      , ns = el.children
      , e_l = el.scrollLeft
      , e_r = e_l + el.offsetWidth
      , e_w = el.scrollWidth
      , gap = 4
      , m = Math.round((e_w - e_l)/el.offsetWidth)
      ;
      // item
      for (var i = 0; i < ns.length; i++){
        var j = ns[i]
        , j_l = j.offsetLeft
        , j_r = j_l + j.offsetWidth
        ;
        
        if( !( e_r - gap < j_l || e_l + gap > j_r ) ){
          if(ns[i].className.indexOf('slider-active') == -1){
            ns[i].className += ' slider-active';
          }
        }else{
          var reg = new RegExp('(\\s|^)slider-active(\\s|$)');
          ns[i].className = ns[i].className.replace(reg,'');
        }
      }

      // nav
      if(arrows){
        l && (e_l*(rtl ? -1 : 1) < space) ? (l.style.display = 'none') : (l.style.display = '');
        r && ((e_w - e_l*(rtl ? -1 : 1)) < el.offsetWidth + space) ? (r.style.display = 'none') : (r.style.display = '');
      }
      // dot
      if(dots){
        var a = dot.querySelector('[data-index="'+(n-m)*(rtl ? -1 : 1)+'"]')
        , b = dot.getElementsByTagName('button') ;
        for (var i = 0; i < b.length; i++){
          b[i].className = '';
        }
        a && (a.className = 'active');
      }
    }

    function _scroll(v){
      if(el.classList.contains(scrollingClass)) return;
      var from = el['scrollLeft'], to = v, duration = 1500;
      var startCallBack = function(){
        el.classList.add(scrollingClass);
      }

      var endCallback = function(){
        el.classList.remove(scrollingClass);
      }

      _scrollTo(el, 'scrollLeft', from, to, duration, startCallBack, endCallback);
      
    }

    function _scrollTo(target, property, from, to, duration, startCallback, endCallback){
      var easing = [0.425, 0.005, 0, 1];
      easing = BezierEasing( easing[0], easing[1], easing[2], easing[3] );
      const render = (p) => {
        target[property] = from + (to - from) * p;
      };
      
      const start = Date.now();
      const loop = () => {
        var p = (Date.now() - start) / duration;
        if (p > 1) {
          // Animation ends
          render(1);
          if (endCallback) endCallback();
        } else {
          requestAnimationFrame(loop);
          render(easing(p));
        }
      };
      if (startCallback) startCallback();
      loop();
    }
  };

  window.scroller = function(el, options){
    var el = el
    , options = options || {}
    , trigger = el.getElementsByClassName('scroller')[0]
    , autoTrigger = options.autoTrigger == false ? false : true
    , autoTriggerUntil = options.autoTriggerUntil || 10
    , n = 0
    , loadingHtml = options.loadingHtml || '<span class="spinner"></span><span class="screen-reader-text"></span>'
    ;
    if(!trigger) return;

    window.addEventListener('scroll', debounce(_scroll, 25), false);
    trigger.addEventListener('click', _load, false);

    function _scroll(){
      if(!trigger) return;
      var t = window.pageYOffset
      , b = t + window.innerHeight
      , i = trigger.getBoundingClientRect()
      , i_t = i.top + t
      , i_b = i_t + trigger.offsetHeight
      ;
      if ((b >= i_t) && (t < i_b)) {
        if(autoTrigger && n < autoTriggerUntil){
          _load();
        }
      }
    }

    function _load(event){
      event && event.preventDefault();
      if( trigger.className.indexOf('is-loading') > -1 ) return;
      trigger.innerHTML = loadingHtml;
      trigger.className += ' is-loading';
      var url = trigger.href
      , r = new XMLHttpRequest();
      r.responseType = 'json';
      r.onreadystatechange = function(){
          if (r.readyState == 4 && r.status == 200){
            trigger.className = '';
            trigger.removeEventListener('click', _load);
            el.removeChild(trigger);
            var dom = document.createElement('div'), i;
            dom.innerHTML = r.response.content;

            while(i=dom.firstChild) el.appendChild(i);

            trigger = el.getElementsByClassName('scroller')[0];
            trigger && trigger.addEventListener('click', _load, false);

            document.dispatchEvent(new Event('refresh'));
            n ++;
          }
      }
      r.open("GET", url, true);
      if( typeof play !== 'undefined' && typeof play.nonce !== 'undefined'  ){
          r.setRequestHeader('X-WP-Nonce', play.nonce);
      }
      r.send();
    }
  };

  window.range = function(input){
    var value = input.getAttribute("value")
    ,values = value === null ? [] : value.split(",")
    ,min = +(input.min || 0)
    ,max = +(input.max || 100)
    ,el_min = document.createElement("span")
    ,el_max = document.createElement("span")
    ,ghost = input.cloneNode()
    ;

    input.classList.add("multirange");
    input.classList.add("original");
    ghost.classList.add("multirange");
    ghost.classList.add("ghost");

    el_min.classList.add('range-min');
    el_max.classList.add('range-max');

    input.value = values[0] || min + (max - min) / 2;
    ghost.value = values[1] || min + (max - min) / 2;

    input.parentNode.insertBefore(ghost, input.nextSibling);
    input.parentNode.insertBefore(el_min, input.previousSibling);
    input.parentNode.insertBefore(el_max, input.previousSibling);

    var descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value");

    Object.defineProperty(input, "originalValue", descriptor.get ? descriptor : {
      get: function() { return this.value; },
      set: function(v) { this.value = v; }
    });

    Object.defineProperties(input, {
      valueLow: {
        get: function() { return Math.min(this.originalValue, ghost.value); },
        set: function(v) { this.originalValue = v; update(); },
        enumerable: true
      },
      valueHigh: {
        get: function() { return Math.max(this.originalValue, ghost.value); },
        set: function(v) { ghost.value = v; update(); },
        enumerable: true
      }
    });

    if (descriptor.get) {
      Object.defineProperty(input, "value", {
        get: function() { return this.valueLow + "," + this.valueHigh; },
        set: function(v) {
          var values = v.split(",");
          this.valueLow = values[0];
          this.valueHigh = values[1];
          update();
        },
        enumerable: true
      });
    }

    if (typeof input.oninput === "function") {
      ghost.oninput = input.oninput.bind(input);
    }

    ghost.addEventListener("mousedown", function passClick(evt) {
      var clickValue = min + (max - min)*evt.offsetX / this.offsetWidth;
      var middleValue = (input.valueHigh + input.valueLow)/2;
      if ( (input.valueLow == ghost.value) == (clickValue > middleValue) ) {
        input.value = ghost.value;
      }
    });

    input.addEventListener("input", update);
    ghost.addEventListener("input", update);

    function update( arg ) {
      var l = 100 * ((input.valueLow - min) / (max - min));
      var r = 100 * ((input.valueHigh - min) / (max - min));
      ghost.style.setProperty("--low", l+'%');
      ghost.style.setProperty("--high", r+'%');
      el_min && ( el_min.innerHTML = input.valueLow, el_min.style.setProperty("--left", l) );
      el_max && ( el_max.innerHTML = input.valueHigh, el_max.style.setProperty("--left", r ) );
      
      if( arg !== 1 ){
        goto();
      }
    }

    var timeoutID = null;
    function goto(){
      clearTimeout(timeoutID);
      timeoutID = setTimeout(function() {

        var url = input.getAttribute('data-url');
        var name = input.getAttribute('name');
        var reg = new RegExp(name+'__(\\d+)-(\\d+)[%2C]*[(\\d+)\\-(\\d+)]*');
        url = url.replace( reg, name+'__'+input.valueLow+'-'+input.valueHigh );

        document.dispatchEvent( new CustomEvent('reload', { detail: { url: url } } ) );

      }, 1000);
    }

    update( 1 );
  };

  window.moreless = function(el, options){
    el.className += ' moreless';
    if(el.scrollHeight > el.clientHeight){
      var dom = document.createElement('button')
      , content = el.innerHTML
      , title = el.getAttribute('title')
      , type = el.getAttribute('type')
      , more = el.getAttribute('more')
      , less = el.getAttribute('less');
      dom.className = 'btn-moreless';
      dom.innerHTML = '<span>'+more+'</span><span>'+less+'</span>';
      el.appendChild(dom);
      dom.addEventListener("click", function(e){
        if(type == 'expand'){
          el.classList.toggle("show");
        }
        if(type == 'modal'){
          modal(title, content);
        }
      });
    }
  }

  window.modal = function(title, content){
    var el = '<div class="modal-backdrop"></div><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><div class="modal-title"><h2>'+title+'</h2></div><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">'+content+'</div></div></div>'
    ,modal = document.createElement('div');

    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = el;
    document.body.appendChild(modal);

    var head = modal.getElementsByClassName('modal-header')[0];
    var back = modal.getElementsByClassName('modal-backdrop')[0];
    var clos = head.getElementsByClassName('close')[0];
    document.body.classList.toggle('modal-open');

    clos.addEventListener("click", function(e){
      modal.remove();
      document.body.classList.toggle('modal-open');
    });
    back.addEventListener("click", function(e){
      modal.remove();
      document.body.classList.toggle('modal-open');
    });
  }

  function debounce(func, wait, immediate) {
    var timeout, result;

    return function() {
      var context = this, args = arguments, later, callNow;

      later = function() {
        timeout = null;
        if (!immediate) { result = func.apply(context, args); }
      };

      callNow = immediate && !timeout;

      clearTimeout(timeout);
      timeout = setTimeout(later, wait);

      if (callNow) { result = func.apply(context, args); }

      return result;
    };
  };

  function throttle(func, wait) {
    var context, args, timeout, result, previous, later;

    previous = 0;
    later = function() {
      previous = new Date();
      timeout = null;
      result = func.apply(context, args);
    };

    return function() {
      var now = new Date(),
          remaining = wait - (now - previous);

      context = this;
      args = arguments;

      if (remaining <= 0) {
        clearTimeout(timeout);
        timeout = null;
        previous = now;
        result = func.apply(context, args);
      }

      else if (!timeout) {
        timeout = setTimeout(later, remaining);
      }

      return result;
    };
  };

  function plugin(){
    var el
    , str_plugin = 'data-plugin'
    , str_option = 'data-option'
    , els = document.querySelectorAll('[data-plugin],[class^="plugin-"],[class*=" plugin-"]')
    , options
    , plugin;
    for (var i = 0; i < els.length; i++) {
      el = els[i];
      if(el.className.indexOf('plug-initialized') > -1){
        return;
      }
      plugin  = el.getAttribute(str_plugin) || el.className.match(/(?:^|\s)plugin-(.*?)(?:$|\s)/)[1];
      options = el.getAttribute(str_option) || '{}';
      options = eval("(" + options + ")");

      var klass = el.className;
      var reg = /data-([^-]+)-([^\s]+)/g;
      var result;

      while((result = reg.exec(klass)) !== null) {
          options[result[1]] = result[2];
      }

      // get options from attribute
      for( var d in el.dataset ){
        if( d.indexOf(plugin) > -1 ){
          var attr = d.replace(plugin, '').toLowerCase();
          if(attr !== ''){
            options[attr] = el.dataset[d];
          }
        }
      }

      if(options['child']){
        el = el.children[0];
      }

      el.removeAttribute(str_plugin);
      el.removeAttribute(str_option);
      el.className += ' plug-initialized';

      if(el[plugin] && options){
        el[plugin].apply(el, options);
      }else if(window[plugin] && window[plugin].call){
        window[plugin].call(self, el, options);
      }
      
    }
  }

  function init(){
    if(location.href.indexOf('dir=rtl') > -1){
      document.body.classList.add('rtl');
    }
    plugin();
  }
  
  window.addEventListener('pjax:complete', function(){
    init();
  });

  init();

})();
