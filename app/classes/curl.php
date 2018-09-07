<?php

/**
 * @file
 * AffirmCurl Class
 *
 * Handles requests going out to Affirm using cURL
 *
 * @category Request Handling
 * @package Affirm API
 * @author Michael Sypolt <michael.sypolt@nurelm.com>
 * @copyright Copyright (c) 2015
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 *
 */
class AffirmCurl {
  public $url; /**< URL to send the request */
  public $post_body; /**< Post body to send to Affirm */
  public $method; /**< HTTP Method to send */
  public $curl; /**< cURL handle keeping track of this */
  public $status; /**< stores the HTTP status from cURL response */
  public $headers; /**< stores the headers from the cURL response */
  public $response; /**< stores the raw response body */
  public $response_object; /**< response in object form, if available */
  public $options; /**< stores the cURL options */

  /**
   * Constructor
   *
   * @param string $url URL to request
   * @param string $method HTTP Method
   * @param array|string $data Data to pack and send to Affirm
   */
  public function __construct($url, $method='GET', $data=''){
    if(is_null($data)){
      $this->post_body = '';
    }
    elseif(is_array($data)){
      $this->post_body = json_encode($data, JSON_UNESCAPED_SLASHES);
    }
    else{
      $this->post_body = $data;
    }
    if(is_null($url)){
      throw new Exception('Missing the URL!');
    }
    $this->status = 0;
    $this->method = $method;
    $this->url = $url;
    $this-> options = array(
      CURLOPT_POST => true,
      CURLOPT_URL => $this->url,
      CURLOPT_POSTFIELDS => $this->post_body,
      CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_CUSTOMREQUEST => $this->method,
    );
    $this->curl = curl_init();
  }

  /**
   * Sends the request to the remote server
   */
  public function send(){
    ob_start();
    curl_setopt_array($this->curl, $this->options);
    $success = curl_exec($this->curl);
    if ($success){
      $this->headers = curl_getinfo($this->curl);
      $this->status = $this->headers['http_code'];
      $this->response = ob_get_clean();
      curl_close($this->curl);
    }
    else{
      throw new Exception('Unable to send cURL request');
    }
  }

  /**
   *
   */
  public function unpack(){
    if ($this->headers['content_type'] == 'application/json' && $this->status > 0){
      $this->response_object = json_decode($this->response);
    }
    else{
      $this->response_object = null;
    }
  }
}
