<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders pages that are usually only seen by an admin
 */

/**************************************************
 * Form Processors
 **************************************************/

/**
 * Processes user admin form submission
 * 
 * @param string $forms Forms path data
 * 
 */
function core_admin_process_user($forms){
  if (isset($_POST['command']) && $_POST['command'] == 'delete' && isset($_POST['user']['id']) && is_numeric($_POST['user']['id']) && isset($_POST['confirmed']) && $_POST['confirmed'] == 1){
    $db = new coreDb();
    $db->write_raw("DELETE FROM `users` WHERE `id` = {$_POST['user']['id']}");
    if ($db->error){
      $_SESSION['message'] = '<span class="error" >Could not delete the user.</span>';
    }
    else{
      $_SESSION['message'] = '<span class="success" >User was successfully deleted.</span>';
      $_POST['path'] = "user";
    }
  }
  elseif (isset($_POST['command']) && $_POST['command'] == 'write' && isset($_POST['user']['id']) && is_numeric($_POST['user']['id'])){
    $id = $_POST['user']['id'];
    $inputs = array();
    $success = true;
    $payload = array();
    
    if ($id < 0){
      $where = null;
      $inputs['created'] = date('Y-m-d H:i:s');
    }
    else{
      $where = array('id' => $id);
    }
    
    // Define form fields (for validation only)
    $fields = array();
    $fields['login'] = new coreField('', 'text', 'nowacky', true, 40);
    $fields['firstname'] = new coreField('', 'text', 'oneline', true, 100);
    $fields['lastname'] = new coreField('', 'text', 'oneline', true, 100);
    $fields['email'] = new coreField('', 'text', 'email', true, 255);
    $fields['desc'] = new coreField('', 'memo', 'all', false, 1000);
    
    foreach ($fields as $key => $data){
      if (isset($_POST['user'][$key])){
        $fields[$key]->value = $_POST['user'][$key];
      }
      $fields[$key]->validate();
      $inputs[$key] = $fields[$key]->value;
      if ($fields[$key]->error){
        $success = false;
      }
      $payload[$key] = array(
        'error' => $fields[$key]->error,
        'message' => $fields[$key]->message,
        'value' => $fields[$key]->value,
      );
    }
    $roles = array();
    foreach ($_POST['roles'] as $role){
      $field = new coreField($role, 'num', 'int');
      $field->validate();
      if (!$field->error){
        $roles[] = $role;
      }
    }
    $groups = array();
    foreach ($_POST['groups'] as $group){
      $field = new coreField($role, 'num', 'int');
      $field->validate();
      if (!$field->error){
        $groups[] = $group;
      }
    }
    
    // Check for unique indexes
    if ($success){
      $db = new coreDb();
      $db->fetch('users', array('id'), array('login' => $inputs['login']));
      if ($db->affected_rows > 0 && $db->output[0]['id'] != $id){
        $success = false;
        $payload['login']['message'] = 'Already taken: Value needs to be unique, please choose another';
        $payload['login']['error'] = 5000;
      }
      $db->fetch('users', array('id'), array('email' => $inputs['email']));
      if ($db->affected_rows > 0 && $db->output[0]['id'] != $id){
        $success = false;
        $payload['email']['message'] = 'Already taken: Value needs to be unique, please choose another';
        $payload['email']['error'] = 5000;
      }
    }
    
    //write inputs
    if ($success){
      $db->write('users', $inputs, $where);
      if (!$db->error){
        if ($id < 0){
          $id = $db->insert_id;
          $user = new coreUser($id);
          $user->setpassword();
          $_POST['path'] = "user/{$id}";
        }
        
        // Apply roles
        $db->write_raw("DELETE FROM `user_roles` WHERE `user_id` = {$id}");
        foreach ($roles as $role){
          $db->write('user_roles', array('role_id' => $role, 'user_id' => $id));
        }
        
        //Apply groups
        $db->write_raw("DELETE FROM `user_groups` WHERE `user_id` = {$id}");
        foreach ($groups as $group){
          $db->write('user_groups', array('group_id' => $group, 'user_id' => $id));
        }
        
        //Reset if requested
        if (isset($_POST['reset']) && $_POST['reset'] == 1){
          $user = new coreUser($id);
          $user->resetpassword($inputs['email']);
        }
        $_SESSION['message'] = '<span class="success">The user has been successfully saved</span>';
      }
      else{
        $_SESSION['message'] = '<span class="error">Database error: Please contact system administrator if this persists.</span>';
      }
    }
    else{
      $_SESSION['message'] = '<span class="error">Please fix the invalid inputs</span>';
      $_SESSION['repost'] = $payload;
    }
    
  }

}

/**
 * Processes group admin form submission
 * 
 * @param string $forms Forms path data
 * 
 */
function core_admin_process_group($forms){
  if (isset($_POST['command']) && $_POST['command'] == 'delete' && isset($_POST['group']['id']) && is_numeric($_POST['group']['id']) && $_POST['group']['id'] > 0 && isset($_POST['confirmed']) && $_POST['confirmed'] == 1){
    $db = new coreDb();
    $db->write_raw("DELETE FROM `groups` WHERE `id` = {$_POST['group']['id']}");
    if ($db->error){
      $_SESSION['message'] = '<span class="error" >Could not delete the group.</span>';
    }
    else{
      $_SESSION['message'] = '<span class="success" >Group was successfully deleted.</span>';
      $_POST['path'] = "group";
    }
  }
  elseif (isset($_POST['command']) && $_POST['command'] == 'write' && isset($_POST['group']['id']) && is_numeric($_POST['group']['id'])){
    $db = new coreDb();
    $id = $_POST['group']['id'];
    $inputs = array();
    $success = true;
    $payload = array();
    if ($id < 0){
      $where = null;
      $inputs['created'] = date('Y-m-d H:i:s');
    }
    else{
      $where = array('id' => $id);
    }
    
    // Define form fields (for validation only)
    $fields = array();
    $fields['sortorder'] = new coreField('', 'num', 'int', false, 40);
    $fields['name'] = new coreField('', 'text', 'oneline', true, 100);
    $fields['desc'] = new coreField('', 'memo', 'all', false, 1000);
    
    foreach ($fields as $key => $data){
      if (isset($_POST['group'][$key])){
        $fields[$key]->value = $_POST['group'][$key];
      }
      $fields[$key]->validate();
      $inputs[$key] = $fields[$key]->value;
      if ($fields[$key]->error){
        $success = false;
      }
      $payload[$key] = array(
        'error' => $fields[$key]->error,
        'message' => $fields[$key]->message,
        'value' => $fields[$key]->value,
      );
    }
    
    // Validate the parent ID
    if ($id == 0){
      $inputs['parent_id'] = null;
    }
    else{
      $field = new coreField($_POST['group']['parent_id'], 'num', 'int');
      $field->validate();
      if ($field->error){
        $inputs['parent_id'] = 0;
      }
      else{
        $inputs['parent_id'] = $field->value;
        $db->fetch('groups', array('id'), array('id' => $inputs['parent_id']));
        $disabled_ids = array();
        $groupobj = new coreGroup($id);
        $disabled_ids = $groupobj->children($id, $disabled_ids);
        if (in_array($inputs['parent_id'], $disabled_ids) || $db->affected_rows == 0){
          $inputs['parent_id'] = 0;
        }
      }
    }
    
    // Check for unique indexes
    if ($success){
      $db->fetch('groups', array('id'), array('name' => $inputs['name']));
      if ($db->affected_rows > 0 && $db->output[0]['id'] != $id){
        $success = false;
        $payload['name']['message'] = 'Already taken: Value needs to be unique, please choose another';
        $payload['name']['error'] = 5000;
      }
    }
    
    //write inputs
    if ($success){
      $db->write('groups', $inputs, $where);
      if (!$db->error){
        if ($id < 0){
          $id = $db->insert_id;
          $_POST['path'] = "group/{$id}/";
        }
        $_SESSION['message'] = '<span class="success">The group has been successfully saved</span>';
      }
      else{
        $_SESSION['message'] = '<span class="error">Database error: Please contact system administrator if this persists.</span>';
      }
    }
    else{
      $_SESSION['message'] = '<span class="error">Please fix the invalid inputs</span>';
      $_SESSION['repost'] = $payload;
    }
    
  }


}

