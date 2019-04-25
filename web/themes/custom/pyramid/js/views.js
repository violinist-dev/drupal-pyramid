/**
 * @file
 * Custom function for our custom views.
 */

(function ($, Drupal) {
  'use strict';

  const formSelector = '.views-exposed-form form select';
  const submitSelector = '.views-exposed-form form .form-actions .form-submit';

  /**
   * Attaches the autosubmit filter behavior to our Views.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the behaviors.
   */
  Drupal.behaviors.pyramidAutosubmitFilters = {
    attach(context) {
      // Get our submit button.
      const $trigger = $(context)
        .find(submitSelector);
      // Get our filters.
      const $filters = $(context)
        .find(formSelector)
        .once('autosubmit-filters');

      if ($trigger.length && $filters.length) {
        $filters.each(function () {
          $(this).on('change', function () {
            $trigger.click();
          });
        });
      }
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        $(context)
          .find(formSelector)
          .removeOnce('autosubmit-filters')
      }
    },
  }

}(jQuery, Drupal))
