<?php

/**
 * @file
 * Default Template file for LibreWebTools
 *
 *
 * @category   Themes
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.info>
 * @copyright  Copyright (c) 2014
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */

/**
 * Renders the copyright disclaimer
 * 
 * @return string HTML Markup
 */
function core_render_copyright(){
  $start_year = 2012;
  $current_year = date('Y');
  $owner = "TransitGuru Limited";

  // If the start year is not the current year: display start-current in copyright
  if ($start_year != $current_year){
?>
        <p class="copy">&copy;<?php echo "{$start_year} &ndash; {$current_year} {$owner}"; ?></p>
<?php
  }
  // Only display current year.
  else{
?>
        <p class="copy">&copy;<?php echo "{$current_year} {$owner}"; ?></p>
<?php
  }
  return TRUE;
}


/**
 * Renders the 404 Not Found page
 * 
 * @return boolean Successful completion
 */
function core_render_404(){
  $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
?>
  <p>Page not found. Please go <a href="/">Home</a>.</p>
<?php
}

header("HTTP/1.1 " . $page->header);
if (!is_null($page->ajax_call) && $page->ajax_call !== '' &&  function_exists($page->ajax_call)){
  $fn = $page->ajax_call;
  $fn();
}
?>              
<!DOCTYPE html>
<html>
  <meta charset="utf-8" />
  <head>
    <title><?php echo $page->title; ?></title>
  </head>
  <body>
<?php 
echo $page->content;
if (!is_null($page->render_call) && $page->render_call !== '' &&  function_exists($page->render_call)){
  $fn = $page->render_call;
  $fn();
}
  core_render_copyright()    
?>
  </body>
</html>
