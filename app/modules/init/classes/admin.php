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
    $begin = mb_strlen($this->path->root) + 1;
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
        if ($group_id == 0){
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
      if(isset($this->inputs->post->id) && 
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $path_id = (int) $this->inputs->post->id;
        $path_obj = new \LWT\Path($path_id);
        $this->form = new \LWT\Form($forms->path);
        if ($path_id == 0){
          $this->form->fields->parent_id->required = false;
          $this->form->fields->name->format = 'oneline';
          $this->form->fields->name->disabled = true;
        }
        if ($path_id == -1){
          $this->form->fields->submit1->value = 'Create';
          unset($this->form->fields->submit2);
        }
        $this->form->fields->id->value = $path_obj->id;
        $this->form->fields->parent_id->value = $path_obj->parent_id;
        $this->form->fields->user_id->value = $path_obj->user_id;
        $this->form->fields->module_id->value = $path_obj->module_id;
        $this->form->fields->name->value = $path_obj->name;
        $this->form->fields->title->value = $path_obj->title;
        $this->form->fields->app->value = $path_obj->app;
        $this->form->fields->core->value = $path_obj->core;
        $this->form->fields->created->value = $path_obj->created;
        $this->form->fields->activated->value = $path_obj->activated;
        $this->form->fields->deactivated->value = $path_obj->deactivated;
        $this->form->fields->summary->value = $path_obj->content->summary;
        $this->form->fields->content->value = $path_obj->content->content;
        // Make Path list
        $l = $path_obj->list();
        $ids = $path_obj->children($path_obj->id);
        $ids = $path_obj->listapp($ids);
        $this->form->fields->parent_id->list = $this->treelist($l, $ids);
        // Make User list
        $obj = new \LWT\User(0);
        $l = $obj->list();
        foreach($l as $v){
          $this->form->fields->user_id->list[] = (object)[
            'name' => $v->firstname . ' ' . $v->lastname . ' (' . $v->login . ')',
            'value' => $v->id,
          ];
        }
        // Make Module list
        $obj = new \LWT\Module(0);
        $l = $obj->list();
        foreach($l as $v){
          $this->form->fields->module_id->list[] = (object)[
            'name' => $v->name,
            'value' => $v->id,
          ];
        }
      }
      else{
        $path_obj = new \LWT\Path(-1);
        $list = $path_obj->list();
        $items = $this->treeitems($list);
        $items[]= (object)[
          'name' => '(new path)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'Path Navigation',
          'desc' => 'Manage url paths within the main application.',
          'name' => 'form_path_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select Path', 'format' => 'text', 'list' => $items,
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
          if (is_null($this->form->fields->created->value)){
            $this->form->fields->created->value = date('Y-m-d H:i:s');
          }
          if ($this->form->error == 0){
            $path_obj->parent_id = $this->form->fields->parent_id->value;
            $path_obj->user_id = $this->form->fields->user_id->value;
            $path_obj->module_id = $this->form->fields->module_id->value;
            $path_obj->name = $this->form->fields->name->value;
            $path_obj->title = $this->form->fields->title->value;
            $path_obj->app = $this->form->fields->app->value;
            $path_obj->core = $this->form->fields->core->value;
            $path_obj->created = $this->form->fields->created->value;
            $path_obj->activated = $this->form->fields->activated->value;
            $path_obj->deactivated = $this->form->fields->deactivated->value;
            $path_obj->content->summary = $this->form->fields->summary->value;
            $path_obj->content->content = $this->form->fields->content->value;
            $path_obj->write();
            // Reload Path list
            $l = $path_obj->list();
            $ids = $path_obj->children($path_obj->id);
            $ids = $path_obj->listapp($ids);
            $this->form->fields->parent_id->list = $this->treelist($l, $ids);
            // Show errors
            $this->form->error = $path_obj->error;
            $this->form->message = $path_obj->message;
            if ($path_obj->name_unique == false){
              $this->form->fields->name->error = 99;
              $this->form->fields->name->message = $path_obj->name_message;
            }
            if ($path_obj->parent_id_unique == false){
              $this->form->fields->parent_id->error = 99;
              $this->form->fields->parent_id->message = $path_obj->parent_id_message;
            }
            if($this->inputs->post->submit == 'Create' && $this->form->error == 0){
              $this->form->fields->id->value = $path_obj->id;
              $this->form->fields->submit1->value = 'Update';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this Path?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes';
          $this->form->fields->submit2->value = 'Cancel';
        }
        elseif($this->inputs->post->submit == 'Yes'){
          $path_obj->delete();
          $this->form->error = $path_obj->error;
          $this->form->message = $path_obj->message;
          if ($this->form->error == 0){
            $this->form->message = 'Path deleted successfully';
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
        $this->form->message = 'Select a path to begin editing.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'module'){
      if(isset($this->inputs->post->id) &&
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $module_id = (int) $this->inputs->post->id;
        $module_obj = new \LWT\Module($module_id);
        $this->form = new \LWT\Form($forms->module);
        if ($module_id == -1){
          $this->form->fields->submit1->value = 'Create';
        }
        $this->form->fields->id->value = $module_obj->id;
        $this->form->fields->core->value = $module_obj->core;
        $this->form->fields->name->value = $module_obj->name;
        $this->form->fields->enabled->value = $module_obj->enabled;
        $this->form->fields->required->value = $module_obj->required;
      }
      else{
        $module_obj = new \LWT\Module(-1);
        $list = $module_obj->list();
        $items = [];
        foreach($list as $r){
          $items[]= (object)[
            'name' => $r->name,
            'value' => $r->id,
          ];
        }
        $items[]= (object)[
          'name' => '(new module)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'Module Navigation',
          'desc' => 'Manage modules for add-on functionality to LWT.',
          'name' => 'form_module_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select Module', 'format' => 'text', 'list' => $items,
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
            $module_obj->core = $this->form->fields->core->value;
            $module_obj->name = $this->form->fields->name->value;
            $module_obj->enabled = $this->form->fields->enabled->value;
            $module_obj->required = $this->form->fields->required->value;
            $module_obj->write();
            $this->form->error = $module_obj->error;
            $this->form->message = $module_obj->message;
            if ($module_obj->name_unique == false){
              $this->form->fields->name->error = 99;
              $this->form->fields->name->message = $module_obj->name_message;
            }
            if($this->inputs->post->submit == 'Create' && $this->form->error == 0){
              $this->form->fields->id->value = $module_obj->id;
              $this->form->fields->submit1->value = 'Update';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this Module?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes';
          $this->form->fields->submit2->value = 'Cancel';
        }
        elseif($this->inputs->post->submit == 'Yes'){
          $module_obj->delete();
          $this->form->error = $module_obj->error;
          $this->form->message = $module_obj->message;
          if($this->form->error == 0){
            $this->form->message = 'Module deleted successfully';
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
        $this->form->message = 'Select a module to begin editing.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'menu'){
      if(isset($this->inputs->post->id) && 
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $menu_id = (int) $this->inputs->post->id;
        $menu_obj = new \LWT\Menu($menu_id);
        $this->form = new \LWT\Form($forms->menu);
        if ($menu_id == 0){
          $this->form->fields->parent_id->required = false;
          $this->form->fields->name->format = 'oneline';
          $this->form->fields->name->disabled = true;
        }
        if ($menu_id == -1){
          $this->form->fields->submit1->value = 'Create';
          unset($this->form->fields->submit2);
        }
        $this->form->fields->id->value = $menu_obj->id;
        $this->form->fields->parent_id->value = $menu_obj->parent_id;
        $this->form->fields->sortorder->value = $menu_obj->sortorder;
        $this->form->fields->name->value = $menu_obj->name;
        $this->form->fields->path_id->value = $menu_obj->path_id;
        $this->form->fields->external_link->value = $menu_obj->external_link;
        // Make Path list
        $path_obj = new \LWT\Path(-1);
        $l = $path_obj->list();
        $this->form->fields->path_id->list = $this->treelist($l);
        // Make Menu list
        $l = $menu_obj->list();
        $ids = $menu_obj->children($menu_obj->id);
        $this->form->fields->parent_id->list = $this->treelist($l, $ids);
      }
      else{
        $menu_obj = new \LWT\Menu(-1);
        $list = $menu_obj->list();
        $items = $this->treeitems($list);
        $items[]= (object)[
          'name' => '(new menu)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'Menu Navigation',
          'desc' => 'Manage menus within the main application.',
          'name' => 'form_menu_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select Menu item', 'format' => 'text', 'list' => $items,
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
          if (is_null($this->form->fields->created->value)){
            $this->form->fields->created->value = date('Y-m-d H:i:s');
          }
          if ($this->form->error == 0){
            $menu_obj->parent_id = $this->form->fields->parent_id->value;
            $menu_obj->sortorder = $this->form->fields->sortorder->value;
            $menu_obj->name = $this->form->fields->name->value;
            $menu_obj->path_id = $this->form->fields->path_id->value;
            $menu_obj->external_link = $this->form->fields->external_link->value;
            $menu_obj->write();
            // Reload Menu list
            $l = $menu_obj->list();
            $ids = $menu_obj->children($menu_obj->id);
            $this->form->fields->parent_id->list = $this->treelist($l, $ids);
            // Show errors
            $this->form->error = $menu_obj->error;
            $this->form->message = $menu_obj->message;
            if ($menu_obj->name_unique == false){
              $this->form->fields->name->error = 99;
              $this->form->fields->name->message = $menu_obj->name_message;
            }
            if ($menu_obj->parent_id_unique == false){
              $this->form->fields->parent_id->error = 99;
              $this->form->fields->parent_id->message = $menu_obj->parent_id_message;
            }
            if($this->inputs->post->submit == 'Create' && $this->form->error == 0){
              $this->form->fields->id->value = $menu_obj->id;
              $this->form->fields->submit1->value = 'Update';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this Menu item?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes';
          $this->form->fields->submit2->value = 'Cancel';
        }
        elseif($this->inputs->post->submit == 'Yes'){
          $menu_obj->delete();
          $this->form->error = $menu_obj->error;
          $this->form->message = $menu_obj->message;
          if ($this->form->error == 0){
            $this->form->message = 'Menu item deleted successfully';
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
        $this->form->message = 'Select a menu to begin editing.';
        $this->form->status = 'warning';
      }
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
    echo '<a href="' . BASE_URI . $this->path->root . '/user">Users</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/role">Roles</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/group">Groups</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/path">Paths</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/module">Modules</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/menu">Menus</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/file">Files</a>&nbsp;&nbsp;' . "\n";
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

