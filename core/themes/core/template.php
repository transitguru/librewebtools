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
  $this->ajax_call();
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
if (!is_null($page->function_call) && $page->function_call !== '' &&  function_exists($page->function_call)){
  $this->function_call();
}
      
?>
  </body>
</html>
