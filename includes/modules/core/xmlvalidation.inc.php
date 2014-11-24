<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transiguru.info>
 * 
 * XML validation and scrubbing
 */

/**
 * Scrubs HTML
 *
 * @param string $input String being evaluated
 * @param string $type Type of document being loaded
 * @param array $elements Whitelisted elements
 * @param array $attributes Whitelisted attributes as keys (values are arrays of elements, or null to permit all elements)
 *
 * @return string $clean_xml Cleaned XML
 *
 */
function core_xmlvalidation_scrubxml($input, $type, $elements, $attributes){
  // Load input into dom document and find the Body
  $input_doc = new DOMDocument('1.0');
  libxml_use_internal_errors(true);
  $input_doc->loadHTML($input);
  libxml_clear_errors();
  $input_body = $input_doc->getElementsByTagName('body')->item(0);
  
  // Begin to build output document
  $output_doc = new DOMDocument('1.0');
  $output_doc->formatOutput = true;
  $root = $output_doc->createElement('html');
  $root = $output_doc->appendChild($root);
  $body = $output_doc->createElement('body');
  $body = $root->appendChild($body);
  $body = core_xmlvalidation_buildxml($input_doc, $output_doc, $input_body, $body, $elements, $attributes);
  
  
  
  //Saving only the contents of the Body tag

  $clean_xml = ""; 
  $children  = $body->childNodes;
  foreach ($children as $child){ 
    $clean_xml .= $output_doc->saveXML($child);
  }
  return $clean_xml;

}

/**
 * Recursively processes the XML tree
 * TODO: Make this work for all XML documents, not just HTML fragments
 *
 * @param object $input_node Input XML Document Node
 * @param object $output_node Output XML Document Node
 */
function core_xmlvalidation_buildxml($input_doc, $output_doc, $input_node, $output_node, $elements, $attributes){
  $children = $input_node->childNodes;
  if (count($children)>0){
    foreach ($children as $child){
      if (in_array($child->nodeName,$elements)){
        $output_child = $output_doc->createElement($child->nodeName);
        $input_attributes = $child->attributes;
        foreach ($input_attributes as $attr){
          if (array_key_exists($attr->name, $attributes) && ($attributes[$attr->name] == null || (count($attributes[$attr->name])>0 && in_array($child->nodeName,$attributes[$attr->name])))){
            $output_child->setAttribute($attr->name, $attr->value);
          }
        }
        $output_child = core_xmlvalidation_buildxml($input_doc, $output_doc, $child, $output_child, $elements, $attributes);
        $output_node->appendChild($output_child);
      }
      elseif($child->nodeName === '#text'){
        $text_node = $output_doc->createTextNode($child->nodeValue);
        $output_node->appendChild($text_node);
      }
      elseif($child->nodeName === '#comment'){
        $text_node = $output_doc->createComment($child->nodeValue);
        $output_node->appendChild($text_node);
      }
    }
  }
  return $output_node;
}
