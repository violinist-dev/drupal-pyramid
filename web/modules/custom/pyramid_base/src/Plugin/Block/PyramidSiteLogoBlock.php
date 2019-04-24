<?php

namespace Drupal\pyramid_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block to display the Site name.
 *
 * @Block(
 *   id = "pyramid_sitelogo_block",
 *   admin_label = @Translation("Pyramid - Site logo"),
 * )
 */
class PyramidSiteLogoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'site_slogan' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['site_slogan'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Site slogan'),
      '#default_value' => $this->configuration['site_slogan'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['site_slogan'] = $form_state->getValue('site_slogan');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['site_logo'] = [
      '#theme' => 'image',
      '#uri' => theme_get_setting('logo.url'),
      '#alt' => $this->t('Home'),
    ];

    $build['site_slogan'] = [
      '#type'     => 'inline_template',
      '#template' => '<div class="{{ classes }}">{{ content|raw }}</div>',
      '#context'  => [
        'content' => $this->configuration['site_slogan'],
        'classes' => 'siteslogan',
      ],
    ];

    return $build;
  }

}
