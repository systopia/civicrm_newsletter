<?php
/*------------------------------------------------------------+
| CiviCRM Advanced Newsletter Management                      |
| Copyright (C) 2020 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

namespace Drupal\civicrm_newsletter;

use Drupal;
use Drupal\cmrf_core\Core;
use Drupal\Core\Utility\Error;
use Exception;

/**
 * Class CiviMRF
 *
 * @package Drupal\civicrm_newsletter
 */
class CiviMRF {

  /**
   * @var Core $core
   *   The CiviMRF core service.
   */
  public $core;

  /**
   * CiviMRF constructor.
   *
   * @param Core $core
   *   The CiviMRF core service.
   */
  public function __construct(Core $core) {
    $this->core = $core;
  }

  /**
   * Retrieves the CiviMRF connector configured to use for the module.
   *
   * @return string | NULL
   */
  protected function connector() {
    return Drupal::config('civicrm_newsletter.settings')->get('cmrf_connector');
  }

  /**
   * Retrieves the configuration for an Advanced Newsletter Management profile
   * from CiviCRM.
   *
   * @param string | NULL $profile_name
   *   The name of the Advanced Newsletter Management profile to retrieve, or
   *   NULL to retrieve all configured Advanced Newsletter Management profiles.
   *
   * @return array | NULL
   *   The Advanced Newsletter Management profile configuration, or NULL if the
   *   profile could not be retrieved.
   */
  public function profileGetSingle($profile_name = NULL) {
    $params = array();
    if ($profile_name) {
      $params['name'] = $profile_name;
    }
    try {
      $call = $this->core->createCall(
        $this->connector(),
        'NewsletterProfile',
        'getsingle',
        $params,
        []
      );
      $this->core->executeCall($call);
      $reply = $call->getReply();
      if ($reply['is_error'] == 1) {
        $return = NULL;
      }
      elseif ($profile_name) {
        $return = reset($reply['values']) + ['name' => $profile_name];
      }
      else {
        $return = $reply['values'];
      }
    }
    catch (Exception $exception) {
      $variables = Error::decodeException($exception);
      Drupal::logger('civicrm_newsletter')->error(
        '%type: @message in %function (line %line of %file).',
        $variables
      );
      $return = [];
    }

    return $return;
  }

  /**
   * Retrieves the configuration for all Advanced Newsletter Management profiles
   * from CiviCRM.
   *
   * @return array
   *   An array of Advanced Newsletter Management profile configurations.
   */
  public function profileGet() {
    $params = array();
    try {
      $call = $this->core->createCall(
        $this->connector(),
        'NewsletterProfile',
        'get',
        $params,
        []
      );
      $this->core->executeCall($call);
      $reply = $call->getReply();
      if ($reply['is_error'] == 1) {
        $return = [];
      }
      else {
        $return = $reply['values'];
      }
    }
    catch (Exception $exception) {
      $variables = Error::decodeException($exception);
      Drupal::logger('civicrm_newsletter')->error(
        '%type: @message in %function (line %line of %file).',
        $variables
      );
      $return = [];
    }

    return $return;
  }

  /**
   * Retrieves the subscription status for a given contact checksum.
   *
   * @param string | NULL $profile_name
   *   The name of the Advanced Newsletter Management profile to retrieve, or NULL
   *   to retrieve all configured Advanced Newsletter Management profiles.
   *
   * @param string $contact_checksum
   *
   * @return array | NULL
   *   The subscription status for the mailing lists defined within the Advanced
   *   Newsletter Management profile, or NULL if the subscription status could not
   *   be retrieved.
   */
  public function subscriptionGet($profile_name, $contact_checksum) {
    $call = $call = $this->core->createCall(
      $this->connector(),
      'NewsletterSubscription',
      'get',
      [
        'profile' => $profile_name,
        'contact_checksum' => $contact_checksum
      ],
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    if (!empty($reply['is_error'])) {
      $subscription = NULL;
    }
    else {
      $subscription = reset($reply['values']);
    }

    return $subscription;
  }

  /**
   * Submits an Advanced Newsletter Management subscription to CiviCRM.
   *
   * @param array $params
   *   The CiviCRM API call parameters.
   *
   * @return array
   *   The CiviCRM API call reply.
   */
  public function subscriptionSubmit(array $params) {
    $call = $call = $this->core->createCall(
      $this->connector(),
      'NewsletterSubscription',
      'submit',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();

    return $reply;
  }

  /**
   * Submits an Advanced Newsletter Management subscription confirmation to
   * CiviCRM.
   *
   * @param array $params
   *   The CiviCRM API call parameters.
   *
   * @return array
   *   The CiviCRM API call reply.
   */
  public function subscriptionConfirm(array $params) {
    $call = $call = $this->core->createCall(
      $this->connector(),
      'NewsletterSubscription',
      'confirm',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();

    return $reply;
  }

  /**
   * Submits an Advanced Newsletter Management subscription auto confirmation to
   * CiviCRM
   *
   * @param array $params
   *   The CiviCRM API call parameters.
   *
   * @return array
   *   The CiviCRM API call reply.
   */
  public function subscriptionAutoconfirm(array $params) {
    $params['autoconfirm'] = 1;
    $call = $call = $this->core->createCall(
      $this->connector(),
      'NewsletterSubscription',
      'confirm',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();

    return $reply;
  }

  /**
   * Requests an Advanced Newsletter Management subscription confirmation link to
   * be sent by CiviCRM.
   *
   * @param array $params
   *   The CiviCRM API call parameters.
   *
   * @return array
   *   The CiviCRM API call reply.
   */
  public function subscriptionRequest(array $params) {
    $call = $call = $this->core->createCall(
      $this->connector(),
      'NewsletterSubscription',
      'request',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();

    return $reply;
  }

}
