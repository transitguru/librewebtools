<?php

/**
 * @file
 * XML class to use for creating or scrubbing XML fragments
 *
 */

class XML{

  /**
   * Markup that will be scrubbed
   */
  public $markup = '';
  /**
   * Type of markup being scrubbed
   * 'xml': generic XML, no defaults are loaded
   * 'svg': load defaults for SVG only
   * 'html+svg': load both HTML and SVG defaults
   * 'html': load defaults for HTML
   * 'basic+svg': Load basic HTML (no images) and SVG defaults
   * 'basic': Load basic HTML (no images)
   * 'simple': Load simple tags and attributes
   */
  public $type = 'html';
  /**
   * Whitelist of elements, simple array with just the element names
   */
  public $elements = array();
  /**
   * Whitelist of attributes, set up as a an array of arrays
   *
   * keys of the top level array are the attribute names
   * values are arrays of elements to accept, make empty array if accepting all
   */
  public $attributes = array();
  /**
   * Whether to include comments (or not)
   */
  public $comments = false;
  
  /**
   * Initializes new XML
   * 
   * @param string $markup untrusted markup
   * @param string $type Qualitative name of format type
   * @param array $elements Allowable elements
   * @param array $attributes Allowable attributes
   * $param boolean $comments Allow comments nodes
   */
  public function __construct($markup, $type, $elements = array(), $attributes = array(), $comments=false){
    // Construct the object
    $this->markup = $markup;
    $this->type = $type;
    if (is_array($elements)){
      $this->elements = $elements;
    }
    if (is_array($attributes)){
      $this->attributes = $attributes;
    }
    if (is_bool($comments)){
      $this->comments = $comments;
    }
    
    // Build defaults
    if ((!is_array($this->elements) || count($this->elements)==0) && (!is_array($this->attributes) || count($this->attributes)==0)){
      if ($this->type == 'simple'){
        $this->elements = $this->allow_elem['simple'];
        $this->attributes = $this->allow_attr['simple'];
      }
      elseif($this->type == 'basic'){
        $this->elements = array_merge($this->allow_elem['simple'], $this->allow_elem['basic']);
        $this->attributes = array_merge($this->allow_attr['simple'], $this->allow_attr['basic']);
      }
      elseif($this->type == 'basic+svg'){
        $this->elements = array_merge($this->allow_elem['simple'], $this->allow_elem['basic'], $this->allow_elem['svg']);
        $this->attributes = array_merge($this->allow_attr['simple'], $this->allow_attr['basic'], $this->allow_attr['svg']);      
      }
      elseif($this->type == 'html'){
        $this->elements = array_merge($this->allow_elem['simple'], $this->allow_elem['basic'], $this->allow_elem['html']);
        $this->attributes = array_merge($this->allow_attr['simple'], $this->allow_attr['basic'], $this->allow_attr['html']);            
      }
      elseif($this->type == 'html+svg'){
        $this->elements = array_merge($this->allow_elem['simple'], $this->allow_elem['basic'], $this->allow_elem['html'], $this->allow_elem['svg']);
        $this->attributes = array_merge($this->allow_attr['simple'], $this->allow_attr['basic'], $this->allow_attr['html'], $this->allow_attr['svg']);            
      }
    }
  }
  public function setType($type){
    $this->type = $type;
  }
  public function setElements($elements){
    if (is_array($elements)){
      $this->elements = $elements;
    }
  }
  public function setAttributes($attributes){
    if (is_array($attributes)){
      $this->attributes = $attributes;
    }
  }
  public function setComments($comments){
    if (is_bool($comments)){
      $this->comments = $comments;
    }
  }
  /**
   * Scrubs the markup
   */
  public function scrub(){
    // Load input into dom document and find the Body
    $input_doc = new DOMDocument('1.0');
    libxml_use_internal_errors(true);
    $input_doc->loadHTML($this->markup);
    libxml_clear_errors();
    $input_body = $input_doc->getElementsByTagName('body')->item(0);
    
    // Begin to build output document
    $output_doc = new DOMDocument('1.0');
    $output_doc->formatOutput = true;
    $root = $output_doc->createElement('html');
    $root = $output_doc->appendChild($root);
    $body = $output_doc->createElement('body');
    $body = $root->appendChild($body);
    $body = $this->buildxml($output_doc, $input_body, $body);
    
    //Saving only the contents of the Body tag

    $this->markup = ""; 
    $children  = $body->childNodes;
    foreach ($children as $child){ 
      $this->markup .= $output_doc->saveXML($child);
    }
    return;

  }

  /**
   * Recursively processes the XML tree
   *
   * @param object $output_doc Output XML Document
   * @param object $input_node Input XML Document Node
   * @param object $output_node Output XML Document Node
   */
  protected function buildxml($output_doc, $input_node, $output_node){
    $children = $input_node->childNodes;
    if (count($children)>0){
      foreach ($children as $child){
        if (in_array($child->nodeName,$this->elements)){
          $output_child = $output_doc->createElement($child->nodeName);
          $input_attributes = $child->attributes;
          foreach ($input_attributes as $attr){
            if (array_key_exists($attr->name, $this->attributes) && ($this->attributes[$attr->name] == null || count($this->attributes[$attr->name])==0 || (count($this->attributes[$attr->name])>0 && in_array($child->nodeName,$this->attributes[$attr->name])))){
              $output_child->setAttribute($attr->name, $attr->value);
            }
          }
          $output_child = $this->buildxml($output_doc, $child, $output_child);
          $output_node->appendChild($output_child);
        }
        elseif($child->nodeName === '#text'){
          $text_node = $output_doc->createTextNode($child->nodeValue);
          $output_node->appendChild($text_node);
        }
        elseif($child->nodeName === '#comment' && $this->comments){
          $text_node = $output_doc->createComment($child->nodeValue);
          $output_node->appendChild($text_node);
        }
        elseif($child->nodeName === '#cdata-section'){
          $string = str_replace('<![CDATA[','',str_replace(']]>','',$child->nodeValue));
          $text_node = $output_doc->createCDATASection($string);
          $output_node->appendChild($text_node);
        }
      }
    }
    return $output_node;
  }
}
