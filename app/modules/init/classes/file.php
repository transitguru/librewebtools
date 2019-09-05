<?php
namespace LWT\Modules\Init;
/**
 * @file
 * LibreWebTools File Class
 *
 * File Downloader
 *
 * @category   Request Handling
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class File Extends \LWT\Subapp{
  /**
   * Fetches and sends the file to the user
   */
  public function ajax(){
    // Find out the application's URL path
    $begin = mb_strlen($this->path->root) + 1;
    if (mb_strlen($this->inputs->uri) > $begin){
      $pathstr = mb_substr($this->inputs->uri, $begin);
    }
    else{
      $pathstr = '';
    }

    $l = mb_strpos($pathstr, '/');
    if ($l >0){
      // Determine what kind of file we are dealing with
      $prefix = mb_substr($pathstr, 0, $l);
      $pathstring = mb_substr($pathstr, $l+1);
      $type = null;

      // Process downloads
      if ($prefix == 'download'){
        $included = DOC_ROOT . '/files/' . $pathstring;
        if (is_file($included) && (!fnmatch('.htaccess*', $pathstring))){
          $finfo = new \finfo();
          $type = $finfo->file($included, FILEINFO_MIME_TYPE);
        }
      }

      // Process CSS
      elseif ($prefix == 'css'){
        $included = DOC_ROOT . '/app/' . $pathstring;
        if (is_file($included) && fnmatch('*.css', $pathstring)){
          $type = 'text/css';
        }
      }

      // Process JS
      elseif ($prefix == 'js'){
        $included = DOC_ROOT . '/app/' . $pathstring;
        if (is_file($included) && fnmatch('*.js', $pathstring)){
          $type = 'application/javascript';
        }
      }

      // Continue processing the file
      if (!is_null($type)){
        $size = filesize($included);
        header('Pragma: ');         // leave blank to avoid IE errors
        header('Cache-Control: ');  // leave blank to avoid IE errors
        header('Content-Length: ' . $size);
        header('Content-Type: ' .$type);
        sleep(0); // gives browser a second to digest headers
        readfile($included);
        exit;
      }
    }

    // If it makes it this far, we have a problem
    http_response_code(404);
    echo '{"error":{"status":404,"message":"Not Found"}}';
    exit;
  }
}

