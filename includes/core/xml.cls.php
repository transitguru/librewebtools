<?php

/**
 * @file
 * XML class to use for creating or scrubbing XML fragments
 *
 */

class XML{

  /**
   * All valid HTML elements
   */  
  protected $html_elements = array(
    'a',           //defines a hyperlink, the named target destination for a hyperlink, or both.
    'abbr',        //represents an abbreviation and optionally provides a full description for it. 
    'address',     //used by authors to supply contact information.
    'area',        //defines a hot-spot region on an image, this element is used only within a <map> element.
    'article',     //HTML5 ONLY! [IE9+] represents a self-contained composition in a document.
    'aside',       //HTML5 ONLY! [IE9+] represents a section of the page with content.
    'audio',       //HTML5 ONLY! [IE9+ basic support] used to embed sound content in documents.
    'b',           //represents a span of text stylistically different from normal text (not necessarily bold)
    'base',        //specifies the base URL to use for all relative URLs contained within a document.
    'bdi',         //HTML5 ONLY! [not supported!] isolates a span of text that might be formatted in a different direction from other text outside it.
    'blockquote',  //indicates that the enclosed text is an extended quotation. 
    'body',        //represents the content of an HTML document. There is only one <body> element in a document.
    'br',          //produces a line break in text (carriage-return). 
    'button',      //represents a clickable button.
    'canvas',      //HTML5 ONLY! [IE9+] used to draw graphics via scripting (usually JavaScript).
    'cite',        //represents a reference to a creative work. 
    'code',        //represents a fragment of computer code. 
    'data',        //HTML5 ONLY! [not supported!] links a given content with a machine-readable translation.
    'datalist',    //HTML5 ONLY! [IE10+] contains a set of <option> elements that represent the values available for other controls.
    'dd',          //indicates the description of a term in a description list (<dl>) element. 
    'del',         //represents a range of text that has been deleted from a document. 
    'dfn',         //represents the defining instance of a term
    'div',         //generic container for flow content, which does not inherently represent anything. 
    'dl',          //encloses a list of pairs of terms and descriptions.
    'dt',          //identifies a term in a definition list. This element can occur only as a child element of a <dl>. 
    'em',          //marks text that has stress emphasis. The <em> element can be nested, with each level of nesting indicating a greater degree of emphasis.
    'embed',       //represents an integration point for an external application or interactive content (in other words, a plug-in).
    'fieldset',    //group several controls as well as labels (<label>) within a web form.
    'figcaption',  //HTML5 ONLY! [IE9+] represents a caption or a legend associated with a figure or an illustratio
    'figure',      //HTML5 ONLY! [IE9+] represents self-contained content, frequently with a caption (<figcaption>
    'footer',      //HTML5 ONLY! [IE9+] represents a footer for its nearest sectioning content or sectioning root elemen
    'form',        //represents a document section that contains interactive controls to submit information to a web server.
    'h1',          //Heading level 1.
    'h2',          //Heading level 2.
    'h3',          //Heading level 3.
    'h4',          //Heading level 4.
    'h5',          //Heading level 5.
    'h6',          //Heading level 6.
    'head',        //provides general information (metadata) about the documen
    'header',      //HTML5 ONLY! [IE9+]  represents a group of introductory or navigational aids
    'hr',          //represents a thematic break between paragraph-level elements
    'html',        //represents the root of an HTML document
    'i',           //represents a range of text that is set off from the normal text for some reaso
    'iframe',      //represents a nested browsing context, effectively embedding another HTML page into the current page. 
    'img',         //represents an image of the document.
    'input',       //used to create interactive controls for web-based forms in order to accept data from the user.
    'ins',         //represents a range of text that has been added to a document.
    'kbd',         //represents user input and produces an inline element displayed in the browser's default monospace font.
    'keygen',      //HTML5 ONLY! [No IE] facilitate generation of key material, and submission of the public key as part of an HTML form.
    'label',       //caption for an item in a user interface. 
    'legend',      //represents a caption for the content of its parent <fieldset>.
    'li',          //used to represent a list item within an <ul>, <ol>, or <menu>
    'link',        //specifies relationships between the current document and an external resource. 
    'main',        //HTML5 ONLY! [No IE] represents the main content of  the <body> of a document or application.
    'map',         //used with <area> elements to define an image map (a clickable link area).
    'mark',        //HTML5 ONLY! [IE9+]  represents highlighted text
    'meta',        //represents any metadata information that cannot be represented by one of the other meta-related elements (<base>, <link>, <script>, <style> or <title>).
    'meter',       //HTML5 ONLY! [No IE] represents either a scalar value within a known range or a fractional value.
    'nav',         //HTML5 ONLY! [IE9+] represents a section of a page that links
    'noscript',    //defines a section of html to be inserted if a script type on the page is unsupported
    'object',      //represents an external resource, which can be treated as an image, a nested browsing context, or a resource to be handled by a plugin.
    'ol',          //represents an ordered list of items.
    'optgroup',    //creates a grouping of options within a <select> element.
    'option',      //used to create a control representing an item within a <select>, an <optgroup> or a <datalist> HTML5 element.
    'output',      //HTML5 ONLY! [No IE] represents the result of a calculation or user action.
    'p',           //represents a paragraph of text. Paragraphs are block-level elements.
    'param',       //defines parameters for <object>.
    'pre',         //represents preformatted text.
    'progress',    //HTML5 ONLY! [IE10+]  used to view the completion progress of a task
    'q',           //indicates that the enclosed text is a short inline quotation.
    'rp',          //HTML5 ONLY! [No FF or O] fall-back parenthesis for browsers non-supporting ruby annotations.
    'rt',          //HTML5 ONLY! [No FF or O] embraces pronunciation of character presented in a ruby annotations.
    'ruby',        //HTML5 ONLY! [No FF or O] represents a ruby annotation. Ruby annotations are for showing pronounciation of East Asian characters.
    's',           //renders text with a strikethrough, or a line through it.
    'samp',        //identify sample output from a computer program.
    'script',      //embed or reference an executable script within an HTML or XHTML document.
    'section',     //HTML5 ONLY! [IE9+] represents a generic section of a document. Each <section> should be identified, typically by including a heading (h1-h6 element) as a child of the <section> element.
    'select',      //epresents a control that presents a menu of options.
    'small',       //makes the text font size one size smaller
    'source',      //HTML5 ONLY! [IE9+] specify multiple media resources for <picture>, <audio> and <video> elements.
    'span',        //generic inline container for phrasing content, which does not inherently represent anything.
    'strong',      //text strong importance, and is typically displayed in bold.
    'style',       //contains style information for a document, or a part of document.
    'sub',         //defines a span of text that should be displayed, for typographic reasons, lower, and often smaller, than the main span of text.
    'sup',         //defines a span of text that should be displayed, for typographic reasons, higher, and often smaller, than the main span of text.
    'table',       //represents data in two dimensions or more.
    'tbody',       //defines one or more rows as the body of its parent <table> element when no <tr> elements are children of the parent.
    'td',          //defines a cell of a table that contains data. It participates in the table model.
    'template',    //HTML5 ONLY! [No IE] mechanism for holding client-side content that is not to be rendered when a page is loaded but may subsequently be instantiated during runtime using JavaScript. 
    'textarea',    //represents a multi-line plain-text editing control.
    'tfoot',       //defines a set of rows summarizing the columns of the table.
    'th',          //defines a cell that is a header for a group of cells of a table.
    'thead',       //defines a set of rows defining the head of the columns of the table.
    'time',        //HTML5 ONLY! [IE9+] represents either a time on a 24-hour clock or a precise date in the Gregorian calendar (with optional time and timezone information).
    'title',       //defines the title of the document, shown in a browser's title bar or on the page's tab.
    'tr',          //defines a row of cells in a table. Those can be a mix of <td> and <th> elements.
    'track',       //HTML5 ONLY! [IE10+] used as a child of the media elements—<audio> and <video>.
    'u',           //In HTML5, this element represents a span of text with an unarticulated, though explicitly rendered, non-textual annotation
    'ul',          //represents an unordered list of items
    'var',         //represents a variable in a mathematical expression or a programming context.
    'video',       //HTML5 ONLY! [IE9+] used to embed video content
    'wbr',         //HTML5 ONLY! [IE5.5-7]  represents a position within text where the browser may optionally break a line
  );
  /**
   * All valid HTML attributes
   */
  protected $html_attributes = array(
    'accept' => array('form', 'input'), //List of filetypes the server accepts
    'accept-charset' => array('form'), //List of supported charsets.
    'accesskey' => array(), //Defines a keyboard shortcut
    'action' => array('form'),  //The URI of a program that processes form.
    'align' => array('applet', 'caption', 'col', 'colgroup',  'hr', 'iframe', 'img', 'table', 'tbody',  'td',  'tfoot' , 'th', 'thead', 'tr'), //Specifies the horizontal alignment of the element.
    'alt' => array('applet', 'area', 'img', 'input'), //Alternative text
    'async' => array('script'), //Indicates that the script should be executed asynchronously.
    'autocomplete' => array('form', 'input'),  //Activates autocomplete
    'autofocus' => array('button', 'input', 'keygen', 'select', 'textarea'), //The element should be automatically focused
    'autoplay' => array('audio', 'video'), //The audio or video should play onload
    'autosave' => array('input'), //Previous values should persist dropdowns of selectable values across page loads.
    'bgcolor' => array('body', 'col', 'colgroup', 'marquee', 'table', 'tbody', 'tfoot', 'td', 'th', 'tr'), //Background color of the element. 
    'border' => array('img', 'object', 'table'), //The border width
    'buffered' => array('audio', 'video'), //Contains the time range of already buffered media.
    'challenge' => array('keygen'), //A challenge string that is submitted along with the public key.
    'charset' => array('meta', 'script'),  //Declares character encoding
    'checked' => array('command', 'input'), //Declares an element as checked
    'cite' => array('blockquote', 'del', 'ins', 'q'), //Contains a URI which points to the source of the quote or change.
    'class' => array(), //Often used with CSS
    'code' => array('applet'), //Specifies the URL of the applet's code
    'codebase' => array('applet'), //This attribute gives the absolute or relative URL
    'color' => array('basefont', 'font', 'hr'), //Defines color.
    'cols' => array('textarea'),  //Defines the number of columns in a textarea.
    'colspan' => array('td', 'th'), //The colspan attribute defines the number of columns a cell should span.
    'content' => array('meta'), //A value associated with http-equiv or name depending on the context.
    'contenteditable' => array(), //Indicates whether the element's content is editable.
    'contextmenu' => array(),    //Defines the ID of a <menu> element which will serve as the element's context menu.
    'controls' => array('audio', 'video'), //Indicates whether the browser should show playback controls to the user.
    'coords' => array('area'), //A set of values specifying the coordinates of the hot-spot region.
    'data' => array('object'),  //Specifies the URL of the resource.
    'data-*' => array(), //Lets you attach custom attributes to an HTML element.
    'datetime' => array('del', 'ins', 'time'), //Indicates the date and time associated with the element.
    'default' => array('track'),  //Indicates that the track should be enabled
    'defer' => array('script'), //Indicates that the script should be executed after the page has been parsed.
    'dir' => array(),  //Defines the text direction.
    'dirname' => array('input', 'textarea'),     
    'disabled' => array('button', 'command', 'fieldset', 'input', 'keygen', 'optgroup', 'option', 'select', 'textarea'), //Disables the element
    'download' => array('a', 'area'), //Indicates that the hyperlink is to be used for downloading a resource.
    'draggable' => array(),  //Defines whether the element can be dragged.
    'dropzone' => array(),  //Indicates that the element accept the dropping of content on it.
    'enctype' => array('form'), //Defines the content type of the form date when the method is POST.
    'for' => array('label', 'output'), //Describes elements which belongs to this one.
    'form' => array('button', 'fieldset', 'input', 'keygen', 'label', 'meter', 'object', 'output', 'progress', 'select', 'textarea'), //Indicates the form that is the owner of the element.
    'formaction' => array('input', 'button'), //Indicates the action of the element
    'headers' => array('td', 'th'), //IDs of the <th> elements which applies to this element.
    'height' => array('canvas', 'embed', 'iframe', 'img', 'input', 'object', 'video'), //Height of element.
    'hidden' => array(), //Prevents rendering of given element, while keeping child elements, e.g. script elements, active.
    'high' => array('meter'), //Indicates the lower bound of the upper range.
    'href' => array('a','area', 'base', 'link'), //The URL of a linked resource.
    'hreflang' => array('a', 'area', 'link'), // Specifies the language of the linked resource.
    'http-equiv' => array('meta'),     
    'icon' => array('command'), //Specifies a picture which represents the command.
    'id' => array(), //Global attribute    Often used with CSS to style a specific element. The value of this attribute must be unique.
    'ismap' => array('img'), //Indicates that the image is part of a server-side image map.
    'itemprop' => array(),     
    'keytype' => array('keygen'), //Specifies the type of key generated.
    'kind' => array('track'), //Specifies the kind of text track.
    'label' => array('track'), //Specifies a user-readable title of the text track.
    'lang' => array(), //Defines the language used in the element.
    'language' => array   ('script'), //Defines the script language used in the element.
    'list' => array('input'), //Identifies a list of pre-defined options to suggest to the user.
    'loop' => array('audio', 'bgsound', 'marquee', 'video'),  //Indicates whether the media should loop.
    'low' => array('meter'), //Indicates the upper bound of the lower range.
    'manifest' => array('html'), //Specifies the URL of the document's cache manifest.
    'max' => array('input', 'meter', 'progress'),    //Indicates the maximum value allowed.
    'maxlength' => array('input', 'textarea'), //Defines the maximum number of characters allowed in the element.
    'media' => array('a', 'area', 'link', 'source', 'style'), //Specifies a hint of the media for which the linked resource was designed.
    'method' => array('form'), //Defines which HTTP method to use when submitting the form.
    'min' => array('input', 'meter'), //Indicates the minimum value allowed.
    'multiple' => array('input', 'select'), //Indicates whether multiple values can be entered.
    'name' => array('button', 'form', 'fieldset', 'iframe', 'input', 'keygen', 'object', 'output', 'select', 'textarea', 'map', 'meta', 'param'), //Name of the element.
    'novalidate' => array('form'), //This attribute indicates that the form shouldn't be validated when submitted.
    'on*' => array(), //Javascript event handler
    'open' => array('details'), //Indicates whether the details will be shown on page load.
    'optimum' => array('meter'), //Indicates the optimal numeric value.
    'pattern' => array('input'), //Defines a regular expression which the element's value will be validated against.
    'ping' => array('a', 'area'), //     
    'placeholder' => array('input', 'textarea'), //Provides a hint to the user of what can be entered in the field.
    'poster' => array('video'), //A URL indicating a poster frame to show until the user plays or seeks.
    'preload' => array('audio', 'video'), //Indicates whether the whole resource, parts of it or nothing should be preloaded.
    'pubdate' => array('time'), //Indicates whether this date and time is the date of the nearest <article> ancestor element.
    'radiogroup' => array('command'),     
    'readonly' => array('input', 'textarea'),  //Indicates whether the element can be edited.
    'rel' => array('a', 'area', 'link'), //Specifies the relationship of the target object to the link object.
    'required' => array('input', 'select', 'textarea'), //Indicates whether this element is required to fill out or not.
    'reversed' => array('ol'), //Indicates whether the list should be displayed in a descending order instead of a ascending.
    'rows' => array('textarea'), //Defines the number of rows in a textarea.
    'rowspan' => array('td', 'th'), //Defines the number of rows a table cell should span over.
    'sandbox' => array('iframe'),   
    'scope' => array('th'),    
    'scoped' => array('style'),     
    'seamless' => array('iframe'),     
    'selected' => array('option'), //Defines a value which will be selected on page load.
    'shape' => array('a', 'area'),    
    'size' => array('input', 'select'), //Defines the width of the element (in pixels) or characters.
    'sizes' => array('link'),  
    'span' => array('col', 'colgroup'),     
    'spellcheck' => array(), //Indicates whether spell checking is allowed for the element.
    'src' => array('audio', 'embed', 'iframe', 'img', 'input', 'script', 'source', 'track', 'video'), //The URL of the embeddable content.
    'srcdoc' => array('iframe'),    
    'srclang' => array('track'),   
    'srcset' => array('img'),
    'start' => array('ol'), //Defines the first number if other than 1.
    'step' => array('input'),  
    'style' => array(), //Defines CSS styles which will override styles previously set.
    'summary' => array('table'),     
    'tabindex' => array(),  //Overrides the browser's default tab order
    'target' => array('a', 'area', 'base', 'form'),     
    'title' => array(),  //Usually a tooltip text
    'type' => array('button', 'input', 'command', 'embed', 'object', 'script', 'source', 'style', 'menu'),  //Defines the type of the element.
    'usemap' => array('img', 'input', 'object'),     
    'value' => array('button', 'option', 'input', 'li', 'meter', 'progress', 'param'), //Defines a default value
    'width' => array('canvas', 'embed', 'iframe', 'img', 'input', 'object', 'video'), //Width of element, deprecated for some of these elements
    'wrap' => array('textarea')  //Indicates whether the text should be wrapped.
  );
  /**
   * Array of valid SVG elements
   * Unless otherwise shown, there is native support in IE9+
   */
  protected $svg_elements = array(
    'a',                     //defines a hyperlink
    'altGlyph',              //[no IE?] allows sophisticated selection of the glyphs used to render its child character data.
    'altGlyphDef',           //[no IE?] defines a substitution representation for glyphs.
    'altGlyphItem',          //[no IE?] provides a set of candidates for glyph substitution by the <altglyph> element.
    'animate',               //[no IE] Inside a shape element and defines how an attribute of an element changes over the animation. The attribute will change from the initial value to the end value in the duration specified.
    'animateMotion',         //[no IE?] causes a referenced element to move along a motion path.
    'animateTransform',      //[no IE?] animates a transformation attribute on a target element, thereby allowing animations to control translation, scaling, rotation and/or skewing.
    'circle',                //SVG basic shape, used to create circles based on a center point and a radius.
    'clipPath',              //restricts the region to which paint can be applied. Conceptually, any parts of the drawing that lie outside of the region bounded by the currently active clipping path are not drawn.
    'defs',                  //referenced elements be defined inside of a defs element. Defining these elements inside of a defs element promotes understandability of the SVG content and thus promotes accessibility.
    'desc',                  //Each container element or graphics element in an SVG drawing can supply a desc description string where the description is text-only. When the current SVG document fragment is rendered as SVG on visual media, desc elements are not rendered as part of the graphics. 
    'ellipse',               //SVG basic shape, used to create ellipses based on a center coordinate, and both their x and y radius.
    'feBlend',               //[no IE] Filter effects
    'feColorMatrix',         //[no IE] Filter effects
    'feComponentTransfer',   //[no IE] Filter effects
    'feComposite',           //[no IE] Filter effects
    'feConvolveMatrix',      //[no IE] Filter effects
    'feDiffuseLighting',     //[no IE] Filter effects
    'feDisplacementMap',     //[no IE] Filter effects
    'feDistantLight',        //[no IE] Filter effects
    'feFlood',               //[no IE] Filter effects
    'feFuncA',               //[no IE] Filter effects
    'feFuncB',               //[no IE] Filter effects
    'feFuncG',               //[no IE] Filter effects
    'feFuncR',               //[no IE] Filter effects
    'feGaussianBlur',        //[no IE] Filter effects
    'feImage',               //[no IE] Filter effects
    'feMerge',               //[no IE] Filter effects
    'feMergeNode',           //[no IE] Filter effects
    'feMorphology',          //[no IE] Filter effects
    'feOffset',              //[no IE] Filter effects
    'fePointLight',          //[no IE] Filter effects
    'feSpecularLighting',    //[no IE] Filter effects
    'feSpotLight',           //[no IE] Filter effects
    'feTile',                //[no IE] Filter effects
    'feTurbulence',          //[no IE] Filter effects
    'filter',                //[no IE10+]serves as container for atomic filter operations.
    'font',                  //[?] defines a font to be used for text layout.
    'font-face',             //[?] corresponds to the CSS @font-face declaration. It defines a font's outer properties.
    'font-face-format',      //[?] describes the type of font referenced by its parent <font-face-uri>.
    'font-face-name',        //[?] points to a locally installed copy of this font, identified by its name.
    'font-face-src',         //[?] corresponds to the src property in CSS @font-face descriptions.
    'font-face-uri',         //[?] points to a remote definition of the current font.
    'foreignObject',         //[?] allows for inclusion of a foreign XML namespace which has its graphical content drawn by a different user agent.
    'g',                     //container used to group objects. Transformations applied to the g element are performed on all of its child elements.
    'glyph',                 //[?] defines a single glyph in an SVG font.
    'glyphRef',              //[?] provides a single possible glyph to the referencing <altglyph> substitution.
    'hkern',                 //[?] The horizontal distance between two glyphs can be fine-tweaked with an hkern Element. 
    'image',                 //The SVG Image Element (<image>) allows a raster image into be included in an SVG document.
    'line',                  //SVG basic shape, used to create a line connecting two points.
    'linearGradient',        //lets authors define linear gradients to fill or stroke graphical elements.
    'marker',                //defines the graphics that is to be used for drawing arrowheads or polymarkers on a given <path>, <line>, <polyline> or <polygon> element.
    'mask',                  //[?] A mask is defined with the mask element. A mask is used/referenced using the mask property.
    'metadata',              //Metadata which is included with SVG content should be specified within metadata elements.
    'missing-glyph',         //[?] The missing-glyph's content is rendered, if for a given character the font doesn't define an appropriate <glyph>.
    'mpath',                 //[?] provides the ability to reference an external <path> element as the definition of a motion path.
    'path',                  //generic element to define a shape. 
    'pattern',               //[?] indicates that the given element shall be filled or stroked with the referenced pattern.
    'polygon',               //defines a closed shape consisting of a set of connected straight line segments.
    'polyline',              //SVG basic shape, used to create a series of straight lines connecting several points
    'radialGradient',        //define radial gradients to fill or stroke graphical elements.
    'rect',                  //SVG basic shape, used to create rectangles based on the position of a corner and their width and height.
    'script',                //equivalent to the script element in HTML and thus is the place for scripts (e.g., ECMAScript).
    'set',                   //[?] provides a simple means of just setting the value of an attribute for a specified duration.
    'stop',                  //The ramp of colors to use on a gradient is defined by the stop elements
    'style',                 //allows style sheets to be embedded directly within SVG content.
    'svg',                   //Root element of an SVG, can be embedded inside an HTML document
    'switch',                //evaluates the requiredFeatures, requiredExtensions and systemLanguage attributes on its direct child elements in order
    'symbol',                //used to define graphical template objects which can be instantiated by a <use> element. 
    'text',                  //defines a graphics element consisting of text.
    'textPath',              //Ability to place text along the shape of a <path> element. 
    'title',                 //Each container element or graphics element in an SVG drawing can supply a title description
    'tref',                  //[?] The textual content for a <text> can be either character data directly embedded within the <text> element or the character data content of a referenced element, where the referencing is specified with a tref element.
    'tspan',                 //Within a <text> element, text and font properties and the current text position can be adjusted with absolute or relative coordinate values by including a tspan element.
    'use',                   //takes nodes from within the SVG document, and duplicates them somewhere else. 
    'view',                  //[?] A view is a defined way to view the image, like a zoom level or a detail view.
    'vkern',                 //The vertical distance between two glyphs in top-to-bottom fonts can be fine-tweaked with an vkern Element. This process is known as Kerning.  
  );
  /**
   * Array of valid SVG attributes
   */
  protected $svg_attributes = array(
    'accelerate' => array(),
    'accent-height' => array(),
    'accumulate' => array(),
    'additive' => array(),
    'alignment-baseline' => array(),
    'allowReorder' => array(),
    'alphabetic' => array(),
    'amplitude' => array(),
    'arabic-form' => array(),
    'ascent' => array(),
    'attributeName' => array(),
    'attributeType' => array(),
    'autoReverse' => array(),
    'azimuth' => array(),
    'baseFrequency' => array(),
    'baseline-shift' => array(),
    'baseProfile' => array(),
    'bbox' => array(),
    'begin' => array(),
    'bias' => array(),
    'by' => array(),
    'calcMode' => array(),
    'cap-height' => array(),
    'class' => array(),
    'clip' => array(),
    'clipPathUnits' => array(),
    'clip-path' => array(),
    'clip-rule' => array(),
    'color' => array(),
    'color-interpolation' => array(),
    'color-interpolation-filters' => array(),
    'color-profile' => array(),
    'color-rendering' => array(),
    'contentScriptType' => array(),
    'contentStyleType' => array(),
    'cursor' => array(),
    'cx' => array(),
    'cy' => array(),
    'd' => array(),
    'decelerate' => array(),
    'descent' => array(),
    'diffuseConstant' => array(),
    'direction' => array(),
    'display' => array(),
    'divisor' => array(),
    'dominant-baseline' => array(),
    'dur' => array(),
    'dx' => array(),
    'dy' => array(),
    'edgeMode' => array(),
    'elevation' => array(),
    'enable-background' => array(),
    'end' => array(),
    'exponent' => array(),
    'externalResourcesRequired' => array(),
    'fill' => array(),
    'fill-opacity' => array(),
    'fill-rule' => array(),
    'filter' => array(),
    'filterRes' => array(),
    'filterUnits' => array(),
    'flood-color' => array(),
    'flood-opacity' => array(),
    'font-family' => array(),
    'font-size' => array(),
    'font-size-adjust' => array(),
    'font-stretch' => array(),
    'font-style' => array(),
    'font-variant' => array(),
    'font-weight' => array(),
    'format' => array(),
    'from' => array(),
    'fx' => array(),
    'fy' => array(),
    'g1' => array(),
    'g2' => array(),
    'glyph-name' => array(),
    'glyph-orientation-horizontal' => array(),
    'glyph-orientation-vertical' => array(),
    'glyphRef' => array(),
    'gradientTransform' => array(),
    'gradientUnits' => array(),
    'hanging' => array(),
    'height' => array(),
    'horiz-adv-x' => array(),
    'horiz-origin-x' => array(),
    'id' => array(),
    'ideographic' => array(),
    'image-rendering' => array(),
    'in' => array(),
    'in2' => array(),
    'intercept' => array(),
    'k' => array(),
    'k1' => array(),
    'k2' => array(),
    'k3' => array(),
    'k4' => array(),
    'kernelMatrix' => array(),
    'kernelUnitLength' => array(),
    'kerning' => array(),
    'keyPoints' => array(),
    'keySplines' => array(),
    'keyTimes' => array(),
    'lang' => array(),
    'lengthAdjust' => array(),
    'letter-spacing' => array(),
    'lighting-color' => array(),
    'limitingConeAngle' => array(),
    'local' => array(),
    'marker-end' => array(),
    'marker-mid' => array(),
    'marker-start' => array(),
    'markerHeight' => array(),
    'markerUnits' => array(),
    'markerWidth' => array(),
    'mask' => array(),
    'maskContentUnits' => array(),
    'maskUnits' => array(),
    'mathematical' => array(),
    'max' => array(),
    'media' => array(),
    'method' => array(),
    'min' => array(),
    'mode' => array(),
    'name' => array(),
    'numOctaves' => array(),
    'offset' => array(),
    'onabort' => array(),
    'onactivate' => array(),
    'onbegin' => array(),
    'onclick' => array(),
    'onend' => array(),
    'onerror' => array(),
    'onfocusin' => array(),
    'onfocusout' => array(),
    'onload' => array(),
    'onmousedown' => array(),
    'onmousemove' => array(),
    'onmouseout' => array(),
    'onmouseover' => array(),
    'onmouseup' => array(),
    'onrepeat' => array(),
    'onresize' => array(),
    'onscroll' => array(),
    'onunload' => array(),
    'onzoom' => array(),
    'opacity' => array(),
    'operator' => array(),
    'order' => array(),
    'orient' => array(),
    'orientation' => array(),
    'origin' => array(),
    'overflow' => array(),
    'overline-position' => array(),
    'overline-thickness' => array(),
    'panose-1' => array(),
    'paint-order' => array(),
    'path' => array(),
    'pathLength' => array(),
    'patternContentUnits' => array(),
    'patternTransform' => array(),
    'patternUnits' => array(),
    'pointer-events' => array(),
    'points' => array(),
    'pointsAtX' => array(),
    'pointsAtY' => array(),
    'pointsAtZ' => array(),
    'preserveAlpha' => array(),
    'preserveAspectRatio' => array(),
    'primitiveUnits' => array(),
    'r' => array(),
    'radius' => array(),
    'refX' => array(),
    'refY' => array(),
    'rendering-intent' => array(),
    'repeatCount' => array(),
    'repeatDur' => array(),
    'requiredExtensions' => array(),
    'requiredFeatures' => array(),
    'restart' => array(),
    'result' => array(),
    'rotate' => array(),
    'rx' => array(),
    'ry' => array(),
    'scale' => array(),
    'seed' => array(),
    'shape-rendering' => array(),
    'slope' => array(),
    'spacing' => array(),
    'specularConstant' => array(),
    'specularExponent' => array(),
    'speed' => array(),
    'spreadMethod' => array(),
    'startOffset' => array(),
    'stdDeviation' => array(),
    'stemh' => array(),
    'stemv' => array(),
    'stitchTiles' => array(),
    'stop-color' => array(),
    'stop-opacity' => array(),
    'strikethrough-position' => array(),
    'strikethrough-thickness' => array(),
    'string' => array(),
    'stroke' => array(),
    'stroke-dasharray' => array(),
    'stroke-dashoffset' => array(),
    'stroke-linecap' => array(),
    'stroke-linejoin' => array(),
    'stroke-miterlimit' => array(),
    'stroke-opacity' => array(),
    'stroke-width' => array(),
    'style' => array(),
    'surfaceScale' => array(),
    'systemLanguage' => array(),
    'tableValues' => array(),
    'target' => array(),
    'targetX' => array(),
    'targetY' => array(),
    'text-anchor' => array(),
    'text-decoration' => array(),
    'text-rendering' => array(),
    'textLength' => array(),
    'to' => array(),
    'transform' => array(),
    'type' => array(),
    'u1' => array(),
    'u2' => array(),
    'underline-position' => array(),
    'underline-thickness' => array(),
    'unicode' => array(),
    'unicode-bidi' => array(),
    'unicode-range' => array(),
    'units-per-em' => array(),
    'v-alphabetic' => array(),
    'v-hanging' => array(),
    'v-ideographic' => array(),
    'v-mathematical' => array(),
    'values' => array(),
    'version' => array(),
    'vert-adv-y' => array(),
    'vert-origin-x' => array(),
    'vert-origin-y' => array(),
    'viewBox' => array(),
    'viewTarget' => array(),
    'visibility' => array(),
    'width' => array(),
    'widths' => array(),
    'word-spacing' => array(),
    'writing-mode' => array(),
    'x' => array(),
    'x-height' => array(),
    'x1' => array(),
    'x2' => array(),
    'xChannelSelector' => array(),
    'xlink:actuate' => array(),
    'xlink:arcrole' => array(),
    'xlink:href' => array(),
    'xlink:role' => array(),
    'xlink:show' => array(),
    'xlink:title' => array(),
    'xlink:type' => array(),
    'xml:base' => array(),
    'xml:lang' => array(),
    'xml:space' => array(),
    'y' => array(),
    'y1' => array(),
    'y2' => array(),
    'yChannelSelector' => array(),
    'z' => array(),
    'zoomAndPan' => array(),
  );

  /**
   * Markup that will be scrubbed
   */
  public $markup = '';
  /**
   * Type of markup being scrubbed
   * 'xml': generic XML, no defaults are loaded
   * 'html': load defaults for HTML
   * 'svg': load defaults for SVG only
   * 'html+svg': load both HTML and SVG defaults
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
    $this->markup = $markup;
    $this->type = $type;
    $this->elements = $elements;
    $this->attributes = $attributes;
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
