<?php

/**
 * @file
 * Bootstrap file for LibreWebTools Test module
 *
 * Loads all included classes for the Test module.
 *
 * @category   Bootstrap
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
// Load the classes
$DIR = __DIR__ . '/classes';
$includes = scandir($DIR);
foreach ($includes as $include){
  if (is_file($DIR . '/' . $include) && $include != '.' && $include != '..' && fnmatch("*.php", $include)){
    require_once ($DIR . '/' . $include);
  }
}

