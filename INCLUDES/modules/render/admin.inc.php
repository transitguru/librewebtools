<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders pages that are usually only seen by an admin
 */

/**
 * Processes any AJAX request for the Admin application
 * 
 * @param boolean $wrapper Optional field designated that it was called from the wrapper 
 * 
 * @return void
 * 
 */
function lwt_ajax_admin_users($wrapper = false){
  if (!$wrapper){
    header('Cache-Control: no-cache');
  }
  if (isset($_POST['ajax']) && $_POST['ajax'] == 1){
    
    //Define Navigation levels if navigating
    $nullify = FALSE;
    for($i=0; $i <= 10; $i++){
      if ($nullify){
        $_SESSION['admin']['navigate'][$i] = NULL;
      }
      if(isset($_POST['navigate'][$i]) && $_POST['navigate'][$i] != ''){
        if($i==0){
          $_SESSION['admin']['navigate'] = array();
        }
        $_SESSION['admin']['navigate'][$i] = $_POST['navigate'][$i];
        $nullify = TRUE;
      }
    }
    
    //Process write events
    if (isset($_POST['command']) && $_POST['command'] == 'write'){
      if (isset($_POST['user']) && count($_POST['user'])>0){
        $inputs = array();
        if ($_POST['user']['id'] == -1){
          $where = NULL;
        }
        else{
          $where = array('id' => $_POST['user']['id']);
        }
        $inputs['login'] = $_POST['user']['login'];
        $inputs['firstname'] = $_POST['user']['firstname'];
        $inputs['lastname'] = $_POST['user']['lastname'];
        $inputs['email'] = $_POST['user']['email'];
        $inputs['desc'] = $_POST['user']['desc'];
        
        $result = lwt_database_write(DB_NAME, 'users', $inputs, $where);
        if ($result['error']){
          $_POST['id'] = $_POST['user']['id'];
        }
        else{
          $user_info = lwt_database_fetch_simple(DB_NAME, 'users', NULL, array('login' => $inputs['login']));
          $id = $user_info[0]['id'];
        }
        
        // Add roles
        $user_roles = lwt_database_fetch_simple(DB_NAME, 'user_roles', NULL, array('user_id' => $id), NULL, NULL, 'role_id');
        $chosen_roles = $_POST['user']['roles'];
        $roles = lwt_database_fetch_simple(DB_NAME, 'roles');
        foreach ($roles as $role){
          // Add new
          if (in_array($role['id'],$chosen_roles) && !array_key_exists($role['id'],$user_roles)){
            $sql = "INSERT INTO `user_roles` (`user_id`,`role_id`) VALUES ({$id},{$role['id']})";
            $result = lwt_database_write_raw(DB_NAME, $sql);
          }
          // Delete old
          elseif(!in_array($role['id'],$chosen_roles) && array_key_exists($role['id'],$user_roles)){
            $sql = "DELETE FROM `user_roles` WHERE `user_id`={$id} AND `role_id`={$role['id']}";
            $result = lwt_database_write_raw(DB_NAME, $sql);
          }
          
          if ($result['error']){
            $message = $result['message'];
          }
        }

        //Add groups
        $user_groups = lwt_database_fetch_simple(DB_NAME, 'user_groups', NULL, array('user_id' => $id), NULL, NULL, 'group_id');
        $chosen_groups = $_POST['user']['groups'];
        $groups = lwt_database_fetch_simple(DB_NAME, 'groups');
        foreach ($groups as $group){
          // Add new
          if (in_array($group['id'],$chosen_groups) && !array_key_exists($group['id'],$user_groups)){
            $sql = "INSERT INTO `user_groups` (`user_id`,`group_id`) VALUES ({$id},{$group['id']})";
            $result = lwt_database_write_raw(DB_NAME, $sql);
          }
          // Delete old
          elseif(!in_array($group['id'],$chosen_groups) && array_key_exists($group['id'],$user_groups)){
            $sql = "DELETE FROM `user_groups` WHERE `user_id`={$id} AND `group_id`={$group['id']}";
            $result = lwt_database_write_raw(DB_NAME, $sql);
          }
          
          if ($result['error']){
            $message = $result['message'];
          }
        }
        
        //Set random password for new user
        if ($_POST['id'] == -1){
          $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
          $len = strlen($chars);
          $pass = "";
          for ($i = 0; $i<12; $i++){
            $num = rand(0,$len-1);
            $pass .= substr($chars, $num, 1);
          }
          lwt_auth_session_setpassword($id, $pass);
        }
        
        //Reset password
        if (isset($_POST['users']['reset']) && $_POST['users']['reset'] == 1 && !$result['error']){
          lwt_auth_session_resetpassword($inputs['email']);
        }
      }
      if (isset($_POST['role']) && count($_POST['role'])>0){
        $inputs = array();
        if ($_POST['role']['id'] == -1){
          $where = NULL;
        }
        else{
          $where = array('id' => $_POST['role']['id']);
        }
        $inputs['name'] = $_POST['role']['name'];
        $inputs['sortorder'] = $_POST['role']['sortorder'];
        $inputs['desc'] = $_POST['role']['desc'];
        
        $result = lwt_database_write(DB_NAME, 'roles', $inputs, $where);
        if ($result['error']){
          $_POST['id'] = $_POST['role']['id'];
          $message = $result['message'];
        }
        else{
          $role_info = lwt_database_fetch_simple(DB_NAME, 'roles', NULL, array('name' => $inputs['name']));
          $id = $role_info[0]['id'];
        }
      }
      if (isset($_POST['group']) && count($_POST['group'])>0){
        $inputs = array();
        if ($_POST['group']['id'] == -1){
          $where = NULL;
        }
        else{
          $where = array('id' => $_POST['group']['id']);
        }
        $inputs['name'] = $_POST['group']['name'];
        $inputs['desc'] = $_POST['group']['desc'];
        
        $result = lwt_database_write(DB_NAME, 'groups', $inputs, $where);
        if ($result['error']){
          $_POST['id'] = $_POST['group']['id'];
          $message = $result['message'];
        }
        else{
          $group_info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('name' => $inputs['name']));
          $id = $group_info[0]['id'];
        }
        // Apply group hierarchy
        if ($_POST['group']['id'] == -1){
          $parent_id = 0;
          if (isset($_POST['group']['parent_id']) && $_POST['group']['parent_id'] != ''){
            $parent_id = $_POST['group']['parent_id'];
          }
          $hierarchy = array(
            'group_id' => $id,
            'parent_id' => $parent_id,
          );
          lwt_database_write(DB_NAME, 'group_hierarchy', $hierarchy, NULL);
        }
        else{
          $parent_id = $_POST['group']['parent_id'];
          $children = array();
          $children = lwt_process_get_children($id,$children);
          $parent = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('group_id' => $id));
          $selected = $parent[0]['parent_id'];
          if (!in_array($parent_id, $children) && $parent_id != $selected){
            $result = lwt_database_write(DB_NAME, 'group_hierarchy', array('parent_id' => $parent_id), array('group_id' => $id));
          }
        }
      }
    }
    
    //Process delete events
    if (isset($_POST['command']) && $_POST['command'] == 'delete'){
      if (isset($_POST['user']) && count($_POST['user'])>0){
        if ($_POST['user']['id']>1){
          $sql = "DELETE FROM `users` WHERE `id`={$_POST['user']['id']}";
          $result = lwt_database_write_raw(DB_NAME, $sql);
        }
        else{
          $result['error'] = true;
          $result['message'] = '<span class="error">You cannot remove the administrator account!</span>';
        }
        if ($result['error']){
          $_POST['id'] = $_POST['user']['id'];
        }
      }
      if (isset($_POST['role']) && count($_POST['role'])>0){
        if ($_POST['role']['id']>1){
          $sql = "DELETE FROM `roles` WHERE `id`={$_POST['role']['id']}";
          $result = lwt_database_write_raw(DB_NAME, $sql);
        }
        else{
          $result['error'] = true;
          $result['message'] = '<span class="error">You cannot remove the administrator or unauthenticated roles!</span>';
        }
        if ($result['error']){
          $_POST['id'] = $_POST['role']['id'];
        }
      }
      if (isset($_POST['group']) && count($_POST['group'])>0){
        if ($_POST['group']['id']>0){
          $children = array();
          $children = lwt_process_get_children($_POST['group']['id'],$children);
          if (count($children)>1){
            $result['error'] = true;
            $result['message'] = '<span class="error">You cannot remove a group with nested groups!</span>';
          }
          else{
            $sql = "DELETE FROM `groups` WHERE `id`={$_POST['group']['id']}";
            $result = lwt_database_write_raw(DB_NAME, $sql);
          }
        }
        else{
          $result['error'] = true;
          $result['message'] = '<span class="error">You cannot remove the root group!</span>';
        }
        if ($result['error']){
          $_POST['id'] = $_POST['group']['id'];
        }
      }
      $_POST['command'] = 'write';
    }

    
    //Render the navigation area if navigating or no error
    if (isset($_POST['command']) && ($_POST['command'] == 'navigate' || ($_POST['command'] == 'write' && !$result['error']))){
      $users = lwt_database_fetch_simple(DB_NAME, 'users', NULL, NULL, NULL, array('lastname','firstname'));
      $num = count($users);
?>    
        <h2><a href="javascript:;" onclick="ajaxPostLite('command=navigate&navigate[0]=users&ajax=1','','adminarea','');">Users (<?php echo $num; ?>)</a></h2>
<?php
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'users'){
?>
          <ul>
            <li><a href="javascript:;" onclick="hideTooltip(event);ajaxPostLite('command=view&id=-1&ajax=1','','adminarea','');" onmousemove="showTooltip(event,'Add User');" onmouseout="hideTooltip(event);">[+]</a></li>
<?php
        foreach ($users as $user){
?>
            <li><a href="javascript:;" onclick="ajaxPostLite('command=view&id=<?php echo $user['id']; ?>&ajax=1','','adminarea','');"><?php echo $user['login']; ?> (<?php echo $user['lastname']; ?>, <?php echo $user['firstname']; ?>)</a></li>
<?php
        }
?>
          </ul>
<?php
      }
      $roles = lwt_database_fetch_simple(DB_NAME, 'roles', NULL, NULL, NULL, array('sortorder', 'name'));
      $num = count($roles);
