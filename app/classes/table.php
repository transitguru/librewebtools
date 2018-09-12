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

  protected $constraint_types = array(
    'foreign' => 'FOREIGN KEY',
    'unique'  => 'UNIQUE',
    'primary' => 'PRIMARY KEY'
  );
  protected $fk_actions = array(
    'cascade'   => 'CASCADE',
    'null'      => 'SET NULL',
    'default'   => 'SET DEFAULT',
    'restrict'  => 'RESTRICT',
    'no'        => 'NO ACTION'
  );

  protected $name = '';         /**< Table name */
  protected $comment = '';      /**< Table comment */
  protected $columns = [];      /**< Array of columns */
  protected $constraints = [];  /**< Array of constraints (keys and such) */


  public $mysql = '';    /**< MySQL/MariaDB version of create table instruction */
  public $pgsql = '';    /**< PostgreSQL version of create table instruction */
  public $sqlite = '';   /**< SQLite version of create table instruction */
  public $error = 0;     /**< Error number that would be returned from operation */
  public $message = '';  /**< Error or success message */

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
    if (is_array($table_defs) && !is_null($table_defs['name']) && !fnmatch('*"*', $table_defs['name'])){
      $this->name = $table_defs['name'];
    }
    else{
      $this->error = 9999;
      $this->message = 'Name is missing or undefined';
      return;
    }

    if (!is_null($table_defs['comment'])){
      $this->comment = $table_defs['comment'];
    }

    if (is_array($table_defs['columns']) && count($table_defs['columns']) > 0){
      foreach($table_defs['columns'] as $column){
        $col_spec = array(
          'name' => null,
          'type' => null,
          'size' => null,
          'scale' => null,
          'null' => null,
          'default' => null,
          'comment' => null
        ); 
        if (is_array($column)){
          if (isset($column['name']) && !is_null($column['name']) && !fnmatch('*"*', $column['name']) && $column['name'] != ''){
            $col_spec['name'] = $column['name'];
          }
          if (isset($column['type']) && array_key_exists($column['type'],$this->rosetta_stone)){
            $col_spec['type'] = $column['type'];
          }
          if (isset($column['size']) && is_numeric($column['size'])){
            $col_spec['size'] = $column['size'];
          }
          if (isset($column['scale']) && is_numeric($column['scale'])){
            $col_spec['scale'] = $column['scale'];
          }
          if (isset($column['null']) && $column['null'] == true){
            $col_spec['null'] = true;
          }
          if (isset($column['default'])){
            $col_spec['default'] = $column['default'];
          }
          if (isset($column['comment']) && $column['comment'] != ''){
            $col_spec['comment'] = $column['comment'];
          }
          if (!is_null($col_spec['name']) && !is_null($col_spec['type'])){
            $this->columns[] = $col_spec;
          }
        }
      }
    }

    if (is_array($table_defs['constraints']) && count($table_defs['constraints']) > 0){
      foreach($table_defs['constraints'] as $constraint){
        $con_spec = array(
          'name' => null,
          'type' => null,
          'columns' => array(),
          'ref_table' => null,
          'ref_columns' => array(),
          'delete' => null,
          'update' => null
        ); 
        if (is_array($constraint)){
          if (isset($constraint['name']) && !is_null($constraint['name']) && !fnmatch('*"*', $constraint['name']) && $constraint['name'] != ''){
            $con_spec['name'] = $constraint['name'];
          }
          if (isset($constraint['type']) && array_key_exists($constraint['type'],$this->constraint_types)){
            $con_spec['type'] = $constraint['type'];
          }
          if (is_array($constraint['columns']) && count($constraint['columns']) > 0){
            $fk_cols = array();
            foreach($constraint['columns'] as $col){
              if (!is_null($col) && !fnmatch('*"*', $col) && $col != ''){
                $fk_cols[] = $col;
              }
            }
            $con_spec['columns'] = $fk_cols;
          }
          if (isset($constraint['ref_table']) && !is_null($constraint['ref_table']) && !fnmatch('*"*', $constraint['ref_table']) && $constraint['ref_table'] != ''){
            $con_spec['ref_table'] = $constraint['ref_table'];
          }
          if (isset($constraint['ref_table']) && is_array($constraint['ref_columns']) && count($constraint['ref_columns']) > 0){
            $ref_cols = array();
            foreach($constraint['ref_columns'] as $rcol){
              if (!is_null($rcol) && !fnmatch('*"*', $rcol) && $rcol != ''){
                $ref_cols[] = $col;
              }
            }
            $con_spec['ref_columns'] = $ref_cols;
          }
          if (isset($constraint['delete']) && array_key_exists($constraint['delete'],$this->fk_actions)){
            $con_spec['delete'] = $con_spec['default'];
          }
          if (isset($constraint['update']) && array_key_exists($constraint['update'],$this->fk_actions)){
            $con_spec['update'] = $con_spec['update'];
          }
          if (!is_null($col_spec['name']) && !is_null($col_spec['type']) && count($con_spec['columns']) > 0){
            $this->constraints[] = $con_spec;
          }
        }
      }
    }
  }

}
