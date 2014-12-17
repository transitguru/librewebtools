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
        elseif ($forms[1] === 'module'){
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
      <li class="<?php echo $class['user']; ?>"><a href="<?php echo APP_ROOT; ?>user/" >Users</a></li>
      <li class="<?php echo $class['group']; ?>"><a href="<?php echo APP_ROOT; ?>group/" >Groups</a></li>
      <li class="<?php echo $class['role']; ?>"><a href="<?php echo APP_ROOT; ?>role/" >Roles</a></li>
      <li class="<?php echo $class['page']; ?>"><a href="<?php echo APP_ROOT; ?>page/" >Pages</a></li>
      <li class="<?php echo $class['file']; ?>"><a href="<?php echo APP_ROOT; ?>file/" >Files</a></li>
      <li class="<?php echo $class['menu']; ?>"><a href="<?php echo APP_ROOT; ?>menu/" >Menus</a></li>
      <li class="<?php echo $class['menu']; ?>"><a href="<?php echo APP_ROOT; ?>menu/" >Modules</a></li>
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
      elseif ($paths[0] === 'modules'){
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
 */
function core_admin_page(){
  $_POST['ajax'] = 1;
  
  //Render application in preparation for making ajax content
?>
  <div id="adminarea">
<?php core_admin_ajax(true); ?>
  </div>
<?php
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
    // Get user input
    $username = $_POST['username'];
    $password = $_POST['pwd'];

    // authenticate user
    $user = new coreUser();
    $user->login($username, $password);
    if ($user->id > 0){
      session_regenerate_id();
      $_SESSION['user_id'] = $user->id;
      $_SESSION['start'] = time();
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
 */
function core_auth_login(){
  $user = new coreUser();
  $user->renderLogin();
}

/**
 * Renders a user profile editing page
 */
function core_auth_profile(){
  $user = new coreUser($_SESSION['user_id']);
  $user->renderProfile();
}

/**
 * Renders a password change form (for those already logged in)
 */
function core_auth_password(){
  $user = new coreUser($_SESSION['user_id']);
  $user->renderPassword();
}

/**
 * Renders the forgotten password page
 */
function core_auth_forgot(){
  $user = new coreUser();
  $user->renderForgot();
}

/**
 * Processes Logout on logout page (must be tied to a content item in the database)
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

/**
 * Renders the copyright disclaimer
 * 
 * @return string HTML Markup
 */
function core_render_copyright(){
  $start_year = 2012;
  $current_year = date('Y');
  $owner = "TransitGuru Limited";

  // If the start year is not the current year: display start-current in copyright
  if ($start_year != $current_year){
?>
        <p class="copy">&copy;<?php echo "{$start_year} &ndash; {$current_year} {$owner}"; ?></p>
<?php
  }
  // Only display current year.
  else{
?>
        <p class="copy">&copy;<?php echo "{$current_year} {$owner}"; ?></p>
<?php
  }
  return TRUE;
}


/**
 * Renders the 404 Not Found page
 * 
 * @return boolean Successful completion
 */
function core_render_404(){
  $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
?>
  <p>Page not found. Please go <a href="/">Home</a>.</p>
<?php
}

/**
 * Fetches and and sends CSS or JS to the user
 */
function core_send_scripts(){
  // Find out the application's URL path
  $begin = strlen(APP_ROOT);
  if (strlen($_SERVER['REQUEST_URI']) > $begin){
    $pathstring = substr($_SERVER['REQUEST_URI'], $begin);
  }
  else{
    $pathstring = '';
  }

  // Check to see if the path is a valid file
  if (is_file(DOC_ROOT . '/' . $pathstring) && (fnmatch('core/*',$pathstring) || fnmatch('custom/*',$pathstring)) && (fnmatch('*.css', $pathstring) || (fnmatch('*.js', $pathstring)))){
    //This is the only information that gets sent back!
    $included = DOC_ROOT . '/' .$pathstring;
    $size = filesize($included);
    $finfo = new finfo();
    $type = $finfo->file($included, FILEINFO_MIME_TYPE);
    if (fnmatch('*.css', $pathstring)){
      $type = 'text/css';
    }
    elseif (fnmatch('*.js', $pathstring)){
      $type = 'application/javascript';
    }
    header('Pragma: ');         // leave blank to avoid IE errors
    header('Cache-Control: ');  // leave blank to avoid IE errors
    header('Content-Length: ' . $size);
    header('Content-Type: ' .$type);
    sleep(0); // gives browser a second to digest headers
    readfile($included);  
    exit;  
  }
}

