<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.limited>
 *
 * This object creates or scrubs XML fragments
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
  public $type = 'simple';
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
   * Allowable Elements
   */
  protected $allow_elem = array(
    'simple' => array(
      'b', 'i', 'u', 'em', 'strong', 's', //very basic formatting
    ),
    'basic' => array(
      'h1','h2','h3','h4','h5','h6', 'p', 'br', 'hr', //headings and paragraphs
      'dd', 'dl', 'dfn', 'dt', 'ul', 'ol', 'li', //lists and such
      'pre', 'code', 'samp', 'ins', 'del', 'mark', 'small', 'strike', 'span', //more formatting
      'blockquote', 'q', 'cite', 'abbr', //Quotations and such
      'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', //tables
    ),
    'html' => array(
      'div', 'footer', 'header', 'article', 'aside', 'nav', 'section', //sections
      'a', 'img', 'figure', 'figcaption', 'map', 'area',  //Image and link support
      'style', //Custom styling
      
    ),
    'svg' => array(
       'g', 'marker', 'mask', 'pattern', 'svg', 'symbol', 'defs', //containers
       'lineargradient', 'radialgradient', 'stop', //gradients
       'circle', 'ellipse', 'image', 'line', 'path', 'polygon', 'polyline', 'rect', 'use',//drawing
       'textPath', 'text', 'tref', 'tspan', //text   
       'view' //others
    ),
  );
  
  /**
   * Allowable Attributes
   */
  protected $allow_attr = array(
    'simple' => array(
      // No attributes allowed
    ),
    'basic' => array(
      'id' => array(), 
      'cite' => array('blockquote', 'del', 'ins', 'q'),
      'class' => array(),
      'colspan' => array('td', 'th'),
      'datetime' => array('del', 'ins', 'time'),
      'dir' => array(),
      'headers' => array('td', 'th'),
      'reversed' => array('ol'), 
      'rowspan' => array('td', 'th'),
      'scope' => array('th'),    
      'start' => array('ol'), 
      'summary' => array('table'),     
      'title' => array(),
    ),
    'html' => array(
      'accesskey' => array(),
      'alt' => array('applet', 'area', 'img', 'input'), 
      'coords' => array('area'),
      'download' => array('a', 'area'), 
      'height' => array('canvas', 'embed', 'iframe', 'img', 'input', 'object', 'video'),
      'hidden' => array(),
      'href' => array('a','area', 'base', 'link'),
      'hreflang' => array('a', 'area', 'link'),
      'lang' => array(), 
      'media' => array('a', 'area', 'link', 'source', 'style'), 
      'rel' => array('a', 'area', 'link'),
      'scoped' => array('style'),     
      'shape' => array('a', 'area'),    
      'src' => array('audio', 'embed', 'iframe', 'img', 'input', 'script', 'source', 'track', 'video'), 
      'srcset' => array('img'),
      'style' => array(),
      'target' => array('a', 'area', 'base', 'form'),     
      'usemap' => array('img', 'input', 'object'),     
      'width' => array('canvas', 'embed', 'iframe', 'img', 'input', 'object', 'video'),
    ),
    'svg' => array(
      //Core Attributes
      'id' => array(), 
      'xml:base' => array(), 
      'xml:lang' => array(),
      'xml:space' => array(),
      'class' => array(),
      'style' => array(),
     
      //Presentation attributes
      'alignment-baseline' => array(),
      'baseline-shift' => array(),
      'clip' => array(),
      'clip-path' => array(),
      'clip-rule' => array(),
      'color' => array(),
      'color-interpolation' => array(),
      'color-interpolation-filters' => array(),
      'color-profile' => array(),
      'color-rendering' => array(),
      'cursor' => array(),
      'direction' => array(),
      'display' => array(),
      'dominant-baseline' => array(),
      'enable-background' => array(),
      'fill' => array(),
      'fill-opacity' => array(),
      'fill-rule' => array(),
      'filter' => array(),
      'flood-color' => array(),
      'flood-opacity' => array(),
      'font-family' => array(),
      'font-size' => array(),
      'font-size-adjust' => array(),
      'font-stretch' => array(),
      'font-style' => array(),
      'font-variant' => array(),
      'font-weight' => array(),
      'glyph-orientation-horizontal' => array(),
      'glyph-orientation-vertical' => array(),
      'image-rendering' => array(),
      'kerning' => array(),
      'letter-spacing' => array(),
      'lighting-color' => array(),
      'marker-end' => array(),
      'marker-mid' => array(),
      'marker-start' => array(),
      'mask' => array(),
      'opacity' => array(),
      'overflow' => array(),
      'pointer-events' => array(),
      'shape-rendering' => array(),
      'stop-color' => array(),
      'stop-opacity' => array(),
      'stroke' => array(),
      'stroke-dasharray' => array(),
      'stroke-dashoffset' => array(),
      'stroke-linecap' => array(),
      'stroke-linejoin' => array(),
      'stroke-miterlimit' => array(),
      'stroke-opacity' => array(),
      'stroke-width' => array(),
      'text-anchor' => array(),
      'text-decoration' => array(),
      'text-rendering' => array(),
      'unicode-bidi' => array(),
      'visibility' => array(),
      'word-spacing' => array(),
      'writing-mode' => array(),
      
      //Drawing attributes
      'cx' => array(),
      'cy' => array(),
      'd' => array(),
      'height' => array(),
      'pathLength' => array(),
      'points' => array(),
      'preserveAspectRatio' => array(),
      'r' => array(),
      'rx' => array(),
      'ry' => array(),
      'transform' => array(),
      'width' => array(),
      'x' => array(),
      'x1' => array(),
      'x2' => array(),
      'y' => array(),
      'y1' => array(),
      'y2' => array(),
      
      //Xlink attributes
      'xlink:href' => array(),
      'xlink:type' => array(),
      'xlink:role' => array(),
      'xlink:arcrole' => array(),
      'xlink:title' => array(),
      'xlink:show' => array(),
      'xlink:actuate' => array(),
      
      //Text element attributes
      'dx' => array(),
      'dy' => array(),
      'lengthAdjust' => array(),
      'method' => array(),
      'rotate' => array(),
      'spacing' => array(),
      'startOffset' => array(),
      'text-anchor' => array(),
      'textLength' => array(),
      
    ),
  );
  
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
      $this->buildDefaults();
    }
  }
  /**
   * Sets qualitative type
   * 
   * @param string $type Qualitative name of format type
   */
  public function setType($type){
    $this->type = $type;
  }
  /**
   * Sets elements
   * 
   * @param array $elements Allowable elements
   */
  public function setElements($elements){
    if (is_array($elements)){
      $this->elements = $elements;
    }
  }
  /**
   * Sets attributes
   * 
   * @param array $attributes Allowable attributes
   */
  public function setAttributes($attributes){
    if (is_array($attributes)){
      $this->attributes = $attributes;
      $this->buildDefaults();
    }
  }
  /**
   * Sets comments
   * 
   * $param boolean $comments Allow comments nodes
   */
  public function setComments($comments){
    if (is_bool($comments)){
      $this->comments = $comments;
    }
  }
  /**
   * Builds defaults based on qualitative type
   */
  protected function buildDefaults(){
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
  /**
   * Scrubs the markup
   */
  public function scrub(){
    // Load input into dom document and find the Body
    $input_doc = new DOMDocument('1.0');
    libxml_use_internal_errors(true);
    $success = $input_doc->loadXML("<root>{$this->markup}</root>");
    libxml_clear_errors();
    if ($success){
      $input_root = $input_doc->getElementsByTagName('root')->item(0);
      
      // Begin to build output document
      $output_doc = new DOMDocument('1.0');
      $output_doc->formatOutput = true;
      $root = $output_doc->createElement('root');
      $output_doc->appendChild($root);
      $root = $this->buildxml($output_doc, $input_root, $root);
      
      //Saving only the contents of the Body tag

      $this->markup = ""; 
      $children  = $root->childNodes;
      foreach ($children as $child){ 
        $this->markup .= $output_doc->saveXML($child);
      }
      return true;
    }
    else{
      $this->markup = htmlspecialchars($this->markup);
      return false;
    }

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
        elseif($child->nodeName === '#comment' && $this->comments == true){
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
