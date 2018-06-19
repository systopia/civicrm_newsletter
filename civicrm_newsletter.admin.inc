<?php

/**
 * Builds the configuration form.
 */
function _civicrm_newsletter_config_form($form, &$form_state) {
  module_load_include('inc', 'civicrm_newsletter', 'civicrm_newsletter.cmrf');

  $form['civicrm_newsletter_cmrf_profile'] = array(
    '#title' => t('CiviMRF profile'),
    '#type' => 'select',
    '#options' => array_map(function($profile) { return $profile['label']; }, cmrf_core_list_profiles()),
    '#default_value' => _civicrm_newsletter_get_cmrf_profile(),
  );

  return system_settings_form($form);
}
