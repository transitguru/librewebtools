<?php
namespace LWT;
/**
 * @file
 * User Class
 *
 * allows for loading and editing of user information and authentication
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class User{
  public $id = 0;               /**< User ID (0 if not logged in) */
  public $login = '';           /**< User Login */
  public $firstname = '';       /**< First name */
  public $lastname = '';        /**< Last name */
  public $email = '';           /**< Email address */
  public $created = '';         /**< Date that the user was created */
  public $desc = '';            /**< Description of user */
  public $groups = [];          /**< Groups that the user is a member of */
  public $roles = [];           /**< Roles that a user is a member of */
  public $message = '';         /**< Message to view when editing or viewing a profile */
  public $error = 0;            /**< Error (zero means no error) */
  public $login_unique = true;  /**< Flag to show if the login is unique */
  public $email_unique = true;  /**< Flag to show if the email is unique */
  public $email_message = '';   /**< Message for error in email unique */
  public $login_message = '';   /**< Message for error in login unique */

  /**
   * Constructs user based on user ID in database, or makes an empty user
   *
   * @param int $id Optional user ID to lookup in the database, or create new
   */
  public function __construct($id = 0){
    if ($id>0){
      // Lookup user by ID
      $db = new Db();
      $q = (object)[
        'command' => 'select',
        'table' => 'users',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $id, 'id' => 'id']
          ]
        ]
      ];
      $db->query($q);
      if ($db->affected_rows == 1){
        $this->id = (int) $db->output[0]->id;
        $this->login = $db->output[0]->login;
        $this->firstname = $db->output[0]->firstname;
        $this->lastname = $db->output[0]->lastname;
        $this->created = $db->output[0]->created;
        $this->email = $db->output[0]->email;
        $this->desc = $db->output[0]->desc;
        $q = (object)[
          'command' => 'select',
          'table' => 'user_roles',
          'fields' => [],
          'where' => (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $id, 'id' => 'user_id']
            ]
          ]
        ];
        $db->query($q);
        $this->roles = [];
        if ($db->affected_rows > 0){
          foreach ($db->output as $field){
            $rid = (int) $field->role_id;
            $this->roles[] = $rid;
          }
        }
        $q = (object)[
          'command' => 'select',
          'table' => 'user_groups',
          'fields' => [],
          'where' => (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $id, 'id' => 'user_id']
            ]
          ]
        ];
        $db->query($q);
        $this->groups = [];
        if ($db->affected_rows > 0){
          foreach ($db->output as $field){
            $gid = (int) $field->group_id;
            $this->groups[] = $gid;
          }
        }
      }
      else{
        $this->clear();
      }
    }
    else{
      // Ensure it is empty
      $this->clear();
      $this->id = $id;
    }
  }

  /**
   * Removes User information from object, but does not destroy the object
   */
  public function clear(){
    $this->id = 0;
    $this->login = '';
    $this->firstname = '';
    $this->lastname = '';
    $this->email = '';
    $this->created = '';
    $this->desc = '';
    $this->groups = [];
    $this->roles = [];
  }

  /**
   * Sets user information using login credentials
   *
   * @param string $username Login name for the user
   * @param string $password Unhashed password from the user
   */
  public function login($username, $password){
    //cleanse input
    $user = trim(strtolower($username));
    $pass = trim($password);
    $db = new Db();

    //lookup the user by ID
    $q = (object)[
      'command' => 'select',
      'table' => 'users',
      'fields' => ['id'],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $user, 'id' => 'login']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      $id = (int) $db->output[0]->id;
      $q = (object)[
        'command' => 'select',
        'table' => 'passwords',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $id, 'id' => 'user_id']
          ]
        ],
        'sort' => [ (object)['id'=>'valid_date', 'dir' => 'a'] ]
      ];
      $db->query($q);
      //Check for password
      if ($db->affected_rows>0){
        foreach ($db->output as $pwd){
          $hashed = $pwd->hashed;
          $valid_date = $pwd->valid_date;
          $passwords[$valid_date] = $hashed;
        }
        if (isset($hashed)){
          if (password_verify($pass, $hashed)){
            //Create the user!
            $this->__construct($id);
          }
        }
      }
    }
  }

  /**
   * Sets a password for a user (User must be loaded)
   *
   * @param string $pass password
   */
  public function setpassword($pass=null){
    if (is_null($pass) && $this->id > 0){
      // Create a random password
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
      $len = strlen($chars);
      $pass = '';
      for ($i = 0; $i<10; $i++){
        $num = rand(0,$len-1);
        $reset_code .= substr($chars, $num, 1);
      }
    }
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $current_date = date("Y-m-d H:i:s");
    $q = (object)[
      'command' => 'insert',
      'table' => 'passwords',
      'inputs' => (object)[
        'user_id' => $this->id,
        'valid_date' => $current_date,
        'hashed' => $hashed,
      ]
    ];
    $db = new Db();
    $db->query($q);
  }

  /**
   * Resets a user's lost password
   *
   * @param string $email User's email address
   *
   * @return object $mail Info to email to user in object
   */
  public function resetpassword($email){
    $mail = (object)['status' => 1];
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'users',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $email, 'id' => 'email']
        ]
      ],
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      $id = (int) $db->output[0]->id;
      $login = $db->output[0]->login;
      $q = (object)[
        'command' => 'select',
        'table' => 'passwords',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $id, 'id' => 'user_id']
          ]
        ],
        'sort' => [ (object)['id'=>'valid_date', 'dir' => 'a'] ]
      ];
      $db->query($q);
      if ($db->affected_rows > 0){
        foreach ($db->output as $data){
          $user_id = (int) $data->user_id;
          $valid_date = $data->valid_date;
        }
      }
      $loop = true;
      while ($loop){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $len = strlen($chars);
        $reset_code = "";
        for ($i = 0; $i<80; $i++){
          $num = rand(0,$len-1);
          $reset_code .= substr($chars, $num, 1);
        }
        $q = (object)[
          'command' => 'select',
          'table' => 'passwords',
          'fields' => ['reset_code'],
          'where' => (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $reset_code, 'id' => 'reset_code']
            ]
          ],
        ];
        $db->query($q);
        if ($db->affected_rows == 0){
          $loop = false;
        }
      }
      $unix = time() + 24 * 60 * 60;
      $reset_date = date('Y-m-d H:i:s',$unix);
      $q = (object)[
        'command' => 'update',
        'table' => 'passwords',
        'inputs' => (object)[
          'reset_code' => $reset_code,
          'reset_date' => $reset_date,
        ],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $user_id, 'id' => 'user_id'],
            (object)['type' => '=', 'value' => $valid_date, 'id' => 'valid_date'],
          ]
        ],
      ];
      $db->query($q);
      if ($db->error == 0){
        $mail->status = 0;
        $mail->reset_code = $reset_code;
        $mail->login = $login;
        $mail->email = $email;
        return $mail;
      }
    }
    return $mail;
  }

  /**
   * Finds a user to reset the password
   *
   * @param string $reset_code Reset code to load user
   *
   * @return int $user_id User ID of user found, zero if none found
   */
  public function find($reset_code){
    $user_id = 0;
    if (!is_string($reset_code)){
      return $user_id;
    }
    $current = date('Y-m-d H:i:s');
    $db = new Db();
    $q = (object)[
      'table' => 'passwords',
      'command' => 'select',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object) [ 'type' => '=', 'value' => $reset_code, 'id' => 'reset_code']
        ]
      ],
      'sort' => [ (object)['id'=>'reset_date', 'dir' => 'd'] ]
    ];
    $db->query($q);
    if($db->error == 0 && $db->affected_rows > 0){
      if ($db->output[0]->reset_date > $current){
        $uid = $db->output[0]->user_id;
        $q = (object)[
          'table' => 'passwords',
          'command' => 'select',
          'fields' => [],
          'where' => (object)[
            'type' => 'and', 'items' => [
              (object) [ 'type' => '=', 'value' => $uid, 'id' => 'user_id']
            ]
          ],
          'sort' => [ (object)['id'=> 'valid_date', 'dir' => 'd'] ]
        ];
        $db->query($q);
        if ($db->output[0]->reset_code == $reset_code){
          $user_id = $uid;
        }
      }
    }
    return $user_id;
  }

  /**
   * Lists all users in an array
   *
   * @return array $list All users as an array of objects
   */
  public function list(){
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'users',
      'fields' => [],
      'sort' => [
        (object) ['id' => 'login'],
      ]
    ];
    $db->query($q);
    $list = [];
    foreach($db->output as $record){
      $list[]= (object)[
        'id' => (int) $record->id,
        'login' => $record->login,
        'firstname' => $record->firstname,
        'lastname' => $record->lastname,
        'email' => $record->email,
        'created' => $record->created,
        'desc' => $record->desc,
      ];
    }
    return $list;
  }

  /**
   * Writes a user profile
   */
  public function write(){
    $db = new Db();

    /** Query object for writing */
    $q = (object)[
      'table' => 'users',
      'inputs' => (object)[
        'login' => $this->login,
        'firstname' => $this->firstname,
        'lastname' => $this->lastname,
        'email' => $this->email,
        'desc' => $this->desc,
      ]
    ];

    /** Query object for testing for duplicate keys */
    $t = (object)[
      'table' => 'users',
      'command' => 'select',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => 'or', 'items' => 
            [
              (object)['type' => '=', 'value' => $this->login, 'id' => 'login', 'cs' => false],
              (object)['type' => '=', 'value' => $this->email, 'id' => 'email', 'cs' => false],
            ],
          ],
          (object)['type' => '<>', 'value' => $this->id, 'id' => 'id']
        ]
      ]
    ];
    $db->query($t);
    if ($db->affected_rows > 0){
      $this->error = 99;
      $this->message = 'The marked values below are already taken';
      foreach($db->output as $field){
        if($this->email == $field->email){
          $this->email_unique = false;
          $this->email_message = 'The email "' . $this->email . '" is already taken by another user.';
        }
        if($this->login == $field->login){
          $this->login_unique = false;
          $this->login_message = 'The login "' . $this->login . '" is already taken by another user.';
        }
      }
      return;
    }

    if ($this->id > 0){
      $q->command = 'update';
      $q->where = (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $this->id, 'id' => 'id']
        ]
      ];
      $db->query($q);
      if ($db->error > 0){
        $this->error = $db->error;
        $this->message = $db->message;
      }
      $msg = 'User successfully updated.';
    }
    elseif ($this->id < 0){
      $q->command = 'insert';
      $q->inputs->created = date('Y-m-d H:i:s');
      $this->created = $q->inputs->created;
      $db->query($q);
      $this->error = $db->error;
      $this->message = $db->message;
      if ($db->error == 0){
        $this->id = (int) $db->insert_id;
        $msg = 'User successfully created.';
      }
    }
    else{
      $this->error = 1;
      $this->message = 'Cannot write user number 0';
    }
    if (!$this->error){
      // Empty out groups and roles database tables
      $q = (object)[
        'command' => 'delete',
        'table' => 'user_groups',
        'where' => (object) [ '
          type' => 'and', 'items' => [
            (object) ['type' => '=', 'value' => $this->id, 'id' => 'user_id']
          ]
        ]
      ];
      $db->query($q);
      $q->table = 'user_roles';
      $db->query($q);

      // Write the new roles and groups
      foreach ($this->groups as $group){
        $q = (object)[
          'command' => 'insert',
          'table' => 'user_groups',
          'inputs' => (object)['group_id' => $group, 'user_id' => $this->id]
        ];
        $db->query($q);
      }
      foreach ($this->roles as $role){
        $q = (object)[
          'command' => 'insert',
          'table' => 'user_roles',
          'inputs' => (object)['role_id' => $role, 'user_id' => $this->id]
        ];
        $db->query($q);
      }
      $this->message = $msg;
    }
  }

  /**
   * Deletes the record, then clears the object
   */
  public function delete(){
    if ($this->id > 0){
      $db = new Db();
      $q = (object)[
        'command' => 'delete',
        'table' => 'users',
        'where' => (object) [
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $this->id, 'id' => 'id']
          ]
        ]
      ];
      $db->query($q);
      if($db->error > 0){
        $this->error = $db->error;
        $this->message = $db->message;
      }
      else{
        $this->clear();
        $this->error = 0;
        $this->message = 'User successfully deleted.';
      }
    }
  }
}

