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

namespace Drupal\civicrm_newsletter\Form;

use Drupal;
use Drupal\civicrm_newsletter\CiviMRF;
use Drupal\civicrm_newsletter\Utils;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

# For Admin:
# use Drupal\Core\Form\ConfigFormBase;

class RequestLinkForm extends FormBase {

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
   * @inheritDoc
   */
  public function getFormID() {
    return 'civicrm_newsletter_request_link_form';
  }

  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    stdClass $profile = NULL,
    $contact_hash = NULL
  ) {
    // If a hash is given, submit to the request API action without showing a form.
    if ($contact_hash) {
      if (!$subscription = $this->cmrf->subscriptionGet($profile->name, $contact_hash)) {
        Drupal::messenger()->addWarning(
          $this->t('Could not retrieve the newsletter subscription status. Please request a new confirmation link.')
        );
        return $this->redirect(
          'civicrm_newsletter.request_link_form',
          ['profile' => $profile->name]
        );
      }

      $params = array(
        'profile' => $profile->name,
        'contact_hash' => $subscription['contact']['hash'],
        'contact_id' => $subscription['contact']['id'],
      );
      $result = $this->cmrf->subscriptionRequest($params);

      if (!empty($result['is_error'])) {
        // The API call returned an error, rebuild the form and notify the user.
        Drupal::messenger()->addError(
          $this->t('Your request could not be submitted, please try again later.')
        );
        $form_state->setRebuild();
      }
      else {
        Drupal::messenger()->addStatus(
          $this->t('Your request has been successfully submitted. You will receive an e-mail with a link to a confirmation page.')
        );
      }
    }
    else {
      // Include the Advanced Newsletter Management profile name.
      $form['profile'] = array(
        '#type' => 'value',
        '#value' => $profile->name,
      );

      // Build form according to received configuration:
      // Add contact fields.
      foreach ($profile->contact_fields as $contact_field_name => $contact_field) {
        if ($contact_field['active']) {
          $form[$contact_field_name] = array(
            '#type' => Utils::contactFieldTypes()[$contact_field['type']],
            '#title' => $contact_field['label'],
            '#description' => $contact_field['description'],
            '#required' => !empty($contact_field['required']),
          );
          if (!empty($contact_field['options'])) {
            $form[$contact_field_name]['#options'] = $contact_field['options'];
            if (empty($contact_field['required'])) {
              $form[$contact_field_name]['#empty_option'] = t('- None -');
            }
          }
        }
      }

      // Add submit button with configured label, if given.
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clean the submitted values from Drupal Form API stuff.
    $params = clone $form_state;
    $params->cleanValues();
    $params = $params->getValues();

    // Submit the subscription using CiviMRF.
    $result = $this->cmrf->subscriptionRequest($params);

    if (!empty($result['is_error'])) {
      // The API call returned an error, rebuild the form and notify the user.
      Drupal::messenger()->addError(
        $this->t('Your request could not be submitted, please try again later.')
      );
      $form_state->setRebuild();
    }
    else {
      Drupal::messenger()->addStatus(
        $this->t('Your request has been successfully submitted. You will receive an e-mail with a link to a confirmation page.')
      );
    }
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
        'access all civicrm newsletter request forms',
        'access civicrm newsletter request form ' . $profile->name,
      ],
      'OR'
    );
  }
}
