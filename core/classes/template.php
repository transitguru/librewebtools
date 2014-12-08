<?php

/**
 * coreTemplate Class
 * 
 * This object renders the approprate template for the page chosen
 *
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreTemplate{
  public $type = 'core';
  public $template = 'core';
  /**
   * Construct the template, requested by the page
   */
  public function __construct($page){
    if (!is_null($page->template) && $page->template !== '' && !is_null($page->type) && $page->type !== ''){
      $this->type = $page->type;
      $this->template = $page->template;
    }
    require_once (DOC_ROOT . '/' . $this->type . '/themes/' . $this->template . '/template.php');
  }

}

