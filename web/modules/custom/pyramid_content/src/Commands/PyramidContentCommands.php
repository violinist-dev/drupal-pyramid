<?php

namespace Drupal\pyramid_content\Commands;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drush\Commands\DrushCommands;

/**
 * Class PyramidContentCommands.
 *
 * @package Drupal\default_content
 */
class PyramidContentCommands extends DrushCommands {

  // Using UUID let us import / update existing content easily.
  // Using constant here allow foreign classes to refer to these UUIDs.
  const HOMEPAGE_UUID  = '0e0cc85a-0bb9-470e-89a3-4d0fe6a99f1f';

  /**
   * Path to content folder.
   *
   * @var string
   */
  public $pathToContent;

  /**
   * Path to images folder.
   *
   * @var string
   */
  public $pathToImages;

  /**
   * {@inheritDoc}
   */
  public function __construct() {
    $this->entityRepository = \Drupal::service('entity.repository');
    $path = drupal_get_path('module', 'pyramid_content');
    $this->pathToContent = $path . '/data';
    $this->pathToImages = $path . '/images';
  }

  // CONTENT
  
  /**
   * Imports homepage.
   *
   * @command pyramid-content:import-homepage
   * @aliases pyci-home
   */
  public function createHomePage() {
    $content = file_get_contents($this->pathToContent . "/page-homepage.json");
    $json = json_decode($content, TRUE);
    
    // Defaults.
    $links = [];
    $components = [];
    
    // Prepare the buttons.
    foreach ($json['links'] as $link) {
      $links = [
        'uri' => $link['uri'],
        'title' => $link['title'],
        'options' => [
          'attributes' => [
            'class' => [$link['class']],
          ],
        ],
      ];    
    }
    // Create the image.
    $media = $this->createMediaImage($json['image'], 'Homepage banner');
    // Save the Hero banner.
    $hero_banner = Paragraph::create([
      'type' => 'hero',
      'field_title' => $json['title'],
      'field_description' => $json['description'],
      'field_image' => ['target_id' => ($media) ? $media->id() : NULL],
      'field_link' => [$links],
    ]);
    $hero_banner->save();
    $components[] = $hero_banner;
    
    // Update or create the homepage.
    $page = Node::create([
        'type' => 'page',
        'title' => $json['title'],
        'field_components' => $components,
        'uuid' => $this::HOMEPAGE_UUID,
      ]);
    $page->save();
  }

  /**
   * Create a Media entity from an image file.
   *
   * @param string $filename
   * @param string $title
   * @param string $alt
   * @param string $folder
   * @return object|bool
   *    The saved Media entity or FALSE if it failed.
   */
  protected function createMediaImage(string $filename, string $title, string $alt = '', string $folder = 'public://images') {
    $media = FALSE;
    $image_data = file_get_contents($this->pathToContent . '/' . $filename);
    $file_image = file_save_data($image_data, $folder . '/' . $filename, FILE_EXISTS_REPLACE);
    if ($file_image) {
      $img = [
        'title' => $title,
        'alt' => $alt,
        'target_id' => $file_image->id()
      ];
      $media = Media::create([
        'bundle' => 'image',
        'name' => $title,
        'field_media_image' => [$img],
      ]);
      $media->save();
    }
    return $media;
  }
}
