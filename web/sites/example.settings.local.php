<?php

// @codingStandardsIgnoreFile

// Load the environment.
$env = getenv('ENVIRONMENT');

/**
 * Expose environment to Drupal
 *
 * Can be used in with \Drupal::settings('environment').
 */
$settings['environment'] = $env;

// Database settings.
$databases['default']['default'] = [
  'database'  => getenv('MYSQL_DATABASE'),
  'driver'    => 'mysql',
  'host'      => getenv('MYSQL_HOSTNAME'),
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'password'  => getenv('MYSQL_PASSWORD'),
  'port'      => getenv('MYSQL_PORT'),
  'prefix'    => '',
  'username'  => getenv('MYSQL_USER'),
];

/**
 * Specific settings for local development environment.
 *
 * Basically, we disable caching.
 */
if ($env == 'local') {
  // Trusted Host Settings support.
  $settings['trusted_host_patterns'][] = '^localhost$';
  $settings['trusted_host_patterns'][] = '^.+\.lndo\.site$';

  // Assertions.
  assert_options(ASSERT_ACTIVE, TRUE);
  \Drupal\Component\Assertion\Handle::register();

  // If devel is present set kint maxLevels.
  if (file_exists(DRUPAL_ROOT . '/modules/contrib/devel/kint/kint/Kint.class.php')) {
    require_once DRUPAL_ROOT . '/modules/contrib/devel/kint/kint/Kint.class.php';
    Kint::$maxLevels = 5;
  }

  // Enable development services.
  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

  // Show all error messages, with backtrace information.
  $config['system.logging']['error_level'] = 'verbose';

  // Disable CSS and JS aggregation.
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess']  = FALSE;

  // Disable caching for migrations.
  $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

  // Disable the render cache.
  $settings['cache']['bins']['render'] = 'cache.backend.null';

  // Disable Internal Page Cache.
  $settings['cache']['bins']['page'] = 'cache.backend.null';

  // Disable Dynamic Page Cache.
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

  // Allow test modules and themes to be installed.
  $settings['extension_discovery_scan_tests'] = FALSE;

  // Enable access to rebuild.php.
  $settings['rebuild_access'] = TRUE;

  // Skip file system permissions hardening.
  $settings['skip_permissions_hardening'] = TRUE;
}

// Configuration management.
$config_split_folders['ignore'] = TRUE;
$config_split_folders['dev']    = ($env == 'local' || $env == 'dev');
$config_split_folders['stage']  = $env == 'stage';
$config_split_folders['prod']   = $env == 'prod';

/**
 * Activate Config Split folders.
 */
foreach ($config_split_folders as $env => $status) {
  $config['config_split.config_split.' . $env]['status'] = $status;
}

/**
 * The active installation profile.
 * @todo Remove this settings when we upgrade to Drupal 9.0.0.
 * @deprecated in Drupal 8.3.0 and will be removed before Drupal 9.0.0.
 */
$settings['install_profile'] = 'config_installer';
