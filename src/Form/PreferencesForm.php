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


class PreferencesForm extends FormBase {

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
    return 'civicrm_newsletter_preferences_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    stdClass $profile = NULL,
    $contact_hash = NULL
  ) {
    $config = Drupal::config('civicrm_newsletter.settings');

    // Include the Advanced Newsletter Management profile name.
    $form['profile'] = array(
      '#type' => 'value',
      '#value' => $profile->name,
    );

    // Retrieve subscription status for contact.
    if (!$subscription = $this->cmrf->subscriptionGet($profile->name, $contact_hash)) {
      Drupal::messenger()->addWarning(
        $this->t('Could not retrieve the newsletter subscription status. Please request a new confirmation link.')
      );
      return $this->redirect(
        'civicrm_newsletter.request_link_form',
        ['profile' => $profile->name]
      );
    }
    elseif ($config->get('preferences_autoconfirm')) {
      // Automatically confirm pending subscriptions.
      $result = $this->cmrf->subscriptionAutoconfirm(array(
        'contact_id' => $subscription['contact']['id'],
        'contact_hash' => $subscription['contact']['hash'],
        'profile' => $profile->name,
      ));
      if (!empty($result['is_error'])) {
        // The API call returned an error, rebuild the form and notify the user.
        Drupal::messenger()->addError(
          $this->t('Your confirmation of pending subscriptions could not be submitted, please try again later.')
        );
        $form_state->setRebuild();
      }
      elseif (!empty($result['values'])) {
        Drupal::messenger()->addStatus(
          $this->t('Your confirmation of pending subscriptions has been successfully submitted. You will receive an e-mail with a summary of your subscriptions.')
        );
      }
      else {
        Drupal::messenger()->addStatus(
          $this->t('Your confirmation of pending subscriptions has been successfully submitted, but no subscriptions were pending to be confirmed.')
        );
      }

      $form['contact_hash'] = array(
        '#type' => 'value',
        '#value' => $subscription['contact']['hash'],
      );
      $form['contact_id'] = array(
        '#type' => 'value',
        '#value' => $subscription['contact']['id'],
      );

      // Build form according to retrieved configuration:
      // Add contact fields.
      foreach ($profile->contact_fields as $contact_field_name => $contact_field) {
        if ($contact_field['active']) {
          $form[$contact_field_name] = array(
            '#type' => Utils::contactFieldTypes()[$contact_field['type']],
            '#title' => $contact_field['label'],
            '#description' => $contact_field['description'],
            '#default_value' => $subscription['contact'][$contact_field_name],
            '#required' => !empty($contact_field['required']),
            '#disabled' => TRUE,
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
      $form['mailing_lists'] = Utils::mailingListsTreeCheckboxes(
        $profile->mailing_lists_tree,
        $subscription['subscription_status']
      );
      $form['mailing_lists']['#title'] = $profile->mailing_lists_label;
      $form['mailing_lists']['#description'] = $profile->mailing_lists_description;
      $form['mailing_lists']['#attributes'] = array(
        'class' => array(
          'form-item-mailing-lists',
        ),
      );
      $form['mailing_lists']['#attached']['library'][] = 'civicrm_newsletter/civicrm_newsletter';

      if (!empty($profile->mailing_lists_unsubscribe_all)) {
        $form['unsubscribe'] = array(
          '#type' => 'fieldset',
          '#title' => $profile->mailing_lists_unsubscribe_all_label,
        );
        $form['unsubscribe']['unsubscribe_all'] = array(
          '#type' => 'checkbox',
          '#title' => $profile->mailing_lists_unsubscribe_all_submit_label,
          '#description' => $profile->mailing_lists_unsubscribe_all_description
        );
        $form['mailing_lists']['#states'] = array(
          'invisible' => array(
            ':input[name="unsubscribe_all"]' => array(
              'checked' => TRUE,
            ),
          ),
        );
      }

      // Add terms and conditions.
      if (!empty($profile->conditions_preferences)) {
        $form['conditions_preferences'] = array(
          '#type' => 'textarea',
          '#title' => $profile->conditions_preferences_label,
          '#description' => $profile->conditions_preferences_description,
          '#value' => $profile->conditions_preferences,
          '#disabled' => TRUE,
        );
      }

      // Add submit button with configured label, if given.
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $profile->submit_label ?: t('Submit'),
      );
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = Drupal::config('civicrm_newsletter.settings');

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
    $params['mailing_lists'] = array_map(function($value) {
      return ($value ? 'Added' : 'Removed');
    }, $params['mailing_lists']);

    // Submit the subscription using CiviMRF.
    $result = $this->cmrf->subscriptionConfirm($params);

    if (!empty($result['is_error'])) {
      // The API call returned an error, rebuild the form and notify the user.
      Drupal::messenger()->addError(
        $this->t('Your confirmation could not be submitted, please try again later.')
      );
      $form_state->setRebuild();
    }
    else {
      Drupal::messenger()->addStatus(
        $this->t('Your confirmation has been successfully submitted. You will receive an e-mail with a summary of your subscriptions.')
      );

      // Redirect to target from configuration.
      if (!empty($redirect_path = $config->get('redirect_paths.preferences_form'))) {
        /* @var Url $url */
        $url = Drupal::service('path.validator')
          ->getUrlIfValid($redirect_path);
        $form_state->setRedirect(
          $url->getRouteName(),
          $url->getRouteParameters(),
          $url->getOptions()
        );
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
        'access all civicrm newsletter preferences forms',
        'access civicrm newsletter preferences form ' . $profile->name,
      ],
      'OR'
    );
  }
}
