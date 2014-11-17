<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Provides template structure and css rendering
 */

/**
 * Renders the template structure of the page
 * 
 * @param string $request Request URI that will determine title and content
 * @return boolean  Successful completion
 */
function core_render_wrapper($request){
  $output = core_process_title($request);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>LibreWebTools - <?php echo $output['title']; ?></title>
    <!-- Stylesheets -->
    <link rel="stylesheet" type="text/css" href="/css/style.css" />
        
    <!-- Scripts -->
    <script type="text/javascript" src="/js/script.js"></script>
        
  </head>
  <body>
    <div class="container">
      <div class="content">
        <h1><?php echo $output['title']; ?></h1>
<?php 
  if ($output['access']){
    core_process_content($request, $output['page_id']);
    $render_call = $output['render_call'];
    if (!is_null($render_call) && function_exists($render_call)){
      $render_call();
    }
  }
  else{
    core_render_404();
  }
?>
      </div>
<?php core_render_copyright();?>
    </div>
  </body>
</html>
<?php
  return TRUE;  
}

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
/**
 * Renders the contact us form
 * 
 * @return boolean Successful completion
 */

function core_render_contact(){
  
}
