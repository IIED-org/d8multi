<?php

namespace Drupal\flickr_api\Service;

/**
 * Class Groups.
 *
 * @package Drupal\flickr_api\Service
 */
class Groups {

  /**
   * @var \Drupal\flickr_api\Service\Client
   */
  protected $client;

  /**
   * Groups constructor.
   *
   * @param \Drupal\flickr_api\Service\Client $client
   * @param \Drupal\flickr_api\Service\Helpers $helpers
   */
  public function __construct(Client $client, Helpers $helpers) {
    // Flickr API Client.
    $this->client = $client;

    // Flickr API Helpers.
    $this->helpers = $helpers;
  }

  /**
   * Returns info about a given group.
   *
   * @param string $id
   *   NSID of the group whose photos you want.
   *
   * @param array $other_args
   * @param bool $cacheable
   *
   * @return array
   *   Response from the flickr method flickr.groups.getInfo.
   *   (https://www.flickr.com/services/api/flickr.groups.getInfo.html)
   */
  public function groupsGetInfo($id, $other_args = [], $cacheable = TRUE) {
    if ($this->helpers->isNsid($id)) {
      $args = ['group_id' => $id];
    }
    else {
      $args = ['group_path_alias' => $id];
    }

    $args = array_merge($args, $other_args);

    $response = $this->client->request(
      'flickr.groups.getInfo',
      $args,
      $cacheable
    );

    if ($response) {
      return $response['group'];
    }

    return FALSE;
  }

}
