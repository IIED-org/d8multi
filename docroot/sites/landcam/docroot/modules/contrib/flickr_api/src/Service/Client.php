<?php

namespace Drupal\flickr_api\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client as GuzzleClient;
use Drupal\Core\Url;

/**
 * Class Client.
 *
 * @package Drupal\flickr_api\Service
 */
class Client {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Client constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   */
  public function __construct(ConfigFactory $config,
                              CacheBackendInterface $cacheBackend,
                              TranslationInterface $stringTranslation) {
    // Get the config.
    $this->config = $config->get('flickr_api.settings');
    // Cache Backend.
    $this->cacheBackend = $cacheBackend;
    // String Translation.
    $this->stringTranslation = $stringTranslation;

    $this->api_uri = $this->config->get('api_uri');
    $this->host_uri = $this->config->get('host_uri');
    $this->api_key = $this->config->get('api_key');
    $this->api_secret = $this->config->get('api_secret');
    $this->api_cache_maximum_age = $this->config->get('api_cache_maximum_age');

    $this->guzzleClient = new GuzzleClient([
      'base_uri' => $this->api_uri,
    ]);

  }

  /**
   * @param $method
   * @param array $args
   * @param bool $cacheable
   *
   * @return bool|mixed
   */
  public function request($method, $args, $cacheable = TRUE) {
    // Build the arg_hash.
    $args = $this->buildArgs($args, $method);
    $arg_hash = $this->buildArgHash($args);

    // $response = &drupal_static(__FUNCTION__); // Can be replaced with the `__METHOD__`.
    $cid = 'flickr_api:' . md5($arg_hash);

    // Check cache.
    if ($cache = $this->cacheBackend->get($cid)) {
      $response = $cache->data;

      // Return result from cache if found.
      return $response;
    }
    // No cache. Do it the hard way.
    else {
      // If we've got a secret, sign the arguments.
      if ($secret = $this->api_secret) {
        $args['api_sig'] = md5($secret . $arg_hash);
      }

      // TODO Implement try catch.
      $response = $this->doRequest('', $args);
      if ($response) {
        // Cache the response if we got one.
        if ($this->api_cache_maximum_age != 0 && $cacheable == TRUE) {
          $this->cacheBackend->set($cid, $response, time() + $this->api_cache_maximum_age);
        }

        // Return result from source if found.
        return $response;
      }
    }

    // Tough luck, no results mate.
    return FALSE;
  }

  /**
   * @param $args
   * @param $method
   * @param string $format
   *
   * @return mixed
   */
  private function buildArgs($args, $method, $format = 'json') {
    // Add in additional parameters then sort them for signing.
    $args['api_key'] = $this->api_key;
    $args['method'] = $method;
    $args['format'] = 'json';
    $args['nojsoncallback'] = 1;
    ksort($args);

    return $args;
  }

  /**
   * @param $args
   *
   * @return string
   */
  private function buildArgHash($args) {
    // Build an argument hash API signing (we'll also use it for the cache id).
    $arg_hash = '';

    foreach ($args as $k => $v) {
      $arg_hash .= $k . $v;
    }

    return $arg_hash;
  }

  /**
   * @param $url
   * @param array $parameters
   * @param string $requestMethod
   *
   * @return bool|mixed
   */
  private function doRequest($url, $parameters = [], $requestMethod = 'GET') {
    if (!$this->api_key || !$this->api_secret) {
      $msg = $this->t('Flickr API credentials are not set. It can be set on the <a href=":config_page">configuration page</a>.',
        [':config_page' => Url::fromRoute('flickr_api.settings')]
      );

      drupal_set_message($msg, 'error');
      return FALSE;
    }
    $response = $this->guzzleClient->request($requestMethod, $url, ['query' => $parameters]);

    // TODO Error checking can be improved.
    if ($response->getStatusCode() == !200) {
      return FALSE;
    }

    // TODO Add some checking.
    $body = $response->getBody();

    // TODO Add some checking.
    return json_decode((string) $body, TRUE);
  }

}