?>
        <h2><a href="javascript:;" onclick="ajaxPostLite('command=navigate&navigate[0]=roles&ajax=1','','adminarea','');">Roles (<?php echo $num; ?>)</a></h2>
<?php
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'roles'){
?>
          <ul>
            <li><a href="javascript:;" onclick="hideTooltip(event);ajaxPostLite('command=view&id=-1&ajax=1','','adminarea','');" onmousemove="showTooltip(event,'Add Role');" onmouseout="hideTooltip(event);">[+]</a></li>
<?php
        foreach ($roles as $role){
?>
            <li><a href="javascript:;" onclick="ajaxPostLite('command=view&id=<?php echo $role['id']; ?>&ajax=1','','adminarea','');"><?php echo $role['name']; ?></a></li>
<?php
        }
?>
          </ul>
<?php
      }
      $groups = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, NULL, NULL, array('name'));
      $num = count($groups);
?>
        <h2><a href="javascript:;" onclick="ajaxPostLite('command=navigate&navigate[0]=groups&ajax=1','','adminarea','');">Groups (<?php echo $num; ?>)</a></h2>
<?php
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'groups'){
?>
          <ul>
<?php
$info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => 0));
?>
            <li><a href="javascript:;" onclick="ajaxPostLite('command=view&id=<?php echo $info[0]['id']; ?>&ajax=1','','adminarea','');" ><?php echo $info[0]['name'];?></a>
