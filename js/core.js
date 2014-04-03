/**
 * @file
 * @author <msypolt@transitguru.info>
 *
 * General JavaScript functions
 */
"use strict";

/***************************************
 * AJAX helper functions               *
 ***************************************/

/**
 * Activates a hidden field to submit an AJAX request
 * This is useful when buttons are used to activate a variable and there is a 
 * chance that buttons would have been the same name. 
 * 
 * This function works best if all buttons call this function and there is one 
 * hidden input that would be edited as a result of this function. Using
 * many hidden inputs could cause names to stick and confuse the server
 * AJAX page. It is best NOT to define the button as a type="submit", however
 * some web browsers would also submit the form a second time when using 
 * buttons. Stylized anchor links work the best on ensuring the invisible
 * submit button is "clicked" and the form is submitted only once.
 * 
 * @param {string} button_id ID of a hidden input that will have its name changed
 * @param {string} name Name of input (preferred to be hidden) that the button would have been named
 * @param {string} value Value of the input that the button would have taken
 * @param {string} submit_id ID of a submit button (can be hidden or shown) so that the form is submitted
 * @param {string} submit_value Value of submit button (to allow for control of AJAX div id update)
 * 
 * @returns {void}
 */
function click_a_button(button_id, name, value, submit_id, submit_value, validate){
  document.getElementById(button_id).setAttribute('name',name);
  document.getElementById(button_id).setAttribute('value',value);
  document.getElementById(submit_id).setAttribute('value',submit_value);
  document.getElementById(submit_id).click();
}

//AJAX Functions
/**
 * This is the engine of the client side request to the server.
 * 
 * Note: Buttons that have same name will inherit the name of one of the
 * buttons which may or may not be the one clicked. It is best to use the 
 * click_a_button() function to populate a hidden input with a name and value
 * for this to work as intended. 
 * 
 * @param {string} formobject Object instance of form that the javascript will send to server
 * @param {string} responsediv ID of div (or other element) that will update when server responds
 * @param {string} responsemsg Markup to place within responsediv while waiting for the server
 * 
 * @returns {void}
 */