/**
 * Processes role admin form submission
 * 
 * @param string $forms Forms path data
 * 
 */
function core_admin_process_role($forms){
  if (isset($_POST['command']) && $_POST['command'] == 'delete' && isset($_POST['role']['id']) && is_numeric($_POST['role']['id']) && $_POST['role']['id'] > 2 && isset($_POST['confirmed']) && $_POST['confirmed'] == 1){
    $status = core_db_write_raw(DB_NAME, "DELETE FROM `roles` WHERE `id` = {$_POST['role']['id']}");
    if ($status['error']){
      $_SESSION['message'] = '<span class="error" >Could not delete the role.</span>';
    }
    else{
      $_SESSION['message'] = '<span class="success" >Role was successfully deleted.</span>';
      $_POST['path'] = "role";
    }
  }
  elseif (isset($_POST['command']) && $_POST['command'] == 'write' && isset($_POST['role']['id']) && is_numeric($_POST['role']['id'])){
    $id = $_POST['role']['id'];
    $inputs = array();
    $success = true;
    $payload = array();
    $date = date('Y-m-d H:i:s');
    if ($id < 0){
      $where = null;
      $inputs['created'] = $date;
    }
    else{
      $where = array('id' => $id);
    }
    
    // validate inputs
    $expected = array(
      'sortorder' => array(
        'type' => 'num', 
        'format' => 'int', 
        'required' => false, 
        'chars'=> 40, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'name' => array(
        'type' => 'text', 
        'format' => 'oneline', 
        'required' => true, 
        'chars'=> 100, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'desc' => array(
        'type' => 'memo', 
        'format' => 'all', 
        'required' => false, 
        'chars'=> 1000, 
        'notrim' => true, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
    );
    
    foreach ($expected as $key => $data){
      if (isset($_POST['role'][$key])){
        $input = $_POST['role'][$key];
      }
      else{
        $input = '';
      }
      $result = core_validate_inputs($input, $data['type'], $data['format'], $data['required'], $data['chars'], $data['notrim'], $data['range'], $data['range_flags']);
      $inputs[$key] = $result['value'];
      if ($result['error']){
        $success = false;
      }
      $payload[$key] = $result;
    }
    
    
    // Check for unique indexes
    if ($success){
      $test = core_db_fetch(DB_NAME, 'roles', array('id'), array('name' => $inputs['name']));
      if (count($test)>0 && $test[0]['id'] != $id){
        $success = false;
        $payload['name']['message'] = 'Already taken: Value needs to be unique, please choose another';
        $payload['name']['error'] = 5000;
      }
    }
    
    //write inputs
    if ($success){
      $status = core_db_write(DB_NAME, 'roles', $inputs, $where);
      if (!$status['error']){
        if ($id < 0){
          $id = $status['insert_id'];
          $_POST['path'] = "role/{$id}";
        }
        $_SESSION['message'] = '<span class="success">The group has been successfully saved</span>';
      }
      else{
        $_SESSION['message'] = '<span class="error">Database error: Please contact system administrator if this persists.</span>';
      }
    }
    else{
      $_SESSION['message'] = '<span class="error">Please fix the invalid inputs</span>';
      $_SESSION['repost'] = $payload;
    }
    
  }

}

/**
 * Processes page admin form submission
 * 
 * @param string $forms Forms path data 
 */
function core_admin_process_page($forms){
  if (isset($_POST['command']) && $_POST['command'] == 'delete' && isset($_POST['page']['id']) && is_numeric($_POST['page']['id']) && $_POST['page']['id'] > 0 && isset($_POST['confirmed']) && $_POST['confirmed'] == 1){
    $pages = core_db_fetch(DB_NAME, 'pages', array('app_root', 'core_page'), array('id' => $id));
    if (count($pages)>0 && ($pages[0]['app_root'] == 1 || $pages[0]['core_page'] == 1)){
      $_SESSION['message'] = '<span class="warning" >Page is protected, please set <em>app_root</em> and <em>core_page</em> to <strong>no</strong>.</span>';
    }
    else{
      $status = core_db_write_raw(DB_NAME, "DELETE FROM `pages` WHERE `id` = {$_POST['page']['id']}");
      if ($status['error']){
        $_SESSION['message'] = '<span class="error" >Could not delete the page.</span>';
      }
      else{
        $_SESSION['message'] = '<span class="success" >Page was successfully deleted.</span>';
        $_POST['path'] = "page";
      }
    }
  }
  elseif (isset($_POST['command']) && $_POST['command'] == 'write' && isset($_POST['page']['id']) && is_numeric($_POST['page']['id'])){
    $id = $_POST['page']['id'];
    $inputs = array();
    $success = true;
    $payload = array();
    $date = date('Y-m-d H:i:s');
    if ($id < 0){
      $where = null;
    }
    else{
      $where = array('id' => $id);
    }

    // validate inputs
    $expected = array(
      'url_code' => array(
        'type' => 'text', 
        'format' => 'nowacky', 
        'required' => true, 
        'chars'=> 100, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'title' => array(
        'type' => 'text', 
        'format' => 'oneline', 
        'required' => true, 
        'chars'=> 255, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'ajax_call' => array(
        'type' => 'text', 
        'format' => 'nowacky', 
        'required' => false, 
        'chars'=> 255, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'render_call' => array(
        'type' => 'text', 
        'format' => 'nowacky', 
        'required' => false, 
        'chars'=> 255, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'activated' => array(
        'type' => 'date', 
        'format' => 'Y-m-d H:i:s', 
        'required' => false, 
        'chars'=> 20, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'deactivated' => array(
        'type' => 'date', 
        'format' => 'Y-m-d H:i:s', 
        'required' => false, 
        'chars'=> 20, 
        'notrim' => false, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'summary' => array(
        'type' => 'memo', 
        'format' => 'all', 
        'required' => false, 
        'chars'=> 100000, 
        'notrim' => true, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
      'content' => array(
        'type' => 'memo', 
        'format' => 'all', 
        'required' => false, 
        'chars'=> 100000, 
        'notrim' => true, 
        'range' => array(null, null, null), 
        'range_flags' => array(false, false, false),
      ),
    );
    
    // Validate the parent ID
    if ($id == 0){
      $inputs['parent_id'] = null;
    }
    else{
      $result = core_validate_inputs($_POST['page']['parent_id'], 'num', 'int');
      if ($result['error']){
        $inputs['parent_id'] = 0;
      }
      else{
        $inputs['parent_id'] = $result['value'];
        $parent_info = core_db_fetch(DB_NAME, 'pages', array('app_root'), array('id' => $inputs['parent_id']));
        $disabled_ids = array();
        $disabled_ids = core_process_get_pagechildren($id, $disabled_ids);
        if (in_array($inputs['parent_id'], $disabled_ids) || count($parent_info)==0 || (count($parent_info)>0 && $parent_info['app_root'] == 1)){
          $inputs['parent_id'] = 0;
        }
      }
    }
    
    // Validate booleans, just in case
    if (isset($_POST['page']['app_root']) && is_numeric($_POST['page']['app_root']) && ($_POST['page']['app_root'] == 0 || $_POST['page']['app_root'] == 1)){
      $inputs['app_root'] = $_POST['page']['app_root'];
    }
    else{
      $inputs['app_root'] = 0;
    }
     if (isset($_POST['page']['core_page']) && is_numeric($_POST['page']['core_page']) && ($_POST['page']['core_page'] == 0 || $_POST['page']['core_page'] == 1)){
      $inputs['core_page'] = $_POST['page']['core_page'];
    }
    else{
      $inputs['core_page'] = 0;
    }
    
    // Loop through expected inputs
    foreach ($expected as $key => $data){
      if (isset($_POST['page'][$key])){
        $input = $_POST['page'][$key];
      }
      else{
        $input = '';
      }
      if ($key == 'url_code' && 'id' == 0){
        // URL code must be empty for 0 id
        $result = array(
          'error' => 0,
          'value' => '',
          'message' => '',
        );
      }
      else{
        $result = core_validate_inputs($input, $data['type'], $data['format'], $data['required'], $data['chars'], $data['notrim'], $data['range'], $data['range_flags']);
      }
      $inputs[$key] = $result['value'];
      if ($result['error']){
        $success = false;
      }
      $payload[$key] = $result;
    }
    $roles = array();
    foreach ($_POST['roles'] as $role){
      $result = core_validate_inputs($role, 'num', 'int');
      if (!$result['error']){
        $roles[] = $role;
      }
    }
    $groups = array();
    foreach ($_POST['groups'] as $group){
      $result = core_validate_inputs($group, 'num', 'int');
      if (!$result['error']){
        $groups[] = $group;
      }
    }
    
    // Check for unique indexes
    if ($success){
      $test = core_db_fetch(DB_NAME, 'pages', array('id'), array('url_code' => $inputs['url_code'], 'parent_id' => $inputs['parent_id']));
      if (count($test)>0 && $test[0]['id'] != $id){
        $success = false;
        $payload['login']['message'] = 'Already taken: URL code at this level needs to be unique, please choose another';
        $payload['login']['error'] = 5000;
      }
    }
    
    $inputs['created'] = date('Y-m-d H:i:s');

    //write inputs
    if ($success){
      $page_inputs = array(
        'parent_id' => $inputs['parent_id'],
        'url_code' => $inputs['url_code'],
        'title' => $inputs['title'],
        'app_root' => $inputs['app_root'],
        'core_page' => $inputs['core_page'],
        'ajax_call' => $inputs['render_call'],
        'activated' => $inputs['activated'],
        'deactivated' => $inputs['deactivated'],
      );
      if ($id < 0){
        $page_inputs['created'] = $date;
        $page_inputs['created'] = $_SESSION['authenticated']['id'];
      }
      elseif($id == 0){
        unset($page_inputs['url_code']);
      }

      $status = core_db_write(DB_NAME, 'pages', $page_inputs, $where);
      if (!$status['error']){
        if ($id < 0){
          $id = $status['insert_id'];
          $_POST['path'] = "page/{$id}";
        }
        
        
        // Apply roles
        core_db_write_raw(DB_NAME, "DELETE FROM `page_roles` WHERE `page_id` = {$id}");
        foreach ($roles as $role){
          core_db_write(DB_NAME, 'page_roles', array('role_id' => $role, 'page_id' => $id));
        }
        
        //Apply groups
        core_db_write_raw(DB_NAME, "DELETE FROM `page_groups` WHERE `page_id` = {$id}");
        foreach ($groups as $group){
          core_db_write(DB_NAME, 'page_groups', array('group_id' => $group, 'page_id' => $id));
        }
        
        // Apply page content (only if necessary)
        $content_inputs = array(
          'page_id' => $id,
          'user_id' => $_SESSION['authenticated']['id'],      
          'title' => $inputs['title'],
          'created' => $date,
          'summary' => $inputs['summary'],        
          'content' => $inputs['content'],
        );
        
        $content_pages = core_db_fetch_raw(DB_NAME, "SELECT * FROM `page_content` WHERE `page_id` = {$id} ORDER BY `created` DESC LIMIT 1");
        if (
          ((!is_array($content_pages) || count($content_pages)==0) && ($content_inputs['summary'] != '' || $content_inputs['content'] != '')) ||
          (is_array($content_pages) && count($content_pages)>0 && ($content_inputs['summary'] != $content_pages[0]['summary'] || $content_inputs['content'] != $content_pages[0]['content']))
        ){
          core_db_write(DB_NAME, 'page_content', $content_inputs);
        }
        
        $_SESSION['message'] = '<span class="success">The page has been successfully saved</span>';
      }
      else{
        $_SESSION['message'] = '<span class="error">Database error: Please contact system administrator if this persists.</span>';
      }
    }
    else{
      $_SESSION['message'] = '<span class="error">Please fix the invalid inputs</span>';
      $_SESSION['repost'] = $payload;
    }
    
  }

}

/**
 * Processes file admin form submission
 * 
 * @param string $forms Forms path data
 * 
 */
function core_admin_process_file($forms){

}

/**
 * Processes menu admin form submission
 * 
 * @param string $forms Forms path data
 * 
 */
function core_admin_process_menu($forms){

}



/**************************************************
 * Form Renderers
 **************************************************/

/**
 * Renders user admin form
 * 
 * @param array $paths Navigation path data
 * 
 */
function core_admin_render_user($paths){
  if (isset($paths[1]) && $paths[1] != ''){
?>
  <?php echo $_SESSION['message']; ?>
<?php
    $db = new coreDb();
    $user = array();
    if (is_numeric($paths[1])){
      $id = $paths[1]; 
      // Negative ID is used for creating a new user
      if($id < 0){
        $user = array(
          'id' => $id,
          'login' => '',
          'firstname' => '',
          'lastname' => '',
          'email' => '',
          'desc' => '',
          'roles' => array(2),
          'groups' => array(4),
        );
      }
      // Lookup to see if there is an existing user
      else{
        $db->fetch('users', null, array('id' => $id));
        if ($db->affected_rows > 0){
          // Set user to database record
          $user = $db->output[0];
          
          // Load current user roles
          $user['roles'] = array();
          $db->fetch('user_roles', null, array('user_id' => $id));
          if ($db->affected_rows > 0){
            foreach($db->output as $role){
              $user['roles'][] = $role['role_id'];
            }
          }
          
          // Load current user groups
          $user['groups'] = array();
          $db->fetch('user_groups', null, array('user_id' => $id));
          if ($db->affected_rows > 0){
            foreach($db->output as $group){
              $user['groups'][] = $group['group_id'];
            }
          }
        }
      }
    }
    if (is_array($user) && count($user)>0){
      if (isset($paths[2]) && $paths[2] == 'delete' && $id > 0){
        if ($id == 1){
          // Don't allow deleting the admin user
?>
      <p>It is not possible to delete <strong><?php echo "{$user['firstname']} {$user['lastname']}"; ?></strong> as this user is the site adminstrator</p>
      <a class="button" href="<?php echo APP_ROOT; ?>user/<?php echo $id; ?>" >Cancel</a>
<?php        
        }
        else{
          // User delete confirmation
?>
  <h3>Delete User</h3>
  <p>Are you sure you want to delete the user named <strong><?php echo "{$user['firstname']} {$user['lastname']}"; ?></strong>, whose login name is <strong><?php echo $user['login'];?></strong>? This cannot be undone!</p>
  <form method="post" action="" id="admin/user" >
    <input type="hidden" name="formid" value="admin/user" />
    <input type="hidden" name="user[id]" value="<?php echo $user['id']; ?>" />
    <input type="hidden" name="confirmed" value="1" />
    <input type="hidden" name="command" value="delete" />
    <a class="button" href="<?php echo APP_ROOT; ?>user/<?php echo $user['id']; ?>" >Cancel</a> 
    <input class="button alert" type="submit" name="send" value="Delete" />   
  </form>
<?php        
        }
      }
      else{
        // Set messages and classes to empty strings
        $class = $msg = array(
          'login' => '',
          'firstname' => '',
          'lastname' => '',
          'email' => '',
          'desc' => '',
        );

        // Check for error/repost
        if (isset($_SESSION['repost']) && is_array($_SESSION['repost'])){
          foreach ($_SESSION['repost'] as $key => $result){
            $user[$key] = $result['value'];
            if ($result['error']){
              $msg[$key] = " <strong>{$result['message']}</strong>";
              $class[$key] = 'invalid';
            }
          }
        }
        // User form
?>   
  <h3>User administration Form</h3>   
  <form method="post" action="" id="admin/user" >
    <input type="hidden" name="formid" value="admin/user" />
    <input type="hidden" name="command" value="write" />
    <input type="hidden" name="user[id]" value="<?php echo $user['id']; ?>" />    
    <label for="user[login]">Login<?php echo $msg['login']; ?></label><input class="required <?php echo $class['login']; ?>" name="user[login]" type="text" maxlength="40" value="<?php echo $user['login']; ?>" />
    <label for="user[firstname]">First Name<?php echo $msg['firstname']; ?></label><input class="required <?php echo $class['firstname']; ?>" name="user[firstname]" type="text" maxlength="100" value="<?php echo $user['firstname']; ?>" />
    <label for="user[lastname]">Last Name<?php echo $msg['lastname']; ?></label><input class="required <?php echo $class['lastname']; ?>" name="user[lastname]" type="text" maxlength="100" value="<?php echo $user['lastname']; ?>" />
    <label for="user[email]">Email Address<?php echo $msg['email']; ?></label><input class="required <?php echo $class['email']; ?>" name="user[email]" type="text" maxlength="255" value="<?php echo $user['email']; ?>" />
    <label for="user[desc]">Description<?php echo $msg['desc']; ?></label><textarea class="<?php echo $class['desc']; ?>" name="user[desc]" type="text" maxlength="1000" /><?php echo $user['desc']; ?></textarea>
    <label for="reset">Reset User's password (sends email)</label><input id="resetter" type="checkbox" name="reset" value="1" /><span class="hand" onclick="document.getElementById('resetter').click()">Reset password</span>
    <label for="roles[]">Roles</label>
    <ul>
<?php
        $db->fetch('roles', null, null, null, array('sortorder', 'name'));
        foreach ($db->output as $role){
          if (in_array(($role['id']), $user['roles'])){
            $checked = 'checked';
          }
          else{
            $checked = '';
          }
?>
      <li><input id="checkbox_role_<?php echo $role['id']; ?>" type="checkbox" value="<?php echo $role['id']; ?>" name="roles[]" <?php echo $checked; ?> /><span class="hand" onclick="document.getElementById('checkbox_role_<?php echo $role['id']; ?>').click();" ><?php echo $role['name']; ?></span></li>
<?php      
        }
?>
    </ul>
    <label for="groups[]">Groups</label>
<?php   
        core_admin_render_grouptree($user['groups']);  
?>
    <input class="button" type="submit" name="send" value="Save" />
    <a class="button" href="<?php echo APP_ROOT; ?>user/<?php echo $user['id']; ?>/" >Reset</a>
    <a class="button" href="<?php echo APP_ROOT; ?>user/" >Close</a>
<?php 
        if ($user['id']>0){
?>
    <a class="right button alert" href="<?php echo APP_ROOT; ?>user/<?php echo $user['id']; ?>/delete/" >Delete...</a>
<?php
        }
?>
  </form>

<?php
      }
    }
    else{
?>
    <p>Page not found, please go <a href="<?php echo APP_ROOT; ?>user/">back</a>.</p>
<?php 
    }
  }
  else{  
    // Navigation to select users
?>
  <h2>Users</h2>
<?php
    $db = new coreDb();
    $db->fetch('users', null, null, null, array('login'));
    if ($db->affected_rows > 0){
?>
  <ul>
    <li><a href="<?php echo APP_ROOT; ?>user/-1/" >[+]</a></li>
<?php
      foreach ($db->output as $user){
?>
    <li><a href="<?php echo APP_ROOT; ?>user/<?php echo $user['id']; ?>/" ><?php echo $user['login']; ?></a> <em><?php echo "{$user['firstname']} {$user['lastname']}"; ?></em></li>
<?php
      }
?>
  </ul>
<?php
    }
    else{
?>
    <p>There are no users, that is a problem!</p>
<?php
    }  
  }
}

/**
 * Renders group admin form
 * 
 * @param array $paths Navigation path data
 * 
 */
function core_admin_render_group($paths){
  if (isset($paths[1]) && $paths[1] != ''){
?>
  <?php echo $_SESSION['message']; ?>
<?php
    $db = new coreDb();
    $group = array();
    if (is_numeric($paths[1])){
      $id = $paths[1]; 
      // Negative ID is used for creating a new group
      if($id < 0){
        $group = array(
          'id' => $id,
          'parent_id' => 0,
          'sortorder' => 0,
          'name' => '',
          'desc' => '',
        );
      }
      // Lookup to see if there is an existing group
      else{
        $db->fetch('groups', null, array('id' => $id));
        if ($db->affected_rows>0){
          // Set group to database record
          $group = $db->output[0];
        }
      }
    }
    if (is_array($group) && count($group)>0){
      if (isset($paths[2]) && $paths[2] == 'delete' && $id >= 0){
        $db->fetch('groups', array('id'), array('parent_id' => $id));
        if ($id == 0){
          // Don't allow deleting the root group
?>
      <p>It is not possible to delete <strong><?php echo $group['name']; ?></strong> as it reflects the root of the group tree.</p>
      <a class="button" href="<?php echo APP_ROOT; ?>group/<?php echo $id; ?>/" >Cancel</a>
<?php        
        }
        elseif($db->affected_rows > 0){
?>
      <p>It is not possible to delete <strong><?php echo $group['name']; ?></strong> as it has subgroups attached to it.</p>
      <a class="button" href="<?php echo APP_ROOT; ?>group/<?php echo $id; ?>/" >Cancel</a> 
<?php       
        }
        else{
          // Group delete confirmation
?>
  <h3>Delete Group</h3>
  <p>Are you sure you want to delete the group named <strong><?php echo $group['name']; ?></strong>? This cannot be undone!</p>
  <form method="post" action="" id="admin/group" >
    <input type="hidden" name="formid" value="admin/group" />
    <input type="hidden" name="group[id]" value="<?php echo $group['id']; ?>" />
    <input type="hidden" name="confirmed" value="1" />
    <input type="hidden" name="command" value="delete" />
    <a class="button" href="<?php echo APP_ROOT; ?>group/<?php echo $group['id']; ?>/" >Cancel</a> 
    <input class="button alert" type="submit" name="send" value="Delete" />   
  </form>
<?php        
        }
      }
      else{
        // Set messages and classes to empty strings
        $class = $msg = array(
          'sortorder' => '',
          'name' => '',
          'desc' => '',
        );

        // Check for error/repost
        if (isset($_SESSION['repost']) && is_array($_SESSION['repost'])){
          foreach ($_SESSION['repost'] as $key => $result){
            $group[$key] = $result['value'];
            if ($result['error']){
              $msg[$key] = " <strong>{$result['message']}</strong>";
              $class[$key] = 'invalid';
            }
          }
        }
        // Group form
?>   
  <h3>Group administration Form</h3>   
  <form method="post" action="" id="admin/group" >
    <input type="hidden" name="formid" value="admin/group" />
    <input type="hidden" name="command" value="write" />
    <input type="hidden" name="group[id]" value="<?php echo $group['id']; ?>" />    
    <label for="group[sortorder]">Sort Order (integer)<?php echo $msg['sortorder']; ?></label><input class="<?php echo $class['sortorder']; ?>" name="group[sortorder]" type="text" maxlength="10" value="<?php echo $group['sortorder']; ?>" />
    <label for="group[name]">Group Name<?php echo $msg['name']; ?></label><input class="required <?php echo $class['name']; ?>" name="group[name]" type="text" maxlength="100" value="<?php echo $group['name']; ?>" />
    <label for="group[desc]">Description<?php echo $msg['desc']; ?></label><textarea class="<?php echo $class['desc']; ?>" name="group[desc]" type="text" maxlength="1000" /><?php echo $group['desc']; ?></textarea>
    <label for="group[parent_id]">Parent Group</label>
<?php   
        $disabled_ids = array();
        $groupobj = new coreGroup($group['id']);
        $disabled_ids = $groupobj->children($group['id'], $disabled_ids);
        core_admin_render_grouptree(array($group['parent_id']), 'group[parent_id]', 'radio', null, $disabled_ids);  
?>
    <input class="button" type="submit" name="send" value="Save" />
    <a class="button" href="<?php echo APP_ROOT; ?>group/<?php echo $group['id']; ?>/" >Reset</a>
    <a class="button" href="<?php echo APP_ROOT; ?>group/" >Close</a>
<?php 
        if ($group['id']>0){
?>
    <a class="right button alert" href="<?php echo APP_ROOT; ?>group/<?php echo $group['id']; ?>/delete/" >Delete...</a>
<?php
        }
?>
  </form>

<?php
      }
    }
    else{
?>
    <p>Page not found, please go <a href="<?php echo APP_ROOT; ?>group/">back</a>.</p>
<?php 
    }
  }
  else{  
    // Navigation to select Groups
?>
  <h2>Groups</h2>
<?php
    $db = new coreDb();
    $db->fetch('groups', array('id'));
    if ($db->affected_rows > 0){
?>
    <p><a href="<?php echo APP_ROOT; ?>group/-1/">[+]</a><p>
<?php
      core_admin_render_groupnav();
    }
    else{
?>
    <p>There are no groups, that is a problem!</p>
<?php
    }  
  }
}

/**
 * Renders role admin form
 * 
 * @param array $paths Navigation path data
 * 
 */
function core_admin_render_role($paths){
  if (isset($paths[1]) && $paths[1] != ''){
?>
  <?php echo $_SESSION['message']; ?>
<?php
    $role = array();
    if (is_numeric($paths[1])){
      $id = $paths[1]; 
      // Negative ID is used for creating a new role
      if($id < 0){
        $role = array(
          'id' => $id,
          'sortorder' => 0,
          'name' => '',
          'desc' => '',
        );
      }
      // Lookup to see if there is an existing role
      else{
        $roles = core_db_fetch(DB_NAME, 'roles', null, array('id' => $id));
        if (is_array($roles) && count($roles)>0){
          // Set role to database record
          $role = $roles[0];
        }
      }
    }
    if (is_array($role) && count($role)>0){
      if (isset($paths[2]) && $paths[2] == 'delete' && $id >= 0){
        if ($id <= 2){
          // Don't allow deleting system required roles
?>
      <p>It is not possible to delete <strong><?php echo $role['name']; ?></strong> as it reflects a system required role.</p>
      <a class="button" href="<?php echo APP_ROOT; ?>role/<?php echo $id; ?>" >Cancel</a>
<?php        
        }
        else{
          // Role delete confirmation
?>
  <h3>Delete Role</h3>
  <p>Are you sure you want to delete the role named <strong><?php echo $role['name']; ?></strong>? This cannot be undone!</p>
  <form method="post" action="" id="admin/role" >
    <input type="hidden" name="formid" value="admin/role" />
    <input type="hidden" name="role[id]" value="<?php echo $role['id']; ?>" />
    <input type="hidden" name="confirmed" value="1" />
    <input type="hidden" name="command" value="delete" />
    <a class="button" href="<?php echo APP_ROOT; ?>role/<?php echo $role['id']; ?>/" >Cancel</a> 
    <input class="button alert" type="submit" name="send" value="Delete" />   
  </form>
<?php        
        }
      }
      else{
        // Set messages and classes to empty strings
        $class = $msg = array(
          'sortorder' => '',
          'name' => '',
          'desc' => '',
        );

        // Check for error/repost
        if (isset($_SESSION['repost']) && is_array($_SESSION['repost'])){
          foreach ($_SESSION['repost'] as $key => $result){
            $role[$key] = $result['value'];
            if ($result['error']){
              $msg[$key] = " <strong>{$result['message']}</strong>";
              $class[$key] = 'invalid';
            }
          }
        }
        // Role form
?>   
  <h3>Role administration Form</h3>   
  <form method="post" action="" id="admin/role" >
    <input type="hidden" name="formid" value="admin/role" />
    <input type="hidden" name="command" value="write" />
    <input type="hidden" name="role[id]" value="<?php echo $role['id']; ?>" />    
    <label for="role[sortorder]">Sort Order (integer)<?php echo $msg['sortorder']; ?></label><input class="<?php echo $class['sortorder']; ?>" name="role[sortorder]" type="text" maxlength="10" value="<?php echo $role['sortorder']; ?>" />
    <label for="role[name]">Role Name<?php echo $msg['name']; ?></label><input class="required <?php echo $class['name']; ?>" name="role[name]" type="text" maxlength="100" value="<?php echo $role['name']; ?>" />
    <label for="role[desc]">Description<?php echo $msg['desc']; ?></label><textarea class="<?php echo $class['desc']; ?>" name="role[desc]" type="text" maxlength="1000" /><?php echo $role['desc']; ?></textarea>
    <br />
    <input class="button" type="submit" name="send" value="Save" />
    <a class="button" href="<?php echo APP_ROOT; ?>role/<?php echo $role['id']; ?>/" >Reset</a>
    <a class="button" href="<?php echo APP_ROOT; ?>role/" >Close</a>
<?php 
        if ($role['id']>2){
?>
    <a class="right button alert" href="<?php echo APP_ROOT; ?>role/<?php echo $role['id']; ?>/delete/" >Delete...</a>
<?php
        }
?>
  </form>

<?php
      }
    }
    else{
?>
    <p>Page not found, please go <a href="<?php echo APP_ROOT; ?>role/">back</a>.</p>
<?php 
    }
  }
  else{  
    // Navigation to select Roles
?>
  <h2>Roles</h2>
<?php
    $roles = core_db_fetch(DB_NAME, 'roles', array('id', 'name'),null,null, array('sortorder', 'name'));
    if (is_array($roles) && count($roles)>0){
?>
    <ul>
      <li><a href="<?php echo APP_ROOT; ?>role/-1">[+]</a></li>
<?php
      foreach ($roles as $role){
?>
      <li><a href="<?php echo APP_ROOT; ?>role/<?php echo $role['id']; ?>"><?php echo $role['name']; ?></a></li>
<?php
      }
?>
    </ul>
<?php
    }
    else{
?>
    <p>There are no roles, that is a problem!</p>
<?php
    }  
  }

}

/**
 * Renders page admin form
 * 
 * @param array $paths Navigation path data
 * 
 */
function core_admin_render_page($paths){
  if (isset($paths[1]) && $paths[1] != ''){
?>
  <?php echo $_SESSION['message']; ?>
<?php
    $page = array();
    if (is_numeric($paths[1])){
      $id = $paths[1]; 
      // Negative ID is used for creating a new page
      if($id < 0){
        $page = array(
          'id' => $id,
          'parent_id' => 0,
          'url_code' => '',
          'title' => '',
          'app_root' => 0,
          'core_page' => 0,
          'ajax_call' => '',
          'render_call' => '',
          'activated' => '',
          'deactivated' => '',
          'summary' => '',
          'content' => '',
          'groups' => array(0),
          'roles' => array(),
        );
      }
      // Lookup to see if there is an existing page
      else{
        $pages = core_db_fetch(DB_NAME, 'pages', null, array('id' => $id));
        if (is_array($pages) && count($pages)>0){
          // Set page to database record(s)
          $page = $pages[0];
          $page_content = core_db_fetch(DB_NAME, 'page_content', null, array('page_id' => $id), null, array('created'));
          if (!is_array($page_content) || count($page_content) == 0){
            $page['summary'] = '';
            $page['content'] = '';
          }
          else{
            foreach ($page_content as $content){
              // Set values to latest records in content history
              $page['summary'] = $content['summary'];
              $page['content'] = $content['content'];
            }
          }
          $page['roles'] = array();
          $roles = core_db_fetch(DB_NAME, 'page_roles', array('role_id'), array('page_id' => $id));
          if (is_array($roles) && count($roles)>0){
            foreach ($roles as $role){
              $page['roles'][] = $role['role_id'];
            }
          }
          $page['groups'] = array();
          $groups = core_db_fetch(DB_NAME, 'page_groups', array('group_id'), array('page_id' => $id));
          if (is_array($groups) && count($groups)>0){
            foreach ($groups as $group){
              $page['groups'][] = $group['group_id'];
            }
          }
          
        }
      }
    }
    if (is_array($page) && count($page)>0){
      if (isset($paths[2]) && $paths[2] == 'delete' && $id >= 0){
        $children = core_db_fetch(DB_NAME, 'pages', array('id'), array('parent_id' => $id));
        if ($page['core_page'] == 1){
          // Don't allow deleting a core page
?>
      <p>It is not possible to delete <strong><?php echo $page['title']; ?></strong> as it is a core page.</p>
      <a class="button" href="<?php echo APP_ROOT; ?>page/<?php echo $id; ?>" >Cancel</a>
<?php        
        }
        elseif(is_array($children) && count($children)>0){
?>
      <p>It is not possible to delete <strong><?php echo $page['title']; ?></strong> as it has subpages attached to it.</p>
      <a class="button" href="<?php echo APP_ROOT; ?>page/<?php echo $id; ?>" >Cancel</a> 
<?php       
        }
        else{
          // Page delete confirmation
?>
  <h3>Delete Page</h3>
  <p>Are you sure you want to delete the page named <strong><?php echo $page['title']; ?></strong>? This cannot be undone! If you want to unpublish, hit cancel and select that option!</p>
  <form method="post" action="" id="admin/page" >
    <input type="hidden" name="formid" value="admin/page" />
    <input type="hidden" name="page[id]" value="<?php echo $page['id']; ?>" />
    <input type="hidden" name="confirmed" value="1" />
    <input type="hidden" name="command" value="delete" />
    <a class="button" href="<?php echo APP_ROOT; ?>page/<?php echo $page['id']; ?>" >Cancel</a> 
    <input class="button alert" type="submit" name="send" value="Delete" />   
  </form>
<?php        
        }
      }
      else{
        // Set messages and classes to empty strings
        $class = $msg = array(
          'sortorder' => '',
          'name' => '',
          'desc' => '',
        );

        // Check for error/repost
        if (isset($_SESSION['repost']) && is_array($_SESSION['repost'])){
          foreach ($_SESSION['repost'] as $key => $result){
            $page[$key] = $result['value'];
            if ($result['error']){
              $msg[$key] = " <strong>{$result['message']}</strong>";
              $class[$key] = 'invalid';
            }
          }
        }
?>   
  <h3>Page administration Form</h3>   
  <form method="post" action="" id="admin/page" >
    <input type="hidden" name="formid" value="admin/page" />
    <input type="hidden" name="command" value="write" />
    <input type="hidden" name="page[id]" value="<?php echo $page['id']; ?>" />    
    <label for="page[url_code]">URL code (for that level, no slashes!)<?php echo $msg['url_code']; ?></label><input class="required <?php echo $class['url_code']; ?>" name="page[url_code]" type="text" maxlength="100" value="<?php echo $page['url_code']; ?>" />
    <label for="page[title]">Page Title<?php echo $msg['title']; ?></label><input class="required <?php echo $class['title']; ?>" name="page[title]" type="text" maxlength="100" value="<?php echo $page['title']; ?>" />
    <label for="page[parent_id]">Parent Page</label>
<?php   
        $disabled_ids = array();
        $disabled_ids = core_process_get_pagechildren($page['id'], $disabled_ids);
        core_admin_render_pagetree(array($page['parent_id']), 'page[parent_id]', 'radio', null, $disabled_ids);  
?>
    <h4>Publishing</h4>
    <fieldset id="publishing" >
      <label for="page[activated]">Publish Date (set if you want it to auto-publish at a certain time)<?php echo $msg['activated']; ?></label><input class="<?php echo $class['activated']; ?>" name="page[activated]" type="text" maxlength="100" value="<?php echo $page['activated']; ?>" />
      <label for="page[deactivated]">Unpublish Date (set if you want it to auto-unpublish at a certain time)<?php echo $msg['deactivated']; ?></label><input class="<?php echo $class['deactivated']; ?>" name="page[deactivated]" type="text" maxlength="100" value="<?php echo $page['deactivated']; ?>" />
      <label for="page[summary]">Optional Summary<?php echo $msg['summary']; ?></label><textarea class="<?php echo $class['summary']; ?>" name="page[summary]" maxlength="100000" ><?php echo $page['summary']; ?></textarea>
      <label for="page[content]">Content<?php echo $msg['content']; ?></label><textarea class="<?php echo $class['content']; ?>" name="page[content]" maxlength="100000" ><?php echo $page['content']; ?></textarea>      
    </fieldset>
    <h4>Advanced</h4>
    <fieldset id="advanced">
      <label for="page[ajax_call]">Function to call before loading page<?php echo $msg['ajax_call']; ?></label><input class="<?php echo $class['ajax_call']; ?>" name="page[ajax_call]" type="text" maxlength="100" value="<?php echo $page['ajax_call']; ?>" />
      <label for="page[render_call]">Function to call within the page<?php echo $msg['render_call']; ?></label><input class="<?php echo $class['render_call']; ?>" name="page[render_call]" type="text" maxlength="100" value="<?php echo $page['render_call']; ?>" />
      <label for="page[app_root]">Root level for a sub-application<?php echo $msg['app_root']; ?></label><input class="<?php echo $class['app_root']; ?>" name="page[app_root]" type="text" maxlength="100" value="<?php echo $page['app_root']; ?>" />
      <label for="page[core_page]">Protected Page (if yes, prevents accidental deletion)<?php echo $msg['core_page']; ?></label><input class="<?php echo $class['core_page']; ?>" name="page[core_page]" type="text" maxlength="100" value="<?php echo $page['core_page']; ?>" />
    </fieldset>

    <h4>Access Permissions</h4>
    <fieldset>
    <label for="roles[]">Roles</label>
    <ul>
<?php
        $roles = core_db_fetch(DB_NAME, 'roles', null, null, null, array('sortorder', 'name'));
        foreach ($roles as $role){
          if (in_array(($role['id']), $page['roles'])){
            $checked = 'checked';
          }
          else{
            $checked = '';
          }
?>
      <li><input id="checkbox_role_<?php echo $role['id']; ?>" type="checkbox" value="<?php echo $role['id']; ?>" name="roles[]" <?php echo $checked; ?> /><span class="hand" onclick="document.getElementById('checkbox_role_<?php echo $role['id']; ?>').click();" ><?php echo $role['name']; ?></span></li>
<?php      
        }
?>
    </ul>
    <label for="groups[]">Groups</label>
<?php   
        core_admin_render_grouptree($page['groups']);  
?>
    </fieldset>
    <input class="button" type="submit" name="send" value="Save" />
    <a class="button" href="<?php echo APP_ROOT; ?>page/<?php echo $page['id']; ?>/" >Reset</a>
    <a class="button" href="<?php echo APP_ROOT; ?>page/" >Close</a>
<?php 
        if ($page['id']>0){
?>
    <a class="right button alert" href="<?php echo APP_ROOT; ?>page/<?php echo $page['id']; ?>/delete/" >Delete...</a>
<?php
        }
?>
  </form>

<?php
      }
    }
    else{
?>
    <p>Page not found, please go <a href="<?php echo APP_ROOT; ?>page/">back</a>.</p>
<?php 
    }
  }
  else{  
    // Navigation to select Pages
?>
  <h2>Pages</h2>
<?php
    $pages = core_db_fetch(DB_NAME, 'pages', array('id'));
    if (is_array($pages) && count($pages)>0){
?>
    <p><a href="<?php echo APP_ROOT; ?>page/-1">[+]</a><p>
<?php
      core_admin_render_pagenav();
    }
    else{
?>
    <p>There are no pages, that is a problem!</p>
<?php
    }  
  }


}

/**
 * Renders file admin form
 * 
 * @param array $paths Navigation path data
 * 
 */
function core_admin_render_file($paths){
  // Navigation to select files
  
  
  
  // File delete confirmation
  
  
  // File form

}

/**
 * Renders menu admin form
 * 
 * @param array $paths Navigation path data
 * 
 */
function core_admin_render_menu($paths){
  // Navigation to select menus (tree)
  
  
  
  // Menu delete confirmation
  
  
  // Menu form

}


/**************************************************
 * Helpers
 **************************************************/

/**
 * Renders recursive group tree admin form
 * 
 * @param array $selected Array of selected group IDs
 * @param string $varname Basename of input elements
 * @param string $type Type of tree (checkbox or radio)
 * @param int $parent_id ID of parent group
 * @param array $disabled_ids Array of IDs of children of current group
 * 
 */ 
function core_admin_render_grouptree($selected, $varname='group', $type='checkbox', $parent_id=null, $disabled_ids=array()){
  // Do stuff at top level
  $db = new coreDb();
  $db->fetch('groups', array('id', 'name'), array('parent_id' => $parent_id), null, array('sortorder', 'name'));
  if ($db->affected_rows >0){
?>
  <ul>
<?php
    foreach($db->output as $group){
      if ($type == 'checkbox'){
        $bracket = 's[]';
      }
      else{
        $bracket = '';
      }
      if (in_array($group['id'], $selected)){
        $checked = 'checked';
      }
      else{
        $checked = '';
      }
      if (in_array($group['id'], $disabled_ids)){
        $disabled = 'disabled';
      }
      else{
        $disabled = '';
      }
?>
    <li><input id="checkbox_group_<?php echo $group['id']; ?>" type="<?php echo $type; ?>" value="<?php echo $group['id']; ?>" name="<?php echo $varname . $bracket; ?>" <?php echo $checked; ?> <?php echo $disabled; ?> /><span class="hand <?php echo $disabled; ?>" onclick="document.getElementById('checkbox_group_<?php echo $group['id']; ?>').click();"><?php echo $group['name']; ?></span>
<?php
      // Recurse!
      core_admin_render_grouptree($selected, $varname, $type, $group['id'], $disabled_ids);
    }
?>
  </ul>
<?php
  }
}

/**
 * Renders recursive group navigation
 * 
 * @param int $parent_id Parent ID of group
 * 
 */ 
function core_admin_render_groupnav($parent_id=null){
  // Do stuff at top level
  $db = new coreDb();
  $db->fetch('groups', array('id', 'name'), array('parent_id' => $parent_id), null, array('sortorder', 'name'));
  if ($db->affected_rows > 0){
?>
  <ul>
<?php
    foreach($db->output as $group){
?>
    <li><a href="<?php echo APP_ROOT; ?>group/<?php echo $group['id']; ?>/"><?php echo $group['name'];?></a></li>
<?php
      // Recurse!
      core_admin_render_groupnav($group['id']);
    }
?>
  </ul>
<?php
  }
}

/**
 * Renders recursive page tree admin form
 * 
 * @param array $selected Array of selected page IDs
 * @param string $varname Basename of input elements
 * @param string $type Type of tree (checkbox or radio)
 * @param int $parent_id ID of parent page
 * @param array $disabled_ids Array of IDs of children of current page
 * 
 */ 
function core_admin_render_pagetree($selected, $varname='page', $type='checkbox', $parent_id=null, $disabled_ids=array()){
  // Do stuff at top level
  $pages = core_db_fetch(DB_NAME, 'pages', array('id', 'url_code', 'title', 'app_root'), array('parent_id' => $parent_id), null, array('url_code'));
  if (is_array($pages) && count($pages)>0){
?>
  <ul>
<?php
    foreach($pages as $page){
      if ($type == 'checkbox'){
        $bracket = 's[]';
      }
      else{
        $bracket = '';
      }
      if (in_array($page['id'], $selected)){
        $checked = 'checked';
      }
      else{
        $checked = '';
      }
      if (in_array($page['id'], $disabled_ids) || $page['app_root'] == 1){
        $disabled = 'disabled';
      }
      else{
        $disabled = '';
      }
?>
    <li>
      <input id="checkbox_page_<?php echo $page['id']; ?>" type="<?php echo $type; ?>" value="<?php echo $page['id']; ?>" name="<?php echo $varname . $bracket; ?>" <?php echo $checked; ?> <?php echo $disabled; ?> /><span class="hand <?php echo $disabled; ?>" onclick="document.getElementById('checkbox_page_<?php echo $page['id']; ?>').click();"><?php echo $page['url_code']; ?>/ <em><?php echo $page['title']; ?></em></span>
<?php
      // Recurse!
      core_admin_render_pagetree($selected, $varname, $type, $page['id'], $disabled_ids);
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
}

/**
 * Renders recursive page navigation
 * 
 * @param int $parent_id Parent ID of page
 * 
 */ 
function core_admin_render_pagenav($parent_id=null){
  // Do stuff at top level
  $pages = core_db_fetch(DB_NAME, 'pages', array('id', 'url_code', 'title'), array('parent_id' => $parent_id), null, array('url_code'));
  if (is_array($pages) && count($pages)>0){
?>
  <ul>
<?php
    foreach($pages as $page){
?>
    <li>
      <a href="<?php echo APP_ROOT; ?>page/<?php echo $page['id']; ?>/"><?php echo $page['url_code'];?>/</a> <em><?php echo $page['title'];?></em>
<?php
      // Recurse!
      core_admin_render_pagenav($page['id']);
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
}

