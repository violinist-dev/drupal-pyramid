<?php

/**
 * @file
 * Contains \DrupalPyramid\composer\ScriptHandler.
 */

namespace DrupalPyramid\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Composer\Util\ProcessExecutor;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;
use Drupal\Component\Utility\Crypt;

class ScriptHandler {

  // BASICS.

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
   * Get the project root folder.
   *
   * @return string
   */
  protected static function getComposerRoot() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $composerRoot = $drupalFinder->getComposerRoot();
    return $composerRoot;
  }

  /**
   * Get path to Drush bin.
   *
   * @return string
   *    The absolute path to Drush bin file.
   */
  protected static function getDrush() {
    return static::getComposerRoot() . '/bin/drush';
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
   * Get the list of folders with custom code.
   * @return array
   */
  public static function getTestGroups() {
    $drupalRoot = self::getDrupalRoot();
    return [
      // 'core',
      'pyramid_base',
      'pyramid_content',
    ];
  }

  // REQUIREMENTS.

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
   * Create required settings files.
   *
   * @param Event $event
   * @return void
   */
  public static function generateFiles(Event $event) {
    $fs = new Filesystem();
    $composerRoot = static::getComposerRoot();
    $drupalRoot = static::getDrupalRoot();

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

    // Create the DotEnv file
    if (!$fs->exists($composerRoot . '.env') and $fs->exists($composerRoot . '/example.env')) {
      $fs->copy($composerRoot . '/example.env', $composerRoot . '/.env');
      $fs->chmod($composerRoot . '/.env', 0666);
      $event->getIO()->write("Create the .env filed with chmod 0666");
    }

    // Prepare the settings file for installation
    if ($fs->exists($drupalRoot . '/sites/example.settings.php')) {
      $fs->copy($drupalRoot . '/sites/example.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($composerRoot . '/config/sync', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }

    // Prepare the local settings file for installation with chmod 666.
    if ($fs->exists($drupalRoot . '/sites/example.settings.local.php')) {
      $fs->copy($drupalRoot . '/sites/example.settings.local.php', $drupalRoot . '/sites/default/settings.local.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.local.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.local.php file with chmod 0666");
    }

    // Prepare the local services file for development.
    if ($fs->exists($drupalRoot . '/sites/example.services.development.yml')) {
      $fs->copy($drupalRoot . '/sites/example.services.development.yml', $drupalRoot . '/sites/default/services.development.yml');
      $fs->chmod($drupalRoot . '/sites/default/services.development.yml', 0666);
      $event->getIO()->write("Create a sites/default/services.development.yml file with chmod 0666");
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
    $io = $event->getIo();
    $fs = new FileSystem();
    $process = new ProcessExecutor($io);
    $drupalRoot = static::getDrupalRoot();
    $salt_file = $drupalRoot . "/sites/default/salt.txt";
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
   * Generate OpenSSL keys.
   * This is useful for headless Drupal (have a look a ContentaCMS).
   *
   * @param Event $event
   */
  public static function generateOpenSslKeys(Event $event) {
    $fs = new Filesystem();
    $composerRoot = static::getComposerRoot();
    $certificatesRoot = $composerRoot . "/certificates";
    $process = new ProcessExecutor($event->getIO());
    
    if (!$fs->exists($certificatesRoot)) {
      $fs->mkdir($certificatesRoot, 0755);
    }

    $event->getIO()->write("Removing old keys");
    $process->execute("rm -f " . $certificatesRoot . "/*.key");
    
    $event->getIO()->write("Generate new keys");
    $process->execute("openssl genrsa -out " . $certificatesRoot . "/private.key 2048");
    $process->execute("openssl rsa -in " . $certificatesRoot . "/private.key -pubout > " . $certificatesRoot . "/public.key");
  }

  /**
   * Apply recommended permissions on Files and Folders.
   *
   * @param Event $event
   * @return void
   */
  public static function fixPermissions(Event $event) {
    $drupalRoot = static::getDrupalRoot();
    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("Fixing files permissions");
    $process->execute("find " . $drupalRoot . " -type d -exec chmod u=rwx,g=rx,o= '{}' \;");
    $event->getIO()->write("Fixing folder permissions");
    $process->execute("find " . $drupalRoot . " -type f -exec chmod u=rw,g=r,o= '{}' \;");
  }
  
  /**
   * Fix OpenSSL keys files permission.
   *
   * @param Event $event
   */
  public static function fixOpenSslKeysPermissions(Event $event) {
    $composerRoot = static::getComposerRoot();
    $certificatesRoot = $composerRoot . "/certificates";

    $process = new ProcessExecutor($event->getIO());

    $event->getIO()->write("Fixing OpenSSL keys permissions");
    $process->execute("chmod 600 " . $certificatesRoot . "/*.key");
  }

  // CLEANING.

  /**
   * Delete git from contrib and vendor folders.
   *
   * @return void
   */
  public static function cleanGit(Event $event) {
    $io = $event->getIo();
    $drupalRoot = self::getDrupalRoot();   
    $process = new ProcessExecutor($io);

    $directories = [
      $drupalRoot,
      'vendor/',
      'config/'
    ];

    foreach ($directories as $dir) {
      $process->execute('find ' . $dir . ' -type d -name ".git" | xargs rm -rf');
      $io->write("Done! Git submodules removed from " . $dir);
    }

  }

  /**
   * Delete node_modules or bower_modules folders.
   *
   * @return void
   */
  public static function cleanNpm(Event $event) {
    $io = $event->getIo();
    $drupalRoot = static::getDrupalRoot();   
    $process = new ProcessExecutor($io);
    $directories = self::getCustomFolders();
    $io->write("Deleting node_modules and bower_modules folders...");
    foreach ($directories as $dir) {
      $process->execute('find ' . $dir . ' -type d -name "node_modules" | xargs rm -rf');
      $process->execute('find ' . $dir . ' -type d -name "bower_modules" | xargs rm -rf');
      $io->write("Done! Garbage folders removed from " . $dir);
    }  
  }  

  /**
   * Delete app keys.
   *
   * @return void
   */
  public static function cleanKeys(Event $event) {
    $fs = new Filesystem();
    $composerRoot = static::getComposerRoot();
    $certificatesRoot = $composerRoot . "/certificates";
    $process = new ProcessExecutor($event->getIO());    
    $process->execute('rm -f ' . $certificatesRoot . '/*.key');
    $event->getIO()->write("Removed old keys");
  }  

  /**
   * Remove vendors for a clean Composer reinstall.
   *
   * @param Event $event
   * @return void
   */
  public static function cleanVendors(Event $event) {
    $fs = new Filesystem();
    $io = $event->getIO();
    $root = self::getComposerRoot();
    $drupalRoot = static::getDrupalRoot();  

    $directories = array(
      "bin",
      "vendor",
      "drush/contrib",
      $drupalRoot . "/core",
      $drupalRoot . "/libraries",
      $drupalRoot . "/modules/contrib",
      $drupalRoot . "/profiles/contrib",
      $drupalRoot . "/themes/contrib",
    );

    $io->write("Removing directories (bin, vendor, core, libraries and contrib)."); 
    $fs->remove($directories);

    if ($fs->exists($root . '/composer.lock')) {
      $event->getIo()->write("Removing composer.lock file");
      $fs->remove($root . '/composer.lock');
    }

    $io->write("Everything's clean!");
    $io->write("Now run 'composer install' to get latest dependencies."); 
  }

  /**
   * Delete local files for a clean deploy.
   *
   * @return void
   */
  public static function resetFiles(Event $event) {
    $io = $event->getIo();
    $drupalRoot = static::getDrupalRoot();   
    $process = new ProcessExecutor($io);
    $files = [
      // Files at project root.
      $drupalRoot . '/sites/salt.txt',
      $drupalRoot . '/sites/default/settings.*',
    ];
    foreach ($files as $file) {
      $process->execute('find ' . $file . ' -type f | xargs rm -f');
      $io->write("Done! File deleted: " . $file);
    }  
  }
  
  // TESTING.

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

  public static function unitTests(Event $event) {
    $groups = self::getTestGroups();
    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("====================");
    $event->getIO()->write("Start Unit Test");
    $event->getIO()->write("====================");

    foreach($groups as $group) {
      $process->execute('./bin/phpunit -c ' . $group . '  > ./logs/unit_tests_' . $group . '.log');
      $process->execute('cat ./logs/unit_tests_' . $group . '.log');
    }
    $event->getIO()->write("Tests completed");
  }

  public static function functionalTests(Event $event) {
    $groups = self::getTestGroups();
    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("====================");
    $event->getIO()->write("No functional tests yet");
    $event->getIO()->write("====================");

    foreach($groups as $group) {
      $process->execute('./bin/phpunit -c ' . $group . '  > ./logs/unit_tests_' . $group . '.log');
      $process->execute('cat ./logs/unit_tests_' . $group . '.log');
    }
    $event->getIO()->write("Tests completed");
  }

  // CUSTOM COMNANDS.

  /**
   * Run Drush commands.
   *
   * @param Event $event
   */
  public static function doDrush(Event $event) {
    $drush = self::getDrush();
    $args = $event->getArguments();
    $drupalRoot = static::getDrupalRoot();
    $process = new ProcessExecutor($event->getIO());
    $process->execute($drush . ' ' . implode(' ', $args) . ' -r ' . $drupalRoot);      
  }

}