function ajaxPost(formobject,responsediv,responsemsg) {
  var xmlHttpReq = false;
  var strURL = formobject.action;
  
  
  // Webkit(Chrome/Safari), Gecko(Mozilla), IE >= 7
  if (window.XMLHttpRequest) {
    xmlHttpReq = new XMLHttpRequest();
  }
  // IE < 7 (who uses that anyway...)
  else if (window.ActiveXObject) {
    xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlHttpReq.open('post', strURL, true);
  
  try{
    var dataSend = new FormData(formobject);
    xmlHttpReq.send(dataSend);
  }
  catch (e){
    xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xmlHttpReq.send(getquerystring(formobject));
  }
  
  xmlHttpReq.onreadystatechange = function() {
    if (xmlHttpReq.readyState == 4) {
      // Show response when server responds
      document.getElementById(responsediv).innerHTML = xmlHttpReq.responseText;
    }
    else{
      // While waiting for response, display waiting message
      document.getElementById(responsediv).innerHTML = responsemsg;
    }
  }
}

/**
 * This function provides a fallback method of creating Form Data 
 * for older version of IE. 
 * 
 * @param {object} form Form object that contains elements
 * @returns {String} Stringified contents of form elements for use in XMLHttpReq
 */
function getquerystring(form) {
  var qstr = "";

  function GetElemValue(name, value) {
    qstr += (qstr.length > 0 ? "&" : "") + escape(name).replace(/\+/g, "%2B") + "=" + escape(value ? value : "").replace(/\+/g, "%2B");
  }
  var elemArray = form.elements;
  for (var i = 0; i < elemArray.length; i++) {
    var element = elemArray[i];
    var elemType = element.type.toUpperCase();
    var elemName = element.name;
    if (elemName) {
      if (elemType == "TEXT" || elemType == "TEXTAREA" || elemType == "PASSWORD" || elemType == "BUTTON" || elemType == "RESET" || elemType == "SUBMIT" || elemType == "FILE" || elemType == "IMAGE" || elemType == "HIDDEN"){
          GetElemValue(elemName, element.value);
      }
      else if (elemType == "CHECKBOX" && element.checked){
        GetElemValue(elemName, element.value ? element.value : "On");
      }
      else if (elemType == "RADIO" && element.checked){
        GetElemValue(elemName, element.value);
      }
      else if (elemType.indexOf("SELECT") != -1){
        for (var j = 0; j < element.options.length; j++) {
          var option = element.options[j];
          if (option.selected){
            GetElemValue(elemName, option.value ? option.value : option.text);
          }
        }
      }
    }
  }
  return qstr;
}

/**
 * A simpler way to handle AJAX data where only a small portion of the
 * page is updated. 
 * 
 * @param {string} data Stringified post inputs data
 * @param {string} action URL for where to handle POST data
 * @param {string} responsediv ID of div (or other element) that will update when server responds
 * @param {string} responsemsg Markup to place within responsediv while waiting for the server
 * @returns {void}
 */
function ajaxPostLite(data, action, responsediv,responsemsg){
  var xmlHttpReq = false;
  var strURL = action;
  
  
  // Webkit(Chrome/Safari), Gecko(Mozilla), IE >= 7
  if (window.XMLHttpRequest) {
    xmlHttpReq = new XMLHttpRequest();
  }
  // IE < 7 (who uses that anyway...)
  else if (window.ActiveXObject) {
    xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlHttpReq.open('post', strURL, true);
  xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xmlHttpReq.send('write_lite=1&' + data);
  
  xmlHttpReq.onreadystatechange = function() {
    if (xmlHttpReq.readyState == 4) {
      // Show response when server responds
      document.getElementById(responsediv).innerHTML = xmlHttpReq.responseText;
    }
    else{
      // While waiting for response, display waiting message
      document.getElementById(responsediv).innerHTML = responsemsg;
    }
  }
}


/**
 * Toggle the hiding or showing of an element
 * 
 * @param {string} id ID of element that is being hidden or shown
 * @returns {Boolean}
 */

function toggle_hide(id,classes){
  if (document.getElementById(id).getAttribute('class') == 'hide'){
    document.getElementById(id).removeAttribute('class');
    if (typeof classes !== 'undefined'){
      document.getElementById(id).setAttribute('class', classes);
    }
  }
  else{
    document.getElementById(id).setAttribute('class', 'hide');
  }
  return false;
}


/**
 * Lets your javascript take a little nap
 * 
 * @param {int} milliseconds Length of time that javascript should wait in milliseconds
 * @returns {void}
 */
function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}

/**
 * Allow a checkbox to be checked or unchecked
 * 
 * @param {string} id
 * @returns {void}
 */

function toggle_checkbox(id){
  if (document.getElementById(id).checked){
    document.getElementById(id).checked=false;
  }
  else{
    document.getElementById(id).checked=true;
  }
}


/**
 * Shows tooltip using a hidden div
 *  
 * @param {object} evt
 * @returns {void}
 */
function showTooltip(evt, text){
//Enter user inputs here
  var xOffset = 5;
  var yOffset = -30;
  var fontSize = 14;
  var fontFamily = "Sans";
  var fontWeight = "Normal";
  var textColor = "#000000";

  var boxFill = "#ffff00";
  var boxStroke = "#000000";
  var boxStrokeWidth = "1";
  var boxOpacity = "1.00";

  //Don't write below this line!
  var tooltip = document.getElementById('tooltip');
  var mx = evt.clientX + xOffset; 
  var my = evt.clientY + yOffset; 
  
  var fontString = fontSize.toString();
  var tooltipStyle = "";
  tooltipStyle = tooltipStyle.concat("font-size: ", fontString, "px; font-family:", fontFamily, "; font-weight:", fontWeight, "; font-color:", textColor, "; background-color: " ,boxFill, "; border:", boxStrokeWidth, "px solid" + boxStroke, "; opacity:", boxOpacity, ";", "top: ", my, "px; left: ", mx, "px; z-index:9; position:fixed; padding: 2px; border-radius: 5px;");
  tooltip.setAttribute("style", tooltipStyle);
  tooltip.innerHTML = text;
  tooltip.setAttribute("class","show");
}

/**
 * Hides a tooltip
 * 
 * @param {object} evt
 * @returns {void}
 */
function hideTooltip(evt){
  var tooltip = document.getElementById('tooltip');
  tooltip.setAttribute("class","hide");
}


function showDialogue(title){
  document.getElementById('dialogue').setAttribute('class', 'dialogue');
  document.getElementById('dialoguebg').setAttribute('class', 'dialoguebg');
  if (typeof title !== 'undefined'){
    document.getElementById('dialogue-title').innerHTML = title;
  }
  else{
    document.getElementById('dialogue-title').innerHTML = 'Dialog Box';
  }
}

function hideDialogue(){
  document.getElementById('dialogue').setAttribute('class', 'hide');
  document.getElementById('dialoguebg').setAttribute('class', 'hide');
  document.getElementById('dialogue-title').innerHTML = '';
  document.getElementById('dialogue-content').innerHTML = '';
}


function confirmDelete(name, postdata, responsediv){
  var text = '<p>Are you sure you want to delete <strong>' + name + '</strong></p><p><button onclick="event.preventDefault();hideDialogue();ajaxPostLite(\'' + postdata + '\',\'\',\'' + responsediv + '\',\'\');">Delete</button><button onclick="event.preventDefault();hideDialogue();">cancel</button></p>';
  document.getElementById('dialogue-content').innerHTML = text;  
}

function uploadFile(path){
  var text = '<form action="" enctype="multipart/form-data" method="post" id="poster" onsubmit="event.preventDefault(); ajaxPost(this,\'adminarea\',\'\');hideDialogue();">';
  text = text + '<input type="hidden" name="command" value="write" /><input type="hidden" name="ajax" value="1" /><input type="hidden" name="file[type]" value="file" /><input type="hidden" name="file[path]" value="' + path + '" />';
  text = text + '<input type="file" name="upload[]" multiple />';
  text = text + '<input type="submit" name="submit" value="Submit" /></form>';
  document.getElementById('dialogue-content').innerHTML = text;
}

function makeDir(path){
  var text = '<label for="file[folder]">Folder Name</label><input id="foldername" name="file[folder]" /><br />';
  text =  text + '<button onclick="event.preventDefault();var folder=getElementById(\'foldername\').value;hideDialogue();ajaxPostLite(\'ajax=1&command=write&file[type]=folder&file[path]=' + path + '&file[folder]=\' + folder ,\'\',\'adminarea\',\'\');">Create</button><button onclick="event.preventDefault();hideDialogue();">cancel</button></p><br />';
  document.getElementById('dialogue-content').innerHTML = text;
}


/**
 * Pads characters to the left of a string if shorter than length
 * 
 * @param {string} str String that will be left-padded
 * @param {string} padString String to pad to the left of the string
 * @param {int} length The number of characters for the resulting string
 * @returns {string} the padded string
 */
function leftPad(str, padString, length) {
  while (str.length < length){
    str = padString + str;
  }
  return str;
}

/**
 * Activates a date-picker
 * 
 * @param {object} e Event handler from mouse-click
 * @param {string} responsediv ID of calendar div to fill with calendar info
 * @param {element} input Input element where the click originated
 * @param {string} min Earliest date permitted (yyyy-mm-dd)
 * @param {string} max Latest date permitted (yyyy-mm-dd)
 * @returns {void}
 */
function showCalendar(e,responsediv, input, min, max){
  var mx = e.clientX; 
  var my = e.clientY;
  var id = input.getAttribute('id');
  var startpoint = input.getAttribute('value');
  var datearray = startpoint.split("-");
  var year = datearray[0];
  var mo = datearray[1];
  document.getElementById(responsediv).setAttribute('class', 'calendar');
  getCalendar(mx,my, year, mo, startpoint, min, max, responsediv,id);
}

/**
 * Renders a calendar
 * 
 * @param {int} ax clientX position of mouse
 * @param {int} ay clientY position of mouse
 * @param {int} year Year for calendar
 * @param {int} mo Month for calendar (1=january)
 * @param {string} startpoint Currently selected date on input element (yyyy-mm-dd)
 * @param {string} min Earliest date permitted (yyyy-mm-dd)
 * @param {string} max Latest date permitted (yyyy-mm-dd)
 * @param {string} responsediv ID of calendar div to fill with calendar info
 * @param {string} id ID of input element that is being handled
 * @returns {void}
 */
function getCalendar(ax,ay, year, mo, startpoint, min, max, responsediv,id){
  // Load month names for humans
  var monthNames = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];

  // Determine previous and next year
  var year1 = year - 1;
  var year2 = year - 0 + 1;
  
  // Today's date
  var today = new Date();
  var ty = today.getFullYear();
  var tm = today.getMonth() + 1;
  var td = today.getDate();
  
  // Current selection's date
  var datearray = startpoint.split("-");
  var sy = datearray[0];
  var sm = datearray[1];
  var sd = datearray[2];
  
  // Create the beginning point for the calendar
  var y = year;
  var m = mo - 1;
  var d = 1;
  var date = new Date(y,m,d);
  var start = date.getDay();
  
  // Initialize variables for HTML output
  var css = 'othermonth';
  var dates = [];
  var nums = [];
  var classes = [];
  
  // Fill in previous months info if month does not begin on Sunday
  if (start > 0){
    var end = 42 - start;
    date.setDate(1 - start);
    var prev = date.getDate();
    for (var i=0;i<start;i++){
      date.setDate(i + prev);
      y = date.getFullYear();
      var yyyy = leftPad(y + '', '0', 4);
      m = date.getMonth() + 1;
      var mm = leftPad(m + '', '0', 2);
      d = date.getDate();
      nums[i] = d;
      var dd = leftPad(d + '', '0', 2);
      dates[i] = '' + yyyy + '-' + mm + '-' + dd + '';
      
      // Styling for certain days
      if(ty == y && tm == m && td == d){
        classes[i] = 'today';
      }
      else if(sy == y && sm == m && sd == d){
        classes[i] = 'startpoint';
      }
      else{
        classes[i] = css;
      }
    }
    // Load previous month's info for fetching a previous calendar
    var pyr = date.getFullYear();
    var pmo = date.getMonth() + 1;
    // set time back to correct month
    date.setDate(prev + start);
    var now = date.getMonth();
  }
  else{
    // Load previous month's info for fetching a previous calendar
    date.setDate(0)
    var pyr = date.getFullYear();
    var pmo = date.getMonth() + 1;
    var day = date.getDate() + 1;
    // set time back to correct month
    date.setDate(day);
    var now = date.getMonth();
    end = 42;
  }
  
  // Now work on this month
  var offset = 0;
  for (var i=0; i<=end; i++){
    date.setDate(1 + i - offset);
    y = date.getFullYear();
    var yyyy = leftPad(y + '', '0', 4);
    m = date.getMonth() + 1;
    var mm = leftPad(m + '', '0', 2);
    d = date.getDate();
    nums[i + start] = d;
    var dd = leftPad(d + '', '0', 2);
    dates[i + start] = '' + yyyy + '-' + mm + '-' + dd + '';
    if (m != now){
      // Overflowed the current month, now showing next month until calendar ends
      if (css == 'currentmonth'){
        css = 'othermonth';
      }
      else{
        css = 'currentmonth';
      }
      offset = i;
      now = m;
    }
    
    // Styling for certain days
    if(ty == y && tm == m && td == d){
      classes[i + start] = 'today';
    }
    else if(sy == y && sm == m && sd == d){
      classes[i + start] = 'startpoint';
    }
    else{
      classes[i + start] = css;
    }
  }
  
  // Load next month's info for fetching a next calendar
  var nyr = date.getFullYear();
  var nmo = date.getMonth() + 1;
  
  // Dump data into HTML to render the calendar
  var i =0;
  
  // Navigation
  var html = '<span class="bold">' + monthNames[mo - 1] + ' ' + year + '</span>';
  html = html + '<button class="right" style="font-weight:normal" onclick="removeCalendar(\'' + responsediv + '\')">x</button><br /><br />';
  html = html + '<div class="float"><button onclick="getCalendar(' + ax + ', ' + ay + ',' + year1 + ', ' + mo +  ', \'' + startpoint + '\', \'' + min + '\', \'' + max + '\' , \'' + responsediv + '\',\'' + id + '\');">&lt;&lt;</button>&nbsp;<button onclick="getCalendar(' + ax + ', ' + ay + ',' + pyr + ', ' + pmo +  ', \'' + startpoint + '\', \'' + min + '\', \'' + max + '\' , \'' + responsediv + '\',\'' + id + '\');">&lt;</button></div>';
  html = html + '<div  class="right"><button onclick="getCalendar(' + ax + ', ' + ay + ','  + nyr + ', ' + nmo +  ', \'' + startpoint + '\', \'' + min + '\', \'' + max + '\' , \'' + responsediv + '\',\'' + id + '\');">&gt;</button>&nbsp;<button onclick="getCalendar(' + ax + ', ' + ay + ','  + year2 + ', ' + mo +  ', \'' + startpoint + '\', \'' + min + '\', \'' + max + '\' , \'' + responsediv + '\',\'' + id + '\');">&gt;&gt;</button></div>';

  // Calendar Grid
  html = html + '<table><thead><th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th></thead><tbody>';
  for (var w=0;w<6;w++){
    html = html + '<tr>';
    for (var d=0;d<7;d++){
      var string = dates[i];
      var style = 'inactive ' + classes[i];
      var onclick = '';
      // Determine if the date is valid for input
      if (string >= min && string <= max){
        onclick = 'onclick="setDay(\'' + string + '\', \'' + id + '\', \'' + responsediv + '\');"';
        style = 'hand ' + classes[i];
      }
      html = html + '<td ' + onclick + ' class="' + style + '">' + nums[i] + '</td>';
      i ++;
    }
    html = html + '</tr>';
  }
  html = html + '</tbody></table>';
  
  //Inject calendar into calendar div and use mouse event to locate it on the page
  document.getElementById(responsediv).innerHTML = html;
  var my = ay - 0;
  var mx = ax + 20;
  document.getElementById(responsediv).setAttribute('style','z-index:9; position:fixed; top:' + my + 'px; left:' + mx + 'px;')
}

