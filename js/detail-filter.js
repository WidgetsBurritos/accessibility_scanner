/**
 * @file
 * Provides some very basic filtering functionality for achecker results.
 */

(function ($, Drupal) {
  Drupal.behaviors.accessibilityScannerDetailFilter = {
    attach: function attach(context) {
      var $filters = $('#achecker-filter');
      $filters.once('accessibilityScannerDetailFilter').each(function () {
        $(this).change(function () {
          var suffix = $(this).val();
          var $visible;
          if (~~suffix.length) {
            $('.achecker-row').not('.achecker-row-' + suffix).hide();
            $visible = $('.achecker-row-' + suffix);
          }
          else {
            $visible = $('.achecker-row');
          }
          $visible.show();
          $('.achecker-row')
            .filter(':visible:even')
            .addClass('achecker-row-odd')
            .removeClass('achecker-row-even');
          $('.achecker-row')
            .filter(':visible:odd')
            .addClass('achecker-row-even')
            .removeClass('achecker-row-odd');
        });
      });
    }
  };
})(jQuery, Drupal);
