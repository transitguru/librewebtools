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
      
?>
  </body>
</html>
