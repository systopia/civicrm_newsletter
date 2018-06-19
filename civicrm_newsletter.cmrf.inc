<?php

/**
 * Retrieves the system name of the CiviMRF profile to use for CiviCRM Advanced
 * Newsletter Management.
 *
 * @return string
 *   The system name of the CiviMRF profile.
 */
function _civicrm_newsletter_get_cmrf_profile() {
  return variable_get('civicrm_newsletter_cmrf_profile', cmrf_core_default_profile()['name']);
}

/**
 * TODO
 *
 * @param null $profile
 *
 * @return null|string
 */
function _civicrm_newsletter_get_connector_id($profile = NULL) {
  $connector_name = 'civicrm_newsletter_connector';
  if (!empty($profile)) {
    $connector_name .= ':' . $profile;
  }

  $core = cmrf_core_get_core();
  $connector_id = variable_get($connector_name);
  if (empty($connector_id)) {
    $connector_id = $core->registerConnector($connector_name, $profile);
    variable_set($connector_name, $connector_id);
  }
  return $connector_id;
}

/**
 * TODO
 *
 * @param $entity
 * @param $action
 * @param $parameters
 * @param $options
 * @param null $profile
 *
 * @return \CMRF\Core\Call
 */
function _civicrm_newsletter_send_call($entity, $action, $parameters, $options, $profile = NULL) {
  $connector_id = _civicrm_newsletter_get_connector_id($profile);
  $core = cmrf_core_get_core();
  $call = $core->createCall($connector_id, $entity, $action, $parameters, $options);
  $core->executeCall($call);
  return $call;
}
