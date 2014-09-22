<?php

/**
 * @file 
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Database reads and writes
 */

/**
 * Defines database credentials
 * 
 * @param type $database Database name
 * @return array $creds Database credentials
 */
function lwt_db_creds($database){
  $creds = array();
  if ($database == DB_NAME){
    $creds['host'] = DB_HOST;
    $creds['user'] = DB_USER;
    $creds['pass'] = DB_PASS;
    $creds['port'] = DB_PORT;
  }
  else{
    // Eventually grab other db creds from the main database!
    $creds = FALSE;
  }
  return $creds;
}

/**
 * Fetches array of table data (uses the raw fetch to do the actual db fetch)
 * 
 * @param string $database Database where info is coming from
 * @param string $table Table where info is coming from
 * @param array $fields Fields that are needed from database (if null, all)
 * @param array $where Optional associative array of WHERE ids/values to filter info
 * @param array $groupby Optional GROUP BY variables
 * @param array $sortby Optional SORT BY variables
 * @param string $id Optional field to use as index instead of numeric index
 * 
 * @return array $output 
 */
function lwt_db_fetch($database, $table, $fields=NULL,  $where=NULL, $groupby=NULL, $sortby=NULL, $id=NULL){
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
      if (is_null($value)){
        $where_elements[] = "`$key` IS NULL";
      }
      else{
        $where_elements[] = "`$key`='$value'";
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
  return lwt_db_fetch_raw($database, $sql, $id);
}

/**
 * Raw Database fetch: creating an array of table data
 * 
 * @param string $database Database where info is coming from
 * @param string $query raw query to send to the database
 * @param string $id Optional field to use as index instead of numeric index
 * 
 * @return array $output
 */
function lwt_db_fetch_raw($database, $query, $id=NULL){
  $db_login = lwt_db_creds($database);
  if ($db_login){
    $conn = new mysqli($db_login['host'], $db_login['user'], $db_login['pass'], $database, $db_login['port']);
    $conn->real_query($query);
    $result = $conn->use_result();
    $output = array();
    while ($fetch = $result->fetch_assoc()){
      if (!is_null($id) and key_exists($id, $fetch)){
        $out_id = $fetch[$id];
        $output[$out_id] = $fetch;
      }
      else{
        $output[] = $fetch;
      }
    }
    $result->close();
    $conn->close();
    return $output;
  }
  else{
    return FALSE;
  }
}

/**
 * Simple database Write (uses raw write to do actual db write)
 * 
 * @param string $database Database name
 * @param string $table Table name
 * @param array $inputs Associative array of Inputs
 * @param array $where Associative array of WHERE clause
 * 
 * @return array $status error number, message, and insert id
 */
function lwt_db_write($database, $table, $inputs, $where = NULL){
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
  return lwt_db_write_raw($database, $sql);
}

/**
 * Raw database Write
 * 
 * @param string $database Database name
 * @param string $sql Raw SQL Query
 * 
 * @return array $status error number, message, and insert id
 */
function lwt_db_write_raw($database, $sql){
  $db_login = lwt_db_creds($database);
  if (!$db_login){
    $status['error'] = 9990;
    $status['message'] = 'Bad database settings';
    return $status;
  }
  else{
    $conn = new mysqli($db_login['host'], $db_login['user'], $db_login['pass'], $database, $db_login['port']);
    $conn->real_query($sql);
    if ($conn->errno > 0){
      $status['error'] = $conn->errno;
      $status['message'] = "Error: {$conn->errno} ({$conn->error})";
    }
    else{
      $status['error'] = 0;
      $status['message'] = 'Records successfully written';
    }
    $status['insert_id'] = $conn->insert_id;
    $status['affected_rows'] = $conn->affected_rows;
    $conn->close();
    return $status;
  }
}

/**
 * Multiple query function, may be removed...
 *
 * @param string $database Database name
 * @param string $sql Raw multi-statement SQL Query
 *
 * @return array $status error number and message
 */
function lwt_db_multiquery($database, $sql){
  $db_login = lwt_db_creds($database);
   if (!$db_login){
    $status['error'] = 9990;
    $status['message'] = 'Bad database settings';
    return $status;
  }
  else{
    $conn = new mysqli($db_login['host'], $db_login['user'], $db_login['pass'], $database, $db_login['port']);
    $conn->multi_query($sql);
    if ($conn->errno > 0){
      $status['error'] = $conn->errno;
      $status['message'] = "Error: {$conn->errno} ({$conn->error})";
    }
    else{
      $status['error'] = 0;
      $status['message'] = 'Multi-Query done successfully';
    }
    while ($conn->next_result()){
      // Flush the multi queries to prevent issues  
    }
    $conn->close();
    
    return $status;
  }
}

