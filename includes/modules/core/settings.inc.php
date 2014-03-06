<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Provides the minimal necessary site settings
 */

function lwt_settings_load(){
  define('DB_NAME', 'librewebtools'); /**< The value should be the same as line 14 on ../sql/database.sql */
  define('DB_USER', 'lwt');           /**< The value should be the same as line 18 (before @'localhost') on ../sql/database.sql */
  define('DB_PASS', 'LibreW38t00ls'); /**< The value should be the same as line 18 (after the PASSWORD */
  return TRUE;
}
  
