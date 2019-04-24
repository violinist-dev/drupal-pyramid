<?php

namespace Drupal\pyramid_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pyramid_base\Form\SiteSearchForm;

/**
 * Provides a block to display the Site name.
 *
 * @Block(
 *   id = "pyramid_sitesearch_block",
 *   admin_label = @Translation("Pyramid - Site search"),
 * )
 */
class PyramidSiteSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'placeholder' => $this->t('Enter your search and press enter...'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['placeholder'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Placeholder'),
      '#default_value' => $this->configuration['placeholder'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['placeholder'] = $form_state->getValue('placeholder');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['search'] = \Drupal::formBuilder()->getForm(SiteSearchForm::class, $this->getConfiguration()['placeholder']);

    $build['#attached']['library'][] = 'pyramid/block.search';

    return $build;
  }

}
