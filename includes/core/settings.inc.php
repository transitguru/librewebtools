<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Provides the minimal necessary site settings
 */

/**
 * Sets up global constants for site settings
 *
 *
 */
function core_settings(){
  define('DB_HOST', 'localhost');     /**< The host for the database connection */
  define('DB_NAME', 'librewebtools'); /**< The database name for the application's data */
  define('DB_USER', 'lwt');           /**< The username for the application's database user */
  define('DB_PASS', 'LibreW38t00ls'); /**< The password for the application's database user (It is recommended to change this from the default!!! */
  define('DB_PORT', 3306);            /**< The port for the database connection */
  return TRUE;
}

