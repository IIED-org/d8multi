<?php

namespace Drupal\flickr\Service;

use Drupal\flickr_api\Service\Photos as FlickrApiPhotos;
use Drupal\flickr_api\Service\Helpers as FlickrApiHelpers;

/**
 * Class Photos.
 *
 * @package Drupal\flickr\Service
 */
class Photos {

  /**
   * Photos constructor.
   *
   * @param \Drupal\flickr_api\Service\Photos $flickrApiPhotos
   * @param \Drupal\flickr\Service\Helpers $helpers
   * @param \Drupal\flickr_api\Service\Helpers $flickrApiHelpers
   */
  public function __construct(FlickrApiPhotos $flickrApiPhotos,
                              Helpers $helpers,
                              FlickrApiHelpers $flickrApiHelpers) {
    // Flickr API Photos.
    $this->flickrApiPhotos = $flickrApiPhotos;

    // Flickr Helpers.
    $this->helpers = $helpers;

    // Flickr API Helpers.
    $this->flickrApiHelpers = $flickrApiHelpers;

  }

  /**
   * @param $photo
   * @param $size
   * @param int $caption
   *
   * @return array
   */
  public function themePhoto($photo, $size, $caption = 0, $parent = NULL) {
    $photoSize = $this->photoGetSize($photo['id'], $size);
    $photoSizeLarge = $this->photoGetSize($photo['id'], 'b');

    if ($photoSize != FALSE) {
      $img = [
        '#theme' => 'image',
        '#style_name' => 'flickr-photo-' . $size . '-' . $photoSize['aspect'],
        '#uri' => $this->flickrApiHelpers->photoImgUrl($photo, $size),
        '#alt' => $photo['title']['_content'] . ' by ' . $photo['owner']['realname'],
        '#title' => $photo['title']['_content'] . ' by ' . $photo['owner']['realname'],
        '#attributes' => [
          'width' => $photoSize['width'],
          'height' => $photoSize['height'],
          // 'style' => 'width: ' . $photoSize['width'] . 'px; height: ' . $photoSize['width'] . 'px;',.
        ],
      ];

      $photoimg = [
        '#theme' => 'flickr_photo',
        '#photo' => $img,
        '#caption' => $caption,
        '#photo_page_url' => $photo['urls']['url'][0]['_content'],
        '#photo_image_large' => $photoSizeLarge['source'],
        '#parent' => $parent,
        '#style_name' => 'flickr-photo-' . $size . '-' . $photoSize['aspect'],
        '#width' => $photoSize['width'],
        '#height' => $photoSize['height'],
        '#attached' => [
          'library' => [
            'flickr/flickr.stylez',
          ],
        ],
      ];

      if ($caption == 1) {
        $photoimg['#caption_data'] = $this->themeCaption($photo, $size, $caption);
      }

      return $photoimg;
    }
  }

  /**
   * @param $photos
   * @param $size
   * @param int $caption
   *
   * @param null $parent
   *
   * @return array
   */
  public function themePhotos($photos, $size, $caption = 0, $parent = NULL) {
    foreach ($photos as $photo) {
      $themedPhotos[] = $this->themePhoto(
        $this->flickrApiPhotos->photosGetInfo($photo['id']),
        $size,
        $caption,
        $parent
      );
    }

    return [
      '#theme' => 'flickr_photos',
      '#photos' => $themedPhotos,
      '#attached' => [
        'library' => [
          'flickr/flickr.stylez',
        ],
      ],
    ];
  }

  /**
   * @param $photo
   * @param $size
   * @param $caption
   *
   * @return array
   */
  public function themeCaption($photo, $size, $caption) {
    return [
      '#theme' => 'flickr_photo_caption',
      '#caption' => $caption,
      '#caption_realname' => $photo['owner']['realname'],
      '#caption_title' => $photo['title']['_content'],
      '#caption_description' => $photo['description']['_content'],
      '#caption_dateuploaded' => $photo['dateuploaded'],
      '#style_name' => 'flickr-photo-' . $size,
      '#photo_size' => $size,
    ];
  }

  /**
   * @param $photoId
   * @param $size
   *
   * @return bool
   */
  public function photoGetSize($photoId, $size) {
    $photoSizes = $this->flickrApiPhotos->photosGetSizes($photoId);
    $sizes = $this->flickrApiHelpers->photoSizes();
    $label = $sizes[$size]['label'];

    foreach ($photoSizes as $size) {
      if ($size['label'] == $label) {
        $size['width'] = (int) $size['width'];
        $size['height'] = (int) $size['height'];
        $size['aspect'] = $this->photoCalculateAspectRatio($size['width'], $size['height']);
        return $size;
      }
    }

    return FALSE;
  }

  /**
   * @param $width
   * @param $height
   *
   * @return string
   */
  public function photoCalculateAspectRatio($width, $height) {
    $aspectRatio = (int) $width / (int) $height;

    if ($aspectRatio > 1) {
      // Image is Landscape.
      return 'landscape';
    }
    if ($aspectRatio < 1) {
      // Image is Portrait.
      return 'portrait';
    }
    else {
      // Image is Square.
      return 'square';
    }
  }

}
