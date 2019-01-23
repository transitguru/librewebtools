<?php
namespace LWT;
/**
 * @file
 * Session Class
 *
 * Contains and processes user information for authentication
 *
 * @category Authentication
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2015 - 2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 *
 */
class Session{
  public $id;              /**< Session ID */
  public $user_id;         /**< User id of logged user */
  public $expire = 60*60;  /**< Number of seconds to expire a session */
  public $data;            /**< Session data */

  /**
   * Recalls a session using the token
   *
   * @param string $token Token from cookie to identify user's session
   */
  public function __construct($token = '0'){
    $success = false; /**< Set to false until we get all needed info */
    $db = new Db();
    if ($token != '' && $token != '0'){
      $this->id = $token;
      $db->fetch('sessions', null, array('name' => $token));
      if($db->error == 0 && count($db->output)>0){
        $time = time() - $this->expire;
        $date = date('Y-m-d H:i:s', $time);
        $valid = $db->output[0]['valid'];
        if($date > $valid){
          $db->delete('sessions', ['name' => $this->id]);
        }
        else{
          $this->data = json_decode($db->output[0]['data'], true);
          $this->user_id = $db->output[0]['user_id'];
          $db->fetch('users', null, array('id' => $userid));
          if($db->error == 0 && count($db->output)>0){
            $success = true;
          }
        }
      }
    }
    if($success){
      // Reset Expiration to later time
      $date = date('Y-m-d H:i:s');
      $db->write('sessions', array('valid' => $date),array('name' => $this->id));
    }
    else{
      // Zero everything out to show unauthenticated user
      $this->id = '0';
      $this->user_id = 0;
    }
  }

  /**
   * Writes session data to the database
   */
  public function write(){
    if ($this->user['id'] != 0){
      $db = new Db();
      $json = json_encode($this->data, JSON_UNESCAPED_SLASHES);
      $date = date('Y-m-d H:i:s');
      $db->write('sessions', array('valid'=>$date, 'data'=> $json), array('name' => $this->id));
    }
  }

  /**
   * Logs user into the application, initializing a session
   *
   * @param string $username Username login
   * @param string $password User's password
   * @param boolean $verify Set to true for testing login only, don't send headers
   *
   * @return boolean Success when using this to verify
   */
  public function login($username, $password, $verify=false){
    $db = new Db();

    //cleanse input
    $user = trim(strtolower($username));
    $pass = trim($password);

    $db->fetch('users', null, array('login' => $user));
    if(is_array($db->output) && count($db->output)>0){
      $user_id = $db->output[0]->id;
    }
    $db->fetch('passwords', null, array('user_id' => $user_id), null, array('valid_date'));
    if(is_array($db->output) && count($db->output)>0){
      foreach ($db->output as $pwd){
        $hash = $pwd['hash'];
      }
      $success = password_verify($pass, $hash);
      if ($verify == true){
        return $success;
      }
      if ($success == true){
        for ($try=0; $try <= 15; $try ++){
          $token = '';
          $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
          $len = strlen($chars);
          for ($i = 0; $i<48; $i++){
            $num = rand(0,$len-1);
            $token .= substr($chars, $num, 1);
          }
          $db->fetch('sessions', array('name'), array('name' => $token));
          if ($db->error == 0 && count($db->output) == 0){
            $ready = true;
            break;
          }
          else{
            echo "Tried token {$token} with error {$db->error}\n";
          }
        }
        if ($ready == false){
          throw new Exception("Tried {$try} times to unsuccessfully create a token!");
        }
        $date = date('Y-m-d H:i:s');
        $db->write('sessions', array('user_id' => $user_id, 'valid' => $date, 'data' => '{}', 'name' => $token));
        $this->__construct($token);
        header('Location: /');
        setcookie('librewebtools', $token, 0, '/');
        exit;
      }
    }
  }

  /**
   * Logs the user out, cleans up session
   */
  public function logout(){
    $db = new Db();
    $db->delete('sessions', ['name' => $this->id]);
    setcookie('librewebtools', '', '/');
    $this->__construct('');
  }
}
