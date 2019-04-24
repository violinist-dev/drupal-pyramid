<?php

// @codingStandardsIgnoreFile
// @see default/default.settings.php

$databases                                = array();
$config_directories                       = array();
$config_directories['sync']               = '../config/sync';
$settings['install_profile']              = 'config_installer';
$settings['hash_salt']                    = file_get_contents($app_root . '/' . $site_path . '/salt.txt');
$settings['update_free_access']           = FALSE;
$settings['file_chmod_directory']         = 0775;
$settings['file_chmod_file']              = 0664;
$settings['file_public_path']             = 'sites/default/files';
$settings['file_private_path']            = 'sites/default/files/private';
$settings['container_yamls'][]            = $app_root . '/' . $site_path . '/services.yml';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];
$settings['entity_update_batch_size'] = 50;

// Include local settings.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
