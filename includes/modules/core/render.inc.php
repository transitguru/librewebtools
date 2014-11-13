<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Provides template structure and css rendering
 */

/**
 * Renders CSS for the application using variables for consistent styling
 * 
 * @return boolean  Successful completion
 */
function core_render_css(){
  $body_color = "#222222";
  $bg_color = "#222222";
  $content = "#eeeeee";
  $heading = "#003300";
  $font = "Liberation Sans";
  $primary = "#666666";
  $secondary = "#006600";
  $accent = "#aaaa00";
  $a_content = $accent;
  $a_hover = $primary;
  $nav_a = "#ffffff";
  $nav_bg = $secondary;
  $nav_bg_hover = $primary;
  $foot_text = $primary;
?>
    <style type="text/css">
      /* Importing Fonts for the site */
      @font-face {
        font-family: "Liberation Sans";
        src: url("/fonts/LiberationSans-Regular.ttf");
        font-weight: normal;
        font-style: normal;
      }
      @font-face {
        font-family: "Liberation Sans";
        src: url("/fonts/LiberationSans-Bold.ttf");
        font-weight: bold;
        font-style: normal;
      }
      @font-face {
        font-family: "Liberation Sans";
        src: url("/fonts/LiberationSans-Italic.ttf");
        font-weight: normal;
        font-style: italic
      }
      @font-face {
        font-family: "Liberation Sans";
        src: url("/fonts/LiberationSans-BoldItalic.ttf");
        font-weight: bold;
        font-style: italic
      }
      
      @font-face {
        font-family: "Liberation Sans Narrow";
        src: url("/fonts/LiberationSansNarrow-Regular.ttf");
        font-weight: normal;
        font-style: normal;
      }
      @font-face {
        font-family: "Liberation Sans Narrow";
        src: url("/fonts/LiberationSansNarrow-Bold.ttf");
        font-weight: bold;
        font-style: normal;
      }
      @font-face {
        font-family: "Liberation Sans Narrow";
        src: url("/fonts/LiberationSansNarrow-Italic.ttf");
        font-weight: normal;
        font-style: italic
      }
      @font-face {
        font-family: "Liberation Sans Narrow";
        src: url("/fonts/LiberationSansNarrow-BoldItalic.ttf");
        font-weight: bold;
        font-style: italic
      }
      
      
      /* Setting up background */
        body { background-color: <?php echo $body_color;?>; font-family: "<?php echo $font;?>"}

        /* anchor links */
        .page_content a {text-decoration:none; color: <?php echo $a_content;?>; font-weight:bold}
        .page_content a:hover {text-decoration:underline; color:<?php echo $a_hover;?>; font-weight:bold}


        /* h tags */
        h1, h2, h3, h4, h5, h6 {color: <?php echo $heading;?>;}
        
        
        /* layout of divs [Note, the whitespace shows how divs are "nested" ] */
        .container {position: relative; top:20px; width:1200px; margin-left:auto; margin-right:auto; background-color: <?php echo $bg_color;?>}
          .banner {height:100px; width:1200px;background-color: <?php echo $accent;?>}
          .main_zone {overflow: hidden;position: relative;width:100%;background-color:<?php echo $secondary;?>;}
            .left {float:left;width:5%;}
                .left ul{display:table-row-group;font-size:14px}
                    .left ul li {float:none; min-width:20px;}
                    .left ul li:hover ul{left:80px; top:0; /* Bring back on-screen when needed */}
            .content_zone {float: left; width:94%;}
                .top {width:100%; background-color:<?php echo $primary;?>;}
                    .top ul {display: table-row; font-size:14px;}
                        .top ul li{width:120px;}
                .page_content {background-color:<?php echo $content;?>;min-height:400px;padding:10px}
                .foot {width:100%; font-family: "Liberation Sans Narrow"; background-color:<?php echo $primary;?>;}
                    .foot ul{display: table-row; font-size:10px}
            .right {float:left;}
          .bottom {clear: both;color: <?php echo $foot_text;?>; background-color: <?php echo $bg_color;?>;}

      
        /* Copyright and Modify Date */
        .copy {font-size:14px;}
        .modify {text-align: Right;}

        /* Span styles and such */
        .bold {font-weight:bold;}
        .italic {font-style:italic;}
        .breadcrumbs {font-size: 12px}
        .page_content li {font-size: 14px}
        table {width:100%; background-color: #000000;}
        th {text-align: left; font-weight:bold; background-color: #999999;}
        td {text-align: left; background-color: #ffffff;}
        .hand {cursor: pointer}
        .checked {background-color:#ffff00;}
        .disabled {color: #aaaaaa;}
        .clear {clear:both;}

        /* nav elements for dropdowns */

        .nav{list-style:none;font-weight:bold;display:block;}
        .nav li{float:left;margin:2px;position:relative;white-space: nowrap;}
        .nav a{color:<?php echo $nav_a; ?>;display:block;padding:4px; text-decoration:none;}

        /*--- DROPDOWN ---*/
        .nav ul{list-style:none;position:absolute;margin:0;padding:0;background:<?php echo $nav_bg; ?>;z-index:5;left:-9999px /* Hide off-screen when not needed (this is more accessible than display:none;) */}
        .nav ul li{float:none;}
        .nav ul a{white-space:nowrap;}
        .nav li:hover ul{left:0; /* Bring back on-screen when needed */}
        .nav li:hover ul li ul {left:-9999px; /* Explicitly hide deeper elements*/}
        .nav li:hover ul li:hover ul {left:100px; top:0 /* Explicitly show deeper elements*/}
        .nav li:hover ul li:hover ul li ul {left:-9999px; /* Explicitly hide even deeper elements*/}
        .nav li:hover ul li:hover ul li:hover ul {left:100px; top:0 /* Explicitly show even deeper elements*/}
        .nav li:hover a{	background:<?php echo $nav_bg_hover; ?>;text-decoration:underline;}
        .nav li:hover ul a{text-decoration:none;background:<?php echo $nav_bg; ?>; /* The persistent hover state does however create a global style for links even before they're hovered. Here we undo these effects. */}
        .nav li:hover ul li a:hover{ background:<?php echo $nav_bg_hover; ?>;text-decoration:underline;/* Here we define the most explicit hover states--what happens when you hover each individual link. */}

        /* Code for expanding and Contracting divs */
        .hide {display: none}
        .show {display: block}
        .expand {cursor: pointer}
        
        /* Directory viewer for files */
        .dir {cursor: pointer; color: #000099; font-weight: bold;}
        .file {color: #333333;}
        

        /* used for displaying success or error */
        .success {background: #88ff88; border: 1px solid #004400; color: #004400; padding:2px;}
        .warning {background: #ffff88; border: 1px solid #444400; color: #004400; padding:2px}
        .error {background: #ff8888;  border: 1px solid #440000; color: #440000; padding:2px}

        .alert {position:fixed; top:200px; width:500px; height:200px; margin-left:auto; margin-right:auto; padding:40px; opacity: .9; background-color: #ffffff}

        /* form elements */
        .columns {clear: both; display:block}
        .columns div {float:left; width:300px; padding-left: 22px; text-indent: -22px ;}
        label {display:block; float:left; clear:both; font-style: italic; min-width:250px;}
        button {border: 2px solid <?php echo $primary;?>;}
        input {border: 2px solid #888888;}
        select {min-width: 200px;}
        .float {float:left;}
        .right {float: right;}
        .text-input {min-width:400px}
        textarea {float:left;border: 2px solid #888888; font-family:arial; min-width:400px; height:100px}
        .req {border: 3px solid #000000}
        .current {background-color: <?php echo $accent; ?>}
        .small {font-size:10px;}

        /* calendar */
        .calendar {background-color: #ffff99; width: 200px; padding: 10px; border-radius: 10px; border: 1px solid #000000}
        .calendar table {width: 100%; background-color:#666666}
        .calendar td, .calendar th {text-align: center}
        .calendar button {border: 1px solid #888888; background-color: #ffff66;font-size: 10px; border-radius: 4px}
        .othermonth {background-color: #aaaaaa;}
        .startpoint {background-color: #ffff99;}
        .today {background-color: #ff0000; font-weight: bold; color: #ffffff}
        .calendar .hand:hover {background-color: #ffff00; font-weight: bold;}
        .inactive {color: #dddddd; font-weight: normal;}
        .othermonth a {font-weight: normal; font-color: #999999}
        .currentmonth {}

        /* report tables */
        .report table{max-width:1000px}
        .report th, .report td {background-color:#ffffff; font-size:10px;}
        .rotate {display:block;width:10px;padding-top:5px;padding-bottom:5px;padding-right:1px;padding-left:1px;margin:0px;text-align:left; vertical-align:middle; white-space:nowrap;-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -o-transform: rotate(-90deg);}
        .tall {height: 200px; vertical-align:bottom;}
        
        /* Dialogue boxes*/
        .inner  {padding: 10px}
        .dialogue {
          z-index: 9;
          position:fixed;
          top: 10%;
          min-width:200px;
          min-height:200px;
          margin-left:auto;
          margin-right:auto;
          border-radius: 10px;
          background-color: #ffffff;
          max-width:90% !important;
          max-height:80%  !important;
          overflow: auto;
          opacity: 1;
          border: #000000 solid 1px;
        }
        .dialoguebg{
          z-index: 8;
          position:fixed;
          top: 0px;
          left: 0px;
          min-height: 100%;
          min-width: 100%;
          opacity: 0.5;
          background-color: #777777;
        }
 
    </style>
<?php
  return TRUE;
}

/**
 * Renders the template structure of the page
 * 
 * @param string $request Request URI that will determine title and content
 * @return boolean  Successful completion
 */
function core_render_wrapper($request){
  $output = core_process_title($request);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>LibreWebTools - <?php echo $output['title']; ?></title>
    <!-- Stylesheets -->
<?php core_render_css(); ?>
    
    <!-- Scripts -->
    <script type="text/javascript" src="/js/core.js"></script>
        
  </head>
  <body>
    <div class="container">
      <div class="banner">
        <svg width="1200" height="100">
          <rect x="40" y="40" width="400" height="20" style="fill: #003300;" />
        </svg>
      </div>
      <div class="main_zone">
        <div class="left">
<?php core_render_buttons(); ?>
        </div>
        <div class="content_zone">
          <div class="top">
<?php core_render_menu(); ?>
          </div>
          <div class="page_content">
            <h1><?php echo $output['title']; ?></h1>
<?php 
  if ($output['access']){
    core_process_url($request);
  }
  else{
    core_render_404();
  }
?>
            <div class="hide" id="tooltip"></div>
            <div class="hide" id="dialoguebg"></div>
            <div class="hide" id="dialogue">
              <div class="right" style="background-color: #cccccc;text-align: center; font-weight:normal; border-top-right-radius: 10px; cursor: pointer; border: #000000 solid 1px; width: 30px; height: 20px" onclick="hideDialogue();">x</div>
              <div class="inner bold" id="dialogue-title">Title</div>
              <div class="inner" id="dialogue-content"></div>
            </div>
          </div>
          <div class="foot">
            <ul class="nav">
              <li title="Contact Us"><a href="/contact/">Contact</a></li>
            </ul>
          </div>
        </div>
        <div class="right">
          &nbsp;
        </div>
      </div>
      <div class="bottom">
<?php core_render_copyright();?>
      </div>
    </div>
  </body>
</html>
<?php
  return TRUE;  
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
        <p class="copy">&copy;<?php echo "{$start_year}&#8211;{$current_year} {$owner}"; ?></p>
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
 * Renders the menu structure 
 * 
 * @return boolean  Successful completion
 */
function core_render_menu(){
  if (is_array($_SESSION['authenticated']) && isset($_SESSION['authenticated']['user'])){
?>
            <ul class="nav">
              <li><a href="/">Home</a></li>
              <li>
                <a href="javascript:;">Account</a>
                <ul>
                  <li><a href="/logout/">Logout</a></li>
                  <li><a href="/profile/">Profile</a></li>
                  <li><a href="/password/">Reset Password</a></li>
                </ul>
              </li>
            </ul>        
<?php
  }
  else{
?>
            <ul class="nav">
              <li>
                <a href="javascript:;">Account</a>
                <ul>
                  <li><a href="/login/">Login</a></li>
                  <li><a href="/register/">Register</a></li>
                </ul>
              </li>
            </ul>          
<?php
  }
  return TRUE;
}

/**
 * Renders buttons for application
 * @todo Make it based on logged in user, company, and role
 * 
 * @return string HMTL
 */
function core_render_buttons(){
  //does nothing right now!
?>
  &nbsp;
<?php
  return TRUE;
}

/**
 * Renders the main homepage
 * 
 * @return boolean Successful completion
 */
function core_render_home(){
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
function core_render_404(){
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

function core_render_contact(){
  
}
