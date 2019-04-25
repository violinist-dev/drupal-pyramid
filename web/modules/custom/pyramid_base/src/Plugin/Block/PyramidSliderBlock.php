<?php

namespace Drupal\pyramid_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display a slider.
 *
 * @Block(
 *   id = "pyramid_slider_block",
 *   admin_label = @Translation("Pyramid - Slider"),
 * )
 */
class PyramidSliderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paragraphManager;

  /**
   * Constructs a new block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $paragraph_storage
   *   The entity manager for Paragraph entities.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $paragraph_storage, EntityViewBuilder $paragraph_view_builder) {
    $this->paragraphManager     = $paragraph_storage;
    $this->paragraphViewBuilder = $paragraph_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('paragraph'),
      $container->get('entity_type.manager')->getViewBuilder('paragraph')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'slider' => NULL,
      'provider' => 'pyramid_slider_block',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['slider'] = [
      '#title'         => $this->t('Slides'),
      '#type'          => 'inline_entity_form',
      '#entity_type'   => 'paragraph',
      '#bundle'        => 'slider',
      '#default_value' => $this->getSliderParagraph(),
      '#group'         => 'container',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $paragraph = $form['settings']['slider']['#entity'];
    if ($paragraph instanceof ParagraphInterface) {
      $this->configuration['slider'] = $paragraph->get('uuid')->getString();
    }

    // Save this plugin name for easier theming.
    $this->configuration['provider'] = 'pyramid_slider_block';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($paragraph = $this->getSliderParagraph()) {
      $build['slider'] = $this->paragraphViewBuilder->view($paragraph);
    }

    $build['#attached']['library'][] = 'pyramid/block.slider';

    return $build;
  }

  /**
   * Load the attached Paragraph entity.
   *
   * @return null|ParagraphInterface
   */
  private function getSliderParagraph() {
    $paragraph = NULL;
    if ($uuid = $this->configuration['slider']) {
      if (is_string($uuid)) {
        $results = $this->paragraphManager->loadByProperties(['uuid' => $uuid]);
        if (!empty($results)) {
          $paragraph = reset($results);
        }
      }
    }
    return $paragraph;
  }

}
