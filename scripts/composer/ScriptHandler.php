<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Composer\Util\ProcessExecutor;
use DrupalFinder\DrupalFinder;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  /**
   * Get the Drupal root folder.
   *
   * @return string
   */
  protected static function getDrupalRoot() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    return $drupalRoot;
  }

  /**
   * Get the list of folders with custom code.
   * @return array
   */
  public static function getCustomFolders() {
    $drupalRoot = self::getDrupalRoot();
    return [
      // $drupalRoot . '/profiles/custom/',
      $drupalRoot . '/modules/custom/',
      $drupalRoot . '/themes/custom/',
    ];
  }

  /**
   * Default function to generate base settings files.
   */
  public static function createRequiredFiles(Event $event) {
    $fs           = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/' . $dir)) {
        $fs->mkdir($drupalRoot . '/' . $dir);
        $fs->touch($drupalRoot . '/' . $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value'    => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
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
    $io       = $event->getIO();

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
    } elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

  /**
   * Generate Salt file.
   *
   * Regenerate salt.txt file and use it settings.php.
   *
   * Use `file_get_contents($app_root . '/' . $site_path . '/salt.txt')`;
   *
   * @param Composer\Script\Event $event
   *   Sent by composer.
   */
  public static function generateSalt(Event $event) {
    $io         = $event->getIo();
    $fs         = new FileSystem();
    $process    = new ProcessExecutor($io);
    $drupalRoot = static::getDrupalRoot();
    $salt_file  = $drupalRoot . "/sites/default/salt.txt";
    $fs->chmod($drupalRoot . "/sites/default", 0775);
    if ($fs->exists($salt_file)) {
      $io->write("Removing old salt.txt file...");
      $process->execute("rm -f " . $salt_file);
    }
    $io->write("Generating " . $salt_file);
    $salt = Crypt::randomBytesBase64(55);
    $process->execute("touch " . $salt_file);
    $process->execute("echo " . $salt . " > " . $salt_file);
    $io->write($salt);
  }

  /**
   * PHPCodeSniffer automatically fixing coding standards.
   *
   * @param Event $event
   */
  public static function phpcbf(Event $event) {
    $folders = self::getCustomFolders();

    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("============");
    $event->getIO()->write("Start PHPCBF");
    $event->getIO()->write("============");
    $process->execute('./bin/phpcbf --config-set installed_paths vendor/drupal/coder/coder_sniffer');
    $process->execute('./bin/phpcbf --standard=Drupal --ignore="node_modules,bower_modules,vendor,*.md" --extensions="php,inc/php,module/php,theme/php" --colors ' . implode(' ', $folders) . ' > logs/errors_coding_practice.log');
    $process->execute('cat ./logs/errors_coding_practice.log');
    $event->getIO()->write("PHPCBF completed");
  }

  /**
   * PHPCodeSniffer.
   *
   * @param Event $event
   */
  public static function phpcs(Event $event) {
    $folders = self::getCustomFolders();
    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("====================");
    $event->getIO()->write("Start PHPCodeSniffer");
    $event->getIO()->write("====================");
    $process->execute('./bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer');
    $process->execute('./bin/phpcs --standard=Drupal --ignore="node_modules,bower_modules,vendor,*.md" --extensions="php,inc/php,module/php,theme/php" ' . implode(' ', $folders) . ' > logs/errors_coding_standard.log');
    $process->execute('cat ./logs/errors_coding_standard.log');
    $event->getIO()->write("PHPCodeSniffer completed");
  }

  /**
   * Apply recommended permissions on Files and Folders.
   *
   * @param Event $event
   * @return void
   */
  public static function fixPermissions(Event $event) {
    $drupalRoot = static::getDrupalRoot();
    $process    = new ProcessExecutor($event->getIO());
    $event->getIO()->write("Fixing files permissions");
    $process->execute("find " . $drupalRoot . " -type d -exec chmod u=rwx,g=rx,o= '{}' \;");
    $event->getIO()->write("Fixing folder permissions");
    $process->execute("find " . $drupalRoot . " -type f -exec chmod u=rw,g=r,o= '{}' \;");
  }

}
