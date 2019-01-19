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
    'serial' => [ 'INT AUTO_INCREMENT', 'SERIAL', 'INTEGER'],
    'bigserial' => [ 'SERIAL', 'BIGSERIAL', 'INTEGER'],
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
    'timex' => [ 'TIME', 'INTERVAL', 'TEXT'],
  );

  /** Defines numeric columns (allowing no quotes for inserting */
  protected $numerics = array(
    'bool',
    'smallint',
    'int',
    'bigint',
    'serial',
    'bigserial',
    'fixed',
    'float',
    'double',
  );

  /** Defines types of supported constraints */
  protected $constraint_types = array(
    'foreign' => 'FOREIGN KEY',
    'unique'  => 'UNIQUE',
    'primary' => 'PRIMARY KEY',
  );

  /** Defines Foreign Key actions */
  protected $fk_actions = array(
    'cascade'   => 'CASCADE',
    'null'      => 'SET NULL',
    'default'   => 'SET DEFAULT',
    'restrict'  => 'RESTRICT',
    'no'        => 'NO ACTION',
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
   * Create the table object by calling the create sql method
   *
   * @param array $table_defs Table definitions (see create_sql method)
   *
   */
  public function __construct($table_defs = array()){
    $this->create_sql($table_defs);
  }

  /**
   * Create the sql statement based on table definitions
   *
   * @param array $table_defs Table definitions in the form of the object below
   *
   * @code
   *   $table_defs = (object) [
   *     'name' => 'table_name',
   *     'comment' => 'Comment to include with the table',
   *     'columns' => [
   *       (object) [
   *         'name' => 'column_name',
   *         'type' => 'varchar', //type in $rosetta_stone
   *         'size' => 6,
   *         'scale' => 2,        //used only with numeric for decimal scale
   *         'null' => false,
   *         'default' => 'default_value',
   *         'comment' => 'Comment to include with the column',
   *       ],
   *     ],
   *     'constraints' => [
   *       (object) [
   *         'name' => 'constraint_name',
   *         'type' => 'unique', // unique, primary, or foreign
   *         'columns' => ['column1', 'column2'],
   *         'ref_table' => 'foreign_key_referenced_table',
   *         'ref_columns' => ['fk_ref_col1', 'fk_ref_col2'],
   *         'delete' => 'cascade', // Use cascade, null, default, restrict, no
   *         'update' => 'cascade', // Use cascade, null, default, restrict, no
   *       ]
   *     ]
   *   ];
   * @endcode
   */
  public function create_sql($table_defs = array()){
    if (is_object($table_defs) && isset($table_defs->name) && !fnmatch('*"*', $table_defs->name)){
      $this->name = $table_defs->name;
    }
    else{
      $this->error = 1;
      $this->message = 'Table name is missing or malformed';
      return;
    }

    /** Drop statment that appears before the table creation */
    $drop_stmt = 'DROP TABLE IF EXISTS "'. $this->name . '";';

    /** Statement that follows MariaDB/MySQL rules */
    $mysql_stmt = $drop_stmt . "\n\n" . 'CREATE TABLE IF NOT EXISTS "' . $this->name . '"' . "(";

    /** Statement that follows PostgreSQL rules */
    $pgsql_stmt = $drop_stmt . "\n\n" . 'CREATE TABLE IF NOT EXISTS "' . $this->name . '"' . "(";

    /** Statement that follows SQLite rules */
    $sqlite_stmt = $drop_stmt . "\n\n" . 'CREATE TABLE IF NOT EXISTS "' . $this->name . '"' . "(";

    // Create temporary fragments for merging into the table creation SQL
    $m_frag = [];
    $p_frag = [];
    $c_frag = [];

    // Postgres needs individual statements for comments
    $p_comm = '';

    if (isset($table_defs->comment)){
      $this->comment = $table_defs->comment;
    }

    if (is_array($table_defs->columns) && count($table_defs->columns) > 0){
      foreach($table_defs->columns as $column){
        /** Column spec object to push into columns property */
        $col_spec = (object) [
          'name' => null,
          'type' => null,
          'size' => null,
          'scale' => null,
          'null' => true,
          'default' => null,
          'comment' => null
        ];

        //Create statements for this column
        $m_st = '';
        $p_st = '';
        $s_st = '';

        if (is_object($column)){
          if (isset($column->name) && !fnmatch('*"*', $column->name) && $column->name != ''){
            $col_spec->name = $column->name;
            $m_st = '"' . $column->name . '" ';
            $p_st = '"' . $column->name . '" ';
            $s_st = '"' . $column->name . '" ';
          }
          if (isset($column->type) && array_key_exists($column->type,$this->rosetta_stone)){
            $col_spec->type = $column->type;
            $m_st .= $this->rosetta_stone["{$col_spec->type}"][0];
            $p_st .= $this->rosetta_stone["{$col_spec->type}"][1];
            $s_st .= $this->rosetta_stone["{$col_spec->type}"][2];
          }
          if (isset($column->size) && is_numeric($column->size)){
            $col_spec->size = $column->size;
            $m_st .= '(' . $col_spec->size;
            $p_st .= '(' . $col_spec->size;
            $s_st .= '(' . $col_spec->size;
            if (isset($column->scale) && is_numeric($column->scale) && $column->type == 'fixed'){
              $col_spec->scale = $column->scale;
              $m_st .= ',' . $col_spec->scale;
              $p_st .= ',' . $col_spec->scale;
              $s_st .= ',' . $col_spec->scale;
            }
            $m_st .= ')';
            $p_st .= ')';
            $s_st .= ')';
          }
          if (isset($column->null) && $column->null == false){
            $col_spec->null = false;
            $m_st .= ' NOT NULL';
            $p_st .= ' NOT NULL';
            $s_st .= ' NOT NULL';
          }
          else{
            $col_spec->null = true;
          }
          if (isset($column->default)){
            $col_spec->default = $column->default;
            if (in_array($column->type, $this->numerics)){
              $m_st .= " DEFAULT " . $col_spec->default;
              $p_st .= " DEFAULT " . $col_spec->default;
              $s_st .= " DEFAULT " . $col_spec->default;
            }
            else{
              $m_st .= " DEFAULT '" . $col_spec->default . "'";
              $p_st .= " DEFAULT '" . $col_spec->default . "'";
              $s_st .= " DEFAULT '" . $col_spec->default . "'";

            }
          }
          if (isset($column->comment) && $column->comment != ''){
            $col_spec->comment = $column->comment;
            $m_st .= " COMMENT '" . $col_spec->comment . "'";
            $p_comm .= ' COMMENT ON COLUMN "' . $this->name . '"."' . $col_spec->name . '" IS \'' . $col_spec->comment . "'; \n";
            $s_st .= " --" . $col_spec->comment;
          }

          if (!is_null($col_spec->name) && !is_null($col_spec->type)){
            //Put col_spec into array
            $this->columns[] = $col_spec;

            //Build statement arrays
            $m_frag[] = $m_st;
            $p_frag[] = $p_st;
            $s_frag[] = $s_st;
          }
        }
      }
    }
    else{
      $this->error = 2;
      $this->message = 'The table has no columns';
      return;
    }

    if (is_array($table_defs->constraints) && count($table_defs->constraints) > 0){
      foreach($table_defs->constraints as $constraint){
        /** Constraint spec object to push into constraints property */
        $con_spec = (object) [
          'name' => null,
          'type' => null,
          'columns' => array(),
          'ref_table' => null,
          'ref_columns' => array(),
          'delete' => null,
          'update' => null
        ];

        //Create statements for this constraint
        $c_st = '';

        if (is_object($constraint)){
          if (isset($constraint->name) && !fnmatch('*"*', $constraint->name) && $constraint->name != ''){
            $con_spec->name = $constraint->name;
            $c_st = 'CONSTRAINT "' . $con_spec->name . '"';
          }
          if (isset($constraint->type) && array_key_exists($constraint->type,$this->constraint_types)){
            $con_spec->type = $constraint->type;
            $c_st .= ' ' . $this->constraint_types["{$con_spec->type}"];
          }
          if (is_array($constraint->columns) && count($constraint->columns) > 0){
            $fk_cols = array();
            foreach($constraint->columns as $col){
              if (!is_null($col) && !fnmatch('*"*', $col) && $col != ''){
                $fk_cols[] = $col;
              }
            }
            $con_spec->columns = $fk_cols;
            $c_st .= ' ( "' .implode('" , "', $con_spec->columns) . '" )';
          }
          if (isset($constraint->ref_table) && !fnmatch('*"*', $constraint->ref_table) && $constraint->ref_table != ''){
            $con_spec->ref_table = $constraint->ref_table;
            $c_st .= ' REFERENCES "' . $con_spec->ref_table . '"';
          }
          if (isset($constraint->ref_table) && is_array($constraint->ref_columns) && count($constraint->ref_columns) > 0){
            $ref_cols = array();
            foreach($constraint->ref_columns as $rcol){
              if (!is_null($rcol) && !fnmatch('*"*', $rcol) && $rcol != ''){
                $ref_cols[] = $rcol;
              }
            }
            $con_spec->ref_columns = $ref_cols;
            $c_st .= ' ( "' .implode('" , "', $con_spec->ref_columns) . '" )';
          }
          if (isset($constraint->delete) && array_key_exists($constraint->delete,$this->fk_actions)){
            $con_spec->delete = $constraint->delete;
            $c_st .= ' ON DELETE ' . $this->fk_actions["{$con_spec->delete}"];
          }
          if (isset($constraint->update) && array_key_exists($constraint->update,$this->fk_actions)){
            $con_spec->update = $constraint->update;
            $c_st .= ' ON UPDATE ' . $this->fk_actions["{$con_spec->update}"];
          }
          if (!is_null($con_spec->name) && !is_null($con_spec->type) && count($con_spec->columns) > 0){
            $this->constraints[] = $con_spec;

            //Build statement arrays
            $m_frag[] = $c_st;
            $p_frag[] = $c_st;
            $s_frag[] = $c_st;
          }
        }
      }
    }
    //build sql statements here
    $mysql_stmt .= "\n  " . implode("\n  ,",$m_frag) . "\n) ENGINE InnoDB";
    $pgsql_stmt .= "\n  " . implode("\n  ,",$p_frag) . "\n)";
    $sqlite_stmt .= "\n  " . implode("\n  ,",$s_frag) . "\n)";

    if (!is_null($this->comment)){
      $mysql_stmt .= " COMMENT '" . $this->comment . "'";
      $p_comm .= "\n" . ' COMMENT ON TABLE "' . $this->name . '" IS \'' . $this->comment . "';\n";
      $sqlite_stmt .= " --" . $this->comment . "";
    }

    $this->mysql = $mysql_stmt . "\n;";
    $this->pgsql = $pgsql_stmt . "\n;\n" . $p_comm;
    $this->sqlite = $sqlite_stmt . "\n;";
    $this->error = 0;
    $this->message = 'SQL successfully created';
  }

}
