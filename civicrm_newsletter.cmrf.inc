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
 * Retrieve or build a CiviMRF connector.
 *
 * @param string | NULL $profile
 *   The CiviMRF profile name, or NULL to use the default profile.
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
 * Send a CiviCRM API call using CiviMRF.
 *
 * @param string $entity
 *   The CiviCRM API entity.
 * @param string $action
 *   The CiviCRM API action.
 * @param array $parameters
 *   The CiviCRM API paramters.
 * @param array $options
 *   The CiviCRM API options.
 * @param string | NULL $cmrf_profile
 *   The name of the CiviMRF profile, or NULL to use the default profile.
 *
 * @return \CMRF\Core\Call
 */
function _civicrm_newsletter_send_call($entity, $action, $parameters = array(), $options = array(), $cmrf_profile = NULL) {
  $connector_id = _civicrm_newsletter_get_connector_id($cmrf_profile);
  $core = cmrf_core_get_core();
  $call = $core->createCall($connector_id, $entity, $action, $parameters, $options);
  $core->executeCall($call);
  return $call;
}

/**
 * Retrieves the configuration for an Advanced Newsletter Management profile
 * from CiviCRM.
 *
 * @param string $profile_name
 *   The name of the Advanced Newsletter Management profile.
 *
 * @return array | NULL
 *   The Advanced Newsletter Management profile configuration, or NULL if the
 *   profile could not be retrieved.
 */
function _civicrm_newsletter_profile_get($profile_name) {
  $call = _civicrm_newsletter_send_call(
    'NewsletterProfile',
    'get',
    array('name' => $profile_name),
    array(),
    _civicrm_newsletter_get_cmrf_profile()
  );
  $reply = $call->getReply();
  if ($reply['is_error'] == 1) {
    $profile = NULL;
  }
  else {
    $profile = reset($reply['values']);
  }

  return $profile;
}

/**
 * Retrieves the subscription status for a given contact hash.
 *
 * @param $contact_hash
 *
 * @return array | NULL
 *   The subscription status for the mailing lists defined within the Advanced
 *   Newsletter Management profile, or NULL if the subscription status could not
 *   be retrieved.
 */
function civicrm_newsletter_subscription_get($contact_hash) {
  $call = _civicrm_newsletter_send_call(
    'NewsletterSubscription',
    'get',
    array('contact_hash' => $contact_hash),
    array(),
    _civicrm_newsletter_get_cmrf_profile()
  );
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
 * @param $params
 *   The CiviCRM API call parameters.
 *
 * @return array
 *   The CiviCRM API call reply.
 */
function _civicrm_newsletter_subscription_submit($params) {
  $call = _civicrm_newsletter_send_call(
    'NewsletterSubscription',
    'submit',
    $params,
    array(),
    _civicrm_newsletter_get_cmrf_profile()
  );
  $reply = $call->getReply();

  return $reply;
}

/**
 * Submits an Advanced Newsletter Management subscription confirmation to
 * CiviCRM.
 *
 * @param $params
 *   The CiviCRM API call parameters.
 *
 * @return array
 *   The CiviCRM API call reply.
 */
function _civicrm_newsletter_subscription_confirm($params) {
  $call = _civicrm_newsletter_send_call(
    'NewsletterSubscription',
    'confirm',
    $params,
    array(),
    _civicrm_newsletter_get_cmrf_profile()
  );
  $reply = $call->getReply();

  return $reply;
}

/**
 * Requests an Advanced Newsletter Management subscription confirmation link to
 * be sent by CiviCRM.
 *
 * @param $params
 *   The CiviCRM API call parameters.
 *
 * @return array
 *   The CiviCRM API call reply.
 */
function _civicrm_newsletter_subscription_request($params) {
  $call = _civicrm_newsletter_send_call(
    'NewsletterSubscription',
    'request',
    $params,
    array(),
    _civicrm_newsletter_get_cmrf_profile()
  );
  $reply = $call->getReply();

  return $reply;
}
