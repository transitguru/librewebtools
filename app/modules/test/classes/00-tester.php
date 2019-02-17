<?php
namespace LWT\Modules\Test;
/**
 * @file
 * Tester Class
 *
 * Facilitates the construction of individual tests within the Test module
 *
 * @category Unit Testing
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Tester{
  /**
   * Generic constructor for a Test object that would inherit this class
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
   * Default function to run if an inherited object does not exist
   */
  public function run(){
    $dump = (object) [
      'status' => 404,
      'message' => 'Test not found, try again!',
      'inputs' => $this->inputs, //TODO Remove this
      'session' => $this->session, // TODO Remove this
    ];
    http_response_code(404);
    header('Pragma: ');
    header('Cache-Control: ');
    $payload = json_encode($dump, JSON_UNESCAPED_SLASHES);
    echo $payload;
    exit;
  }
}

