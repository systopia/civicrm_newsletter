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
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\PathElement;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cmrf_core;

class ConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'civicrm_newsletter.settings';

  /**
   * @var cmrf_core\Core $cmrf_core
   */
  public $cmrf_core;

  /**
   * ConfigForm constructor.
   *
   * @param cmrf_core\Core $cmrf_core
   */
  public function __construct(cmrf_core\Core $cmrf_core) {
    $this->cmrf_core = $cmrf_core;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * Inject dependencies.
     *
     * @var cmrf_core\Core $cmrf_core
     */
    $cmrf_core = $container->get('cmrf_core.core');
    return new static(
      $cmrf_core
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_newsletter_config_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config(static::SETTINGS);

    $form['cmrf_connector'] = [
      '#type' => 'select',
      '#title' => $this->t('CiviMRF Connector'),
      '#description' => $this->t('The CiviMRF connector to use for connecting to CiviCRM. @cmrf_connectors_link',
        [
          '@cmrf_connectors_link' => Link::fromTextAndUrl(
            $this->t('Configure CiviMRF Connectors'),
            Url::fromRoute('entity.cmrf_connector.collection')
          )->toString(),
        ]),
      '#options' => $this->cmrf_core->getConnectors(),
      '#default_value' => $config->get('cmrf_connector'),
      '#required' => TRUE,
    ];

    $form['preferences_autoconfirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Confirm subscriptions in preferences form'),
      '#description' => $this->t('Whether to automatically confirm pending subscriptions when loading the preferences form. When this is disabled, subscriptions can only be confirmed using the opt-in URL.'),
      '#default_value' => $config->get('preferences_autoconfirm'),
    ];

    $settings_definition = Drupal::service('config.typed')
      ->getDefinition(static::SETTINGS);
    $form['redirect_paths'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Redirect paths'),
      '#description' => $this->t('You may define paths to redirect to after successful submissions of the respective forms.'),
      '#open' => !empty($config->get('redirect_paths')),
    ];
    foreach ($settings_definition['mapping']['redirect_paths']['mapping'] as $redirect_path => $definition) {
      $form['redirect_paths'][$redirect_path] = [
        '#type' => 'path',
        '#title' => $definition['label'],
        '#default_value' => $config->get('redirect_paths.' . $redirect_path),
        '#convert_path' => PathElement::CONVERT_NONE,
      ];
    }
    $form['redirect_disable_messages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable status messages when redirecting'),
      '#description' => $this->t('Whether to not show status messages when redirecting. This may be useful when you want full control over what to display to the user on a separate page.'),
      '#default_value' => $config->get('redirect_disable_messages'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings_definition = Drupal::service('config.typed')
      ->getDefinition(static::SETTINGS);
    $configFactory = $this->configFactory()->getEditable(static::SETTINGS);

    $configFactory->set('cmrf_connector', $form_state->getValue('cmrf_connector'));
    $configFactory->set('preferences_autoconfirm', $form_state->getValue('preferences_autoconfirm'));
    foreach (array_keys($settings_definition['mapping']['redirect_paths']['mapping']) as $redirect_path) {
      if (!empty($value = $form_state->getValue(['redirect_paths', $redirect_path]))) {
        $configFactory->set('redirect_paths.' . $redirect_path, $value);
      }
      else {
        $configFactory->clear('redirect_paths.' . $redirect_path);
      }
    }
    $configFactory->set('redirect_disable_messages', $form_state->getValue('redirect_disable_messages'));

    $configFactory->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

}
