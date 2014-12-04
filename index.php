<?php

/**
 * @file
 * Bootstrap file for LibreWebTools
 *
 * This bootstraps the entire application and will provide a means to 
 * access all available modules.
 *
 * LICENSE: All LibreWebTools code is Copyright 2012 - 2014 by Michael Sypolt of 
 * TransitGuru Limited <msypolt@transitguru.limited>.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program as the file LICENSE.txt; if not, please see
 * http://www.gnu.org/licenses/gpl-3.0.txt.
 * 
 *
 * @category   Bootstrap
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.info>
 * @copyright  Copyright (c) 2014
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.5
 */

// Load the core
$start = microtime(true);
define('DOC_ROOT', getcwd());

$PATH = DOC_ROOT . '/includes/classes';
$includes = scandir($PATH);
foreach ($includes as $include){
  if (is_file($PATH . '/' . $include) && $include != '.' && $include != '..' && fnmatch("*.php", $include)){
    include ($PATH . '/' . $include);
  }
}
echo "<!DOCTYPE html>\n<html>\n  <head>\n    <meta charset=\"utf-8\" />\n    <title>LibreWebTools</title>\n</head>\n  <body>\n    <h1>Under Construction</h1><p>Please visit my <a href=\"https://github.com/transitguru/librewebtools\">GitHub</a> for more information about release plans.</p>\n";
$svg = file_get_contents(DOC_ROOT . '/includes/design/design.svg');
echo $svg;
$end = microtime(true);
$time = 1000 * ($end - $start);
echo $time . "ms";
echo "\n  </body>\n</html>";

