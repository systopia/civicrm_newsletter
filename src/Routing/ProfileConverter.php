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

namespace Drupal\civicrm_newsletter\Routing;


use Drupal\civicrm_newsletter\CiviMRF;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ProfileConverter implements ParamConverterInterface {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF service.
   */
  protected $cmrf;

  /**
   * ProfileConverter constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF service
   */
  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;
  }

  /**
   * @inheritDoc
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      return (object) $this->cmrf->profileGetSingle($value);
    } catch (Exception $exception) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'civicrm_newsletter_profile';
  }
}