<?php
        lwt_admin_render_grouplinks(0);
?>
            </li>
            <li><a href="javascript:;" onclick="hideTooltip(event);ajaxPostLite('command=view&id=-1&ajax=1','','adminarea','');" onmousemove="showTooltip(event,'Add Group');" onmouseout="hideTooltip(event);">[+]</a></li>
          </ul>
<?php
      }
    }
    
    //Render editing interfaces if requested or error upon write
    elseif (isset($_POST['command']) && ($_POST['command'] == 'view' || ($_POST['command'] == 'write' && $result['error']))){
      // User editing area
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'users'){
        if ($_POST['id'] == -1 && !isset($inputs)){
          $user = array(
            'id' => -1,
            'login' => '',
            'firstname' => '',
            'lastname' => '',
            'email' => '',
            'desc' => '',
          );
        }
        elseif($_POST['id'] == -1){
          $user['id'] = $_POST['id'];
          $user['login'] = $inputs['login'];
          $user['firstname'] = $inputs['firstname'];
          $user['lastname'] = $inputs['lastname'];
          $user['email'] = $inputs['email'];
          $user['desc'] = $inputs['desc'];
        }
        else{
          $users = lwt_database_fetch_simple(DB_NAME, 'users', NULL, array('id' => $_POST['id']));
          $user = $users[0];
        }
        if ($result['error']){
          $message = $result['message'];
        }
        else{
          $message = '<span class="warning">Please enter data in the form</span>';
        }
        echo $message;
?>
      <form action="" enctype="multipart/form-data" method="post" id="poster" onsubmit="event.preventDefault(); ajaxPost(this,'adminarea','');">
        <h2>General Information</h2>
        <input type="hidden" name="command" value="write" />
        <input type="hidden" name="ajax" value="1" />
        <input type="hidden" name="user[id]" value="<?php echo $user['id']; ?>" />
        <label for="user[login]">Login</label><input type="text" name="user[login]" value="<?php echo $user['login']; ?>" /><br />
        <label for="user[firstname]">First Name</label><input type="text" name="user[firstname]" value="<?php echo $user['firstname']; ?>" /><br />
        <label for="user[lastname]">Last Name</label><input type="text" name="user[lastname]" value="<?php echo $user['lastname']; ?>" /><br />
        <label for="user[email]">Email</label><input type="text" name="user[email]" value="<?php echo $user['email']; ?>" /><br />
        <label for="user[desc]">Description</label><textarea name="user[desc]"><?php echo htmlentities($user['desc']); ?></textarea><br class="clear" />
        <h2>Roles</h2>
        <ul>
<?php
        $roles = lwt_database_fetch_simple(DB_NAME, 'roles', NULL, NULL, NULL, array('sortorder', 'id'));
        $user_roles = lwt_database_fetch_simple(DB_NAME, 'user_roles', NULL, array('user_id' => $user['id']), NULL, NULL, 'role_id');
        foreach ($roles as $role){
?>
          <li><input type="checkbox" id="role_<?php echo $role['id'];?>" name="user[roles][]" value="<?php echo $role['id']; ?>" <?php if(array_key_exists($role['id'], $user_roles)){echo 'checked';} ?> /><span class="hand" onclick="document.getElementById('role_<?php echo $role['id'];?>').click();" ><?php echo $role['name']; ?></span></li>
<?php
        }
?>
        </ul>
        <h2>Groups</h2>
        <ul>
<?php
        $user_groups = lwt_database_fetch_simple(DB_NAME, 'user_groups', NULL, array('user_id' => $user['id']), NULL, NULL, 'group_id');
        $info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => 0));
