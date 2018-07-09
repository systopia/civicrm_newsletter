(function ($) {
  Drupal.behaviors.civicrm_newsletter = {
    attach: function (context, settings) {
      var $mailingLists = $('.form-type-checkboxes.form-item-mailing-lists', context);
      var $checkboxes = $mailingLists.find('.form-type-checkbox').find('input[type="checkbox"]');
      var updateToggleLabel = function($mailingLists) {
        var $checkboxes = $mailingLists.find('.form-type-checkbox').find('input[type="checkbox"]');
        $mailingLists.find('.toggle-all').text(($checkboxes.length === $checkboxes.filter('[checked]').length ? Drupal.t('Deselect all') : Drupal.t('Select all')));
      };
      $mailingLists.children('.form-checkboxes').prepend(
        $('<a>')
          .attr('href', '#')
          .addClass('toggle-all')
          .text(($checkboxes.length === $checkboxes.filter('[checked]').length ? Drupal.t('Deselect all') : Drupal.t('Select all')))
          .click(function(event) {
            var $checkboxes = $(this).siblings().find('input[type="checkbox"]');
            if ($checkboxes.length === $checkboxes.filter('[checked]').length) {
              $(this).siblings().find('input[type="checkbox"]').removeAttr('checked');
            }
            else {
              $(this).siblings().find('input[type="checkbox"]').attr('checked', 'checked');
            }
            updateToggleLabel($mailingLists);
            event.preventDefault();
          })
      );
      $checkboxes.click(function() {
        updateToggleLabel($mailingLists);
      });
      updateToggleLabel($mailingLists);
    }
  };
})(jQuery);
