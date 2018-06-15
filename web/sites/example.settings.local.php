<?php

// @codingStandardsIgnoreFile

// Assertions.
assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

// If devel is present set kint maxLevels.
if (file_exists(DRUPAL_ROOT . '/modules/contrib/devel/kint/kint/Kint.class.php')) {
  require_once DRUPAL_ROOT . '/modules/contrib/devel/kint/kint/Kint.class.php';
  Kint::$maxLevels = 5;
}

// Enable local development services.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Disable caching for migrations.
$settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

// Disable the render cache.
$settings['cache']['bins']['render'] = 'cache.backend.null';

// Disable Internal Page Cache.
$settings['cache']['bins']['page'] = 'cache.backend.null';

// Disable Dynamic Page Cache.
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// Allow test modules and themes to be installed.
$settings['extension_discovery_scan_tests'] = TRUE;

// Enable access to rebuild.php.
$settings['rebuild_access'] = TRUE;

// Skip file system permissions hardening.
$settings['skip_permissions_hardening'] = TRUE;

// Database settings.
$databases['default']['default'] = [
  'database' => getenv('MYSQL_DATABASE'),
  'driver' => 'mysql',
  'host' => getenv('MYSQL_HOSTNAME'),
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'password' => getenv('MYSQL_PASSWORD'),
  'port' => getenv('MYSQL_PORT'),
  'prefix' => 'korian_',
  'username' => getenv('MYSQL_USER'),
];

// Configuration management.
$config_split_folders['dev'] = true;
$config_split_folders['stage'] = false;
$config_split_folders['prod'] = false;
