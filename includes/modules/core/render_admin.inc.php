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
}

function lwt_render_admin_content(){
  echo APP_ROOT . "<br />";
  echo 'Content Admin Page!';
}
