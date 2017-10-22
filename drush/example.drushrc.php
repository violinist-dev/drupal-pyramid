<?php

/**
 * @file
 * Examples of valid statements for a Drush runtime config (drushrc) file.
 * You can configure many other options in this file.
 * @see http://api.drush.org/api/drush/examples%21example.drushrc.php/8.0.x
 * 
 * Rename this file to drushrc.php and optionally copy it to one of the places
 * listed below in order of precedence:
 *
 * 1.  Drupal site folder (e.g. sites/{default|example.com}/drushrc.php).
 * 2.  Drupal /drush and sites/all/drush folders, or the /drush folder
 *       in the directory above the Drupal root.
 * 3.  In any location, as specified by the --config (-c) option.
 * 4.  User's .drush folder (i.e. ~/.drush/drushrc.php).
 * 5.  System wide configuration folder (e.g. /etc/drush/drushrc.php).
 * 6.  Drush installation folder.
 */

/**
 * Project info.
 */
$project = [
  "protocol" => "https",
  "name" => "<project_name>",
];

/**
 * Drush URL Setup.
 * @see https://docs.devwithlando.io/tutorials/pantheon.html#drush-url-setup
 */
$options['uri'] = $project['protocol']. "://" . $project['name'] . ".lndo.site";

/**
 * Set a predetermined username and password when using site-install.
 * @see https://drushcommands.com/drush-8x/core/site-install/
 */
$command_specific['site-install'] = [
  'account-name' => 'admin',
  'account-pass' => 'admin',
  // 'config-dir' => '../config',  
  // 'sites-subdir' => 'example1',  
  // 'profile' => 'config_installer',
];
