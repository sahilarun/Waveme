/**
 * 1.0
 * waveform for plyr
 * 
 * @ flatfull.com All Rights Reserved.
 * Author url: flatfull.com
 */

// https://github.com/tornqvist/bpm-detective
(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.DetectBPM=f()}})(function(){var define,module,exports;return function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r}()({1:[function(require,module,exports){"use strict";Object.defineProperty(exports,"__esModule",{value:true});exports.default=detect;var OfflineContext=window.OfflineAudioContext||window.webkitOfflineAudioContext;function detect(buffer){var source=getLowPassSource(buffer);source.start(0);return[findPeaks,identifyIntervals,groupByTempo(buffer.sampleRate),getTopCandidate].reduce(function(state,fn){return fn(state)},source.buffer.getChannelData(0))}function getTopCandidate(candidates){return candidates.sort(function(a,b){return b.count-a.count}).splice(0,5)[0].tempo}function getLowPassSource(buffer){var length=buffer.length,numberOfChannels=buffer.numberOfChannels,sampleRate=buffer.sampleRate;var context=new OfflineContext(numberOfChannels,length,sampleRate);var source=context.createBufferSource();source.buffer=buffer;var filter=context.createBiquadFilter();filter.type="lowpass";source.connect(filter);filter.connect(context.destination);return source}function findPeaks(data){var peaks=[];var threshold=.9;var minThresold=.3;var minPeaks=15;while(peaks.length<minPeaks&&threshold>=minThresold){peaks=findPeaksAtThreshold(data,threshold);threshold-=.05}if(peaks.length<minPeaks){throw new Error("Could not find enough samples for a reliable detection.")}return peaks}function findPeaksAtThreshold(data,threshold){var peaks=[];for(var i=0,l=data.length;i<l;i+=1){if(data[i]>threshold){peaks.push(i);i+=1e4}}return peaks}function identifyIntervals(peaks){var intervals=[];peaks.forEach(function(peak,index){var _loop=function _loop(i){var interval=peaks[index+i]-peak;var foundInterval=intervals.some(function(intervalCount){if(intervalCount.interval===interval){return intervalCount.count+=1}});if(!foundInterval){intervals.push({interval:interval,count:1})}};for(var i=0;i<10;i+=1){_loop(i)}});return intervals}function groupByTempo(sampleRate){return function(intervalCounts){var tempoCounts=[];intervalCounts.forEach(function(intervalCount){if(intervalCount.interval!==0){var theoreticalTempo=60/(intervalCount.interval/sampleRate);while(theoreticalTempo<90){theoreticalTempo*=2}while(theoreticalTempo>180){theoreticalTempo/=2}theoreticalTempo=Math.round(theoreticalTempo);var foundTempo=tempoCounts.some(function(tempoCount){if(tempoCount.tempo===theoreticalTempo){return tempoCount.count+=intervalCount.count}});if(!foundTempo){tempoCounts.push({tempo:theoreticalTempo,count:intervalCount.count})}}});return tempoCounts}}},{}],2:[function(require,module,exports){module.exports=require("./detect").default},{"./detect":1}]},{},[2])(2)});

window.Waveform = {};

