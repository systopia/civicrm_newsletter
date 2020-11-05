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

use Drupal\civicrm_newsletter\CiviMRF;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
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

  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement.

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement.
  }

  /**
   * @param AccountInterface $account
   *   The user account to check access for.
   * @param string $profile_name
   *   The profile name.
   *
   * @return AccessResult | AccessResultReasonInterface
   */
  public function access(AccountInterface $account, $profile_name) {
    return AccessResult::allowedIfHasPermissions(
      $account,
      [
        'access civicrm all newsletter preferences forms',
        'access civicrm newsletter preferences form ' . $profile_name,
      ],
      'OR'
    );
  }
}
