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
  public $output = null;      /**< Results that would be returned from query */
  public $error = 0;          /**< Error number that would be returned from query */
  public $message = '';       /**< Error or success message */
  public $affected_rows = 0;  /**< Number of affected rows */
  public $insert_id = null;   /**< ID of record that was recently inserted */

  /** Types that are recognized by this DB connector */
  protected $types = ['mysql','pgsql','sqlite'];
  /** Commands that are recognized by this DB connector */
  protected $commands = ['select','insert','delete', 'update'];
  /** Types of comparisons that are allowed */
  protected $comparisons = ['<>', '<', '<=', '>=', '=', '%'];
  /** Types of groups that are seen in a WHERE clause */
  protected $group_types = ['and', 'or'];
  protected $db = null;       /**< DB information for this object */
  protected $pdo = null;      /**< PDO object to use for querying */

  /**
   * Create the database object
   *
   * @param object $src Database source if overriding the settings
   */
  public function __construct($src = null){
    $settings = new Settings();
    if ($src == null || $src == $settings->db->name){
      $this->db = $settings->db;
    }
    elseif(is_object($src) && isset($src->type) && in_array($src->type, $this->types)){
      // Be careful with this feature where an array defining db connection can connect
      $this->db = (object)[
        'name' => null,
        'type' => null,
        'host' => null,
        'user' => null,
        'pass' => null,
        'port' => null,
      ];
      $this->db->type = $src->type;
      if (isset($src->name)){
        $this->db->name = $src->name;
      }
      if (isset($src->host)){
        $this->db->host = $src->host;
      }
      if (isset($src->user)){
        $this->db->user = $src->user;
      }
      if (isset($src->pass)){
        $this->db->pass = $src->pass;
      }
      if (isset($src->port)){
        $this->db->port = $src->port;
      }
    }
    else{
      // Throw an error
      $this->error = 9990;
      $this->message = 'Bad database settings';
    }
    if ($this->error == 0){
      // If set, set host and port
      if($this->db->host == null || $this->db->host == 'localhost'){
        $host = 'host=127.0.0.1';
      }
      else{
        $host = "host={$this->db->host}";
      }
      if($this->db->port == null){
        $port = '';
      }
      else{
        $port = ";port={$this->db->port}";
      }
      $this->error = 0;

      $username = null;
      $password = null;
      $options = null;

      // sqlite setup
      if ($this->db->type == 'sqlite'){
        $dsn = "sqlite:{$this->db->name}";
      }
      // MySQL/MariaDB setup
      elseif ($this->db->type == 'mysql'){
        $dsn = "mysql:{$host}{$port};dbname={$this->db->name}";
        $username = $this->db->user;
        $password = $this->db->pass;
      }
      // PostgreSQL setup
      elseif ($this->db->type == 'pgsql'){
        $dsn = "pgsql:{$host}{$port};dbname={$this->db->name};user={$this->db->user};password={$this->db->pass}";
      }
      else{
        $this->error = 9990;
        $this->message = 'Unsupported DB type';
      }
      try{
        $this->pdo = new \PDO($dsn, $username, $password, $options);
        $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        if ($this->db->type == 'sqlite'){
          $this->pdo->exec("PRAGMA foreign_keys = 1");
        }
        if ($this->db->type == 'mysql'){
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
   * Database query builder using an object that defines the query
   *
   * @param object $query Object defining the query as shown below
   *
   * @code
   *   $query = (object)[
   *     'command' => 'select',   // select, delete, insert, update
   *     'table' => 'sometable',  // Table being worked on
   *     'fields' => [            // Fields to select (empty, null, or missing means *)
   *       'col_1',
   *       'col_2',
   *       'col_3',
   *     ],
   *     'inputs' => (object)[    // Fields that would be set for insert or update
   *       'col_1' => 45,
   *       'col_2' => 'hello there',
   *       'col_3' => 'Some Text',
   *     ],
   *     'where' => (object)[     // Where clause for select, delete, update
   *       'type' => 'and',       // Use lowercase for AND, OR, etc
   *       'items' => [
   *         (object) ['id' => 'col1', 'value' => 90, 'type' => '<'],
   *         (object) ['id' => 'col2', 'value' => 'hello', 'type' => '%', 'cs' => false],
   *         (object) ['type' => 'or', 'items' => [] ],
   *       ],
   *     ],
   *     'group' => [             // group by clause for select
   *       'col_1',
   *       'col_2',
   *       'col_3',
   *     ],
   *     'sort' => [      // order by clause for select
   *       (object)['id' => 'col_1', 'dir' => 'a', 'cs' => true], //ascending, case sensitive
   *       (object)['id' => 'col_2', 'dir' => 'd', 'cs' => false], //desc, not case sensitive
   *       (object)['id' => 'col_3', 'dir' => 'd'],              // case sensitive
   *     ],
   *   ];
   * @endcode
   */
  public function query($query){
    // Make sure we can actually do something
    if (!is_object($query) || !isset($query->table)){
      $this->error = 1;
      $this->message = 'Malformed query';
      return;
    }

    // Set command
    $sql = '';
    if(isset($query->command) && in_array($query->command, $this->commands)){
      $cmd = $query->command;
    }
    else{
      $cmd = 'select';
    }

    // Set table
    $table = $this->convert_to_sql($query->table, true);
    if (!$table){
      $this->error = 9999;
      $this->message = 'Bad input settings';
      return;
    }

    // fields (select)
    if ($cmd == 'select'){
      $sql = 'SELECT ';
      $selects = [];
      if(isset($query->fields) && is_array($query->fields) && count($query->fields)>0){
        foreach($query->fields as $field){
          $f = $this->convert_to_sql($field, true);
          if ($f === false){
            $this->error = 9999;
            $this->message = 'Bad input settings';
            return;
          }
          $selects[]= $f;
        }
        $sql .= implode(', ',$selects) . ' ';
      }
      else{
        $sql .= '* ';
      }
      $sql .= 'FROM ' . $table . ' ';
    }

    // inputs (update, insert)
    if (isset($query->inputs) && is_object($query->inputs) && in_array($cmd, ['update','delete'])){
      $values = [];
      $fields = [];
      $queries = [];
      foreach($query->inputs as $field => $value){
        $f = $this->convert_to_sql($field,true);
        $v = $this->convert_to_sql($value);
        if ($f === false || $v === false){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $values[]= $v;
        $fields[]= $f;
        $queries[]= $f . ' = ' . $v . ' ';
      }
      if ($cmd == 'insert'){
        $value_string = implode(', ', $values);
        $field_string = implode(', ', $fields);
        $sql = 'INSERT INTO ' . $table . ' ';
        $sql .= '(' . $field_string . ') VALUES (' . $value_string . ') ';
      }
      elseif($cmd == 'update'){
        $query_string = implode(', ', $queries);
        $sql = 'UPDATE ' . $table . ' SET ' . $query_string . ' ';
      }
    }

    // Delete command
    if ($cmd == 'delete'){
      $sql = 'DELETE FROM ' . $table . ' ';
    }

    // where (select, update, delete)
    if (isset($query->where) && is_object($query->where) && in_array($cmd, ['select','update','delete'])){
      $string = $this->process_where($query->where);
      if ($string == false){
        $this->error = 9999;
        $this->message = 'Bad input settings';
        return;
      }
      $sql .= ' WHERE ' . $string;
    }

    // group (select)
    if (isset($query->group) && is_array($query->group) && $cmd == 'select'){
      $groups = [];
      foreach ($query->group as $group){
        $g = $this->convert_to_sql($group, true);
        if ($g === false){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $groups[]= $g;
      }
      $query_string = implode(' , ', $qroups);
      $sql .= 'GROUP BY ' . $query_string . ' ';
    }

    // sort (select)
    if (isset($query->sort) && is_array($query->sort) && $cmd == 'select'){
      $sorts = [];
      foreach ($query->sort as $field){
        if (!isset($field->id)){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $f = $this->convert_to_sql($field->id, true);
        if ($f === false){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return;
        }
        $d = 'ASC';
        if (isset($field->dir) && $field->dir == 'd'){
          $d = 'DESC';
        }
        if (isset($field->cs) && $field->cs == false){
          $f = 'lower(' . $f . ')';
        }
        $sorts[]= $f . ' ' . $d;
      }
      $sort_string = implode(' , ' , $sorts);
      $sql .= 'ORDER BY ' . $sort_string . ' ';
    }
    if ($cmd == 'select'){
      $this->fetch_raw($sql);
    }
    else{
      $this->write_raw($sql);
    }
  }

  /**
   * Sanitizes input for writing into database and adds appropriate quotes
   *
   * @param mixed $value Value to be sanitized
   * @param boolean $token Set to true if an SQL token such as table name
   *
   * @return mixed $output Sanitized output
   */
  private function convert_to_sql($value, $token = false){
    $output = false;

    if (!fnmatch('*"*', $value) && $token == true){
      $output = '"' . $value . '"';
    }
    elseif(!$token){
      $type = gettype($value);
      if($value === true){
        $output = 'TRUE';
      }
      elseif($value === false){
        $output = 'FALSE';
      }
      elseif($type == 'integer' || $type == 'double'){
        $output = $value;
      }
      elseif($type == 'string'){
        $clean = str_replace("\\", "\\\\", $value);
        $clean = str_replace("'", "\\'",$clean);
        $output = "'" . $clean . "'";
      }
      elseif($type == 'null' || $value == null){
        $output = 'NULL';
      }
    }
    return $output;
  }

  /**
   * Processes WHERE clause group
   *
   * @param object $where type and array of group of WHERE statements and/or groups
   *
   * @return string $sql SQL statement string of where clause
   */
  private function process_where($where){
    if (isset($where->type) && in_array($where->type, $this->group_types)){
      $glue = ' ' . mb_strtoupper($where->type) . ' ';
    }
    elseif (isset($where->items)){
      $glue = ' AND ';
    }
    if (isset($where->items) && is_array($where->items)){
      $queries = [];
      $sql = '';
      foreach ($where->items as $field){
        if (property_exists($field, 'value')){
          if(!isset($field->id)){
            return false;
          }
          $f = $this->convert_to_sql($field->id,true);
          $v = $this->convert_to_sql($field->value);
          $eq = '=';
          $cs = true;
          if (isset($field->type)){
            if($field->type == '%'){
              $eq = 'LIKE';
            }
            elseif(in_array($field->type,$this->comparisons)){
              $eq = $field->type;
            }
          }
          if (isset($field->cs) && $field->cs == false){
            $cs = false;
          }
          if ($f === false || $v === false){
            return false;
          }
          if ($cs == false){
            $f = 'lower(' . $f . ')';
            $v = mb_strtolower($v);
          }
          $queries[]= $f . ' ' . $eq . ' ' . $v;
        }
        else{
          $string = $this->process_where($field);
          if ($string == false){
            return false;
          }
          $queries[]= '( ' . $string . ' )';
        }
        $sql .= implode($glue, $queries);
      }
      return $sql;
    }
  }

  /**
   * Raw database Write
   *
   * @param string $sql Raw SQL Query
   */
  public function write_raw($sql){
    if (!is_null($this->pdo)){
      $this->affected_rows = $this->pdo->exec($sql);
      $error_info = $this->pdo->errorInfo();
      $this->error = $error_info[0];
      if ($this->error != '00000'){
        $msg = $error_info[1] . ': ' . $error_info[2];
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
      $error_info = $this->pdo->errorInfo();
      $this->error = $error_info[0];
      if (!$query){
        $this->error = -99;
        $msg = "Some undefined error occurred";
        $this->message = "Error: {$this->error} ({$msg})";
        $this->affected_rows = 0;
      }
      elseif ($this->error != '00000'){
        $msg = $error_info[1] . ': ' . $error_info[2];
        $this->message = "Error: {$this->error} ({$msg})";
        $this->affected_rows = 0;

      }
      else{
        $this->error = 0;
        $this->message = 'Records successfully fetched';
        while($fetch = $query->fetch(\PDO::FETCH_OBJ)){
          if (!is_null($id) && property_exists($fetch, $id)){
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

