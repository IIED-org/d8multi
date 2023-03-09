<?php

namespace Drupal\mailchimp\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Authentication AJAX command.
 */
class MailchimpAuthenticationCommand implements CommandInterface {

  /**
   * URL to use for authentication.
   *
   * @var string
   */
  protected $url;

  /**
   * Temporary token issued from Mailchimp server.
   *
   * @var string
   */
  protected $temp_token;

  /**
   * The domain being authenticated.
   *
   * @var string
   */
  protected $domain;


  /**
   * Authtentication command constructor.
   *
   * @param string $url
   *   URL to use for authentication.
   * @param string $temp_token
   *   Temporary token to use in authentication process.
   * @param string $domain
   *   User ID to use in authentication process.
  */
  public function __construct($url, $temp_token, $domain) {
    $this->url = $url;
    $this->temp_token = $temp_token;
    $this->domain = $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'authentication',
      'url' => $this->url,
      'temp_token' => $this->temp_token,
      'domain' => $this->domain,
    ];
  }

}
