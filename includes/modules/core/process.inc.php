<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Processing requests and finding the right page
 * 
 * @todo put the lookup information into the core database
 */

/**
 * Provides a title for a given request URI and runs any preprocess calls
 * @todo migrate gatekeeper logic here and use permissions database values
 * 
 * @param string $request Request URI
 * @return array $ouput (title, page_id, and access)
 */
function core_process_title($request){
  $path = explode("/",$request);
  $i = 0;
  $app_root = 0;
  foreach ($path as $i => $url_code){
    if ($i == 0){
      $page_id = 0;
      $ROOT = '';
      continue;
    }
    if($url_code !== '' && $app_root == 0){
      $info = core_db_fetch(DB_NAME,'pages',NULL, array('parent_id' => $page_id, 'url_code' => $url_code));
      if (count($info)>0){
        $page_id = $info[0]['id'];
        $app_root = $info[0]['app_root'];
        $ajax_call = $info[0]['ajax_call'];
        $render_call = $info[0]['render_call'];
        $created = $info[0]['created'];
        $activated = $info[0]['activated'];
        $deactivated = $info[0]['deactivated'];
        $title = $info[0]['title'];
        $ROOT .= '/' . $url_code;
      }
      else{
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        $output['access'] = FALSE;
        $output['title'] = 'Not Found';
        return $output;
      }
    }
  }
  
  // Set Application root for pages that act like applications
  define('APP_ROOT', $ROOT);
  $output = array();
  $groups = array();
  
  if ($ROOT != '/'){
    $ROOT .= '/';
  }
  else{
    $output['page_id'] = $page_id = 0;
  }
  var_dump($page_id);
  // Check permissions
  $roles = $_SESSION['authenticated']['roles'];
  if (isset($_SESSION['authenticated']['groups']) && count($_SESSION['authenticated']['groups'])>0){
    foreach ($_SESSION['authenticated']['groups'] as $group){
      $groups = core_process_grouptree($group, $groups);
    }
  }
  else{
    $group = 1; /**< Maps to the unauthenticated user*/
    $groups = core_process_grouptree($group, $groups);
  }
  $output['access'] = core_process_permissions($page_id, $groups, $roles);
  var_dump($output);
  if ($output['access']){
    // Check to see if it is still published
    $time = date('Y-m-d H:m:s');
    var_dump($activated);
    if (!is_null($activated) && $time < $activated){
      $output['access'] = false;
    }
    if (!is_null($activated) && $time < $activated){
      $output['access'] = false;
    }
    if(!is_null($deactivated) && $time > $deactivated){
      $output['access'] = false;
    }
    
    // Run ajax call, if it exists
    $fn = $info[0]['preprocess_call'];
    $output['title'] = $info[0]['title'];
    if (!is_null($ajax_call) && function_exists($ajax_call)){
      $ajax_call();
    }
    $output['page_id'] = $page_id;
    $output['render_call'] = $render_call;
    return $output;
  }
  else{
    // Return 404 title and send 404 header
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    $output['title'] = 'Not Found';
    return $output;
  }
}

  

/**
 * Processes permissions to content based on group and role
 * 
 * @param int $content_id Content id
 * 
 * @return boolean Access to content
 */
function core_process_permissions($page_id, $groups, $roles){
  //First, never ever lockout THE Admin user
  if (isset($_SESSION['authenticated']['user_id']) &&  $_SESSION['authenticated']['user_id'] == 1){
    return TRUE;
  }

  //Assume no access
  $access = FALSE;
  
  $group_access = core_db_fetch(DB_NAME, 'page_groups', NULL, array('page_id' => $page_id));
  if (count($group_access)>0){
    foreach ($group_access as $record){
      if (in_array($record['group_id'],$groups)){
        $access = TRUE;
      }
    }
  }
  
  // Check for Role overrides (if unset, means everyone can access!)
  $role_access = core_db_fetch(DB_NAME, 'page_roles', NULL, array('page_id' => $page_id));
  if (count($role_access)>0){
    //Reset access to false
    $access = FALSE;
    foreach ($role_access as $record){
      if (in_array($record['role_id'],$roles)){
        $access = TRUE;
      }
    }
  }
  return $access;
}

/**
 * Provides user group IDs that can be accessed, based on input
 * 
 * @param type $group Group ID where the search up and down the tree begins
 * @param array $groups Group IDs already found from previous iterations
 * @return array All Group IDs that a user may access
 */
function core_process_grouptree($group, $groups){
  if ($group == NULL){
    return $groups;
  }
  
  //find children
  $groups = core_process_get_children($group, $groups);
  
  //find parents until we reach root
  $search = $group;
  $loop = true;
  while($loop){
    $record = core_db_fetch(DB_NAME, 'groups', NULL, array('id' => $group));
    if ($record[0]['parent_id'] == 0){
      $loop = false;
      $groups[0] = 0;
    }
    else{
      $groups[$search] = $search = $record[0]['parent_id'];
    }
  }
  return $groups;  
}

/**
 * Finds children to the group IDs for a given parent
 * 
 * @param int $parent Parent ID to find the children
 * @param type $groups Array of group IDs that are available to keep appending
 * @return array Array of Group IDs (this gets appended to the input)
 */
function core_process_get_children($parent, $groups){
  $groups[$parent] = $parent;
  $children = core_db_fetch(DB_NAME, 'groups', NULL, array('parent_id' => $parent));
  if (count($children)>0){
    foreach ($children as $child){
      $groups = core_process_get_children($child['id'],$groups);
    }
  }
  return $groups;
}

/**
 * Finds children to the content IDs for a given parent
 * 
 * @param int $parent Parent ID to find the children
 * @param type $contents Array of content IDs that are available to keep appending
 * @return array Array of Content IDs (this gets appended to the input)
 */
function core_process_get_pagechildren($parent, $pages){
  $pages[$parent] = $parent;
  $children = core_db_fetch(DB_NAME, 'pages', NULL, array('parent_id' => $parent));
  if (count($children)>0){
    foreach ($children as $child){
      $pages = core_process_get_contentchildren($child['id'],$pages);
    }
  }
  return $pages;
}


/**
 * Provides markup for the page_content div
 * 
 * @param string $request Request URI
 * @param int $page_id ID of the page being loaded
 */
function core_process_content($request, $page_id){
  // Retrieve any page content, if it exists
  if(is_numeric($page_id)){
    $info = core_db_fetch(DB_NAME, 'page_content', NULL, array('page_id' => $page_id));
    if (count($info)>0){
      $content = $info[0]['content'];
      if (!is_null($content)){
        echo $content;
      }
    }
  }
}


/**
 * Processes File Downloads
 * 
 * @return void
 */
function core_process_download(){
  // Stop output buffering
  ob_clean();
  
  // Don't Cache the result
  header('Cache-Control: no-cache');
  $chars = strlen(APP_ROOT);
  $request = trim(substr($_SERVER['REQUEST_URI'],$chars),"/ ");
  
  //This is the only information that gets sent back!
  $included = $_SERVER['DOCUMENT_ROOT']."/FILES/core/".$request;
  $size = filesize($included);
  $type = mime_content_type($included);
  header('Pragma: ');         // leave blank to avoid IE errors
  header('Cache-Control: ');  // leave blank to avoid IE errors
  header('Content-Length: ' . $size);
  // This next line forces a download so you don't have to right click...
  header('Content-Disposition: attachment; filename="'.basename($included).'"');
  header('Content-Type: ' .$type);
  sleep(0); // gives browser a second to digest headers
  readfile($included);
  exit;
}

