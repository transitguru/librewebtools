<?php
namespace LWT\Modules\Init;
/**
 * @file
 * LibreWebTools Auth Class
 *
 * Administration and site management
 *
 * @category   Administration
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Admin Extends \LWT\Subapp{
  /**
   * list of allowable paths that end up rendering or outputting a form
   */
  public $valid_paths = ['user','role','group','path','module','menu','file'];

  /**
   * Runs prior to any HTML output
   */
  public function ajax(){
    // Load the applicable forms
    $directory = DOC_ROOT . '/app/modules/init/config/';
    $form_doc = file_get_contents($directory . 'forms.json');
    $forms = json_decode($form_doc)->admin;
    $this->form = null;

    // Process the path
    $begin = mb_strlen($this->path->root);
    if (mb_strlen($this->inputs->uri) > $begin){
      $this->pathstring = mb_substr($this->inputs->uri, $begin);
    }
    else{
      $this->pathstring = '';
    }

    // Prevent caching of the page
    header('Pragma: ');
    header('Cache-Control: ');

    // Route request based on path
    if ($this->pathstring == 'role'){
      if(isset($this->inputs->post->id) && 
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $role_id = (int) $this->inputs->post->id;
        $role_obj = new \LWT\Role($role_id);
        $this->form = new \LWT\Form($forms->role);
        if ($role_id == -1){
          $this->form->fields->submit1->value = 'Create';
        }
        $this->form->fields->id->value = $role_obj->id;
        $this->form->fields->name->value = $role_obj->name;
        $this->form->fields->sortorder->value = $role_obj->sortorder;
        $this->form->fields->desc->value = $role_obj->desc;
      }
      else{
        $role_obj = new \LWT\Role(-1);
        $list = $role_obj->list();
        $items = [];
        foreach($list as $r){
          $items[]= (object)[
            'name' => $r->name,
            'value' => $r->id,
          ];
        }
        $items[]= (object)[
          'name' => '(new role)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'User Roles Navigation',
          'desc' => 'Manage roles for major user permissions classes.',
          'name' => 'form_role_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select Role', 'format' => 'text', 'list' => $items,
              'value' => 'Navigate'],
            'submit1' => (object)['name' => 'submit', 'element' => 'submit',
              'label' => '', 'format' => 'text', 'value' => 'Navigate'],
          ],
        ];
        $this->form = new \LWT\Form($defs);
      }
      if (isset($this->inputs->post->submit)){
        if(in_array($this->inputs->post->submit, ['Create','Update'])){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
          if ($this->form->error == 0){
            $role_obj->name = $this->form->fields->name->value;
            $role_obj->sortorder = $this->form->fields->sortorder->value;
            $role_obj->desc = $this->form->fields->desc->value;
            $role_obj->write();
            $this->form->error = $role_obj->error;
            $this->form->message = $role_obj->message;
            if ($role_obj->name_unique == false){
              $this->form->fields->name->error = 99;
              $this->form->fields->name->message = $role_obj->name_message;
            }
            if($this->inputs->post->submit == 'Create' && $this->form->error == 0){
              $this->form->fields->id->value = $role_obj->id;
              $this->form->fields->submit1->value = 'Update';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this Role?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes';
          $this->form->fields->submit2->value = 'Cancel';
        }
        elseif($this->inputs->post->submit == 'Yes'){
          $role_obj->delete();
          $this->form->error = $role_obj->error;
          $this->form->message = $role_obj->message;
          if($this->form->error == 0){
            $this->form->message = 'Role deleted successfully';
            $this->form->status = 'success';
            foreach ($this->form->fields as $key => $field){
              if ($key != 'submit3'){
                unset($this->form->fields->{$key});
              }
            }
          }
        }
      }
      else{
        $this->form->message = 'Select a role to begin editing.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'group'){
      if(isset($this->inputs->post->id) && 
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $group_id = (int) $this->inputs->post->id;
        $group_obj = new \LWT\Group($group_id);
        $this->form = new \LWT\Form($forms->group);
        if ($group_obj->id == 0){
          $this->form->fields->parent_id->required = false;
        }
        if ($group_id == -1){
          $this->form->fields->submit1->value = 'Create';
          unset($this->form->fields->submit2);
        }
        $this->form->fields->id->value = $group_obj->id;
        $this->form->fields->parent_id->value = $group_obj->parent_id;
        $this->form->fields->name->value = $group_obj->name;
        $this->form->fields->sortorder->value = $group_obj->sortorder;
        $this->form->fields->desc->value = $group_obj->desc;
        // Make Group list
        $l = $group_obj->list();
        $ids = $group_obj->children($group_obj->id);
        $this->form->fields->parent_id->list = $this->treelist($l, $ids);
      }
      else{
        $group_obj = new \LWT\Group(-1);
        $list = $group_obj->list();
        $items = $this->treeitems($list);
        $items[]= (object)[
          'name' => '(new group)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'User Groups Navigation',
          'desc' => 'Manage fine-grained groups for hierarchical user management.',
          'name' => 'form_group_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select Group', 'format' => 'text', 'list' => $items,
              'value' => 'Navigate'],
            'submit1' => (object)['name' => 'submit', 'element' => 'submit',
              'label' => '', 'format' => 'text', 'value' => 'Navigate'],
          ],
        ];
        $this->form = new \LWT\Form($defs);
      }
      if (isset($this->inputs->post->submit)){
        if(in_array($this->inputs->post->submit, ['Create','Update'])){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
          if ($this->form->error == 0){
            $group_obj->name = $this->form->fields->name->value;
            $group_obj->parent_id = $this->form->fields->parent_id->value;
            $group_obj->sortorder = $this->form->fields->sortorder->value;
            $group_obj->desc = $this->form->fields->desc->value;
            $group_obj->write();
            // Reload Group list
            $l = $group_obj->list();
            $ids = $group_obj->children($group_obj->id);
            $this->form->fields->parent_id->list = $this->treelist($l, $ids);
            // Show errors
            $this->form->error = $group_obj->error;
            $this->form->message = $group_obj->message;
            if ($group_obj->name_unique == false){
              $this->form->fields->name->error = 99;
              $this->form->fields->name->message = $group_obj->name_message;
            }
            if ($group_obj->parent_id_unique == false){
              $this->form->fields->parent_id->error = 99;
              $this->form->fields->parent_id->message = $group_obj->parent_id_message;
            }
            if($this->inputs->post->submit == 'Create' && $this->form->error == 0){
              $this->form->fields->id->value = $group_obj->id;
              $this->form->fields->submit1->value = 'Update';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this Group?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes';
          $this->form->fields->submit2->value = 'Cancel';
        }
        elseif($this->inputs->post->submit == 'Yes'){
          $group_obj->delete();
          $this->form->error = $group_obj->error;
          $this->form->message = $group_obj->message;
          if ($this->form->error == 0){
            $this->form->message = 'Group deleted successfully';
            $this->form->status = 'success';
            foreach ($this->form->fields as $key => $field){
              if ($key != 'submit3'){
                unset($this->form->fields->{$key});
              }
            }
          }
        }
      }
      else{
        $this->form->message = 'Select a group to begin editing.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'user'){
      if(isset($this->inputs->post->id) && 
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $user_id = (int) $this->inputs->post->id;
        $user_obj = new \LWT\User($user_id);
        $this->form = new \LWT\Form($forms->user);
        if ($user_id == -1){
          $this->form->fields->submit1->value = 'Create';
          unset($this->form->fields->submit2);
        }
        $this->form->fields->id->value = $user_obj->id;
        $this->form->fields->login->value = $user_obj->login;
        $this->form->fields->firstname->value = $user_obj->firstname;
        $this->form->fields->lastname->value = $user_obj->lastname;
        $this->form->fields->email->value = $user_obj->email;
        $this->form->fields->desc->value = $user_obj->desc;
        $this->form->fields->roles->value = $user_obj->roles;
        $this->form->fields->groups->value = $user_obj->groups;

        // Make Role list
        $obj = new \LWT\Role(0);
        $l = $obj->list();
        foreach($l as $v){
          $this->form->fields->roles->list[] = (object)[
            'name' => $v->name,
            'value' => $v->id,
          ];
        }

        // Make Group list
        $obj = new \LWT\Group(0);
        $l = $obj->list();
        $this->form->fields->groups->list = $this->treelist($l);
      }
      else{
        $user_obj = new \LWT\User(-1);
        $list = $user_obj->list();
        $items = [];
        foreach($list as $r){
          $items[]= (object)[
            'name' => $r->login,
            'value' => $r->id,
          ];
        }
        $items[]= (object)[
          'name' => '(new user)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'User Login Navigation',
          'desc' => 'Manage user logins for the application.',
          'name' => 'form_user_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select User', 'format' => 'text', 'list' => $items,
              'value' => 'Navigate'],
            'submit1' => (object)['name' => 'submit', 'element' => 'submit',
              'label' => '', 'format' => 'text', 'value' => 'Navigate'],
          ],
        ];
        $this->form = new \LWT\Form($defs);
      }
      if (isset($this->inputs->post->submit)){
        if(in_array($this->inputs->post->submit, ['Create','Update'])){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
          if ($this->form->error == 0){
            $user_obj->login = $this->form->fields->login->value;
            $user_obj->firstname = $this->form->fields->firstname->value;
            $user_obj->lastname = $this->form->fields->lastname->value;
            $user_obj->email = $this->form->fields->email->value;
            $user_obj->desc = $this->form->fields->desc->value;
            $groups = $this->form->fields->groups->value;
            $roles = $this->form->fields->roles->value;
            $user_obj->roles = [];
            $user_obj->groups = [];
            if (is_array($roles) && count($roles)>0){
              foreach ($roles as $rid){
                $user_obj->roles[] = $rid;
              }
            }
            if (is_array($groups) && count($groups)>0){
              foreach ($groups as $gid){
                $user_obj->groups[] = $gid;
              }
            }
            $user_obj->write();
            $this->form->error = $user_obj->error;
            $this->form->message = $user_obj->message;
            if ($user_obj->email_unique == false){
              $this->form->fields->email->error = 99;
              $this->form->fields->email->message = $user_obj->email_message;
            }
            if ($user_obj->email_unique == false){
              $this->form->fields->login->error = 99;
              $this->form->fields->login->message = $user_obj->login_message;
            }
            if($this->inputs->post->submit == 'Create' && $this->form->error == 0){
              $this->form->fields->id->value = $user_obj->id;
              $this->form->fields->submit1->value = 'Update';
              $user_obj->setpassword();
              $mail = $user_obj->resetpassword($user_obj->email);
              $this->form->message .= '  The password reset code for the user is <em>' . $mail->reset_code . '</em>';
            }
            elseif($this->form->error == 0 && $this->form->fields->reset->value == 1){
              $user_obj->setpassword();
              $mail = $user_obj->resetpassword($user_obj->email);
              $this->form->message = 'The password reset code for the user is <em>' . $mail->reset_code . '</em>';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this User?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes';
          $this->form->fields->submit2->value = 'Cancel';
        }
        elseif($this->inputs->post->submit == 'Yes'){
          $user_obj->delete();
          $this->form->error = $user_obj->error;
          $this->form->message = $user_obj->message;
          if ($this->form->error == 0){
            $this->form->message = 'User deleted successfully';
            $this->form->status = 'success';
            foreach ($this->form->fields as $key => $field){
              if ($key != 'submit3'){
                unset($this->form->fields->{$key});
              }
            }
          }
        }
      }
      else{
        $this->form->message = 'Select a user to begin editing.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'path'){
      $this->form = new \LWT\Form($forms->path);
    }
    elseif ($this->pathstring == 'module'){
      $this->form = new \LWT\Form($forms->module);
    }
    elseif ($this->pathstring == 'menu'){
      $this->form = new \LWT\Form($forms->menu);
    }
    elseif ($this->pathstring == 'file'){
      $this->form = new \LWT\Form($forms->file);
    }
    elseif ($this->pathstring == ''){
    }
    else{
      http_response_code(404);
    }
    if (fnmatch('application/json*', $this->inputs->content_type)){
      header('Content-Type: application/json');
      if(is_object($this->form)){
        $json = $this->form->export_json();
      }
      else{
        http_response_code(404);
        $json = '{"status":"Not Found","code":404}';
      }
      echo $json;
      exit;
    }
  }

  /**
   * Helper to make items for select from hierarchical tree list
   *
   * @param Array $list List of items in hierarchical tree
   * @param Array $items Running list of items to eventually return
   * @param int $depth Depth of current list to aid in visual nesting
   *
   * @return Array $items Array of items to use for select element
   */
  private function treeitems($list, $items = [], $depth = 0){
    $depth ++;
    foreach($list as $g){
      $name = str_repeat('-', $depth) . ' ' . $g->name;
      $items[]= (object)[
        'name' => $name,
        'value' => $g->id,
      ];
      $items = $this->treeitems($g->children, $items, $depth);
    }
    return $items;
  }

  /**
   * Recursively makes list for nested radio lists
   *
   * @param Array $items Items that are being processed at that certain level
   *
   * @return Array $list List to be displayed at a particular level
   * @return Array $ids IDs to disable (defaults to empty array)
   */
  private function treelist($items, $ids = []){
    $list = [];
    foreach($items as $v){
      if (isset($v->children) && is_array($v->children)){
        $children = $this->treelist($v->children, $ids);
      }
      else{
        $children = [];
      }
      $obj = (object)[
        'name' => $v->name,
        'value' => $v->id,
        'list' => $children,
      ];
      if (in_array($v->id, $ids, true)){
        $obj->nope = true;
      }

      $list[] = $obj;
    }
    return $list;
  }

  /**
   * Runs while inside the template, usually rendering some HTML
   */
  public function render(){
    echo '<a href="' . BASE_URI . $this->path->root . 'user">Users</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . 'role">Roles</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . 'group">Groups</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . 'path">Paths</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . 'module">Modules</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . 'menu">Menus</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . 'file">Files</a>&nbsp;&nbsp;' . "\n";
    if (in_array($this->pathstring,$this->valid_paths)){
      if (is_object($this->form)){
        $html = $this->form->export_html();
        echo $html;
      }
      else{
        echo "<h3>Administration Module</h3>\n";
        echo "<p>The form is not found, please try an option above</p>";
      }
    }
    elseif($this->pathstring == ''){
      echo '<h3>Administration Module</h3><p>Please select an option above</p>';
    }
    else{
      echo "<h3>Administration Module</h3>\n";
      echo "<p>The form is not found, please try an option above</p>";
    }
  }
}

