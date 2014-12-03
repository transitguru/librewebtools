<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.limited>
 *
 * This object reads and writes to the database
 *
 */
 
class DB{
  public $output = array();   /**< Results that would be returned from query */
  public $error = 0;          /**< Error number that would be returned from query */
  public $message = '';       /**< Error or success message */
  public $affected_rows = 0;  /**< Number of affected rows */
  public $insert_id = null;   /**< ID of record that was recently inserted */
  protected $type = null;     /**< Database type */
  protected $name = null;     /**< Database name */
  protected $host = null;     /**< Database host */
  protected $user = null;     /**< Database user */
  protected $pass = null;     /**< Database pass */
  protected $port = null;     /**< Database port */
  
  /**
   * Create the database object
   * 
   * @param string $name Database name
   */
  public function __construct($name){
    if ($name == DB_NAME){
      $this->type = DB_TYPE;
      $this->name = DB_NAME;
      $this->host = DB_HOST;
      $this->user = DB_USER;
      $this->pass = DB_PASS;
      $this->port = DB_PORT;
    }
    else{
      // Eventually grab other db creds from the main database!
      $this->error = 9990;
      $this->message = 'Bad database settings';
    }
  }


  /**
   * Simple database Write (uses raw write to do actual db write)
   * 
   * @param string $table Table name
   * @param array $inputs Associative array of Inputs
   * @param array $where Associative array of WHERE clause
   * 
   */
  public function write($table, $inputs, $where = NULL){
    $fields = array();
    $values = array();
    foreach ($inputs as $field => $value){
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
        $status['error'] = 9999;
        $status['message'] = 'Bad input settings';
        return $status;
      }
    }
    if (is_null($where)){
      $field_string = implode('` , `',$fields);
      $value_string = implode(',', $values);
      $sql = "INSERT INTO `$table` (`$field_string`) VALUES ($value_string)";
    }
    else{
      $queries = array();
      foreach ($values as $field => $value){
        $queries[] = "`$field`=$value";
      }
      $wheres = array();
      foreach ($where as $field => $value){
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
          $status['error'] = 9999;
          $status['message'] = 'Bad input settings';
          return $status;
        }
        $wheres[] = "`$field`=$value";
      }
      $sql = "UPDATE `$table` SET " . implode(" , ",$queries) . " WHERE " . implode(" AND ", $wheres);
    }
    $this->write_raw($sql);
  }
  
  /**
   * Raw database Write
   * 
   * @param string $sql Raw SQL Query
   * 
   * @return array $status error number, message, and insert id
   */
  public function write_raw($sql){
    if (!is_null($this->name)){
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->name, $this->port);
      $conn->real_query($sql);
      if ($conn->errno > 0){
        $this->error = $conn->errno;
        $this->message = "Error: {$conn->errno} ({$conn->error})";
      }
      else{
        $this->error = 0;
        $this->message = 'Records successfully written';
      }
      $this->insert_id = $conn->insert_id;
      $this->affected_rows = $conn->affected_rows;
      $conn->close();
    }
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
   * 
   */
  function fetch($table, $fields=NULL,  $where=NULL, $groupby=NULL, $sortby=NULL, $id=NULL){
    if (!is_array($fields)){
      $field_string = '*';
    }
    else{
      $field_string = "`".implode('` , `',$fields)."`";
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
          $status['error'] = 9999;
          $status['message'] = 'Bad input settings';
          return $status;
        }
        if (is_null($value)){
          $where_elements[] = "`$key` IS NULL";
        }
        else{
          $where_elements[] = "`{$key}`={$value}";
        }
      }
      $where_string = "WHERE " . implode(' AND ', $where_elements);
    }
    if (!is_array($groupby)){
      $groupby_string = '';
    }
    else{
      $groupby_string = "GROUP BY `".implode('` , `', $groupby)."`";
    }
    if (!is_array($sortby)){
      $sortby_string = '';
    }
    else{
      $sortby_string = "ORDER BY `".implode('` , `', $sortby)."`";
    }
    $sql = "SELECT {$field_string} FROM `{$table}` {$where_string} {$groupby_string} {$sortby_string}";
    $this->fetch_raw($sql, $id);
  }



  /**
   * Raw Database fetch: creating an array of table data
   * 
   * @param string $query raw query to send to the database
   * @param string $id Optional field to use as index instead of numeric index
   * 
   */
  public function fetch_raw($sql, $id=NULL){
    if (!is_null($this->name)){
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->name, $this->port);
      $conn->real_query($sql);
      $this->output = array();
      if ($conn->errno > 0){
        $this->error = $conn->errno;
        $this->message = "Error: {$conn->errno} ({$conn->error})";
        $this->affected_rows = 0;
        
      }
      else{
        $this->error = 0;
        $this->message = 'Records successfully fetched';
        $result = $conn->use_result();
        while ($fetch = $result->fetch_assoc()){
          if (!is_null($id) and key_exists($id, $fetch)){
            $out_id = $fetch[$id];
            $this->output[$out_id] = $fetch;
          }
          else{
            $this->output[] = $fetch;
          }
        }
        $result->close();
        $conn->close();
        $this->affected_rows = count($this->output);
      }
    }
  }
  
  /**
   * Multiple query, may be removed...
   *
   * @param string $database Database name
   * @param string $sql Raw multi-statement SQL Query
   *
   * @return array $status error number and message
   */
  public function multiquery($sql){
    if (!is_null($this->name)){
      $conn = new mysqli($this->host, $this->user, $this->pass, $this->name, $this->port);
      $conn->multi_query($sql);
      if ($conn->errno > 0){
        $this->error = $conn->errno;
        $this->message = "Error: {$conn->errno} ({$conn->error})";
        $this->affected_rows = 0;
        $this->insert_id = null;
      }
      else{
        $this->error = 0;
        $this->message = 'Multi-Query done successfully';
        $this->affected_rows = 0; // This is a lie, but need to reset the value
        $this->insert_id = null;  // This is a lie, but need to reset the value
      }
      while ($conn->next_result()){
        // Flush the multi queries to prevent issues  
      }
      $conn->close();
    }
  }
 
}
