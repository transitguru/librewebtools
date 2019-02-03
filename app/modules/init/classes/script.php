<?php
namespace LWT\Modules\Init;
/**
 * @file
 * LibreWebTools Script Class
 *
 * Script Loader
 *
 * @category   Request Handling
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Script Extends \LWT\Subapp{
  /**
   * Fetches and and sends CSS or JS to the user
   */
  public function ajax(){
    // Find out the application's URL path
    $begin = strlen(APP_ROOT);
    if (strlen($this->inputs->uri) > $begin){
      $pathstring = substr($this->inputs->uri, $begin);
    }
    else{
      $pathstring = '';
    }

    // Check to see if the path is a valid file
    $included = DOC_ROOT . '/app/' .$pathstring;
    if (is_file($included) && (fnmatch('*.css', $pathstring) || (fnmatch('*.js', $pathstring)))){
      //This is the only information that gets sent back!
      $size = filesize($included);
      $finfo = new \finfo();
      $type = $finfo->file($included, FILEINFO_MIME_TYPE);
      if (fnmatch('*.css', $pathstring)){
        $type = 'text/css';
      }
      elseif (fnmatch('*.js', $pathstring)){
        $type = 'application/javascript';
      }
      header('Pragma: ');         // leave blank to avoid IE errors
      header('Cache-Control: ');  // leave blank to avoid IE errors
      header('Content-Length: ' . $size);
      header('Content-Type: ' .$type);
      sleep(0); // gives browser a second to digest headers
      readfile($included);  
      exit;  
    }
    http_response_code(404);
    echo '{"error":{"status":404,"message":"Not Found"}}';
    exit;
  }
}