?>
          <li><input type="checkbox" id="group_<?php echo $info[0]['id']; ?>" name="user[groups][]" value="<?php echo $info[0]['id']; ?>" <?php if(array_key_exists($info[0]['id'], $user_groups)){echo 'checked';} ?> /><span class="hand" onclick="document.getElementById('group_<?php echo $info[0]['id'];?>').click();" ><?php echo $info[0]['name'];?></span>
        <?php lwt_admin_render_groupcheckbox(0, $user_groups); ?>
          </li>
        </ul>
        <input type="checkbox" name="users[reset]" value="1" <?php if ($user['id'] == -1){ echo 'checked';} ?> />Reset User's password (will email user reset information)<br /> 
        <input type="submit" name="submit" value="Update" />
      </form>
      <button onclick="event.preventDefault();ajaxPostLite('command=view&id=<?php echo $user['id']; ?>&ajax=1','','adminarea','');">Reset form</button>
      <button onclick="event.preventDefault();ajaxPostLite('command=navigate&navigate[0]=users&ajax=1','','adminarea','');">Cancel</button>
<?php
        if ($user['id'] > 1){
?>
      <button class="right" onclick="event.preventDefault();ajaxPostLite('command=delete&user[id]=<?php echo $user['id']; ?>&ajax=1','','adminarea','');">Delete</button>
<?php
        }
      }
      // Role editing area
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'roles'){
        if ($_POST['id'] == -1 && !isset($inputs)){
          $role = array(
            'id' => -1,
            'name' => '',
            'sortorder' => '0',
            'desc' => '',
          );
        }
        elseif($_POST['id'] == -1){
          $role['id'] = $_POST['id'];
          $role['name'] = $inputs['name'];
          $role['sortorder'] = $inputs['sortorder'];
          $role['desc'] = $inputs['desc'];
        }
        else{
          $roles = lwt_database_fetch_simple(DB_NAME, 'roles', NULL, array('id' => $_POST['id']));
          $role = $roles[0];
        }
        if ($result['error']){
          $message = $result['message'];
        }
        else{
          $message = '<span class="warning">Please enter data in the form</span>';
        }
        echo $message;
?>
      <form action="" enctype="multipart/form-data" method="post" id="poster" onsubmit="event.preventDefault(); ajaxPost(this,'adminarea','');">
        <h2>General Information</h2>
        <input type="hidden" name="command" value="write" />
        <input type="hidden" name="ajax" value="1" />
        <input type="hidden" name="role[id]" value="<?php echo $role['id']; ?>" />
        <label for="role[name]">Role</label><input type="text" name="role[name]" value="<?php echo $role['name']; ?>" /><br />
        <label for="role[sortorder]">Sort Weight</label><input type="text" name="role[sortorder]" value="<?php echo $role['sortorder']; ?>" /><br />
        <label for="role[desc]">Description</label><textarea name="role[desc]"><?php echo htmlentities($role['desc']); ?></textarea><br class="clear" />
        <input type="submit" name="submit" value="Update" />
      </form>
      <button onclick="event.preventDefault();ajaxPostLite('command=view&id=<?php echo $role['id']; ?>&ajax=1','','adminarea','');">Reset form</button>
      <button onclick="event.preventDefault();ajaxPostLite('command=navigate&navigate[0]=roles&ajax=1','','adminarea','');">Cancel</button>
<?php
        if ($role['id'] > 1){
?>
      <button class="right" onclick="event.preventDefault();ajaxPostLite('command=delete&role[id]=<?php echo $role['id']; ?>&ajax=1','','adminarea','');">Delete</button>
<?php
        }
      }
      // Group editing area
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'groups'){
        if ($_POST['id'] == -1 && !isset($inputs)){
          $group = array(
            'id' => -1,
            'name' => '',
            'desc' => '',
          );
        }
        elseif($_POST['id'] == -1){
          $group['id'] = $_POST['id'];
          $group['name'] = $inputs['name'];
          $group['desc'] = $inputs['desc'];
        }
        else{
          $groups = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => $_POST['id']));
          $group = $groups[0];
        }
        if ($result['error']){
          $message = $result['message'];
        }
        else{
          $message = '<span class="warning">Please enter data in the form</span>';
        }
        echo $message;
