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

namespace Drupal\civicrm_newsletter\Controller;

use Drupal;
use Drupal\civicrm_newsletter\CiviMRF;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OptIn extends ControllerBase {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF service.
   */
  protected $cmrf;

  /**
   * ConfigForm constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF service.
   */
  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * Inject dependencies.
     *
     * @var CiviMRF $cmrf
     */
    $cmrf = $container->get('civicrm_newsletter.cmrf');
    return new static(
      $cmrf
    );
  }

  /**
   * Sets the page title depending on the profile.
   *
   * @param stdClass $profile
   *   The CiviCRM Advanced Newsletter Management profile.
   *
   * @return string
   *   The form title.
   */
  public function title(stdClass $profile) {
    return $profile->form_title;
  }

  /**
   * @param AccountInterface $account
   *   The user account to check access for.
   * @param stdClass $profile
   *   The CiviCRM Advanced Newsletter Management profile.
   *
   * @return AccessResult | AccessResultReasonInterface
   */
  public function access(AccountInterface $account, stdClass $profile) {
    return AccessResult::allowedIfHasPermissions(
      $account,
      [
        'access all civicrm newsletter opt-in pages',
        'access civicrm newsletter opt-in page ' . $profile->name,
      ],
      'OR'
    );
  }

  /**
   * Build the opt-in page
   *
   * @param stdClass $profile
   *   The Advanced Newsletter Management profile.
   * @param null $contact_checksum
   *   The CiviCRM Contact checksum identifying the newsletter subscriber.
   */
  public function buildPage(stdClass $profile = NULL, $contact_checksum = NULL) {
    $config = Drupal::config('civicrm_newsletter.settings');
    $page = [];
    $messages = [];

    // Retrieve subscription status for contact.
    if (!$subscription = $this->cmrf->subscriptionGet($profile->name, $contact_checksum)) {
      Drupal::messenger()->addWarning(
        $this->t('Could not retrieve the newsletter subscription status. Please request a new confirmation link.')
      );
      return $this->redirect(
        'civicrm_newsletter.request_link_form',
        ['profile' => $profile->name]
      );
    }
    else {
      // Automatically confirm pending subscriptions.
      $result = $this->cmrf->subscriptionAutoconfirm([
        'contact_id' => $subscription['contact']['id'],
        'contact_checksum' => $subscription['contact']['checksum'],
        'profile' => $profile->name,
      ]);
      if (!empty($result['is_error'])) {
        // The API call returned an error, rebuild the form and notify the user.
        Drupal::messenger()->addError(
          $this->t('Your confirmation of pending subscriptions could not be submitted, please try again later.')
        );
      }
      elseif (!empty($result['values'])) {
        $messages[] = [
          'status' => Drupal::messenger()::TYPE_STATUS,
          'message' => $this->t('Your confirmation of pending subscriptions has been successfully submitted. You will receive an e-mail with a summary of your subscriptions.'),
        ];
      }
      else {
        $messages[] = [
          'status' => Drupal::messenger()::TYPE_STATUS,
          'message' => $this->t('Your confirmation of pending subscriptions has been successfully submitted, but no subscriptions were pending to be confirmed.'),
        ];
      }
    }

    // Redirect to target from configuration.
    if (!empty($redirect_path = $config->get('redirect_paths.optin_page'))) {
      /* @var Url $url */
      $url = Drupal::service('path.validator')
        ->getUrlIfValid($redirect_path);
      $page = $this->redirect(
        $url->getRouteName(),
        $url->getRouteParameters(),
        $url->getOptions()
      );
    }
    if (!is_a($page, RedirectResponse::class) || !$config->get('redirect_disable_messages')) {
      foreach ($messages as $message) {
        switch ($message['status']) {
          case Drupal::messenger()::TYPE_STATUS:
            Drupal::messenger()->addStatus($message['message']);
            break;
        }
      }
    }

    return $page;
  }

}
