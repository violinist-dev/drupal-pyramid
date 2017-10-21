<?php

/**
 * @file
 * Contains \DrupalPyramid\composer\ScriptHandler.
 */

namespace DrupalPyramid\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;
use Composer\Util\ProcessExecutor;

class ScriptHandler {

  /**
   * Get Drupal root.
   *
   * @return string
   *    Drupal root folder path.
   */
  protected static function getDrupalRoot() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    return $drupalFinder->getDrupalRoot();
  }  

  /**
   * Create necessary files and folders for Drupal install.
   *
   * @param Event $event
   * @return void
   */
  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalRoot = self::getDrupalRoot();

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

  /**
   * Delete git from contrib and vendor folders.
   *
   * @return void
   */
  public static function gitCleanup(Event $event) {
    $io = $event->getIo();
    $drupalRoot = self::getDrupalRoot();
    $process = new ProcessExecutor($io);

    $directories = [
      $drupalRoot . '/modules/contrib/',
      $drupalRoot . '/profiles/contrib/',
      $drupalRoot . '/themes/contrib/',
      './vendor/'
    ];

    foreach ($directories as $dir) {
      $process->execute('find ' . $dir . ' -type d -name ".git" | xargs rm -rf');
      $io->write("Done! Git submodules removed from " . $dir);
    }

  }

  /**
   * Delete contrib and vendor folders.
   *
   * @return void
   */
  public static function dependencyCleanup() {
    $fs = new Filesystem();
    $root = getcwd();

    $directories = array(
      "bin",
      "web/core",
      "web/libraries",
      "web/modules/contrib",
      "web/profiles/contrib",
      "web/themes/contrib",
      "drush/contrib",
      "vendor",
    );

    $directories = array_map(function ($directory) use ($root) {
      return $root.'/'.$directory;
    }, $directories);

    $fs->remove($directories);

    echo "(!) Now you can run 'composer install' to get the latest dependencies. \n";

  }

  /**
   * Generate OpenSSL keys.
   * This is useful for headless Drupal for instance.
   *
   * @param Event $event
   * @return void
   */
  public static function generateOpenSslKeys(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $certificatesRoot = $drupalFinder->getComposerRoot() . "/certificates";
    $process = new ProcessExecutor($event->getIO());
    
    if (!$fs->exists($certificatesRoot)) {
      $fs->mkdir($certificatesRoot, 0755);
    }

    $event->getIO()->write("Removing old keys");
    $process->execute("rm -f " . $certificatesRoot . "/*.key");
    
    $event->getIO()->write("Generate new keys");
    $process->execute("openssl genrsa -out " . $certificatesRoot . "/private.key 2048");
    $process->execute("openssl rsa -in " . $certificatesRoot . "/private.key -pubout > " . $certificatesRoot . "/public.key");

    // Fix keys permissions.
    self::fixOpenSslKeysPermissions($event);
  }
  
  /**
   * Fix OpenSSL keys files permission.
   *
   * @param Event $event
   * @return void
   */
  public static function fixOpenSslKeysPermissions(Event $event) {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $certificatesRoot = $drupalFinder->getComposerRoot() . "/certificates";

    $process = new ProcessExecutor($event->getIO());

    $event->getIO()->write("Fixing OpenSSL keys permissions");
    $process->execute("chmod 600 " . $certificatesRoot . "/*.key");
  }
  
  /**
   * Fix files and folders permissions.
   *
   * @see https://www.drupal.org/node/244924 
   * @param Event $event
   * @return void
   */
  public static function fixPermissions(Event $event) {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    
    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("Fixing files permissions");
    $process->execute("find " . $drupalRoot . " -type d -exec chmod u=rwx,g=rx,o= '{}' \;");
    $event->getIO()->write("Fixing folder permissions");
    $process->execute("find " . $drupalRoot . " -type f -exec chmod u=rw,g=r,o= '{}' \;");

    // Fix keys permissions.
    self::fixOpenSslKeysPermissions($event);        
  }

}
