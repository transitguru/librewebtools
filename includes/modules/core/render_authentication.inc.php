<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders Authentication related pages
 */

/**
 * Renders a login page
 * 
 * @return boolean Successful completion
 */
function lwt_render_login(){

?>
        <?php echo $_SESSION['message']; ?><br />
            <form id="login-form" method="post" action="">
              <label for="username">Username:</label> <input type="text" name="username" /><br />
              <label for="pwd">Password:</label> <input type="password" name="pwd" />
              <input name="login" type="submit" id="login" value="Log In">
            </form>
        <p>
          Need to <a href="/register/">register</a>? <br />
          <a href="/forgot/">Forgot</a> your password?
        </p>
<?php
  return TRUE;
}





