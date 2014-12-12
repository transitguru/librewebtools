<?php

/**
 * coreFile Class
 * 
 * This object processes files
 *
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreFile{

  public $filename = ''; /**< Filename to be downloaded */

  /**
   * Processes File Downloads
   */
  function download(){
    // Stop output buffering
    ob_clean();
    
    // Don't Cache the result
    header('Cache-Control: no-cache');
    
    //This is the only information that gets sent back!
    $included = $_SERVER['DOCUMENT_ROOT']."/files/core/".$this->filename;
    $size = filesize($included);
    $type = mime_content_type($included);
    header('Pragma: ');         // leave blank to avoid IE errors
    header('Cache-Control: ');  // leave blank to avoid IE errors
    header('Content-Length: ' . $size);
    // This next line forces a download so you don't have to right click...
    header('Content-Disposition: attachment; filename="'.basename($included).'"');
    header('Content-Type: ' .$type);
    sleep(0); // gives browser a second to digest headers
    readfile($included);
    exit;
  }

}
