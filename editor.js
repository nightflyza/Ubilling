// Startup variables
var imageTag = false;
var mystored_selection = '';

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));

var is_win = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
var is_mac = (clientPC.indexOf("mac")!=-1);

// Replacement for arrayname.length property
function getarraysize(thearray) {
	for (i = 0; i < thearray.length; i++) {
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
			return i;
		}
	return thearray.length;
}

// Replacement for arrayname.push(value) not implemented in IE until version 5.5
// Appends element to the array
function arraypush(thearray,value) {
	thearray[ getarraysize(thearray) ] = value;
}

// Replacement for arrayname.pop() not implemented in IE until version 5.5
// Removes and returns the last element of an array
function arraypop(thearray) {
	thearraysize = getarraysize(thearray);
	retval = thearray[thearraysize - 1];
	delete thearray[thearraysize - 1];
	return retval;
}


function insert_text(textarea, text) {
	if (textarea.createTextRange && textarea.caretPos) {
		var caretPos = textarea.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? caretPos.text + text + ' ' : caretPos.text + text;
	} else {
		var selStart = textarea.selectionStart;
		var selEnd = textarea.selectionEnd;
		mozWrap(textarea, text, '')
		textarea.selectionStart = selStart + text.length;
		textarea.selectionEnd = selEnd + text.length;
	}
}

function checkselection() {
	var myselection = '';
	
	if ( window.getSelection ) {
		myselection = window.getSelection();
	} else if ( document.selection ) {
		myselection = document.selection.createRange().text;
	} else if ( document.getSelection ) {
		myselection = document.getSelection();
	}
	
	if ( myselection != '' && myselection != null ) {
		if ( myselection != mystored_selection ) {
			mystored_selection = (myselection.toString() != '') ? myselection.toString() : null;
		}
	} else {
		mystored_selection = null;
	}
}

function addquote(textarea) {
	if ( mystored_selection != '' && mystored_selection != null ) {
		if (textarea){
			insert_text(textarea, '[quote]' + mystored_selection + '[/quote]\n');
		}
	}
	return false;
}

function bbstyle(textarea, bbnumber, id) {
    donotinsert = new Array();
    if(!bbcode[id]) {
        bbcode[id] = new Array();
    }
    donotinsert[id] = false;
	theSelection = false;
	bblast[id] = 0;
	textarea.focus();

	if (bbnumber == -1) { // Close all open tags & default button names
		while (bbcode[id][0]) {
			butnumber = arraypop(bbcode[id]) - 1;
			textarea.value += bbtags[butnumber + 1];
		}
		imageTag = false; // All tags are closed including image tags :D
		textarea.focus();
		return;
	}

	if ((clientVer >= 4) && is_ie && is_win) {
		theSelection = document.selection.createRange().text; // Get text selection
		if (theSelection) {
			// Add tags around selection
			document.selection.createRange().text = bbtags[bbnumber] + theSelection + bbtags[bbnumber+1];
			textarea.focus();
			theSelection = '';
			return;
		}
	} else if (textarea.selectionEnd && (textarea.selectionEnd - textarea.selectionStart > 0)) {
		mozWrap(textarea, bbtags[bbnumber], bbtags[bbnumber+1]);
		textarea.focus();
		theSelection = '';
		return;
	}

	// Find last occurance of an open tag the same as the one just clicked
	for (i = 0; i < bbcode[id].length; i++) {
		if (bbcode[id][i] == bbnumber+1) {
			bblast[id] = i;
			donotinsert[id] = true;
		}
	}
	if (donotinsert[id]) {		// Close all open tags up to the one just clicked & default button names
		while (bbcode[id][bblast[id]]) {
		    butnumber = arraypop(bbcode[id]) - 1;
		    insert_text(textarea, bbtags[butnumber + 1]);
			imageTag = false;
		}
		textarea.focus();
		return;
	} else { // Open tags

		if (imageTag && (bbnumber != 10)) {		// Close image tag before adding another
			insert_text(textarea, bbtags[11]);

			lastValue = arraypop(bbcode[id]) - 1;	// Remove the close image tag from the list
			imageTag = false;
		}

		// Open tag
		insert_text(textarea, bbtags[bbnumber]);

		if ((bbnumber == 10) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
		arraypush(bbcode[id],bbnumber+1);
		textarea.focus();
		return;
	}

	storeCaret(textarea);
}

// From http://www.massless.org/mozedit/
function mozWrap(txtarea, open, close)
{
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	if (selEnd == 1 || selEnd == 2) 
		selEnd = selLength;

	var s1 = (txtarea.value).substring(0,selStart);
	var s2 = (txtarea.value).substring(selStart, selEnd)
	var s3 = (txtarea.value).substring(selEnd, selLength);
	txtarea.value = s1 + open + s2 + close + s3;
	return;
}

// Insert at Claret position. Code from
// http://www.faqts.com/knowledge_base/view.phtml/aid/1052/fid/130
function storeCaret(textEl) {
	if (textEl.createTextRange) {
		textEl.caretPos = document.selection.createRange().duplicate();
	}
}
