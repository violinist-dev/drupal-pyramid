<?php

namespace Drupal\pyramid_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block to display custom HTML content.
 *
 * @Block(
 *   id = "pyramid_content_block",
 *   admin_label = @Translation("Pyramid - Content"),
 * )
 */
class PyramidContentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['content'] = [
      '#type'          => 'text_format',
      '#title'         => $this->t('Content'),
      '#default_value' => $this->configuration['content'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['content'] = [
      '#markup' => $this->configuration['content']['value'],
    ];

    return $build;
  }

}