/**
 * Sets the date in an input from the datepicker
 * 
 * @param {string} date Date for input element (yyyy-mm-dd)
 * @param {string} id ID of input element to update
 * @param {string} responsediv ID of calendar that will be closed
 * @returns {void}
 */
function setDay(date, id, responsediv){
  document.getElementById(id).setAttribute('value',date);
  document.getElementById(id).focus();
  document.getElementById(id).blur();
  removeCalendar(responsediv);
}

/**
 * Removes calendar information and hides the container div
 * 
 * @param {string} responsediv ID of calendar that will be closed
 * @returns {void}
 */
function removeCalendar(responsediv){
  document.getElementById(responsediv).innerHTML = '';
  document.getElementById(responsediv).setAttribute('class', 'hide');
}

/**
 * Updates an end date so that it is not before than the start date
 * 
 * @param {element} input Begin date input element
 * @param {string} update ID of end date to update
 * @returns {void}
 */
function updateEndDate(input, update){
  var date1 = input.getAttribute('value');
  var date2 = document.getElementById(update).getAttribute('value');
  if (date1 > date2){
    document.getElementById(update).setAttribute('value', date1);
  }
}

function updateTime(input, update, date1, date2){
  var time1 = input.getAttribute('value');
  var time2 = document.getElementById(update).getAttribute('value');
  var da1 = document.getElementById(date1).getAttribute('value');
  var da2 = document.getElementById(date2).getAttribute('value');
  if (da1 == da2 && time1 > time2){
    document.getElementById(update + '___' + time2).selected=true;
  } 
}
