<?php

/**
 * @file
 * Contains \DrupalPyramidTheme\ScriptHandler.
 */

namespace DrupalPyramidTheme;

class ScriptHandler {

  /**
   *
   * Install npm and build assets.
   *
   * @return void
   */
  public static function build() {
    $env = (getenv('ENV') === 'production') ? '--production' : '';
    $themePath = "./web/themes/contrib/drupal_pyramid_theme/";
    passthru("echo '==============================='");
    passthru("echo 'Generating Pyramid theme assets'");
    passthru("echo '==============================='");
    passthru("cd {$themePath} && npm install {$env} && npm run build:all");
  }
  
  public static function update() {
    $env = (getenv('ENV') === 'production') ? '--production' : '';
    $themePath = "./web/themes/contrib/drupal_pyramid_theme/";
    passthru("echo '==============================='");
    passthru("echo 'Updating Pyramid theme assets'");
    passthru("echo '==============================='");
    passthru("cd {$themePath} && npm update {$env} && npm run build:all");
  }

}
