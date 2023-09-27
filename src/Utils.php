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


use Drupal;

class Utils {

  /**
   * Returns a mapping of CiviCRM HTML types to Drupal Form API types.
   *
   * @return array
   */
  public static function contactFieldTypes() {
    return array(
      'Select' => 'select',
      'Multi-Select' => 'checkboxes',
      'Text' => 'textfield',
      'Textarea' => 'textarea',
      'CheckBox' => 'checkbox',
      'Radio' => 'radio',
    );
  }

  public static function getContactFieldType($field) {
    $type = self::contactFieldTypes()[$field['type']];
    if ($type == 'checkbox' && isset($field['options'])) {
      $type = 'checkboxes';
    }
    if ($type == 'radio' && isset($field['options'])) {
      $type = 'radios';
    }
     if ($type == 'radio' && !isset($field['options'])) {
       $type = 'checkbox';
     }
    return $type;
  }

  /**
   * Transforms a mailing lists tree from a retrieved profile configuration into
   * nested fieldset elements with checkboxes.
   *
   * @param array $tree
   *
   * @param array $default_values
   *
   * @return array
   */
  public static function mailingListsTreeCheckboxes($tree, $default_values = array()) {
    $element = [];
    // Add an extra level of fieldsets for distinguishing between parent and
    // child groups.
    if (Drupal::config('civicrm_newsletter.settings')->get('parent_groups_selectable')) {
      $element['#type'] = 'fieldset';
    }

    foreach ($tree as $group_id => $group_definition) {
      $checkbox = array(
        '#type' => 'checkbox',
        '#title' => $group_definition['title'],
        '#attributes' => array(
          'data-civicrm-group-name' => $group_definition['name'],
        ),
        '#description' => $group_definition['description'],
        '#default_value' => (array_key_exists($group_id, $default_values)) ? 1 : 0,
      );
      if (!empty($group_definition['children'])) {
        $element[$group_id . '_fieldset'] = array(
          '#type' => 'fieldset',
          '#title' => $group_definition['title'],
          '#description' => $group_definition['description'],
        );

        if (Drupal::config('civicrm_newsletter.settings')->get('parent_groups_selectable')) {
          // Checkbox for the group itself.
          $element[$group_id . '_fieldset']['mailing_lists_' . $group_id] = $checkbox;
        }

        // Nested fieldset with checkboxes.
        $element[$group_id . '_fieldset']['children'] = self::mailingListsTreeCheckboxes($group_definition['children'], $default_values);
      }
      else {
        $element['mailing_lists_' . $group_id] = $checkbox;
        // Move groups without children to the top of the fieldset.
        // TODO: Instead, increase weight of groups with children for keeping
        //       the implicit ordering of groups within each level of hierarchy.
        $element['mailing_lists_' . $group_id]['#weight'] = -1;
      }
    }

    return $element;
  }

}
