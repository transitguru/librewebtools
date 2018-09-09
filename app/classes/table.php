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
    'serial' => [ 'INT PRIMARY KEY AUTO_INCREMENT', 'SERIAL', 'INTEGER PRIMARY KEY AUTOINCREMENT'],
    'bigserial' => [ 'SERIAL', 'BIGSERIAL', 'INTEGER PRIMARY KEY AUTOINCREMENT'],
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

  /**
   * Create the table object
   *
   * @param array $table_defs Table definitions in the form of the array below
   *
   * @code
   *   $table_defs = array(
   *     'name' => 'table_name',
   *     'comment' => 'Comment to include with the table',
   *     'columns' => array(
   *       [0] => array(
   *         'name' => 'column_name',
   *         'type' => 'varchar', //type in $rosetta_stone
   *         'size' => 6,
   *         'scale' => 2,        //used only with numeric for decimal scale
   *         'null' => false,
   *         'default' => 'default_value',
   *         'comment' => 'Comment to include with the column'
   *       ),
   *     ),
   *     'constraints' => array(
   *       [0] => array(
   *         'name' => 'constraint_name',
   *         'type' => 'unique', // unique, primary, or foreign
   *         'columns' => ['column1', 'column2'],
   *         'ref_table' => 'foreign_key_referenced_table',
   *         'ref_columns' => ['fk_ref_col1', 'fk_ref_col2'],
   *         'delete' => 'cascade', // Use cascade, null, default, restrict, no
   *         'update' => 'cascade'  // Use cascade, null, default, restrict, no
   *       )
   *     )
   *   );
   * @endcode
   */
  public function __construct($table_defs = array()){
  }

}
