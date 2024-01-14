(function ($, window) {
    'use strict';

    // upload
    var upload = {
      el: '#upload-modal',
      el_msg: '.form-message',
      url: '',
      server: '',
      trigger: '',
      type: '',
      items: [],
      init: function(){
        var that = this;
        this.setUrl = this.setUrl.bind(this);
        this.sortable = this.sortable.bind(this);
        this.sortable();
        // form processing
        $(document).on('click', '.form-validate input[type="submit"], #commentform input[type="submit"]', function (e) {
          var form = $(this).closest('form');
          that.validate(form);
        });

        $(document).on('click', '.btn-edit, .btn-upload', function(e){
            e.preventDefault();
            if(!play.is_user_logged_in) return;
            that.items = [];
            var id = $(this).closest('[data-play-id]').attr('data-play-id');
            $(that.el).remove();
            getModal(that.el, {name: 'upload_form', post_id: id}, that.sortable);
        });

        // remove
        $(document).on('click', '.track-list .remove', function (e) {
          var item = $(this).parent();
          item.remove();
          that.updateList();
        });
        
        // render image inline
        $(document).on('change', 'input[type="file"]', function(e){
          var input = $(this)[0];
          // show image inline
          if (input.files && input.files[0]) {
            var file = input.files[0];
            var img  = $(this).parent().find('img');
            if(img.length == 0) {
              img = $('<img>');
              $(this).next('.post-thumbnail').html(img);
            }
            try{
              var reader = new FileReader();
              reader.onload = function (e) {
                img.removeAttr('srcset');
                img.attr('src', e.target.result);
              };
              reader.readAsDataURL(file);
            }catch(e){}
          }
        });

        // online upload
        $(document).on('change', '[name="stream_url"]', function(e){
          if($(this).val().match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g)){
            that.url = $(this).val();
            $(that.el).remove();
            getModal(that.el, {name: 'upload_form', form: true}, that.setUrl);
            $(this).val('');
          }
        });

        // drag over
        $(document).on('dragover', '.dragdrop-upload', function (e) {
          e.stopPropagation();
          e.preventDefault();
          $(this).addClass('dropover');
        });

        // Drop upload
        $(document).on('drop', '.dragdrop-upload', function (e) {
          e.stopPropagation();
          e.preventDefault();
          $(this).removeClass('dropover');
          var items = e.originalEvent.dataTransfer.files;
          var files = [];
          var types = ['mp3','m4a','ogg','wav','mp4','m4v','mov','wmv','avi','mpg','ogv','3gp','3g2'];
          for (var i = items.length - 1; i >= 0; i--) {
            if( types.includes( items[i]['name'].split('.').pop() ) ){
              files.push( items[i] );
            }
          }
          if(files.length > 0){
            that.bulkUpload(files);
          }
        });

        // click upload
        $(document).on('change', '[name="stream_file"]', function(e){
          that.trigger = $(this);
          var files = $(this)[0].files;
          that.bulkUpload(files);
        });

        // change file
        $(document).on('change', '[name="upload_file"]', function(e){
          that.trigger = $(this);
          that.type = '';
          if($(this).attr('data-type') == 'single'){
            that.type = 'single';
          }
          var files = $(this)[0].files;
          var file = files[0];
          file.el = $(this).next('.progress');
          file['title'] = file['name'].replace(/\.[^/.]+$/, '').replace(/[\-_]/g, ' ');
          that.uploadFile( file );
        });

        // submit update
        $(document).on('click', '#upload [type="submit"]', function(e){
          e.preventDefault();
          $(that.el_msg, that.el).html('');
          var form = $(this).closest('form');
          var tracks = [];
          // get tracks
          if(that.items.length > 1){
            $('.track-list li').each(function(index){
              var _this = $(this),
              id = _this.attr('id'),
              item = that.getItem(id);
              tracks[index] = {};
              tracks[index].title = _this.find('input').val();
              if(item){
                tracks[index].url = item['url'];
                tracks[index].metadata = item['metadata'];
                if(item['waveform']){
                  tracks[index].waveform = item['waveform'].join(',');
                }
              }
            });
            if(!that.getUploaded(that.items)){
              form.removeClass('processing');
              form.find('.file-uploading').show();
              return false;
            }
          }

          if(!that.validate(form)){ return false; };

          var data = new FormData(form[0]);

          if(tracks.length){
            data.append("tracks", JSON.stringify(tracks));
          }

          // upload
          $.ajax({
            url: play.rest.endpoints.upload,
            data: data,
            type: 'POST',
            processData: false,
            contentType: false,
            cache: false,
            beforeSend: function (xhr) {
              xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
          }).then(function (res) {
            if(res.status == 'success'){
              $(that.el_msg, form).first().append( '<div class="success">'+res.msg+'</div>' );
              setTimeout(function() {
                var modal = $(form).closest('.modal');
                if(modal.length){
                  modal.modal('hide');
                }
                if(res.redirect){
                  redirect(res.redirect);
                }
               }, 3000);
            }else if(res.status == 'error'){
              $(that.el_msg, form).first().append( '<div class="error">'+res.msg+'</div>' );
            }
            form.removeClass('processing');
          });
          return false;
        });
      },
      bulkUpload: function(files){
        this.items = files;
        
        if(this.items.length > 1){
          this.type = 'multiple';
        }else{
          this.type = 'single';
        }
        this.upload = this.upload.bind(this);
        $(this.el).remove();
        getModal(this.el, {name: 'upload_form', form: true, type: (this.items.length > 1 ? 'playlist' : 'single') }, this.upload);
      },
      upload: function(){
        if(this.type == 'single'){
          $('.tracks').append('<ul class="track-list single"></ul>');
        }
        for (var i = this.items.length - 1; i >= 0; i--) {
          this.items[i]['id'] = i;
          this.items[i]['title'] = this.items[i]['name'].replace(/\.[^/.]+$/, '').replace(/[\-_]/g, ' ');
          this.items[i]['el'] = $('<li id="' + i + '" class="input"><div class="progress"><div class="progress-bar"></div></div><span class="handle"></span><input class="track-list-title" value="' + this.items[i]['title'] + '" /><span class="remove">×</span></div>');
          $('.track-list').append( this.items[i]['el'] );
          this.uploadFile(this.items[i], i);
        }
        this.sortable();
      },
      uploadFile: function(file){
        var that = this;

        // get waveform data
        if(play.waveform){
          var reader = new FileReader();
          reader.onload = function (event) {
            file['waveform'] = [];
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            try{
              audioContext.decodeAudioData(event.target.result, function(buffer) {
                var wf = new Waveform();
                file['waveform'] = wf.parsePeaks(buffer);
                file['bpm'] = DetectBPM(buffer);
              });
            }catch(e){}
          };
          reader.readAsArrayBuffer(file);
        }
        
        // upload file
        var data = new FormData();
        data.append("action", "upload_stream");
        data.append("file", file);
        file['uploading'] = true;

        $(that.el_msg, that.el).html('');

        $.ajax({
          url: play.rest.endpoints.upload_stream,
          type: 'POST',
          data: data,
          processData: false,
          contentType: false,
          enctype: 'multipart/form-data',
          beforeSend: function (xhr) {
              xhr.setRequestHeader('X-WP-Nonce', play.nonce);
          },
          xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
              xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                  var p = (evt.loaded / evt.total);
                  p = parseInt(p * 100);
                  if(file.el){
                    file.el.find('.progress-bar').css('width', p+'%');
                  }
                }
              }, false);
            }
            return xhr;
          }
        }).then(function (res) {
          if(file.el){
            file.el.find('.progress-bar').css('width', '0');
          }
          if (res.status == 'success') {
            file['url'] = res.url;
            file['metadata'] = res.metadata;
            that.setUpload(file);
          }else if(res.status == 'error'){
            $(that.el_msg, that.el).first().append( '<div class="error">'+res.msg+'</div>' );
          }
        }).always(function(){
          file['uploading'] = false;
          if(that.items.length > 1 && that.getUploaded(that.items)){
            $('.file-uploading').hide();
            $('.file-uploaded').show();
          }
        });
      },
      getUploaded: function(items){
        var uploaded = true;
        for (var i = items.length - 1; i >= 0; i--) {
          if(items[i]['uploading']){
            uploaded = false;
          }
        }
        return uploaded;
      },
      setUpload: function(item){
        // set url
        if( this.trigger && this.trigger.parent().hasClass('file-upload') ){
          this.trigger.parent().prev('input').val( item.url );
        }

        if( this.type == 'single' ){
          var form = $('.form');
          form.find('[name="title"]').val(item.title);
          form.find('[name="stream"]').val(item.url);

          if(item.metadata['title']){
            form.find('[name="title"]').val(item.metadata['title']);
          }

          if(item.metadata['length_formatted']){
            form.find('[name="duration"]').val(item.metadata['length_formatted']);
          }

          if(item.metadata['artist']){
            form.find('[name="artist"]').val(item.metadata['artist']);
          }

          if(item.waveform && item.waveform.length){
            form.append('<input type="hidden" name="waveform" value="'+item.waveform.join(',')+'" />');
          }

          if(item.bpm){
            form.append('<input type="hidden" name="bpm" value="'+item.bpm+'" />');
          }

        }else{
          if(item.metadata['title']){
            item.el.find('.track-list-title').val(item.metadata['title']);
          }
        }

      },
      getItem: function(id){
        for(var i=0; i<this.items.length; i++){
          if( parseInt(this.items[i].id) === parseInt(id) ){
              return this.items[i];
          }
        }
      },
      validate: function(form){
        var valid = true;
        form.find('[required]').each(function(){
          if(!$.trim($(this).val())){
              $(this).focus();
              valid = false;
              return false;
          }else{
              valid = true;
          }
        });
        if(valid){
          form.addClass('processing');
        }
        return valid;
      },
      setUrl: function(){
        var _this = this;
        $('[name="stream"]').val(_this.url);
        playImport(_this.url, {}, function(data){
          var WF = new Waveform();
          data.title && $('[name="title"]').val(data.title);
          data.description && $('[name="content"]').val(data.description);
          data.duration && $('[name="duration"]').val( WF.msToTime( data.duration ) );
          data.tags && $('[name="tag"]').val(data.tags);

          if( data.stream_url ){
            $('[name="upload"]').append('<input type="hidden" name="stream_url" value="'+ data.stream_url +'"/>');
          }
          if( data.waveform_data ){
            $('[name="upload"] [name="waveform"]').remove();
            $('[name="upload"]').append('<input type="hidden" name="waveform" value="'+ data.waveform_data +'"/>');
          }
          if( data.blob ){
            var container = new DataTransfer();
            container.items.add(data.blob);
            $('.file-upload input[name="image"]')[0].files = container.files;
            if( data.artwork_url ){
              $('.file-upload img').attr('src', data.artwork_url);
              $('.file-upload input').removeAttr('required');
            }
          }
        });
      },
      sortable: function(){
        var that = this;
        $('.track-list').length && sortable('.track-list', {handle: '.handle'})[0].addEventListener('sortupdate', function(e) {
          that.updateList();
        });
      },
      updateList: function(){
        var items = $('.track-list li[id]').map(function() { return this.id; }).get();
        $('[name="post"]').val( items.join() );
      },
      import: function(url, obj, callback){
        var proxyServer = play.rest.endpoints.proxy+'?url=';
        var server = '';
        if( url.indexOf('soundcloud.com') !== -1 ){
          server = 'soundcloud';
          url = proxyServer + url;
        }
        if( url.indexOf('hearthis.at') !== -1 ) {
          server = 'hearthis';
          url = url.replace( 'https://hearthis.at/', 'https://api-v2.hearthis.at/' );
        }
        if( url.indexOf('mixcloud.com') !== -1 ) {
          server = 'mixcloud';
          url = url.replace( 'https://www.mixcloud.com/', 'https://api.mixcloud.com/' );
        }

        url.match(/(http:|https:|)\/\/(player.|www.|music.|m.)?(vimeo\.com|youtu(be\.com|\.be|be\.googleapis\.com))\/(video\/|embed\/|watch\?v=|v\/)?([A-Za-z0-9._%-]*)(\&\S+)?/);
        if( RegExp.$3.indexOf('youtu') > -1 ) {
          server = 'youtube';
          url = 'https://www.googleapis.com/youtube/v3/videos?id='+ RegExp.$6 +'&key='+ play.youtube_api_key +'&part=snippet,contentDetails,statistics,status';
        }
        if( RegExp.$3.indexOf('vimeo') > -1 ) {
          server = 'vimeo';
          url = 'https://vimeo.com/api/v2/video/'+ RegExp.$6 +'.json';
        }

        obj.url = url;
        $.ajax(obj)
        .then(function(data){
          if( $.isArray(data) ){
            data = data[0];
          }
          if(server == 'soundcloud'){
            var m = data.match(/window\.__sc_hydration = (.*);<\/script>/);
            if(m){
              var n = eval(m[1]);
              for(var i=0; i<n.length; i++){
                if( n[i].hydratable === 'sound' ){
                  data = n[i].data;
                  var tags = data.tag_list.match(/\w+|"[^"]+"/g);
                  if(tags){
                    data.tags = tags.join(', ').replaceAll('"','');
                  }
                }
              }
            }
          }
          if(server == 'hearthis'){
            data.duration = data.duration * 1000;
            data.artwork_url = proxyServer + data.artwork_url;
          }
          if(server == 'mixcloud'){
            data = JSON.parse(data);
            data.title = data.name;
            data.artwork_url = data.pictures.extra_large;
            data.duration = data.audio_length * 1000;
            if(data.tags){
              var tags = data.tags.map(function(tag){ return tag.name });
              data.tags = tags.join(', ');
            }
          }
          if(server == 'soundcloud' && data.artwork_url){
            data.artwork_url = data.artwork_url.replace('-large', '-t500x500');
          }
          if(server == 'vimeo'){
            data.duration = data.duration * 1000;
            data.artwork_url = data.thumbnail_large;
          }
          var WF = new Waveform();
          if(server == 'youtube'){
            if(data['items']){
              var item = data['items'][0];
              data.title = item.snippet.title;
              data.description = item.snippet.description;
              data.artwork_url = proxyServer+(item.snippet.thumbnails.maxres ? item.snippet.thumbnails.maxres.url : item.snippet.thumbnails.standard.url);
              data.duration = WF.timeToMS( item.contentDetails.duration );
              data.tags = item.snippet.tags;
            }
          }

          if(callback){
            data.type = 'single';
            callback(data);
          }

          // save youtube and soundwave thumbnail
          if( data.artwork_url ){

            var xhr = new XMLHttpRequest();
            xhr.onload = function() {
                var reader = new FileReader();
                reader.onloadend = function() {
                  var res = reader.result.split(',');
                  var base64Image = res[1].replace(/\s/g, '');
                  var type = res[0].split(':')[1].split(';')[0];
                  var binaryImg = atob(base64Image);

                  var n = binaryImg.length;
                  var u8arr = new Uint8Array(n);
                  while (n--) {
                    u8arr[n] = binaryImg.charCodeAt(n);
                  }

                  var name = data.title +'.'+ type.replace('image/','');
                  var file = new File([u8arr], name, {type: type});

                  var obj = {};
                  obj.blob = file;
                  obj.artwork_url = data.artwork_url;

                  if(callback) callback( obj );
                }
                reader.readAsDataURL(xhr.response);
            };
            xhr.open('GET', data.artwork_url);
            xhr.responseType = 'blob';
            xhr.send();
          };

          // soundcloud and hearthis waveform data
          if(data.waveform_url){
            var obj = {};
            var url = data.waveform_url.replace('.png', '.json');
            var d = [];
            if(data.waveform_data){
              url = data.waveform_data;
            }
            if(server == 'hearthis'){
              url = proxyServer + url;
            }
            obj.url = url;
            $.ajax(obj).then(function(data){
              var obj = {};
              if(data.samples){
                // soundcloud
                d = data.samples;
              }else{
                // hearthis
                d = eval( data );
              }
              obj.waveform_data = WF.resizeData(d, WF.options.waveLength);
              if(callback){
                callback(obj);
              }
            });
          }

        });
      },
    }
    upload.init();

    window.playImport = upload.import;

    function redirect(url){
      if(typeof Pjax !== undefined && url.toLowerCase().indexOf('reload') === -1){
        $(document).trigger( 'reload', [url] );
      }else{
        location.href = url;
      }
    }

    // search
    var timeoutID = null;
    $(document).on('click', '#icon-search', function(e){
      setTimeout(function(){
        $('[name="s"]').focus();
      },0);
    });
    $(document).on('keyup', '#header-search-form input, .search-form input', function(e){
      var $input = $(this);
      if($input.val().length < 1) return;
      $input.parent().addClass('search-loading');
      $input.parent().find('.dropdown-menu').dropdown('show');
      clearTimeout(timeoutID);
      timeoutID = setTimeout(function() { search($input); }, 1000);
    });
    $(document).on('keypress', '#header-search-form input, .search-form input', function(e){
      if(e.which == 13){
        var $input = $(this);
        $input.blur().parent().find('.dropdown-menu').dropdown('hide');
      }
    });
    function search(input) {
      var dropdown = input.parent().find('.dropdown-menu');
      var query = input.val();
      $.ajax({
          url : play.rest.endpoints.search,
          datatype: 'json',
          type: 'get',
          data:{
            search: query
          },
          beforeSend: function() {
            dropdown.html('');
          }
      }).then( function( data ) {
          var el = '';
          $.each(data, function(k, v){
            el += '<a class="dropdown-item '+ (v.type == 'user' ? 'dropdown-user' : '') +'" href="'+v.url+'">'+v.thumbnail+'<span><span>'+v.title+'</span><span class="author">'+v.author+'</span></span></a>'
          });
          input.parent().removeClass('search-loading');
          dropdown.append(el);
          $(document).trigger('refresh');
      });
    }

    // comment
    if (typeof EventTarget !== "undefined") {
        var func = EventTarget.prototype.addEventListener;
        EventTarget.prototype.addEventListener = function (type, fn, capture) {
            this.func = func;
            if(typeof capture !== "boolean"){
                capture = capture || {};
                capture.passive = false;
            }
            this.func(type, fn, capture);
        };
    };
    $(document).on('click', '#commentform #submit', function(e){
        //e.preventDefault();
        var that = $(this),
            form = that.closest('#commentform'),
            comment = $('#comment').val(),
            status = $('<div class="comment-message"></div>');
        if($.trim(comment) == ''){
          return false;
        }
        form.find('.comment-message').remove();
        status.insertBefore('.comment-form-comment');
        
        that.prop('disabled', true);
        var data = form.serialize();
        var url = form.attr('action');

        $.ajax({
            type: 'post',
            url: url,
            data: data,
            error: function(jqXHR, textStatus, errorThrown){
               var res = jqXHR.responseText;
               var error = res.match(/<body id="error-page">([\s\S^<]*?)<\/body>/);
               form.find('.comment-message').html('').append( error[0] ).addClass('error');
               that.prop('disabled', false);
            }
        }).then(
          function(data, textStatus, jqXHR){
            that.prop('disabled', false);
            var error = data.match(/<body id="error-page">([\s\S^<]*?)<\/body>/);
            if(error){
                form.find('.comment-message').html('').append( error[0] ).addClass('error');
            }else{
                $(document).trigger( 'reload', [window.location.href.replace(window.location.hash,'')+'#comments'] );
            }
          }
        );
        return false;
    });

    // popper
    var popper = null;
    var popper_el = 'dropdown-more';

    function destory_popper(){
      if (popper) {
        popper.destroy();
        popper = null;
      }
    }

    // more
    $(document).on('click', '.btn-more', function (e) {
      e.preventDefault();
      e.stopPropagation();
      destory_popper();

      var $id = $(this).closest('[data-play-id]').attr('data-play-id') || $(this).attr('data-id');
      var $dp = $('#'+popper_el);
      if($dp.length > 0){
        $dp.attr('data-play-id', $id);
        $dp.html(play.el_more);
      }else{
        $dp = $('<div id="'+popper_el+'" class="dropdown-menu" data-window data-play-id="'+ $id +'">'+play.el_more+'</div><div class="dropdown-backdrop"></div>');
        $('body').append($dp);
      }

      var btns = $dp.find('.btn-edit, .btn-remove');
      if(play.is_user_logged_in && $(this).attr('data-editable') == 'true'){
        btns.each(
          function(){
            var url = play.edit_url + $id+'&action='+$(this).attr('data-action');
            $(this).attr('data-url', url);
          }
        );
      }else{
        btns.remove();
      }

      if($(this).attr('data-playable') == 'false'){
        $dp.find('.btn-next-play, .btn-queue, .btn-play-now, .dropdown-divider').remove();
      }

      var type = $(this).attr('data-type');
      if(type == 'playlist' || type == 'album'){
        $dp.find('.btn-playlist').remove();
      }
      if(type == 'user'){
        $dp.find('.btn-playlist').remove();
        $dp.find('.btn-next-play, .btn-queue, .btn-play-now').attr('data-user-id', $id);
      }
      $dp.find('.btn-like').attr('data-id', $id);
      $dp.find('.btn-share').attr('data-share-type', type).attr('data-url', $(this).attr('data-url')).attr('data-embed-url', $(this).attr('data-embed-url'));

      popper = new Popper($(this), $dp, {
        modifiers: {
          preventOverflow: { enabled: true },
        },
      });

      $dp.addClass('show');
      $('body').addClass('dropdown-open');
    });
  
    // dismiss popper
    $(document).on('click', function (e) {
      destory_popper();
      $('#'+popper_el).removeClass('show');
      $('body').removeClass('dropdown-open');
    });

    // delete account
    $(document).on('click', '.btn-delete-account', function(e){
        e.preventDefault();
        getModal('#remove-modal', {name:'delete-account'});
    });

    // update profile
    $(document).on('click', '#your-profile [type="submit"]', function(e){
      e.preventDefault();
      var form = $(this).closest('form');
      var data = new FormData(form[0]);
      var el_msg = '.form-message';
      $(el_msg, form).html('');
      form.addClass('processing');
      // upload
      $.ajax({
        url: play.rest.endpoints.profile,
        data: data,
        type: 'POST',
        processData: false,
        contentType: false,
        cache: false,
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', play.nonce);
        }
      }).then(function (res) {
        if(res.status == 'success'){
          $(el_msg, form).first().append( '<div class="success">'+res.msg+'</div>' );
          redirect(location.href);
        }else if(res.status == 'error'){
          $(el_msg, form).first().append( '<div class="error">'+res.msg+'</div>' );
        }
        form.removeClass('processing');
      }).fail(function() {
        form.removeClass('processing');
        redirect(location.href);
      });
      return false;
    });

    // btn like/follow
    $(document).on('click', '.btn-like, .btn-follow', function(e) {
      if(!play.is_user_logged_in) return;
      e.preventDefault();
      var $this = $(this),
          id = $this.attr('data-id'),
          action = $this.attr('data-action'),
          type = $this.attr('data-type');
      $this.attr('disabled', 'disabled');
      $.ajax({
        url: play.rest.endpoints[action],
        datatype: 'json',
        type: 'get',
        data: {
          type: type,
          action: action,
          nonce : play.nonce,
          id : id,
          url: location.href
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', play.nonce);
        }
      }).then(
        function(data){
          $this.attr('disabled', false);
          if(data.error){
            if(data.url){
              //location.href = data.url;
            }
          }else{
            var el = $('[data-id="'+id+'"][data-action="'+action+'"][data-type="'+type+'"]');
            if(data.status == 1){
              el.addClass('active');
            }else{
              el.removeClass('active');
            }
            $this.find('.count').text(data.count);
            // trigger data
            $(document).trigger(action+'.play', [id, data.status, data.type]);
          }
        }
      );
    });


    // password
    var pwdmeter = {
      el: '#login-form',
      el_field: '',
      el_strength: '.pwd-strength',
      el_hint: '.pwd-hint',
      init: function(){
        var that = this;
        $( document )
        .on(
          'keyup change',
          '#registerform [name="pwd"], #resetpasswordform [name="pwd"]',
          that.strengthMeter
        );

        $( '#resetpasswordform [name="pwd"]' ).trigger( 'change' );

        $( document )
        .on(
          'click',
          '.btn-toggle-pwd',
          that.toggle
        );

        $( document )
        .on(
          'click',
          '.btn-generate-pwd',
          that.generatePwd
        );

        that.toggleBtn();

        $( document ).on('pjax:complete loginModal:loaded', function() {
          that.toggleBtn();
        });
      },
      toggleBtn: function(){
        $('[type="password"]:not(".is-toggled")').addClass('is-toggled').after('<button type="button" class="button btn-toggle-pwd"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" class="eye-on"></path><circle cx="12" cy="12" r="3" class="eye-on"></circle><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" class="eye-off"></path><line x1="1" y1="1" x2="23" y2="23" class="eye-off"></line></svg></button>');
      },
      toggle: function(){
        var pwd = $(this).prev('input'),
            type = pwd.attr('type') == 'password' ? 'text' : 'password';
        
        pwd.attr('type', type );
      },
      includeMeter: function( wrapper, field ) {
        var meter = wrapper.find(this.el_strength);
        if ( '' === field.val() ) {
          meter.hide();
          $(document).trigger( 'pwd-strength-hide' );
        } else if ( 0 === meter.length ) {
          field.parent().after( '<p class="pwd-strength" aria-live="polite"></p>' );
          $(document).trigger( 'pwd-strength-added' );
        } else {
          meter.show();
          $(document).trigger( 'pwd-strength-show' );
        }
      },
      strengthMeter: function(){
        var field       = $(this),
            wrapper     = field.closest('form'),
            submit      = $( 'button[type="submit"]', wrapper ),
            strength    = 1,
            fieldValue  = field.val();

        pwdmeter.includeMeter( wrapper, field );

        strength = pwdmeter.checkPasswordStrength( wrapper, field );

        if (
          fieldValue.length > 0 &&
          strength < play.min_password_strength &&
          -1 !== strength
        ) {
          submit.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
        } else {
          submit.prop( 'disabled', false ).removeClass( 'disabled' );
        }
      },
      checkPasswordStrength: function( wrapper, field ) {
        var meter   = wrapper.find( this.el_strength ),
          hint      = wrapper.find( this.el_hint ),
          hint_html = '<p class="pwd-hint">' + play.i18n.pwd.hint + '</p>',
          strength  = pwdmeter.meter( field.val() ),
          error     = '';

        // Reset.
        meter.removeClass( 'short bad good strong' );
        hint.remove();

        if ( meter.is( ':hidden' ) ) {
          return strength;
        }

        switch ( strength ) {
          case 0 :
            meter.addClass( 'short' ).html( play.i18n.pwd.short + error );
            meter.after( hint_html );
            break;
          case 1 :
            meter.addClass( 'bad' ).html( play.i18n.pwd.bad + error );
            meter.after( hint_html );
            break;
          case 2 :
            meter.addClass( 'bad' ).html( play.i18n.pwd.bad + error );
            meter.after( hint_html );
            break;
          case 3 :
            meter.addClass( 'good' ).html( play.i18n.pwd.good + error );
            break;
          case 4 :
            meter.addClass( 'strong' ).html( play.i18n.pwd.strong + error );
            break;
          case 5 :
            meter.addClass( 'short' ).html( play.i18n.pwd.mismatch );
            break;
        }

        return strength;
      },
      generatePwd: function(){
        var that = $(this);
        $.ajax({
            url: play.rest.endpoints.generatepwd,
            type: 'GET',
            processData: false,
            contentType: false,
            cache: false
        }).then(function (data) {
          var field = that.closest('form').find('[name="pwd"]');
          field.val(data.data);
          field.trigger('change');
        } );
      },
      meter : function( password1, password2 ) {
        if (password1 != password2 && password2 && password2.length > 0)
          return 5;

        if ( 'undefined' === typeof window.zxcvbn ) {
          // Password strength unknown.
          return -1;
        }

        var result = zxcvbn( password1, [] );
        return result.score;
      }
    }
    pwdmeter.init();
    
    // login
    var login = {
      el: '#login-form',
      el_modal: '#login-modal',
      el_msg: '.form-message',
      btn: null,
      action: 'login',
      init: function(){
            var that = this;
            that.initEvents();
      },
      initEvents: function(){
          var that = this;

          // trigger
          $(document).on('click', '.btn-like, .btn-playlist, .btn-follow, .btn-ajax-login, .btn-ajax-register, .btn-ajax-login a, .btn-ajax-register a', function(e){
              if(play.is_user_logged_in) return;
              e.preventDefault();
              that.action = ($(this).hasClass('btn-ajax-register') || $(this).parent().hasClass('btn-ajax-register')) ? 'register' : 'login';
              that.showModal();
          });

          // switch form
          $(document).on('click', that.el+' a[class*="btn-"]', function(e){
              e.preventDefault();
              var url = $(this).attr('href');
              that.action = that.getURLParameter(url, 'action');
              that.showForm();
          });
          
          // submit form
          $(document).on('click', that.el+' button[type="submit"]', function(e){
              e.preventDefault();
              that.btn = $(this);
              that.ajaxForm();
          });

          // 
          $(document).on('require_login', function(e){
              that.showModal();
          });
      },
      showModal: function(){
        var that = this;
        if(play.disable_login_modal == '1'){
          redirect(play.login_url);
          return;
        }
        if($(that.el).length > 0 && $(that.el).closest(that.el_modal).length == 0) return;
        this.showForm = this.showForm.bind(this);
        getModal(that.el_modal, {name: 'login_form'}, that.showForm);
      },
      getURLParameter: function(url, name) {
        return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
      },
      ajaxForm: function(){
          var that = this;
          
          var form = $(that.btn).closest('form'), valid = true;
          var redirect_to = form.find('[name="redirect_to"]').val();
          that.action = form.find('[name="form-action"]').val();
          $(that.el_msg, that.el).html('');
          
          form.find('input[type="text"], input[type="password"], input[type="email"], [required]').each(function(){
              var input = $(this);
              var email_reg = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
              // register form
              if(input.attr('name') == 'user_login'){
                var val = input.val().toLowerCase();
                input.val(val);
              }
              // resetpassword form
              if(input.attr('name') == 'pass2'){
                var val = document.querySelector('#pass1').value;
                input.val(val);
              }
              if(!$.trim(input.val()) || (input.attr('type') == 'email' && !email_reg.test( input.val() ) ) ){
                  input.focus();
                  valid = false;
                  return false;
              }else{
                  valid = true;
              }
          });
          if(!valid) return false;
          form.addClass('processing');
          that.btn.prop('disabled', true);
          var data = new FormData(form[0]);
          $.ajax({
              url: play.rest.endpoints.auth,
              data: data,
              type: 'POST',
              processData: false,
              contentType: false,
              cache: false
          }).then(function (res) {
              form.removeClass('processing');
              that.btn.prop('disabled', false);
              form.find('input[type="text"], input[type="email"], input[type="password"]').val('');
              if(res.status == 'error'){
                $(document).trigger('login:error');
                $(that.el_msg, form).first().append( '<div class="error">'+res.msg+'</div>' );
              }else if(res.status == 'success'){
                $(document).trigger('login:success');
                $(that.el_msg, form).first().append( '<div class="success">'+res.msg+'</div>' );

                if(that.action == 'register'){
                  $(that.el_msg, form).nextAll('p').addClass('hideme').hide();
                }
                
                setTimeout(function() {
                  var modal = $(that.btn).closest('.modal');
                  if(modal.length){
                    var url = window.location.href;
                    if(res.redirect.indexOf('reload') > -1){
                      url = url+'?reload';
                    }
                    res.redirect = url;
                    modal.modal('hide');
                  }

                  if(res.nonce && res.nonce !== '' && play){
                    play.is_user_logged_in = true;
                    play.nonce = res.nonce;
                  }

                  if(res.redirect){
                    redirect(res.redirect);
                  }
                }, play.rest.timeout_redirect);
              }
          }).fail(function() {
            // get 404 when other plugin redirect the wp_login
            location.reload();
          });
      },
      showForm: function(){
        var that = this;
        var target = $('#'+that.action+'form');
        if(target.length){
          $(that.el_msg, that.el).html('');
          $(that.el+' form').hide();
          target.find('.hideme').show();
          target.show();
        }
        $(document).trigger('loginModal:loaded');
      }
    }
    login.init();
    
    // playlist
    var playlist = {
        id: 0,
        data: [],
        el: '#playlist-modal',
        btn: null,
        link: '',
        init: function(){
          var that = this;
          this.r = this.r.bind(this);
          this.setLink = this.setLink.bind(this);
          // add playlist modal to body
          $(document).on('click', '.btn-playlist', function(e){
            if(!play.is_user_logged_in) return;
            e.preventDefault();
            var id = $(this).closest('[data-play-id]').attr('data-play-id');
            if(id){
              that.id = id;
            }
            getModal(that.el, {name:'playlist'}, that.r);
            $(that.el).find('form').show();
          });

          // new playlist
          $(document).on('click', that.el+' .btn-new', function(e){
              e.preventDefault();
              that.btn = $(this);
              that.c();
          });

          // remove playlist
          $(document).on('click', that.el+' .btn-remove', function(e){
              e.preventDefault();
              that.btn = $(this);
              that.d();
          });

          // update
          $(document).on('click', that.el+' .btn-add, '+ that.el+' .btn-added', function(e){
              e.preventDefault();
              that.btn = $(this);
              that.u();
          });

          // dismiss modal
          $(document).on('click', that.el+' a', function(e){
            $(e.target).closest('.modal').modal('hide');
          });

          // remove
          $(document).on('click', '.btn-remove', function(e){
              e.preventDefault();
              that.link = $(this).attr('data-url');
              getModal('#remove-modal', {name:'remove'}, that.setLink);
          });
          
        },
        c: function(){
          var that = this,
              form = $(that.el).find('form'),
              title = form.find('input');
          if($.trim(title.val()) == ''){
            return;
          }
          that.btn.prop('disabled', 'disabled');
          $.ajax({
            type : "GET",
            dataType : "json",
            url : play.rest.endpoints.playlist,
            data : {nonce: play.nonce, post_id: that.id, title: title.val(), type: 'c'},
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
          }).then(function( res ) {
            that.data.unshift(res.data);
            form.hide();
            that.btn.prop('disabled', false);
            title.val('');
            that.render();
          });
        },
        r: function(){
          var that = this;
          $.ajax({
            type : "GET",
            dataType : "json",
            url : play.rest.endpoints.playlist,
            data : {nonce: play.nonce, post_id: that.id, type: 'r'},
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
          }).then(function(data){
            if(data.status == 'success'){
              that.data = data.data;
              that.render();
            }
          });
        },
        u: function(){
          var that = this,
              $id  = that.btn.closest('[data-play-id]').attr('data-play-id'),
              $obj = that.getObj($id, that.data);

          that.btn.prop('disabled', 'disabled');

          if(that.btn.is('.btn-add')){
            $obj['post'].push(that.id);
          }else{
            $obj['post'].splice( $.inArray(that.id, $obj['post']), 1);
          }
          
          $.ajax({
            type : "GET",
            dataType : "json",
            url : play.rest.endpoints.playlist,
            data : {nonce: play.nonce, post_id: $id, post: $obj['post'], type: 'u'},
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
          }).then(function( res ) {
            that.btn.prop('disabled', false);
            if(res.status == 'success'){
              that.render();
            }
          });
        },
        d: function(){
          var that = this,
              $id  = that.btn.closest('[data-play-id]').attr('data-play-id');
              
          that.btn.prop('disabled', 'disabled');
          
          $.ajax({
            type : "GET",
            dataType : "json",
            url : play.rest.endpoints.playlist,
            data : {nonce: play.nonce, post_id: $id, type: 'd'},
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
          }).then(function( data ) {
            that.btn.prop('disabled', false);
            if(data.status == 'success'){
              $.each(that.data, function( index, value ) {
                if($id == that.data[index]['id']){
                  that.data.splice( index, 1);
                  return false;
                }
              });
              that.render();
            }
          });
        },
        render: function(){
          var that = this,
              els = $(that.el+' .block-loop-items');
          els.empty();
          $.each(that.data, function(index, value){
            var item = $('#tpl-item').clone();
            item.attr('id', '').attr('style', '');
            item.attr('data-play-id', value['id']);
            value['thumb'] && item.find('img').attr('src', value['thumb']);
            item.find('.entry-title').html( value['title'] ).attr('href', value['url']);
            item.find('.count').html( value['post'].length );
            item.find('.post-thumbnail a').attr('href', value['url']);

            item.find('.btn-add, .btn-added').hide();
            if(that.id > 0){
              if( $.inArray( that.id, value['post'] ) !== -1 ){
                item.find('.btn-add').hide();
                item.find('.btn-added').show();
              }else{
                item.find('.btn-added').hide();
                item.find('.btn-add').show();
              }
            }
            $(els).append(item);
          });
          $(document).trigger('refresh');
        },
        getObj: function(key, objs){
          var $obj = false;
          $.each(objs, function( index, value ) {
            if(key == objs[index]['id']){
              $obj = objs[index];
              return;
            }
          });
          return $obj;
        },
        setLink: function(){
          $('[data-remove]').attr('href', this.link);
        }
    }
    playlist.init();

    // share
    var share = {
        el: '#share-modal',
        el_embed: '.share-embed',
        el_url: '#share-url',
        link: '',
        embed: '',
        type: '',
        init: function(){
          var that = this;
          this.setUrl = this.setUrl.bind(this);
          $(document).on('click', '.btn-share', function(e){
              var iframe = $(that.el_embed).find('iframe');
              if(iframe.length > 0){
                var code = iframe[0].outerHTML;
                iframe.remove();
                $(that.el_embed).append(code);
              }
              that.type = $(this).attr('data-share-type');
              that.link = $(this).attr('data-url') || $(this).parent().prev().attr('data-url');
              that.embed = $(this).attr('data-embed-url');
              if(!that.link){
                that.link = location.href;
              }
              getModal(that.el, {name:'share'}, that.setUrl, that.close);
          });
          $(document).on('click', '#share-url, #embed-code', function(e){
            $(this).select();
            document.execCommand("copy");
          });
        },
        setUrl: function(){
          var that = this;
          $('.share-list a').each( function(index) {
            $(this).attr( 'href', $(this).attr('data-url') + encodeURIComponent( that.link ) );
          });
          $(that.el_url).val( that.link );
          if($('.share-embed iframe').length > 0){
            $('.share-embed iframe').attr( 'src', that.embed ).attr('type',that.type);
            $('#embed-code').val( $('.share-embed iframe')[0].outerHTML );
          }
        },
        close: function(){
          $('.share-embed iframe').attr( 'src', ' ' );
        }
    }
    share.init();

    // notification
    var notification = {
        inter: play.rest.timeout_notify,
        timeout: null,
        el: '#dropdown-notification',
        init: function(){
          this.initEvent();
          if(!play.is_user_logged_in) return;
          this.getNotification();
        },
        initEvent: function(){
          var _this = this;
          $(document).on('click', _this.el, function(e){
            _this.getNotification();
          });
        },
        getNotification: function() {
          var _this = this;
          clearTimeout(_this.timeout);
          $.ajax({
            url : play.rest.endpoints.notification,
            type : "GET",
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
          }).then(function(res){
            $('.dropdown-notification-list').html(res.content);
            $(document).trigger('refresh');
            var count = $(res.content).find('.is-new').length;
            if(count > 0){
              $(_this.el).find('.count').html(count);
            }else{
              $(_this.el).find('.count').html('');
            }
            setTimeout(function(){
              _this.getNotification();
            }, _this.inter);
          });
        }
    }
    notification.init();

    var getModal = function(modal, data, callback, closeCallback){
      if(!$(modal).length){
        var m = $('<div class="modal fade modal-loading" id="'+modal.substr(1)+'"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="spinner"></div></div></div></div></div>');
        $('body').append(m);
        var url = play.rest.endpoints.modal;
        $.ajax({
            type : "GET",
            url : url,
            data : data,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', play.nonce);
            }
        }).then(function(res){
            if(res){
                m.removeClass('modal-loading').find('.modal-content').html(res.content);
                if(callback){
                  callback();
                }
            }else{
                $(modal).modal('hide');
            }
        });
      }else{
        if(callback){
          callback();
        }
      }
      $(modal).modal('show');
      $(modal).on('hidden.bs.modal', function (e) {
        if(closeCallback){
          closeCallback();
        }
      });
      $(document).trigger('modal:loaded');
    }

})(jQuery, window);
