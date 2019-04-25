<?php

namespace Drupal\pyramid_base\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Site search block.
 */
class SiteSearchForm extends FormBase {

  /**
   * {@inheritDoc}.
   */
  public function getFormId() {
    return 'pyramid_site_search';
  }

  /**
   * {@inheritDoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $placeholder = '') {
    $form['search'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Search'),
      '#placeholder'   => $placeholder,
      '#title_display' => 'invisible',
    ];
    $form['action'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Search'),
      '#id'    => 'pyramid-sitesearch',
    ];
    $form['#attributes'] = [
      'class' => [
        'pyramid-site-search',
        'columns',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search       = $form_state->getValue('search');
    $redirect_url = Url::fromRoute('view.news.all', ['title' => $search]);
    $form_state->setRedirectUrl($redirect_url);
  }

}
