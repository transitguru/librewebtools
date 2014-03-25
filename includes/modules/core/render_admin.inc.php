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
  header('Cache-Control: no-cache');
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
      $_POST['command'] = 'write';
    }

    
    //Render the navigation area if navigating or no error
    if (isset($_POST['command']) && ($_POST['command'] == 'navigate' || ($_POST['command'] == 'write' && !$result['error']))){
      $users = lwt_database_fetch_simple(DB_NAME, 'users', NULL, NULL, NULL, array('lastname','firstname'));
      $num = count($users);
?>    
      <ul>
        <li><a href="javascript:;" onclick="ajaxPostLite('command=navigate&navigate[0]=users&ajax=1','','adminarea','');">Users (<?php echo $num; ?>)</a>
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
?>
        </li>
      </ul>
<?php
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
        <h2>Groups</h2>
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
?>
      
<?php
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

function lwt_ajax_admin_content(){
  if (isset($_POST) && $_POST['ajax'] == 1){
    echo '<pre>';
    var_dump($_POST);
    echo '</pre>';
    exit;
  }
}


function lwt_render_admin_content(){
  echo APP_ROOT . "<br />";
  echo 'Content Admin Page!';
}
