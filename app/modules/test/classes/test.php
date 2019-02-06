<?php
namespace LWT\Modules\Test;
/**
 * @file
 * LibreWebTools Test Class
 *
 * Test class used in calling test suites via the JSON object received via post
 *
 * @category   Unit Testing
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Test Extends \LWT\Subapp{
  public function ajax(){
    //This function will use properly formed JSON to call the appropriate test
    if (fnmatch('application/json*', $this->inputs->content_type) || fnmatch('text/json*', $this->inputs->content_type)){
      header('Pragma: ');
      header('Cache-Control: ');
      $payload = (object)[
        'status' => 'success',
        'code' => 200,
        'var_dump' => (object)[
          'user_input' => $this->inputs,
          'session' => $this->session,
        ],
      ];
      echo json_encode($payload, JSON_UNESCAPED_SLASHES);
      exit;
    }
  }

  public function render(){
    echo "<p>You did not send the proper payload for testing</p>";
  }
}
