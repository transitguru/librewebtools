<?php
namespace LWT;
/**
 * @file
 * Db Class
 *
 * reads and writes to the database
 *
 * @category Database Access
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2015-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Db{
  public $output = array();   /**< Results that would be returned from query */
  public $error = 0;          /**< Error number that would be returned from query */
  public $message = '';       /**< Error or success message */
  public $affected_rows = 0;  /**< Number of affected rows */
  public $insert_id = null;   /**< ID of record that was recently inserted */
  
  /** Types that are recognized by this DB connector */
  protected $types = array( 
    'mysql', 
    'pgsql', 
    'sqlite',
  ); 
  protected $db = null;       /**< DB information for this object */
  protected $pdo = null;      /**< PDO object to use for querying */
  
  /**
   * Create the database object
   * 
   * @param string $name Database name
   */
  public function __construct($src = null){
    $settings = new Settings();
    if ($src == null || $src == $settings->db['name']){
      $this->db = $settings->db;
    }
    elseif(is_array($src) && isset($src['type']) && in_array($src['type'], $this->types)){
      // Be careful with this feature where an array defining db connection can connect
      $this->db = array(
        'name' => null,
        'type' => null,
        'host' => null,
        'user' => null,
        'pass' => null,
        'port' => null,
      );
      if (isset($src['name']) && !is_null($src['name'])){
        $this->db['name'] = $src['name'];
      }
      if (isset($src['type']) && !is_null($src['type'])){
        $this->db['type'] = $src['type'];
      }
      if (isset($src['host']) && !is_null($src['host'])){
        $this->db['host'] = $src['host'];
      }
      if (isset($src['user']) && !is_null($src['user'])){
        $this->db['user'] = $src['user'];
      }
      if (isset($src['pass']) && !is_null($src['pass'])){
        $this->db['pass'] = $src['pass'];
      }
      if (isset($src['port']) && !is_null($src['port'])){
        $this->db['port'] = $src['port'];
      }
    }
    else{
      // Throw an error
      $this->error = 9990;
      $this->message = 'Bad database settings';
    }
    if ($this->error == 0){
      // If set, set host and port
      if($this->db['host'] == null || $this->db['host'] == 'localhost'){
        $host = 'host=127.0.0.1';
      }
      else{
        $host = "host={$this->db['host']}";
      }
      if($this->db['port'] == null){
        $port = '';
      }
      else{
        $port = ";port={$this->db['port']}";
      }
      $this->error = 0;

      // sqlite setup
      if ($this->db['type'] == 'sqlite'){
        $dsn = "sqlite:{$this->db['name']}";
        $username = null;
        $password = null;
        $options = null;
      }
      // MySQL/MariaDB setup
      elseif ($this->db['type'] == 'mysql'){
        $dsn = "mysql:{$host}{$port};dbname={$this->db['name']}";
        $username = $this->db['user'];
        $password = $this->db['pass'];
        $options = null;
      }
      // PostgreSQL setup
      elseif ($this->db['type'] == 'pgsql'){
        $dsn = "pgsql:{$host}{$port};dbname={$this->db['name']};user={$this->db['user']};password={$this->db['pass']}";
        $username = null;
        $password = null;
        $options = null;
      }
      else{
        $this->error = 9990;
        $this->message = 'Unsupported DB type';
      }
      try{
        $this->pdo = new \PDO($dsn, $username, $password, $options);
        if ($this->db['type'] == 'sqlite'){
          $this->pdo->exec("PRAGMA foreign_keys = 1");
        }
        if ($this->db['type'] == 'mysql'){
          $this->pdo->exec("SET sql_mode='ANSI_QUOTES,NO_AUTO_VALUE_ON_ZERO'");
        }
        $this->pdo->exec("SET NAMES utf8");
      }
      catch (\Exception $e){
        $this->error = 9999;
        $this->message = 'Bad database settings';
      }
    }
  }

  /**
   * Simple database Write (uses raw write to do actual db write)
   *
   * @param string $table Table name
   * @param array $inputs Associative array of Inputs
   * @param array $where Associative array of WHERE clause
   */
  public function write($table, $inputs, $where = NULL){
    $fields = array();
    $values = array();
    foreach ($inputs as $field => $value){
      if (fnmatch('*"*', $field)){
        $this->error = 9999;
        $this->message = 'Bad input settings';
        return;
      }
      $type = gettype($value);
      if ($type == 'boolean' || $type == 'integer' || $type == 'double'){
        $values[$field] = $value;
        $fields[] = $field;
      }
      elseif ($type == 'string' && $value !== ''){
        $values[$field] = "'" . str_replace("'", "\\'",str_replace("\\", "\\\\", $value)) . "'";
        $fields[] = $field;
      }
      elseif ($type == 'null' || $value == NULL || $value === ''){
        $values[$field] = 'NULL';
        $fields[] = $field;
      }
      else{
        $this->error = 9999;
        $this->message = 'Bad input settings';
        return;
      }
    }
    if (is_null($where)){
      $field_string = implode( '" , "',$fields);
      $value_string = implode(',', $values);
      $sql = "INSERT INTO \"{$table}\" (\"{$field_string}\") VALUES ({$value_string})";
    }
    else{
      $queries = array();
      foreach ($values as $field => $value){
        $queries[] = "\"{$field}\"=$value";
      }
      $wheres = array();
      foreach ($where as $field => $value){
        if (fnmatch('*"*', $field)){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $type = gettype($value);
        if ($type == 'boolean' || $type == 'integer' || $type == 'double'){
        }
        elseif ($type == 'string' && $value !== ''){
          $value = "'" . str_replace("'", "\\'",str_replace("\\", "\\\\", $value)) . "'";
        }
        elseif ($type == 'null' || $value == NULL || $value === ''){
          $value = 'NULL';
        }
        else{
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $wheres[] = "\"{$field}\"={$value}";
      }
      $sql = "UPDATE \"{$table}\" SET " . implode(" , ",$queries) . " WHERE " . implode(" AND ", $wheres);
    }
    $this->write_raw($sql);
  }

  /**
   * Database Delete (uses raw write to do actual db write)
   *
   * @param string $table Table name
   * @param array $where Associative array of WHERE clause
   */
  public function delete($table, $where = NULL){
    if (is_null($where)){
      $sql = "DELETE FROM \"{$table}\"";
    }
    else{
      $wheres = array();
      foreach ($where as $field => $value){
        if (fnmatch('*"*', $field)){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $type = gettype($value);
        if ($type == 'boolean' || $type == 'integer' || $type == 'double'){
        }
        elseif ($type == 'string' && $value !== ''){
          $value = "'" . str_replace("'", "\\'",str_replace("\\", "\\\\", $value)) . "'";
        }
        elseif ($type == 'null' || $value == NULL || $value === ''){
          $value = 'NULL';
        }
        else{
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $wheres[] = "\"{$field}\"={$value}";
      }
      $sql = "DELETE FROM \"{$table}\" WHERE " . implode(" AND ", $wheres);
    }
    $this->write_raw($sql);
  }
  
  /**
   * Fetches array of table data (uses the raw fetch to do the actual db fetch)
   * 
   * @param string $table Table where info is coming from
   * @param array $fields Fields that are needed from database (if null, all)
   * @param array $where Optional associative array of WHERE ids/values to filter info
   * @param array $groupby Optional GROUP BY variables
   * @param array $sortby Optional SORT BY variables
   * @param string $id Optional field to use as index instead of numeric index
   */
  public function fetch($table, $fields=NULL,  $where=NULL, $groupby=NULL, $sortby=NULL, $id=NULL){
    if (!is_array($fields)){
      $field_string = '*';
    }
    else{
      $field_string = '"' . implode( '" , "',$fields) . '"';
    }
    if (!is_array($where)){
      $where_string = '';
    }
    else{
      $where_elements = array();
      foreach ($where as $key => $value){
        $type = gettype($value);
        if ($type == 'boolean' || $type == 'integer' || $type == 'double'){
          $value = $value;
        }
        elseif ($type == 'string'){
          $value = "'" . str_replace("'", "\\'",str_replace("\\", "\\\\", $value)) . "'";
        }
        elseif ($type == 'null' || $value == NULL){
          $value = NULL;
        }
        else{
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        if (is_null($value)){
          $where_elements[] = "\"{$key}\" IS NULL";
        }
        else{
          $where_elements[] = "\"{$key}\"={$value}";
        }
      }
      $where_string = "WHERE " . implode(' AND ', $where_elements);
    }
    if (!is_array($groupby)){
      $groupby_string = '';
    }
    else{
      $groupby_string = 'GROUP BY "' .implode('" , "' , $groupby). '"';
    }
    if (!is_array($sortby)){
      $sortby_string = '';
    }
    else{
      $sortby_string = 'ORDER BY "' . implode('" , "', $sortby) . '"';
    }
    $sql = "SELECT {$field_string} FROM \"{$table}\" {$where_string} {$groupby_string} {$sortby_string}";
    $this->fetch_raw($sql, $id);
  }

  /**
   * Raw database Write
   * 
   * @param string $sql Raw SQL Query
   */
  public function write_raw($sql){
    if (!is_null($this->pdo)){
      $this->affected_rows = $this->pdo->exec($sql);
      if ($this->pdo->errorCode() > 0){
        $this->error = $this->pdo->errorCode();
        $msg = $this->pdo->errorInfo()[2];
        $this->message = "Error: {$this->error} ({$msg})";
      }
      else{
        $this->error = 0;
        $this->message = 'Records successfully written';
      }
      $this->insert_id = $this->pdo->lastInsertId();
    }
  }

  /**
   * Raw Database fetch: creating an array of table data
   * 
   * @param string $sql raw query to send to the database
   * @param string $id Optional field to use as index instead of numeric index
   */
  public function fetch_raw($sql, $id=NULL){
    if (!is_null($this->pdo)){
      $query = $this->pdo->query($sql);
      $this->output = array();
      if (!$query){
        $this->error = -99;
        $msg = "Some undefined error occurred";
        $this->message = "Error: {$this->error} ({$msg})";
        $this->affected_rows = 0;
      }
      elseif ($query->errorCode() > 0){
        $this->error = $query->errorCode();
        $msg = $query->errorInfo()[2];
        $this->message = "Error: {$this->error} ({$msg})";
        $this->affected_rows = 0;

      }
      else{
        $this->error = 0;
        $this->message = 'Records successfully fetched';
        while($fetch = $query->fetch(\PDO::FETCH_ASSOC)){
          if (!is_null($id) && key_exists($id, $fetch)){
            $out_id = $fetch[$id];
            $this->output[$out_id] = $fetch;
          }
          else{
            $this->output[] = $fetch;
          }
        }
        $this->affected_rows = count($this->output);
      }
    }
  }
}

