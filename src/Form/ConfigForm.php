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

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('cmrf_connector', $form_state->getValue('cmrf_connector'))
      ->save();

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
