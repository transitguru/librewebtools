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
      $q = (object)[
        'command' => 'select',
        'table' => 'sessions',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $this->id, 'id' => 'name']
          ]
        ]
      ];
      $db->query($q);
      if($db->error == 0 && count($db->output)>0){
        $time = time() - $this->expire;
        $date = date('Y-m-d H:i:s', $time);
        $valid = $db->output[0]->valid;
        if($date > $valid){
          $q->command = 'delete';
          $db->query($q);
        }
        else{
          $this->data = json_decode($db->output[0]->data);
          $this->user_id = (int) $db->output[0]->user_id;
          $q = (object)[
            'command' => 'select',
            'table' => 'users',
            'where' => (object)[
              'type' => 'and', 'items' => [
                (object)['type' => '=', 'value' => $this->user_id, 'id' => 'id']
              ]
            ]
          ];
          $db->query($q);
          if($db->error == 0 && count($db->output)>0){
            $success = true;
          }
        }
      }
    }
    if($success){
      // Reset Expiration to later time
      $date = date('Y-m-d H:i:s');
      $q = (object)[
        'command' => 'update',
        'table' => 'sessions',
        'inputs' => (object)['valid' => $date],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $this->id, 'id' => 'name']
          ]
        ]
      ];
      $db->query($q);
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
    if ($this->user->id != 0){
      $db = new Db();
      $json = json_encode($this->data, JSON_UNESCAPED_SLASHES);
      $date = date('Y-m-d H:i:s');
      $q = (object)[
        'command' => 'update',
        'table' => 'sessions',
        'inputs' => (object)['valid' => $date, 'data' => $json],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $this->id, 'id' => 'name']
          ]
        ]
      ];
      $db->query($q);
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

    $q = (object)[
      'command' => 'select',
      'table' => 'users',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $user, 'id' => 'login']
        ]
      ]
    ];
    $db->query($q);
    if(is_array($db->output) && count($db->output)>0){
      $user_id = (int) $db->output[0]->id;
      $q = (object)[
        'command' => 'select',
        'table' => 'passwords',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $user_id, 'id' => 'user_id']
          ]
        ],
        'sort' => [
          (object)['dir' => 'a', 'id' => 'valid_date']
        ]
      ];
      $db->query($q);
      if(is_array($db->output) && count($db->output)>0){
        foreach ($db->output as $pwd){
          $hash = $pwd->hashed;
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
            $q = (object)[
              'command' => 'select',
              'table' => 'sessions',
              'fields' => ['name'],
              'where' => (object)[
                'type' => 'and', 'items' => [
                  (object)['type' => '=', 'value' => $token, 'id' => 'name']
                ]
              ]
            ];
            $db->query($q);
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
          $cookie_exp = time() + 30 * 24 * 60 * 60;
          $q = (object)[
            'command' => 'insert',
            'table' => 'sessions',
            'inputs' => (object)[
              'user_id' => $user_id,
              'valid' => $date,
              'data' => '{}',
              'name' => $token,
            ]
          ];
          $db->query($q);
          $this->__construct($token);
          setcookie('librewebtools', $token, $cookie_exp, BASE_URI . '/');
          header('Location: ' . BASE_URI . '/');
          exit;
        }
      }
    }
  }

  /**
   * Logs the user out, cleans up session
   */
  public function logout(){
    $db = new Db();
    $q = (object)[
      'command' => 'delete',
      'table' => 'sessions',
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $this->id, 'id' => 'name']
        ]
      ]
    ];
    $db->query($q);
    setcookie('librewebtools', '', 0, BASE_URI . '/');
    $this->__construct('');
    header('Location: ' . BASE_URI . '/');
    exit;
  }
}