?>
      <form action="" enctype="multipart/form-data" method="post" id="poster" onsubmit="event.preventDefault(); ajaxPost(this,'adminarea','');">
        <h2>General Information</h2>
        <input type="hidden" name="command" value="write" />
        <input type="hidden" name="ajax" value="1" />
        <input type="hidden" name="group[id]" value="<?php echo $group['id']; ?>" />
        <label for="group[name]">Group</label><input type="text" name="group[name]" value="<?php echo $group['name']; ?>" /><br />
        <label for="group[desc]">Description</label><textarea name="group[desc]"><?php echo htmlentities($group['desc']); ?></textarea><br class="clear" />
        <label for="group[parent_id]">Select Parent</label><br class="clear" />
<?php
        $children = array();
        $children = lwt_process_get_children($group['id'],$children);
        $parent = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('group_id' => $group['id']));
        $selected = $parent[0]['parent_id'];
        $info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => $group['group_id']));
?>
        <ul>
        <li><input type="radio" id="group_<?php echo $info[0]['id']; ?>" name="group[parent_id]" value="<?php echo $info[0]['id']; ?>" <?php if($info[0]['id'] == $selected){echo 'checked';} ?> <?php if(in_array($info[0]['id'], $children)){echo 'disabled';} ?> /><span class="hand<?php if(in_array($info[0]['id'], $children)){echo ' disabled';} ?>" onclick="document.getElementById('group_<?php echo $info[0]['id'];?>').click();" ><?php echo $info[0]['name'];?></span>
<?php
        lwt_admin_render_groupradio(0, $selected, $children);
?>
        </ul>
        <input type="submit" name="submit" value="Update" />
      </form>
      <button onclick="event.preventDefault();ajaxPostLite('command=view&id=<?php echo $group['id']; ?>&ajax=1','','adminarea','');">Reset form</button>
      <button onclick="event.preventDefault();ajaxPostLite('command=navigate&navigate[0]=groups&ajax=1','','adminarea','');">Cancel</button>
<?php
        if ($group['id'] > 0){
?>
      <button class="right" onclick="event.preventDefault();ajaxPostLite('command=delete&group[id]=<?php echo $group['id']; ?>&ajax=1','','adminarea','');">Delete</button>
<?php
        }
      }
    }
    
    //exit if this was not a wrapper function
    if (!$wrapper){
      exit;
    }
  }
}


/**
 * Renders the Admin user page when loading the site within wrapper
 * 
 * 
 * @return boolean Successful rendering of page
 * 
 */
function lwt_render_admin_users(){
  //Reset admin navigation if POST not set
  if (!isset($_POST) || count($_POST)==0){
    $_SESSION['admin']['navigate'] = array();
    $_POST['ajax'] = 1;
    $_POST['command'] = 'navigate';
  }
  
  //Render application in preparation for making ajax content
?>
  <div id="adminarea">
<?php lwt_ajax_admin_users(true); ?>
  </div>
<?php
  return TRUE;
}

