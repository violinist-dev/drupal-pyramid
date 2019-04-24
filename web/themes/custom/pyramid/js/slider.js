/**
 * @file
 * Custom function for our custom theme. Obvious.
 */

(function ($, Drupal) {
  'use strict';

  const sliderSelector = '.slider';
  const sliderOnceName = 'slider-trigger';

  /**
   * Attaches slider behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the behaviors.
   */
  Drupal.behaviors.sliderInit = {
    attach(context) {
      // Act on our trigger element.
      const $trigger = $(context)
        .find(sliderSelector)
        .once(sliderOnceName);

      // Slider configuration.
      const options = {
        dots: true,
        arrows: false,
        infinite: true,
        autoplay: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        lazyLoad: 'ondemand'
      };

      if ($trigger.length) {
        $(document).ready(function(){
          $trigger.slick(options);
        });
      }
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        $(context)
          .find(sliderSelector)
          .removeOnce(sliderOnceName)
      }
    },
  }

}(jQuery, Drupal))
