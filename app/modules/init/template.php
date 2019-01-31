<?php

/**
 * @file
 * Default Template file for LibreWebTools
 *
 *
 * @category   Themes
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */

header("HTTP/1.1 " . $path->header);
if (!is_null($sub_app) && method_exists($path->app, 'ajax')){
  $sub_app->ajax();
}
?>              
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title><?php echo $path->title; ?></title>
<?php $this->loadScripts();?>
  </head>
  <body>
    <div class="container">
      <div class="content">
        <h1><?php echo $path->title; ?></h1>
<?php 
if (!is_null($path->path_id) && $path->path_id >= 0){
  echo $path->content;
  if (!is_null($sub_app) && method_exists($path->app, 'render')){
    $sub_app->render();
  }
}
else{
  $sub_app->render_404();
}
?>     
      </div>
<?php $sub_app->render_copyright(); ?>
    </div>
  </body>
</html>
