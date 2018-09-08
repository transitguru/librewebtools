<?php
namespace LWT;
/**
 * Table Class
 *
 * reads and writes to the database
 *
 * @category Database Access
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2015-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Table{
  protected $rosetta_stone = array(
    'bool' => array(
      'mysql'  => 'TINYINT', 
      'pgsql'  => 'SMALLINT', 
      'sqlite' => 'INTEGER'
    ),
    
    'smallint' => array(
      'mysql'  => 'SMALLINT', 
      'pgsql'  => 'SMALLINT', 
      'sqlite' => 'INTEGER'
    ),
    
    'int' => array(
      'mysql'  => 'INT', 
      'pgsql'  => 'INT', 
      'sqlite' => 'INTEGER'
    ),

    'bigint' => array(
      'mysql'  => 'INT', 
      'pgsql'  => 'INT', 
      'sqlite' => 'INTEGER'
    ),

    'serial' => array(
      'mysql'  => 'INT PRIMARY KEY AUTO_INCREMENT', 
      'pgsql'  => 'SERIAL', 
      'sqlite' => 'INTEGER PRIMARY KEY AUTO_INCREMENT'
    ),

    'bigserial' => array(
      'mysql'  => 'SERIAL', 
      'pgsql'  => 'BIGSERIAL', 
      'sqlite' => 'INTEGER PRIMARY KEY AUTO_INCREMENT'
    ),

    'fixed' => array(
      'mysql'  => 'NUMERIC', 
      'pgsql'  => 'NUMERIC', 
      'sqlite' => 'NUMERIC'
    ),

    'float' => array(
      'mysql'  => 'FLOAT', 
      'pgsql'  => 'REAL', 
      'sqlite' => 'REAL'
    ),
  
    'double' => array(
      'mysql'  => 'DOUBLE', 
      'pgsql'  => 'DOUBLE PRECISION', 
      'sqlite' => 'REAL'
    ),

    'varchar' => array(
      'mysql'  => 'VARCHAR', 
      'pgsql'  => 'VARCHAR', 
      'sqlite' => 'TEXT'
    ),

    'text' => array(
      'mysql'  => 'TEXT', 
      'pgsql'  => 'TEXT', 
      'sqlite' => 'TEXT'
    ),

    'longtext' => array(
      'mysql'  => 'LONGTEXT', 
      'pgsql'  => 'TEXT', 
      'sqlite' => 'TEXT'
    ),

    'blob' => array(
      'mysql'  => 'BLOB', 
      'pgsql'  => 'BYTEA', 
      'sqlite' => 'BLOB'
    ),

    'longblob' => array(
      'mysql'  => 'LONGBLOB', 
      'pgsql'  => 'BYTEA', 
      'sqlite' => 'BLOB'
    ),

    'datetime' => array(
      'mysql'  => 'DATETIME', 
      'pgsql'  => 'TIMESTAMP', 
      'sqlite' => 'TEXT'
    ),

    'date' => array(
      'mysql'  => 'DATE', 
      'pgsql'  => 'DATE', 
      'sqlite' => 'TEXT'
    ),

    'time' => array(
      'mysql'  => 'TIME', 
      'pgsql'  => 'TIME', 
      'sqlite' => 'TEXT'
    ),

    'timex' => array(
      'mysql'  => 'TIME', 
      'pgsql'  => 'INTERVAL', 
      'sqlite' => 'TEXT'
    ),

  );
}
