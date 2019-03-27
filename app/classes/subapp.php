<?php
namespace LWT;
/**
 * @file
 * Subapp Class
 *
 * Facilitates the construction of subapps within modules
 *
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Subapp{
  /**
   * Generic constructor for a Module that would inherit this class
   *
   * @param \LWT\Path $path Path object
   * @param Object $user_input Inputs from user (URI, POST, GET, FILES)
   * @param \LWT\Session $session User Session object
   */
  public function __construct($path = [], $user_input = [], $session = []){
    $this->path = $path;
    $this->inputs = $user_input;
    $this->session = $session;
  }

  /**
   * Renders the 404 Not Found page
   *
   */
  public function render_404(){
    echo '<p>Page not found. Please go <a href="' . BASE_URI . '">Home</a>.</p>';
  }

  /**
   * Renders the copyright disclaimer
   *
   */
  function render_copyright(){
    $start_year = 2012;
    $current_year = date('Y');
    $owner = "TransitGuru Limited";

    echo '<p class="copy">&copy;';
    if ($start_year != $current_year){
      echo $start_year . ' &ndash; ';
    }
    echo $current_year . ' ' . $owner . '</p>';
  }
}

