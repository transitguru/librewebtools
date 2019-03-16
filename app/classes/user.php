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
  public $groups = array();     /**< Groups that the user is a member of */
  public $roles = array();      /**< Roles that a user is a member of */
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
            $this->roles[$rid] = $rid;
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
            $this->groups[$gid] = $gid;
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
   */
  public function resetpassword($email){
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
          $user_id = (int) $data['user_id'];
          $valid_date = $data['valid_date'];
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
          $loop = FALSE;
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
      if ($db->error > 0){
        echo $db->error;
        echo "Fail!\n";
      }
      else{
        $headers = "From: LibreWebTools <noreply@transitguru.limited>\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8";
        mail($email, "Password Reset", "Username: {$login}\r\nPlease visit the following url to reset your password:\r\nhttp://librewebtools.org/forgot/{$reset_code}/", $headers);
      }
    }
  }

  /**
   * Renders a user profile editing page (intended for one's own user)
   */
  public function renderProfile(){
    if ($this->id > 0){
      $this->message = '';
      $this->error = 0;

      // Define form fields
      $fields = array();
      $fields['login'] = new Field($this->login, 'text', 'nowacky', true, 40);
      $fields['login']->element = 'text';
      $fields['login']->label = 'Login';
      $fields['login']->name = 'login';

      $fields['firstname'] = new Field($this->firstname, 'text', 'oneline', true, 100);
      $fields['firstname']->element = 'text';
      $fields['firstname']->label = 'First Name';
      $fields['firstname']->name = 'firstname';

      $fields['lastname'] = new Field($this->lastname, 'text', 'oneline', true, 100);
      $fields['lastname']->element = 'text';
      $fields['lastname']->label = 'Last Name';
      $fields['lastname']->name = 'lastname';

      $fields['email'] = new Field($this->email, 'text', 'email', true, 255);
      $fields['email']->element = 'text';
      $fields['email']->label = 'Email';
      $fields['email']->name = 'email';

      if (isset($_POST['submit']) && $_POST['submit']=='Update'){
        $this->message = '<span class="success">Success!</span>';
        $this->error = 0;

        // Set values to User POST
        $fields['login']->value = $_POST['login'];
        $fields['firstname']->value = $_POST['firstname'];
        $fields['lastname']->value = $_POST['lastname'];
        $fields['email']->value = $_POST['email'];

        // Validate the fields
        foreach ($fields as $key => $field){
          $fields[$key]->validate();
          if ($fields[$key]->error){
            $this->error = $fields[$key]->error;
          }
        }

        // Check for unique login
        if (!$this->error && $this->login != $fields['login']->value){
          $test = new Db();
          $qt = (object)[
            'command' => 'select',
            'table' => 'users',
            'fields' => [],
            'where' => (object)[
              'type' => 'and', 'items' => [
                (object)['type' => '=', 'value' => $fields['login']->value, 'id' => 'login']
              ]
            ]
          ];
          $test->query($qt);
          if ($test->affected_rows > 0){
            $fields['login']->message = 'Already Taken: ';
            $this->error = 9999;
          }
        }

        if (!$this->error){
          $this->login = $fields['login']->value;
          $this->firstname = $fields['firstname']->value;
          $this->lastname = $fields['lastname']->value;
          $this->email = $fields['email']->value;
          $this->write();
          if (!$this->error){
            $this->message = '<span class="success">Success!</span>';
          }
        }
        else{
          $this->message = '<span class="error">Please fix the fields</span>';
        }
      }
      elseif (isset($_POST['submit']) && $_POST['submit']=='Cancel'){
        $this->message = '<span class="warning">Profile was not changed.</span>';
      }

      echo $this->message; ?><br />
      <h1>Edit your Profile</h1>
      <form action="" method="post" name="update_profile" id="update_profile">
<?php
      foreach ($fields as $field){
        $field->render();
      }
?>
        <input type="submit" name="submit" value="Update" /><input type="submit" name="submit" value="Cancel" />
      </form>
<?php
    }
  }

  /**
   * Renders the reset password page (for those already logged in)
   */
  public function renderPassword(){
    // Check if _POST is set and process form
    $message = '';
    if (isset($_POST['submit']) && $_POST['submit']=='Update'){
    $message = '<span class="success">Data submitted correctly</span>';
    $error = false;
      $testuser = new User();
      $testuser->login($this->login, $_POST['current_pwd']);
      if ($testuser->id < 1){
        $message = '<span class="error">Existing password is not valid, please re-enter it.</span>';
        $error = true;
      }
      elseif ($_POST['pwd'] != $_POST['conf_pwd']){
        $message = '<span class="error">New Passwords do not match.</span>';
        $error = true;
      }
      if (!$error){
        $this->setpassword($_POST['pwd']);
        if (!$this->error){
          $message = '<span class="success">Password successfully updated.</span>';
        }
        else{
          $message = '<span class="error">Error updating password.</span>';
        }
      }
    }
    if (isset($_POST['submit']) && $_POST['submit']=='Cancel'){
      $message = '<span class="warning">Password was not changed.</span>';
    }

?>
<?php echo $message; ?><br />
  <form action='' method='post' name='update_profile' id='update_profile'>
    <label for="current_pwd">Current Password</label><input name="current_pwd" type="password" /><br />
    <label for="pwd">New Password</label><input name="pwd" type="password" /><br />
    <label for="conf_pwd">Confirm Password</label><input name="conf_pwd" type="password" /><br />
    <input type="submit" name="submit" id="submit" value="Update" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Cancel" />
  </form>
<?php
  }

  /**
   * Renders the forgot password page
   */
  public function renderForgot(){
    if($_SERVER['REQUEST_URI'] == APP_ROOT){
      if ($_POST['submit'] == 'Reset Password'){
        $email = $_POST["email"];
        $this->resetpassword($email);
        $message = '<span class="warning">The information has been submitted. You should receive password reset instructions in your email.</span>';
      }
  ?>
        <?php echo $message; ?><br />
        <form action='' method='post' name='update_profile' id='update_profile'>
          <label for="email">Email Address: </label><input type="text" name="email" id="email" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Reset Password" /><br />
        </form>
  <?php
    }
    else{
      $chars = strlen(APP_ROOT);
      $reset_request = trim(substr($_SERVER['REQUEST_URI'],$chars),"/ ");
      $date = date('Y-m-d H:i:s');
      $db = new Db();
      $db->fetch_raw("SELECT * FROM `passwords` WHERE `reset_code`='{$reset_request}' AND `reset_date` > '{$date}'");
      if ($db->affected_rows == 0){
  ?>
      <p>The reset code does not match. Please visit the <a href="<?php echo APP_ROOT; ?>">Forgot Password</a> page</p>
  <?php
      }
      else{
        $_SESSION['reset_user'] = $db->output[0]['user_id'];
        $submit = 'Update';

        // Check if _POST is set and process form
        $message = '';
        if ($_POST['submit']=='Update'){
          // Define form fields
          $inputs['pwd'] = $_POST['pwd'];
          $inputs['conf_pwd'] = $_POST['conf_pwd'];

          if ($inputs['pwd'] != $inputs['conf_pwd']){
            $message = '<span class="error">New Passwords do not match.</span>';
            $error = true;
          }
          if (!$error){
            $this->__construct($_SESSION['reset_user']);
            $this->setpassword($inputs['pwd']);
            if (!$this->error){
              $message = '<span class="success">Password successfully updated.</span>';
              unset($_SESSION['reset_user']);
              header("Location: /login/");
            }
            else{
              $message = '<span class="error">Error updating password.</span>';
            }
          }
        }
        if ($_POST['submit']=='Cancel'){
          $message = '<span class="warning">Password was not changed.</span>';
        }

  ?>
  <?php echo $message; ?><br />
  <h1>Edit your Password</h1>
  <form action='' method='post' name='update_profile' id='update_profile'>
    <label for="pwd">Password: </label><input name="pwd"  type="password" value="" /><br />
    <label for="conf_pwd">Confirm Password: </label><input name="conf_pwd"  type="password" value="" /><br />
    <input type="submit" name="submit" id="submit" value="Update" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Cancel" />
  </form>
  <?php
      }
    }

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
      $this->error = $db->error;
      $this->message = $db->message;
    }
    elseif ($this->id < 0){
      $q->command = 'insert';
      $q->inputs->created = date('Y-m-d H:i:s');
      $this->created = $inputs['created'];
      $db->query($q);
      $this->error = $db->error;
      $this->message = $db->message;
      if (!$db->error){
        $this->id = (int) $db->insert_id;
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
          'inputs' => (object)['group_id' => $role, 'user_id' => $this->id]
        ];
        $db->query($q);
      }
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
      if(!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }
}

