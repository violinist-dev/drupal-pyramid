<?php

namespace Drupal\pyramid_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a block to display the Site name.
 *
 * @Block(
 *   id = "pyramid_sitename_block",
 *   admin_label = @Translation("Pyramid - Site name"),
 * )
 */
class PyramidSiteNameBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'site_name' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['site_name'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Site name'),
      '#default_value' => $this->configuration['site_name'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['site_name'] = $form_state->getValue('site_name');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['site_name'] = [
      '#type'     => 'inline_template',
      '#template' => '<a class="{{ classes }}" href="{{ url }}">{{ label|raw }}</a>',
      '#context'  => [
        'url'     => Url::fromRoute('<front>')->toString(),
        'label'   => $this->configuration['site_name'],
        'classes' => 'sitename',
      ],
    ];

    return $build;
  }

}
