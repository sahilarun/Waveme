(function ($) {
  "use strict";
  
  // start player
  var playlist = false;
  $(document).on('click.play', '.btn-play, .btn-play-now, .btn-next-play, .btn-queue', function(e){
    e.preventDefault();
    createPlyr([]);

    if(!playlist) return;

    var id = $(this).closest('[data-play-id]').attr('data-play-id') || $(this).attr('data-user-id'),
        type = $(this).attr('data-user-id') ? 'user' : 'post',
        from = $(this).closest('.is-album').attr('data-play-id') || $(this).closest('.is-playlist').attr('data-play-id'),
        index = $(this).attr('data-index') || 0,
        ids = [];

    if( $(this).hasClass('active') ){
      playlist.pause();
      return;
    }
    if(type == 'post' && playlist.getIndex(id) > 0){
      playlist.play({id: id}, index);
    }else{
      var url = play.rest.endpoints.play + '/' + id;
      var data = {
          type: type
      };
      if($(this).closest('.album-tracks').length && from){
        data.from = from;
      }
      if($('.btn-play[data-play-id='+id+']').hasClass('btn-play-auto') ){
        var ids = [];
        $('.album-track').each(function(key, item){
          ids.push( parseInt( $(item).attr('data-play-id') ) );
        });
        data.ids = ids;
      }
      $.ajax({
        url: url,
        type: 'get',
        datatype: 'json',
        data: data,
        async: ((/iPhone|iPod|iPad/.test(navigator.platform)) ? false : true), // for ios
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', play.nonce);
        }
      }).then(
        function(data){
          playlist.play(data, index);
        }
      );
    }

  });

  function createPlyr(items){
    if(playlist) return;
    if(!play.is_user_logged_in && play.login_to_play == '1'){
      $(document).trigger('require_login');
      return;
    }
    var play_el = $('<div class="plyr-playlist player fixed-bottom" id="plyr-playlist"><audio playsinline id="player"></audio></div>').appendTo('body');
    $('html').addClass('open-player');
    playlist = new Playlist(
      {
        playlist: '#plyr-playlist', 
        player: '#player'
      },
      items,
      {
        theme: play.player_theme,
        timeoutCount: play.rest.timeout_count,
        iconUrl: play.url + 'libs/plyr/plyr.svg',
        blankVideo: play.url + 'libs/plyr/blank.mp4',
        history: play.player_history,
        adsInterval: play.ad_interval ? play.ad_interval : 3,
        ads: {
          enabled: play.ad_tagurl ? true : false,
          tagUrl: play.ad_tagurl
        },
        i18n: play.i18n,
        autoplay: true, // for ads
        playsinline: true,
      }
    );

    playlist.player.on('timeupdate', function(e){
      // update the waveform
      var item = playlist.getCurrent();
      if(!item) return;
      var percent = playlist.player.currentTime / playlist.player.duration;
      var waves = $('.waveform .waveform_wrap');
      var wave = $('[data-id="'+item.id+'"].waveform_wrap');
      waves.not(wave).trigger('timeupdate', 0);
      wave && percent && wave.trigger('timeupdate', percent);
    });
    return playlist;
  }

  $(document).on('playlist', function(e){
    createPlyr();
  });

  // init
  function init(){
    $.fn.popover && $('[data-toggle="popover"]').popover();
    $.fn.tooltip && $('[data-toggle="tooltip"]').tooltip();
    waveform();
  }
  
  // waveform
  function waveform(){
    $('.waveform').each(function(){
      var $this = $(this);
      var $color = $this.css('color');
      var $data = $this.attr('data-waveform');
      if(!$data) return;

      $data = eval('['+$data+']');
      var data = {container:$this.find('.waveform-container'), id:$this.attr('data-id'), duration: $this.attr('data-duration')};
      var wf = new Waveform(data);
      wf.load($data);
      
      // update the player 
      $(wf.wrap).on('update', function(e, percent, id){
        if(!playlist) return;
        var item = playlist.getCurrent();
        if(id == item.id){
          playlist.player.currentTime = playlist.player.duration * percent;
        }
      });

      // remove data attribute
      $this.removeAttr('data-waveform');
    });
  }
  
  $(document).on('pjax:end, refresh', function(e){
    init();
  });

  $(window).on('load', function() {
    init();
    try{
      if( $('.no-player').length > 0 ){
        return;
      }
      // play default
      if(play.default_id){
        var pl = createPlyr(play.default_id);
        return;
      }
      // play history
      var data = localStorage.getItem('plyr');
      if(data){
        data = JSON.parse(data);
        if(data.items.length > 0 && play.player_history){
          var pl = createPlyr(data.items);
        }
      }
    }catch(err){
      
    }
  });

  // like in player
  $(document).on('like.play', function(e, id, status, type){
    if(!playlist || type !== 'post') return;
    var item = playlist.getItem(id);
    if(item) item.like = status;
  });

  // auto get next
  $(document).on('complete.play', function(e, obj){
    var id = obj.ids.slice(-1).pop();
    var url = play.rest.endpoints.play + '/' + id;
    $.ajax({
      url: url,
      type: 'get',
      datatype: 'json',
      data:{
        type: 'next',
        ids: obj.ids
      }
    }).then(
      function(data){
        if(data !== false){
          playlist.play(data, 0);
        }
      }
    );
  });

  // played to count
  $(document).on('played', function(e, obj){
    var id = obj.id;
    var url = play.rest.endpoints.play + '/' + id;
    $.ajax({
      url: url,
      type: 'get',
      datatype: 'json',
      data: {
        type: 'played',
        nonce: play.nonce
      },
      beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', play.nonce);
      }
    });
  });

})(jQuery);
