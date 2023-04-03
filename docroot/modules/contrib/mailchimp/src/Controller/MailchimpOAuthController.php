<?php

namespace Drupal\mailchimp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for processing access token from OAuth middleware server.
 */
class MailchimpOAuthController extends ControllerBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->stateService = $container->get('state');
    return $instance;
  }

  /**
   * Request access token from middleware server.
   *
   * @param string $temp_token
   *   The temporary token that was issued from the initial request.
   */
  public function getAccessToken(string $temp_token) {
    $post_params = [
      'form_params' => [
        'type' => 'access_token',
        'temp_token' => $temp_token,
      ],
    ];
    $client = $this->client();
    $url = Url::fromRoute('mailchimp.admin.oauth');

    try {
      $middleware_url = $this->config('mailchimp.settings')->get('oauth_middleware_url');
      $response = $client->request('POST', $middleware_url . '/access-token', $post_params);
      // Check for response with access_token, and store in database.
      if ($response->getStatusCode() == '200') {
        // Check for a token in the response.
        $json = $response->getBody()->getContents();
        $message = json_decode($json, TRUE);

        if (isset($message['access_token'])) {
          $access_token = $message['access_token'];
          $data_center = $message['data_center'];
          // Save authentication values to state.
          $this->stateService->set('mailchimp_access_token', $access_token);
          $this->stateService->set('mailchimp_data_center', $data_center);
          return new RedirectResponse($url->toString());
        }
      }
    }
    catch (\GuzzleHttp\Exception\GuzzleException $e) {
      $this->messenger()->addError($e->getMessage());
    }

    return new RedirectResponse($url->toString());

  }

  /**
   * Initialize a new Guzzle client.
   *
   * @return Client
   *   Guzzle client.
   */
  protected function client() {
    // Intialize Guzzle Client.
    return new Client([
      'base_uri' => '',
      'timeout'  => 100.0,
    ]);
  }


}
