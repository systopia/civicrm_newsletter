<?php
declare(strict_types = 1);

namespace Drupal\civicrm_newsletter\Form\Callbacks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Render\Element\PathElement;
use Drupal\Core\Render\Element\Url;

final class PathOrUrlCallbacks {

  public static function validate(array &$element, FormStateInterface $formState, array &$form): void {
    if (is_string($element['#value'] ?? NULL) && preg_match('~^[[:alnum:]]+://~', ltrim($element['#value']))) {
      Url::validateUrl($element, $formState, $form);
    } else {
      $element['#validate_path'] = TRUE;
      $element['#convert_path'] = PathElement::CONVERT_NONE;
      PathElement::validateMatchedPath($element, $formState, $form);
    }
  }

}
