/**
 * @file
 * Custom function for our custom theme. Obvious.
 */

(function ($, Drupal) {
  'use strict';

  const burgerMenuSelector = '.menu-burger';
  const burgerContentSelector = '[data-menu-burger="true"]';
  
  /**
   * Attaches the mobile burger menu behavior to our header.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the behaviors.
   */
  Drupal.behaviors.pyramidMenuBurger = {
    attach(context) {
      // Act on our trigger element.
      const $target = $(context)
        .find(burgerContentSelector);
      const $trigger = $(context)
        .find(burgerMenuSelector)
        .once('menu-burger-trigger');

      if ($trigger.length && $target.length) {
        $trigger.on('click', function () {
          $target.toggleClass('is-hidden-mobile');
        });
      }
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        $(context)
          .find(burgerMenuSelector)
          .removeOnce('menu-burger-trigger')
      }
    },
  }

}(jQuery, Drupal))
