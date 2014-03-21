<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders pages that are normally available without a login
 */

/**
 * Renders the main homepage
 * 
 * @return boolean Successful completion
 */
function lwt_render_home(){
?>
        <?php echo $_SESSION['message']; ?><br />

<?php
    if (isset($_SESSION['authenticated']) && isset($_SESSION['authenticated']['user'])){
?>
            <p><a href="/logout/">Logout</a></p>
<?php      
    }
    else{
?>
            <p><a href="/login/">Please Login</a></p>
<?php
    }
?>
            <p>Welcome to the homepage. Not much is going on here as it is under construction!</p>
<?php
  return TRUE;
}

/**
 * Renders the 404 Not Found page
 * 
 * @return boolean Successful completion
 */
function lwt_render_404(){
  $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
?>
  <p>Page not found. Please go <a href="/">Home</a> or try <a href="/login/">Logging on</a>.</p>
<?php
}
/**
 * Renders the contact us form
 * 
 * @return boolean Successful completion
 */

function lwt_render_contact(){
  
}
