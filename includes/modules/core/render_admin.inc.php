<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders pages that are usually only seen by an admin
 */

function lwt_ajax_admin_users(){
  if (isset($_POST) && $_POST['ajax'] == 1){
    echo '<pre>';
    var_dump($_POST);
    echo '</pre>';
    exit;
  }
}

function lwt_ajax_admin_content(){
  if (isset($_POST) && $_POST['ajax'] == 1){
    echo '<pre>';
    var_dump($_POST);
    echo '</pre>';
    exit;
  }
}


function lwt_render_admin_users(){
  echo APP_ROOT . "<br />";
  echo 'User Admin Page!';
?>
  <div id="testarea"></div>
  <form action="" enctype="multipart/form-data" method="post" id="poster" onsubmit="event.preventDefault(); ajaxPost(this,'testarea','');"> 
    <input type="hidden" value="1" name="ajax" />
    <input type="text" name="text" />
    <input type="submit" name="submit" value="submit" />
  </form>
<?php
}

function lwt_render_admin_content(){
  echo APP_ROOT . "<br />";
  echo 'Content Admin Page!';
}
