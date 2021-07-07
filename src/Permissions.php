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

namespace Drupal\civicrm_newsletter;


use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Permissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

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
   * Get permissions for each profile.
   *
   * @return array
   *   The permissions array.
   */
  public function permissions() {
    $permissions = &drupal_static(__FUNCTION__);
    if (!isset($permissions)) {
      $permissions = [];

      foreach ($this->cmrf->profileGet() as $profile_name => $profile) {
        $permissions['access civicrm newsletter subscription form ' . $profile_name] = [
          'title' => t(
            'Access newsletter subscription form with profile %profile_name',
            ['%profile_name' => $profile_name]
          ),
          'description' => t(
            'Allow users to access the public newsletter subscription form with profile %profile_name.',
            ['%profile_name' =>$profile_name]
          ),
        ];
        $permissions['access civicrm newsletter preferences form ' . $profile_name] = [
          'title' => t(
            'Access newsletter preferences form with profile %profile_name',
            ['%profile_name' => $profile_name]
          ),
          'description' => t(
            'Allow users to access the newsletter preferences form with profile %profile_name.',
            ['%profile_name' => $profile_name]
          ),
        ];
        $permissions['access civicrm newsletter request form ' . $profile_name] = [
          'title' => t(
            'Access newsletter request form with profile %profile_name',
            ['%profile_name' => $profile_name]
          ),
          'description' => t(
            'Allow users to access the request link form for profile %profile_name.',
            ['%profile_name' => $profile_name]
          ),
        ];
      }
    }

    return $permissions;
  }
}
