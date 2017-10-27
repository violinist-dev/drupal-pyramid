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
use Drupal\Component\Utility\Crypt;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  /**
   * Drush config file.
   *
   * @var string $configFile
   */
  public static $configFile = '.drush.yml';

  /**
   * Parse the config file and return settings.
   *
   * @param boolean $var
   * @return mixed
   *    The array of settings OR the requested setting value.
   */
  public static function getSettings($var = FALSE) {
    $configFile = file_get_contents(self::$configFile);
    $defaultSettings = file_get_contents('example' . self::$configFile);
    $settings = array_merge(
      Yaml::parse($defaultSettings),
      Yaml::parse($configFile)
    );
    return ($var && isset($settings[$var])) ? $settings[$var] : $settings;
  }

  /**
   * Get path to Drush bin.
   *
   * @return string
   *    The absolute path to Drush bin file.
   */
  public static function getDrush() {
    return 'bin/drush';
  }

  /**
   * Get Drupal webroot.
   *
   * @return string
   *    The absolute path to Drupal webroot.
   */
  public static function getDrupalRoot() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    return $drupalRoot;
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

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.local.php') and $fs->exists($drupalRoot . '/sites/example.settings.local.php')) {
      $fs->copy($drupalRoot . '/sites/example.settings.local.php', $drupalRoot . '/sites/default/settings.local.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.local.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.local.php file with chmod 0666");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists(self::$configFile) and $fs->exists('example' . self::$configFile)) {
      $fs->copy('example' . self::$configFile, self::$configFile);
      $fs->chmod(self::$configFile, 0666);
      $event->getIO()->write("Create " . self::$configFile . " file with chmod 0666");
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
   * Delete contrib and vendor folders.
   *
   * @return void
   */
  public static function dependencyCleanup(Event $event) {
    $fs = new Filesystem();
    $io = $event->getIO();
    $drupalRoot = self::getDrupalRoot();  
    
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

    $io->write("Removing composer.lock file."); 
    if ($fs->exists('composer.lock')) { 
      $fs->remove('composer.lock'); 
    } 
 
    $io->write("Everything's clean!");
    $io->write("Now run 'composer install' to get latest dependencies."); 
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
    $drupalRoot = self::getDrupalRoot();

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

    self::fixPermissions($event, $drupalRoot . '/sites/default');
  }

  /**
   * Generate OpenSSL keys.
   * This is useful for headless Drupal (have a look a ContentaCMS).
   *
   * @param Event $event
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
  public static function fixPermissions(Event $event, $path = FALSE) {    
    $drupalRoot = self::getDrupalRoot();

    $dir = is_string($path) ? $path : $drupalRoot;

    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("Fixing files permissions");
    $process->execute("find " . $dir . " -type d -exec chmod u=rwx,g=rx,o= '{}' \;");
    $event->getIO()->write("Fixing folder permissions");
    $process->execute("find " . $dir . " -type f -exec chmod u=rw,g=r,o= '{}' \;");
        
  }

  /**
   * Re install Drupal.
   *
   * @param Event $event
   */
  public static function siteReset(Event $event) {
    $io = $event->getIO();
    $args = $event->getArguments();

    $drush = self::getDrush();
    $drupalRoot = self::getDrupalRoot();
    $settings = self::getSettings();

    // Quiet deletion.
    $auto = isset($args[0]) && $args[0] == 'y' ? TRUE : FALSE;
    if (!$auto) {
      $io->write("You're about to delete your current site.");
      $continue = $io->ask("Are you OK? (y/n) ");
      if ($continue != 'y') {
        $io->write("You're website is safe... :)");
        return;
      }
    }

    self::siteInstall($event);
    self::siteResetConfig($event);
    self::siteResetUser($event);
    
    $process = new ProcessExecutor($event->getIO());
    $process->execute($drush . ' config-split-export -y -r ' . $drupalRoot);
  }

  /**
   * Reset Drupal site settings.
   *
   * @param Event $event
   */
  public static function siteInstall(Event $event) {
    $drush = self::getDrush();
    $drupalRoot = self::getDrupalRoot();
    $settings = self::getSettings();

    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("Reinstall and reset website");    
    $process->execute($drush . " site-install config_installer -y -r " . $drupalRoot);      
  }

  /**
   * Reset Drupal site settings.
   *
   * @param Event $event
   */
  public static function siteResetConfig(Event $event) {
    $io = $event->getIO();
    $drush = self::getDrush();
    $drupalRoot = self::getDrupalRoot();
    $settings = self::getSettings();
    if (!isset($settings['site'])) {
      $io->write("Site settings not found in " . self::$configFile);
      return;
    }

    $process = new ProcessExecutor($io);
    foreach ($settings['site'] as $key => $value) {
      $process->execute($drush . ' config-set system.site ' . $key . ' "' . $value . '" -y -r ' . $drupalRoot);
    }
    $io->write("=============");
    $io->write("New settings");
    $io->write("=============");
    foreach ($settings['site'] as $key => $value) {
      $io->write($key . " = " . $value);
    }
  }

  /**
   * Reset Drupal user.
   *
   * @param Event $event
   */
  public static function siteResetUser(Event $event) {
    $drush = self::getDrush();
    $drupalRoot = self::getDrupalRoot();
    $settings = self::getSettings();

    $process = new ProcessExecutor($event->getIO());
    $event->getIO()->write("Reset site user");
    $process->execute($drush . ' user-create ' . $settings['account']['name'] . ' --password="' . $settings['account']['password'] . '" --mail="' . $settings['account']['mail'] . '" -y -r ' . $drupalRoot);
    $process->execute($drush . ' user-add-role "administrator" --name="' . $settings['account']['name'] . '" -y -r ' . $drupalRoot);
    $process->execute($drush . ' user-password ' . $settings['account']['name'] . ' --password="' . $settings['account']['password'] . '" -y -r ' . $drupalRoot);
    $process->execute($drush . ' user-block --name="admin" -y -r ' . $drupalRoot);
  }


}
