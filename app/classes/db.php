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
  protected $comparisons = ['<>', '<', '>', '<=', '>=', '=', '%'];
  /** Types of groups that are seen in a WHERE clause */
  protected $group_types = ['and', 'or'];
  protected $db = null;       /**< DB information for this object */
  protected $pdo = null;      /**< PDO object to use for querying */
  /** Statistics (or similar) functions that are used understood in all SQL dialects */
  protected $functions = ['sum','avg','max','min','count','lower','upper'];

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
   * Runs query using an object (see build_sql for specifications)
   *
   * @param object $query SQL query in an object as described in build_sql()
   */
  public function query($query){
    $cmd = 'select';
    if(isset($query->command) && in_array($query->command, $this->commands)){
      $cmd = $query->command;
    }
    $sql = $this->build_sql($query);
    if ($cmd == 'select'){
      $this->fetch_raw($sql);
    }
    else{
      $this->write_raw($sql);
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
   *       (object) ['id' => 'col_1'],
   *       (object) ['id' => 'col_2', 'stat' => 'sum', 'as' => 'sum_of_col_2],
   *       (object) ['id' => 'col_3'],
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
   *       (object)['id' => 'col_1'],
   *       (object)['id' => 'col_2', 'cs' => false],  // using case insensitive
   *       (object)['id' => 'col_3'],
   *     ],
   *     'sort' => [      // order by clause for select
   *       (object)['id' => 'col_1', 'dir' => 'a', 'cs' => true], //ascending, case sensitive
   *       (object)['id' => 'col_2', 'dir' => 'd', 'cs' => false], //desc, not case sensitive
   *       (object)['id' => 'col_3', 'dir' => 'd'],              // case sensitive
   *     ],
   *   ];
   * @endcode
   *
   * @return string $sql SQL statement to use for writing a query
   */
  public function build_sql($query){
    // Make sure we can actually do something
    if (!is_object($query) || !isset($query->table)){
      $this->error = 1;
      $this->message = 'Malformed query';
      return false;
    }

    // Set command
    $sql = '';
    $cmd = 'select';
    if(isset($query->command) && in_array($query->command, $this->commands)){
      $cmd = $query->command;
    }

    // Set table
    $table = $this->convert_to_sql($query->table, true);
    if (!$table){
      $this->error = 9999;
      $this->message = 'Bad input settings';
      return false;
    }

    // fields (select)
    if ($cmd == 'select'){
      $sql = 'SELECT ';
      $selects = [];
      if(isset($query->fields) && is_array($query->fields) && count($query->fields)>0){
        foreach($query->fields as $field){
          if (isset($field->id)){
            $f = $this->convert_to_sql($field->id, true);
            if (isset($field->fun) && in_array($field->fun,$this->functions)){
              $f = mb_strtoupper($field->fun) . '(' . $f . ')';
            }
            if (isset($field->as)){
              $a = $this->convert_to_sql($field->as, true);
              if ($a !== false){
                $f = $f . ' AS ' . $a;
              }
            }
          }
          else{
            $f = $this->convert_to_sql($field, true);
          }
          if ($f === false){
            $this->error = 9999;
            $this->message = 'Bad input settings';
            return false;
          }
          $selects[]= $f;
        }
        $sql .= implode(', ',$selects);
      }
      else{
        $sql .= '*';
      }
      $sql .= ' FROM ' . $table . ' ';
    }

    // inputs (update, insert)
    if (isset($query->inputs) && is_object($query->inputs) && in_array($cmd, ['update','insert'])){
      $values = [];
      $fields = [];
      $queries = [];
      foreach($query->inputs as $field => $value){
        $f = $this->convert_to_sql($field,true);
        $v = $this->convert_to_sql($value);
        if ($f === false || $v === false){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return false;
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
        return false;
      }
      $sql .= ' WHERE ' . $string;
    }

    // group (select)
    if (isset($query->group) && is_array($query->group) && $cmd == 'select'){
      $groups = [];
      foreach ($query->group as $group){
        if (isset($group->id)){
          $g = $this->convert_to_sql($group->id, true);
          if ($g === false){
            $this->error = 9999;
            $this->message = 'Bad input settings';
            return false;
          }
          if (isset($group->cs) && $group->cs == false){
            $g = 'LOWER(' . $g . ')';
          }
          $groups[]= $g;
        }
      }
      if (count($groups)>0){
        $query_string = implode(' , ', $groups);
        $sql .= ' GROUP BY ' . $query_string . ' ';
      }
    }

    // sort (select)
    if (isset($query->sort) && is_array($query->sort) && $cmd == 'select'){
      $sorts = [];
      foreach ($query->sort as $field){
        if (!isset($field->id)){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return false;
        }
        $f = $this->convert_to_sql($field->id, true);
        if ($f === false){
          $this->error = 9999;
          $this->message = 'Bad input settings';
          return false;
        }
        $d = 'ASC';
        if (isset($field->dir) && $field->dir == 'd'){
          $d = 'DESC';
        }
        if (isset($field->cs) && $field->cs == false){
          $f = 'LOWER(' . $f . ')';
        }
        $sorts[]= $f . ' ' . $d;
      }
      $sort_string = implode(' , ' , $sorts);
      $sql .= ' ORDER BY ' . $sort_string . ' ';
    }
    return $sql;
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

    if ($token == true){
      $matches = [];
      preg_match('/[\w-]*/', $value, $matches);
      if (count($matches)>0 && $matches[0] == $value){
        $output = '"' . $value . '"';
      }
    }
    elseif($token == false){
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
        $search = ["\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a"];
        $replace = ["\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z"];
        $clean = str_replace($search, $replace, $value);
        $output = "'" . $clean . "'";
      }
      elseif($type == 'NULL' || $value == null){
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
    $glue = ' AND ';
    if (isset($where->type) && in_array($where->type, $this->group_types)){
      $glue = ' ' . mb_strtoupper($where->type) . ' ';
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
          if (isset($field->type)){
            if($field->type == '%'){
              $eq = 'LIKE';
            }
            elseif($v === 'NULL'){
              $eq = 'IS';
              if ($field->type == '<>'){
                $eq .= ' NOT';
              }
            }
            elseif(in_array($field->type,$this->comparisons)){
              $eq = $field->type;
            }
          }
          if (isset($field->cs) && $field->cs == false){
            $f = 'LOWER(' . $f . ')';
            $v = mb_strtolower($v);
          }
          if ($f === false || $v === false){
            return false;
          }
          $queries[]= $f . ' ' . $eq . ' ' . $v;
        }
        else{
          $string = $this->process_where($field);
          if ($string === false){
            return false;
          }
          $queries[]= '( ' . $string . ' )';
        }
      }
      $sql = implode($glue, $queries);
      return $sql;
    }
    else{
      return false;
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
  public function fetch_raw($sql, $id=null){
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