(function ($) {
  "use strict";
  
  Waveform = function(opts){
    this.data = [];
    this.peaks = [];
    this.wrap = null;
    this.wave = null;
    this.waveCtx = null;
    this.progress = 0;
    this.options = this._extends({}, this._options, opts);
    if(typeof this.options.duration === "string" && this.options.duration.indexOf(':') > -1){
      this.options.duration = this.timeToMS(this.options.duration);
    }
    this.init();
  }

  Waveform.prototype = {
    _options: {
      id: Math.random().toString(32).substring(2),
      rtl: (getComputedStyle(document.body).direction == 'rtl' ? true : false),
      barSpacing: 1,
      barWidth: 2,
      barHeight: 50,
      barRadius: 0,
      waveLength: 200,
      color: 'rgba(160,160,160,0.4)',
      progressColor: false,
      width: 300,
      height: 80,
      duration: 0,
      align: 'bottom',
    },
    _extends:function(target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];
        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }
      return target;
    },
    init: function(){
      this.container = 'string' === typeof this.options.container ? document.querySelector( this.options.container ) : this.options.container;
      if(!this.container) return;
      this.wrap = $('<div class="waveform_wrap" data-id="'+this.options.id+'"><span class="waveform-time"><span class="waveform-elapsed"></span><span class="waveform-duration"></span></span></div>');
      this.wrap.find('.waveform-duration').html(this.msToTime(this.options.duration));
      this.wave = document.createElement('canvas');
      this.waveCtx = this.wave.getContext('2d');

      this.wrap.append(this.wave).appendTo( this.container );
      this.options.width = this.wrap.width();

      this.wave.height = this.options.height;
      this.wave.width = this.options.width * 2;
      this.wave.style.width = '100%';
      this.wave.style.height = this.options.height/2 + 'px';

      this.initEvents();
    },
    clearWave: function() {
        this.waveCtx.clearRect(
            0,
            0,
            this.waveCtx.canvas.width,
            this.waveCtx.canvas.height
        );
    },
    drawWave: function() {
      if(this.data.length == 0 || !this.container) return;
      this.clearWave();
      this.prepareData();
      this.draw();
    },
    draw: function(){
      var peaks = this.peaks;
      var peaksLength = peaks.length;
      var height = this.wave.height;
      var progressPeak = Math.round(peaksLength*this.progress);
      var x = 0, y = 0, h = 0, w = this.options.barWidth*2;
      this.wave.width = this.options.width*2;
      
      this.waveCtx.fillStyle = this.options.progressColor ? this.options.progressColor : $(this.container).css('borderTopColor');
      
      for ( var i = 0; i < peaksLength; i++ ) {
        if(i >= progressPeak){
          this.waveCtx.fillStyle = this.options.color;
        }
        x = i*(this.options.barSpacing*2 + w);
        if(this.options.rtl){
          x = this.options.width*2 - x;
        }
        h = peaks[i];
        switch(this.options.align){
          case 'top':
            y = 0;
            break;
          case 'middle' :
            y = Math.ceil((height - peaks[i])/2);
            break;
          case 'center':
            // reset and move to the center of our circle
            this.waveCtx.setTransform(1,0,0,1, this.wave.width/2, this.wave.height/2);
            // rotate the context so we face the correct angle
            this.waveCtx.rotate(i*2*Math.PI/peaksLength - Math.PI/2);
            // move along y axis to reach the inner radius
            this.waveCtx.translate(0, (this.wave.height - 2*this.options.barHeight)/2);
            // centered on x
            x = -this.options.barWidth/2;
            // from the inner radius
            y = 0;
            break;
          default:
            y = height - peaks[i];
        }
        this.waveCtx.save();
        if(this.options.barRadius > 0){
          this.drawRoundRect(this.waveCtx, x, y, w, h, this.options.barRadius);
        }else{
          this.waveCtx.fillRect(x, y, w, h);
        }
        this.waveCtx.restore();
      }
    },
    drawRoundRect: function(ctx, x, y, width, height, radius) {
      if (height === 0) {
        return;
      }
      ctx.beginPath();
      ctx.moveTo(x + radius, y);
      ctx.lineTo(x + width - radius, y);
      ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
      ctx.lineTo(x + width, y + height - radius);
      ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
      ctx.lineTo(x + radius.bl, y + height);
      ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
      ctx.lineTo(x, y + radius);
      ctx.quadraticCurveTo(x, y, x + radius, y);
      ctx.closePath();
      ctx.fill();
    },
    prepareData: function(){
      var width = this.options.width;
      var height = this.options.height;
      if(this.options.align == 'center'){
        width = (this.options.height - this.options.barHeight*2)*Math.PI;
        height = this.options.barHeight;
      }
      var scale = Math.max.apply(null, this.data)/height;
      this.peaks = this.data.map(function(i) {
          return Math.round( i / scale );
      });
      var n = Math.round( width/(this.options.barWidth+this.options.barSpacing) );
      this.peaks = this.resizeData(this.peaks, n);
    },
    resizeData: function(data, n){
      var num = data.length - n;
      var ratio = data.length / Math.abs(num);
      for ( var i = 0; i < Math.abs(num); i++ ) {
        var j = (i+1)*ratio;
        if(num < 0){
          var k = Math.round(ratio*i) + i;
          var d = Math.round(data[k]+data[k+1])/2;
          data.splice( j+i, 0, d );
        }else{
          data.splice( j-i, 1 );
        }
      }
      return data;
    },
    initEvents: function(){
      var self = this;
      var timer_id;
      window.addEventListener("resize", function() {
          clearTimeout(timer_id);
          timer_id = setTimeout(function() {
              self.options.width = self.wrap.width();
              self.drawWave();
          }, 500);
      });

      this.wrap.on("click", function(e) {
        var client = self.wrap[0].getBoundingClientRect();
        var clientX = e.targetTouches
          ? e.targetTouches[0].clientX
          : e.clientX;
        var w = clientX - client.left;
        if(self.options.rtl){
          w = self.options.width - w;
        }
        var percent = (w/self.options.width).toFixed(4);
        if(self.options.align == 'center'){
          var clientY = e.targetTouches
            ? e.targetTouches[0].clientY
            : e.clientY;

          var ey = (clientY - client.top);
          var ex = (clientX - client.left);
          var cy = self.options.height/4;
          var cx = self.options.width/2;
          var dy = ey - cy;
          var dx = ex - cx;
          var theta = Math.atan2(dy, dx);
          theta *= 180 / Math.PI;
          if (theta < 0) theta = 360 + theta;
          percent = (theta/360).toFixed(4);
        }
        self.wrap.trigger('update', [percent, self.options.id]);
      });

      this.wrap.on('timeupdate', function(e, percent){
        self.update(percent);
      });
    },
    update: function(progress){
      if(this.progress == progress) return;
      this.progress = progress;
      this.drawWave();
      this.updateTime();
      if(this.progress == 1){
        this.reset();
      }
    },
    reset: function(){
      this.update(0);
    },
    drawTime: function(){
      this.wrap.find('.waveform-duration').html(this.msToTime(this.options.duration));
      this.updateTime();
    },
    updateTime: function(){
      var time = this.options.duration * this.progress;
      var t = this.msToTime(time);
      this.wrap.find('.waveform-elapsed').html(t);
    },
    load: function(data){
      if('string' === typeof data){
        this.loadAudioData(data);
        return;
      }
      this.data = data;
      this.drawWave();
      this.drawTime();
    },
    loadAudioData: function( url, callback ){
      var self = this;
      var request = new XMLHttpRequest();
      request.open('GET', url, true);
      request.responseType = 'arraybuffer';
      request.onload = function() {
          var audioContext = new (window.AudioContext || window.webkitAudioContext)();
          audioContext.decodeAudioData(request.response, function(buffer) {
            self.options.duration = buffer.duration * 1000;
            self.data = buffer.data = self.parsePeaks(buffer);
            try{
              buffer.bpm = DetectBPM(buffer);
            }catch(err){
              
            }
            if(self.container){
              self.drawWave();
              self.drawTime();
            }
            if(callback) callback(buffer);
          });
      };
      request.send();
    },
    parsePeaks: function (buffer) {
      var length = this.options.waveLength;
      var sampleSize = buffer.length / length;
      var sampleStep = ~~(sampleSize / 10) || 1;
      var channels = buffer.numberOfChannels;
      var peaks = [];
      var zero = true;

      for (var c = 0; c < channels; c++) {
        var chan = buffer.getChannelData(c);
        for (var i = 0; i < length; i++) {
          var start = ~~(i * sampleSize);
          var end = ~~(start + sampleSize);
          var max = 0;
          for (var j = start; j < end; j += sampleStep) {
            var value = chan[j];
            if (value > max) {
              max = value;
            } else if (-value > max) {
              max = -value;
            }
          }
          if (c == 0 || max > peaks[i]) {
            peaks[i] = Math.round(max*50);
            if(max > 0){
              zero = false;
            }
          } 
        }
      }
      return zero ? [] : peaks;
    },
    msToTime: function(d) {
      if(d == '' && d != 0){
        return '';
      }
      var milliseconds = parseInt((d%1000)/100)
          , seconds = parseInt((d/1000)%60)
          , minutes = parseInt((d/(1000*60))%60)
          , hours = parseInt((d/(1000*60*60))%24);

      seconds = (seconds < 10) ? "0" + seconds : seconds;
      minutes = (minutes < 10) ? "0" + minutes : minutes;
      if(hours != ''){
        hours = (hours < 10) ? "0" + hours : hours;
        return hours + ":" + minutes + ":" + seconds;
      }
      return minutes + ":" + seconds;
    },
    timeToMS: function(time) {
      var matches = time.match(/[0-9]+[HMS]/g);
      if(matches){
        var seconds = 0;
        matches.forEach(function (part) {
            var unit = part.charAt(part.length-1);
            var amount = parseInt(part.slice(0,-1));
            switch (unit) {
                case 'H':
                    seconds += amount*60*60;
                    break;
                case 'M':
                    seconds += amount*60;
                    break;
                case 'S':
                    seconds += amount;
                    break;
                default:
                    // noop
            }
        });
        return seconds * 1000;
      }

      var t = time.split(':');
      return (t.length==1) ? (Number(t[0])*1000) : ( (t.length == 2) ? ( Number(t[0])*60*1000 + Number(t[1])*1000 ) : ( Number(t[0])*60*60*1000 + Number(t[1])*60*1000 + Number(t[2])*1000) );
    }
  }

})(jQuery);
