<?php

/**
 * @file
 * Bootstrap file for LibreWebTools core tools
 *
 * This provides a means for the database to invoke the relevant ajax_call or 
 * render_call as shown in the fields in the pages table
 *
 * @category   Bootstrap
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.info>
 * @copyright  Copyright (c) 2014
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */

/**
 * Processes any AJAX request for the Admin application
 * 
 * @param boolean $wrapper Designated as true if called within full page load 
 * 
 * @return void
 * 
 */
function core_admin_ajax($wrapper = false){
  // Only run when this variable is set, to prevent double rendering
  if (isset($_POST['ajax']) && $_POST['ajax'] == 1){
    // Send no cache headers
    if (!$wrapper){
      header('Cache-Control: no-cache');
    }

    // Find out the application's URL path
    $begin = strlen(APP_ROOT);
    if (strlen($_SERVER['REQUEST_URI']) > $begin){
      $_POST['path'] = $pathstring = substr($_SERVER['REQUEST_URI'], $begin, -1);
    }
    else{
      $_POST['path'] = '';
    }
    
    // Process forms
    unset($_SESSION['repost']);
    if(isset($_POST['formid']) && isset($_POST['command'])){
      $forms = explode( '/', $_POST['formid']);
      if (isset($forms[0]) && $forms[0] == 'admin' && isset($forms[1])){
        if ($forms[1] === 'user'){
          core_admin_process_user($forms);
        }
        elseif ($forms[1] === 'group'){
          core_admin_process_group($forms);
        }
        elseif ($forms[1] === 'role'){
          core_admin_process_role($forms);
        }
        elseif ($forms[1] === 'page'){
          core_admin_process_page($forms);
        }
        elseif ($forms[1] === 'file'){
          core_admin_process_file($forms);
        }
        elseif ($forms[1] === 'menu'){
          core_admin_process_menu($forms);
        }
      }
    }
    else{
      $_SESSION['message'] = '';
    }
    
    // Explode the path string
    $paths = explode('/', $_POST['path']);
    
    // Set current class
    $class = array(
      'user' => '',
      'group' => '',
      'role' => '',
      'page' => '',
      'file' => '',
      'menu' => '',
    );
    
    if (isset($paths[0])){
      $class["{$paths[0]}"] = 'current';
    }
  // Admin menu
?>
    <ul class="adminmenu">
      <li class="<?php echo $class['user']; ?>"><a href="<?php echo APP_ROOT; ?>user" >Users</a></li>
      <li class="<?php echo $class['group']; ?>"><a href="<?php echo APP_ROOT; ?>group" >Groups</a></li>
      <li class="<?php echo $class['role']; ?>"><a href="<?php echo APP_ROOT; ?>role" >Roles</a></li>
      <li class="<?php echo $class['page']; ?>"><a href="<?php echo APP_ROOT; ?>page" >Pages</a></li>
      <li class="<?php echo $class['file']; ?>"><a href="<?php echo APP_ROOT; ?>file" >Files</a></li>
      <li class="<?php echo $class['menu']; ?>"><a href="<?php echo APP_ROOT; ?>menu" >Menus</a></li>
    </ul>

<?php
    
    // Process URL path to render forms
    if (isset($paths[0]) && $paths[0] != ''){
      if ($paths[0] === 'user'){
        core_admin_render_user($paths);
      }
      elseif ($paths[0] === 'group'){
        core_admin_render_group($paths);
      }
      elseif ($paths[0] === 'role'){
        core_admin_render_role($paths);
      }
      elseif ($paths[0] === 'page'){
        core_admin_render_page($paths);
      }
      elseif ($paths[0] === 'file'){
        core_admin_render_file($paths);
      }
      elseif ($paths[0] === 'menu'){
        core_admin_render_menu($paths);
      }
      else{
        core_render_404();
      }
    }
    else{
?>
  <h2>Welcome</h2>
  <p>Welcome to the administration area. Please choose a task from above to begin administering the site.</p>
<?php
    }
    
    //exit if this was not inside a wrapper (using ajax)
    if (!$wrapper){
      exit;
    }
  }
}
 
/**
 * Renders the Admin user page when loading the site within wrapper
 * 
 * @return boolean Successful rendering of page
 * 
 */
function core_admin_page(){
  $_POST['ajax'] = 1;
  
  //Render application in preparation for making ajax content
?>
  <div id="adminarea">
<?php core_admin_ajax(true); ?>
  </div>
<?php
  return true;
}



/**
 * Processes Login on login page (must be tied to a content item in the database)
 * 
 * @return void
 * 
 */
function core_auth_authentication(){
  if (isset($_SESSION['redirect']) && $_SESSION['redirect'] != ''){
    $redirect = $_SESSION['redirect'];
  }
  elseif (!isset($_SESSION['requested_page']) || $_SESSION['requested_page'] == APP_ROOT){
    $redirect = "/";
  }
  else{
    $redirect = $_SESSION['requested_page'];
  }
  if (isset($_POST['login']) && $_POST['login'] == 'Log In') {
     // strip whitespace from user input
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // authenticate user
    $user = new coreUser();
    $user->login($username, $password);
    if ($user->id > 0){
      session_regenerate_id();
      $_SESSION['user_id'] = $user->id;
      unset($_SESSION['redirect']);
      header("Location: {$redirect}");
      exit;
    }
    else{
      //Nothing
    }
  }
}

/**
 * Renders a login page
 * 
 * @return boolean Successful completion
 */
function core_auth_login(){

?>
        <?php echo $_SESSION['message']; ?><br />
            <form id="login-form" method="post" action="">
              <label for="username">Username:</label> <input type="text" name="username" /><br />
              <label for="pwd">Password:</label> <input type="password" name="pwd" />
              <input name="login" type="submit" id="login" value="Log In">
            </form>
        <p>
          <a href="/forgot/">Forgot</a> your password?
        </p>
<?php
  return TRUE;
}

/**
 * Processes Logout on logout page (must be tied to a content item in the database)
 * 
 * @return void
 * 
 */
function core_auth_logout(){
  if (isset($_COOKIE[session_name()])){
    setcookie(session_name(), '', time()-86400, '/');
  }
  // end session and redirect
  session_destroy();
  header("Location: /");
  exit;
}

