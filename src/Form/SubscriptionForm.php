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
use Drupal\Core\Url;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


class SubscriptionForm extends FormBase {

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
    return 'civicrm_newsletter_subscription_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, stdClass $profile = NULL) {
    $config = Drupal::config('civicrm_newsletter.settings');

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

    // Add mailing lists selection.
    if ($config->get('single_group_hide') && 1 === count($profile->mailing_lists)) {
      // Show no selection if there's only one mailing list.
      $mailing_list_id = key($profile->mailing_lists);
      $form['mailing_lists_' . $mailing_list_id] = [
        '#type' => 'value',
        '#value' => 1,
      ];
    }
    else {
      $form['mailing_lists'] = Utils::mailingListsTreeCheckboxes($profile->mailing_lists_tree);
      $form['mailing_lists']['#title'] = $profile->mailing_lists_label;
      $form['mailing_lists']['#description'] = $profile->mailing_lists_description;
      $form['mailing_lists']['#attributes'] = array(
        'class' => array(
          'form-item-mailing-lists',
        ),
      );
      $form['mailing_lists']['#attached']['library'][] = 'civicrm_newsletter/civicrm_newsletter';
    }

    // Add terms and conditions.
    if (!empty($profile->conditions_public)) {
      $form['conditions_public'] = array(
        '#type' => 'textarea',
        '#title' => $profile->conditions_public_label,
        '#description' => $profile->conditions_public_description,
        '#value' => $profile->conditions_public,
        '#disabled' => TRUE,
      );
    }

    // Add submit button with configured label, if given.
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $profile->submit_label ?: t('Submit'),
    );

    // Support Honeypot module, see
    // https://www.drupal.org/project/honeypot.
    try {
      Drupal::service('honeypot')
        ->addFormProtection($form, $form_state, [
          'honeypot',
          'time_restriction',
        ]);
    }
    catch (ServiceNotFoundException $exception) {
      // Nothing to do if the service does not exist.
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Make sure at least one mailing list is selected.
    // Build mailing_lists array.
    $mailing_lists = [];
    foreach ($form_state->getValues() as $name => $value) {
      if (strpos($name, 'mailing_lists_') === 0) {
        $mailing_lists[explode('mailing_lists_', $name)[1]] = $value;
      }
    }
    // Remove unchecked checkbox values (those that are 0).
    $mailing_lists = array_keys(array_filter($mailing_lists));
    if (empty($mailing_lists)) {
      $form_state->setError(
        $form['mailing_lists'],
        $this->t('Please select at least one mailing list to subscribe to.')
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = Drupal::config('civicrm_newsletter.settings');
    $messages = [];

    // Clean the submitted values from Drupal Form API stuff.
    $params = clone $form_state;
    $params->cleanValues();
    $params = $params->getValues();

    // Build mailing_lists array.
    foreach ($params as $name => $value) {
      if (strpos($name, 'mailing_lists_') === 0) {
        $params['mailing_lists'][explode('mailing_lists_', $name)[1]] = $value;
        unset($params[$name]);
      }
    }
    // Remove unchecked checkbox values (those that are 0).
    $params['mailing_lists'] = array_keys(array_filter($params['mailing_lists']));

    // Submit the subscription using CiviMRF.
    $result = $this->cmrf->subscriptionSubmit($params);

    if (!empty($result['is_error'])) {
      // The API call returned an error, rebuild the form and notify the user.
      Drupal::messenger()->addError(
        $this->t('Your subscription could not be submitted, please try again later.')
      );
      $form_state->setRebuild();
    }
    else {
      $messages[] = [
        'status' => Drupal::messenger()::TYPE_STATUS,
        'message' => $this->t('Your subscription has been successfully submitted. You will receive an e-mail with a link to a confirmation page. Your subscription will not be active until you confirm it.'),
      ];
      // Redirect to target from configuration.
      if (!empty($redirect_path = $config->get('redirect_paths.subscription_form'))) {
        /* @var Url $url */
        $url = Drupal::service('path.validator')
          ->getUrlIfValid($redirect_path);
        $form_state->setRedirect(
          $url->getRouteName(),
          $url->getRouteParameters(),
          $url->getOptions()
        );
      }
      if (!$form_state->getRedirect() || !$config->get('redirect_disable_messages')) {
        foreach ($messages as $message) {
          switch ($message['status']) {
            case Drupal::messenger()::TYPE_STATUS:
              Drupal::messenger()->addStatus($message['message']);
              break;
          }
        }
      }
    }
  }

  /**
   * Sets the form title depending on the profile.
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
        'access all civicrm newsletter subscription forms',
        'access civicrm newsletter subscription form ' . $profile->name,
      ],
      'OR'
    );
  }
}
