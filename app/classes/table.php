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

  /** Defines data types for the database engines
   *  Keys of associative array map to LWT datatypes and contain plain arrays
   *  [0] for mysql, [1] for pgsql, [2] for sqlite
   */
  protected $rosetta_stone = array(
    'bool' => [ 'TINYINT', 'SMALLINT', 'INTEGER'],
    'smallint' => [ 'SMALLINT', 'SMALLINT', 'INTEGER'],
    'int' => [ 'INT', 'INT', 'INTEGER'],
    'bigint' => [ 'INT', 'INT', 'INTEGER'],
    'serial' => [ 'INT PRIMARY KEY AUTO_INCREMENT', 'SERIAL', 'INTEGER PRIMARY KEY AUTO_INCREMENT'],
    'bigserial' => [ 'SERIAL', 'BIGSERIAL', 'INTEGER PRIMARY KEY AUTO_INCREMENT'],
    'fixed' => [ 'NUMERIC', 'NUMERIC', 'NUMERIC'],
    'float' => [ 'FLOAT', 'REAL', 'REAL'],
    'double' => [ 'DOUBLE', 'DOUBLE PRECISION', 'REAL'],
    'varchar' => [ 'VARCHAR', 'VARCHAR', 'TEXT'],
    'text' => [ 'TEXT', 'TEXT', 'TEXT'],
    'longtext' => [ 'LONGTEXT', 'TEXT', 'TEXT'],
    'blob' => [ 'BLOB', 'BYTEA', 'BLOB'],
    'longblob' => [ 'LONGBLOB', 'BYTEA', 'BLOB'],
    'datetime' => [ 'DATETIME', 'TIMESTAMP', 'TEXT'],
    'date' => [ 'DATE', 'DATE', 'TEXT'],
    'time' => [ 'TIME', 'TIME', 'TEXT'],
    'timex' => [ 'TIME', 'INTERVAL', 'TEXT']
  );

}
