<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders pages that are usually only seen by an admin
 */

/**
 * Processes any AJAX request for the Admin application
 * 
 * @param boolean $wrapper Optional field designated that it was called from the wrapper 
 * 
 * @return void
 * 
 */
function core_ajax_admin($wrapper = false){
  if (!$wrapper){
    header('Cache-Control: no-cache');
  }
  // Do admin stuff
  echo "Admin page coming soon! ...";
  
  //exit if this was not a "wrapper" (i.e. not using AJAX)
  if (!$wrapper){
    exit;
  }
 
}
 
 /**
 * Renders the Admin user page when loading the site within wrapper
 * 
 * 
 * @return boolean Successful rendering of page
 * 
 */
function core_render_admin(){
  //Reset admin navigation if POST not set
  if (!isset($_POST) || count($_POST)==0){
    $_SESSION['admin']['navigate'] = array();
    $_POST['ajax'] = 1;
    $_POST['command'] = 'navigate';
  }
  
  //Render application in preparation for making ajax content
?>
  <div id="adminarea">
<?php core_ajax_admin(true); ?>
  </div>
<?php
  return TRUE;
}

