<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * 
 * This object loads default site settings
 */
class Settings{
  public $host = 'localhost';       /**< The host for the database connection */
  public $type = 'mysql';           /**< Type of database */
  public $name = 'librewebtools';   /**< The database name for the application's data */
  public $user = 'lwt';             /**< The username for the application's database user */
  public $pass = 'LibreW38t00ls';   /**< The password for the application's database user (It is recommended to change this from the default!!! */
  public $port = 3306;              /**< The port for the database connection */
  
  public function __construct(){
    // For now, the object justs puts everything out there as constants
    define('DB_HOST', $this->host);
    define('DB_TYPE', $this->type);
    define('DB_NAME', $this->name);
    define('DB_USER', $this->user);
    define('DB_PASS', $this->pass);
    define('DB_PORT', $this->port);
  }

}
