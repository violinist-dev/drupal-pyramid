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
  const HOMEPAGE_UUID = '0e0cc85a-0bb9-470e-89a3-4d0fe6a99f1f';

  // Defaut author (admin).
  const DEFAULT_USER = 1;

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
   * {@inheritDoc}.
   */
  public function __construct() {
    $this->entityRepository = \Drupal::service('entity.repository');
    $path = drupal_get_path('module', 'pyramid_content');
    $this->pathToContent = $path . '/data';
    $this->pathToImages = $path . '/images';
  }

  // CONTENT.
  /**
   * Imports homepage.
   *
   * @command pyramid-content:import-homepage
   * @aliases import-content
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
      'field_links' => [$links],
    ]);
    $hero_banner->save();
    $components[] = $hero_banner;

    // Update or create the homepage.
    if ($node = \Drupal::service('entity.repository')->loadEntityByUuid('node', $this::HOMEPAGE_UUID)) {
      $node->delete();
    }

    $page = Node::create([
      'type' => 'page',
      'title' => $json['title'],
      'body' => ['value' => $this->getLorem()],
      'field_components' => $components,
      'uuid' => $this::HOMEPAGE_UUID,
      'uid' => $this::DEFAULT_USER,
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
   *
   * @return object|bool
   *   The saved Media entity or FALSE if it failed.
   */
  protected function createMediaImage(string $filename, string $title, string $alt = '', string $folder = 'public://') {
    $media = FALSE;
    $image_data = file_get_contents($this->pathToImages . '/' . $filename);
    $file_image = file_save_data($image_data, $folder . '/' . $filename, FILE_EXISTS_REPLACE);
    if ($file_image) {
      $img = [
        'title' => $title,
        'alt' => $alt,
        'target_id' => $file_image->id(),
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

  /**
   * Spicy Bacon Ipsum.
   *
   * @return string
   */
  public function getLorem() {
    return "Spicy jalapeno bacon ipsum dolor amet sausage picanha velit pork chop bacon jerky chicken short loin ullamco laboris swine voluptate. Sunt venison shoulder in dolor qui ut quis, shank cillum est doner elit. Pancetta esse short loin, officia ham cupidatat filet mignon ullamco veniam. Velit fugiat pork loin pork belly. Biltong cillum esse meatball, minim ipsum culpa spare ribs hamburger lorem exercitation eu chuck turkey est. Brisket hamburger veniam do pork incididunt, cillum porchetta proident aliqua boudin irure nulla jerky.

    Landjaeger ground round dolore venison meatball esse, magna sed ham hock. Bacon proident consectetur, ipsum frankfurter nisi beef pig fatback est shank ut tempor. Veniam ea chicken beef ribs, consequat fatback burgdoggen. Ullamco eiusmod eu nostrud. Nulla esse sint beef ribs flank frankfurter. Nisi ribeye pork loin id shankle.
    
    Ham hock shank velit id, leberkas deserunt jowl incididunt picanha strip steak do. Andouille tenderloin sirloin boudin ut, kielbasa pork loin non lorem do rump. Spare ribs sunt in fatback picanha labore. Cupidatat short loin kevin ipsum cillum ham hock ut.
    
    Qui rump magna andouille ham consequat sunt picanha non velit pork belly chicken elit meatball occaecat. Pork belly labore pig tongue, frankfurter proident pork short ribs doner ribeye ground round. Excepteur et cow irure qui prosciutto ribeye aliqua sausage. Tongue mollit in velit tri-tip, dolore ham hock chuck id pork chop adipisicing strip steak burgdoggen in elit.";
  }

}
