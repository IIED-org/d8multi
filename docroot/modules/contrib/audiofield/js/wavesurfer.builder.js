/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  'use strict';

  Drupal.AudiofieldWavesurfer = {};

  Drupal.AudiofieldWavesurfer.generate = function (context, file, settings) {
    $.each($(context).find('#' + file.id).once('generate-waveform'), function (index, wavecontainer) {
      var wavesurfer = WaveSurfer.create({
        container: '#' + $(wavecontainer).attr('id') + ' .waveform',
        audioRate: settings.audioRate,
        autoCenter: settings.autoCenter,
        barGap: settings.barGap,
        barHeight: settings.barHeight,
        barWidth: settings.barWidth,
        cursorColor: settings.cursorColor,
        cursorWidth: settings.cursorWidth,
        forceDecode: settings.forceDecode,
        normalize: settings.normalize,
        progressColor: settings.progressColor,
        responsive: settings.responsive,
        waveColor: settings.waveColor
      });

      wavesurfer.load(file.path);

      wavesurfer.setVolume(settings.volume);

      $(wavecontainer).find('.player-button.playpause').on('click', function (event) {
        Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
      });

      $(wavecontainer).find('.volume').on('change', function (event) {
        wavesurfer.setVolume($(event.currentTarget).val() / 10);
      });

      if (!!settings.autoplay) {
        wavesurfer.on('ready', wavesurfer.play.bind(wavesurfer));
      }
    });
  };

  Drupal.AudiofieldWavesurfer.generatePlaylist = function (context, settings) {
    $.each($(context).find('#wavesurfer_playlist').once('generate-waveform'), function (index, wavecontainer) {
      var wavesurfer = WaveSurfer.create({
        container: '#' + $(wavecontainer).attr('id') + ' .waveform',
        audioRate: settings.audioRate,
        autoCenter: settings.autoCenter,
        barGap: settings.barGap,
        barHeight: settings.barHeight,
        barWidth: settings.barWidth,
        cursorColor: settings.cursorColor,
        cursorWidth: settings.cursorWidth,
        forceDecode: settings.forceDecode,
        normalize: settings.normalize,
        progressColor: settings.progressColor,
        responsive: settings.responsive,
        waveColor: settings.waveColor
      });

      wavesurfer.setVolume(settings.volume);

      var first = $(wavecontainer).find('.playlist .track').first();

      var label = $(wavecontainer).find('label').first();
      label.html('Playing: ' + first.html());

      first.addClass('playing');

      wavesurfer.load(first.attr('data-src'));

      $(wavecontainer).find('.player-button.playpause').on('click', function (event) {
        Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
      });

      $(wavecontainer).find('.player-button.next').on('click', function (event) {
        Drupal.AudiofieldWavesurfer.Next(wavecontainer, wavesurfer);
      });
      $(wavecontainer).find('.player-button.previous').on('click', function (event) {
        Drupal.AudiofieldWavesurfer.Previous(wavecontainer, wavesurfer);
      });

      $(wavecontainer).find('.playlist .track').on('click', function (event) {
        if ($(this).hasClass('playing')) {
          Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
        }
        else {
          Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, $(event.currentTarget));
        }
      });

      $(wavecontainer).find('.volume').on('change', function (event) {
        wavesurfer.setVolume($(event.currentTarget).val() / 10);
      });

      if (!!settings.autoplay) {
        wavesurfer.on('ready', wavesurfer.play.bind(wavesurfer));
      }

      wavesurfer.on('finish', function (event) {
        Drupal.AudiofieldWavesurfer.Next(wavecontainer, wavesurfer);
      });
    });
  };

  Drupal.AudiofieldWavesurfer.PlayPause = function (wavecontainer, wavesurfer) {
    wavesurfer.playPause();
    var button = $(wavecontainer).find('.player-button.playpause');
    if (wavesurfer.isPlaying()) {
      $(wavecontainer).addClass('playing');
      button.html('Pause');
    } else {
      $(wavecontainer).removeClass('playing');
      button.html('Play');
    }
  };

  Drupal.AudiofieldWavesurfer.Load = function (wavecontainer, wavesurfer, track) {
    wavesurfer.load(track.attr('data-src'));
    wavesurfer.on('ready', function (event) {
      $(wavecontainer).removeClass('playing');
      $(wavecontainer).addClass('playing');
      $(wavecontainer).find('.player-button.playpause').html('Pause');
      wavesurfer.play();
    });

    $(wavecontainer).find('.track').removeClass('playing');

    track.addClass('playing');

    $(wavecontainer).find('label').first().html('Playing: ' + track.html());
  };

  Drupal.AudiofieldWavesurfer.Next = function (wavecontainer, wavesurfer) {
    if (wavesurfer.isPlaying()) {
      Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
    }

    var track = $(wavecontainer).find('.track.playing').next();
    if (typeof track.attr('data-src') === 'undefined') {
      track = $(wavecontainer).find('.track').first();
    }

    Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, track);
  };

  Drupal.AudiofieldWavesurfer.Previous = function (wavecontainer, wavesurfer) {
    if (wavesurfer.isPlaying()) {
      Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
    }

    var track = $(wavecontainer).find('.track.playing').prev();
    if (typeof track.attr('data-src') === 'undefined') {
      track = $(wavecontainer).find('.track').last();
    }

    Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, track);
  };

  Drupal.behaviors.audiofieldwavesurfer = {
    attach: function attach(context, settings) {
      $.each(settings.audiofieldwavesurfer, function (key, settingEntry) {
        if (settingEntry.playertype === 'default') {
          $.each(settingEntry.files, function (key2, file) {
            Drupal.AudiofieldWavesurfer.generate(context, file, settingEntry);
          });
        } else if (settingEntry.playertype === 'playlist') {
          Drupal.AudiofieldWavesurfer.generatePlaylist(context, settingEntry);
        }
      });
    }
  };
})(jQuery, Drupal);
