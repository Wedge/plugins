/* Javascript for the main Helpdesk */

/* Attachment selector, based on http://the-stickman.com/web-development/javascript/upload-multiple-files-with-a-single-file-element/
* The code below is modified under the MIT licence, http://the-stickman.com/using-code-from-this-site-ie-licence/ not reproduced here for
* convenience of users using this software (as this is an active downloaded file) */
function shd_attach_select(oOptions)
{
	shd_attach_select.prototype.opts = oOptions;
	shd_attach_select.prototype.count = 0;
	shd_attach_select.prototype.id = 0;
	shd_attach_select.prototype.max = (oOptions.max) ? oOptions.max : -1;
	shd_attach_select.prototype.addElement(document.getElementById(shd_attach_select.prototype.opts.file_item));
}

shd_attach_select.prototype.addElement = function (element)
{
	// Make sure it's a file input element, ignore it if not
	if (element.tagName == 'INPUT' && element.type == 'file')
	{
		element.name = 'file_' + this.id++;
		element.multi_selector = this;
		element.onchange = function()
		{
			if (element.value == '')
				return;

			// Check if it's a valid extension (if we're checking such things)
			if (!shd_attach_select.prototype.checkExtension(element.value))
			{
				alert(shd_attach_select.prototype.opts.message_ext_error_final);
				element.value = '';
				return;
			}

			var new_element = document.createElement('input');
			new_element.type = 'file';
			new_element.className = 'input_file';
			new_element.setAttribute('size', '60');

			// Add new element, update everything
			this.parentNode.insertBefore(new_element, this);
			this.multi_selector.addElement(new_element);
			this.multi_selector.addListRow(this);

			// Hide this: we can't use display:none because Safari doesn't like it
			this.style.position = 'absolute';
			this.style.left = '-1000px';
		};

		this.count++;
		shd_attach_select.prototype.current_element = element;
		this.checkActive();
	}
};

shd_attach_select.prototype.checkExtension = function (filename)
{
	if (!shd_attach_select.prototype.opts.attachment_ext)
		return true; // we're not checking

	if (!filename || filename.length == 0)
	{
		shd_attach_select.prototype.opts.message_ext_error_final = shd_attach_select.prototype.opts.message_ext_error.replace(' ({ext})', '');
		return false; // pfft, didn't specify anything
	}

	var dot = filename.lastIndexOf(".");
	if (dot == -1)
	{
		shd_attach_select.prototype.opts.message_ext_error_final = shd_attach_select.prototype.opts.message_ext_error.replace(' ({ext})', '');
		return false; // no extension
	}

	var ext = (filename.substr(dot + 1, filename.length)).toLowerCase();
	var arr = shd_attach_select.prototype.opts.attachment_ext;
	var func = Array.prototype.indexOf ?
		function(arr, obj) { return arr.indexOf(obj) !== -1; } :
		function(arr, obj) {
			for (var i = -1, j = arr.length; ++i < j;)
				if (arr[i] === obj) return true;
			return false;
	};
	var value = func(arr, ext);
	if (!value)
		shd_attach_select.prototype.opts.message_ext_error_final = shd_attach_select.prototype.opts.message_ext_error.replace('{ext}', ext);

	return value;
};

shd_attach_select.prototype.addListRow = function (element)
{
	var new_row = document.createElement('div');
	var new_row_button = document.createElement('input');
	new_row_button.type = 'button';
	new_row_button.value = this.opts.message_txt_delete;
	new_row_button.className = 'button_submit';
	new_row.element = element;

	new_row_button.onclick = function ()
	{
		// Remove element from form
		this.parentNode.element.parentNode.removeChild(this.parentNode.element);
		this.parentNode.parentNode.removeChild(this.parentNode);
		this.parentNode.element.multi_selector.count--;
		shd_attach_select.prototype.checkActive();
		return false;
	};

	new_row.innerHTML = element.value + '&nbsp; &nbsp;';
	new_row.appendChild(new_row_button);
	document.getElementById(this.opts.file_container).appendChild(new_row);
};

shd_attach_select.prototype.checkActive = function()
{
	var elements = document.getElementsByTagName('input');
	var session_attach = 0;
	for (i in elements)
	{
		if (elements[i] && elements[i].type == 'checkbox' && elements[i].name == 'attach_del[]' && elements[i].checked == true)
			session_attach++;
	}

	var flag = !(shd_attach_select.prototype.max == -1 || (this.max >= (session_attach + shd_attach_select.prototype.count)));
	shd_attach_select.prototype.current_element.disabled = flag;
};

/* Quick reply stuff */

function QuickReply(oOptions)
{
	this.opt = oOptions;
	this.bCollapsed = this.opt.bDefaultCollapsed;
}

// When a user presses quote, put it in the quick reply box (if expanded).
QuickReply.prototype.quote = function (iMessageId, sSessionId, sSessionVar, bTemplateUpgraded)
{
	// Add backwards compatibility with old themes.
	if (sSessionVar == true)
	{
		bTemplateUpgraded = true;
		sSessionVar = 'sesc';
	}

	if (this.bCollapsed)
	{
		// This is for compatibility.
		if (bTemplateUpgraded)
			return true;
		else
		{
			location = we_prepareScriptUrl(this.opt.sScriptUrl) + 'action=helpdesk;sa=reply;quote=' + iMessageId + ';ticket=' + this.opt.iTicketId + '.' + this.opt.iStart + ';' + sSessionVar + '=' + sSessionId;
			return false;
		}
	}
	else
	{
		// Doing it the XMLhttp way?
		if (window.XMLHttpRequest)
		{
			ajax_indicator(true);
			getXMLDocument(we_prepareScriptUrl(this.opt.sScriptUrl) + 'action=helpdesk;sa=ajax;op=quote;quote=' + iMessageId + ';' + sSessionVar + '=' + sSessionId + ';xml' + ';mode=' + (oEditorHandle_shd_message.bRichTextEnabled ? 1 : 0), this.onQuoteReceived);
		}

		// Move the view to the quick reply box.
		if (navigator.appName == 'Microsoft Internet Explorer')
			window.location.hash = this.opt.sJumpAnchor;
		else
			window.location.hash = '#' + this.opt.sJumpAnchor;

		return false;
	}
};

// This is the callback function used after the XMLhttp request.
QuickReply.prototype.onQuoteReceived = function (oXMLDoc)
{
	var sQuoteText = '';

	for (var i = 0; i < oXMLDoc.getElementsByTagName('quote')[0].childNodes.length; i++)
		sQuoteText += oXMLDoc.getElementsByTagName('quote')[0].childNodes[i].nodeValue;

	oEditorHandle_shd_message.insertText(sQuoteText, false, true);

	ajax_indicator(false);
};

function CannedReply(oOptions)
{
	this.opt = oOptions;
	document.getElementById("canned_replies").style.display = "";
}

CannedReply.prototype.getReply = function ()
{
	var iReplyId = document.getElementById('canned_replies_select').value;
	if (!iReplyId || parseInt(iReplyId, 10) < 1)
		return false;

	// Doing it the XMLhttp way?
	if (window.XMLHttpRequest)
	{
		ajax_indicator(true);
		getXMLDocument(we_prepareScriptUrl(this.opt.sScriptUrl) + 'action=helpdesk;sa=ajax;op=canned;ticket=' + this.opt.iTicketId + ';reply=' + iReplyId + ';' + this.opt.sSessionVar + '=' + this.opt.sSessionId + ';xml' + ';mode=' + (oEditorHandle_shd_message.bRichTextEnabled ? 1 : 0), this.onReplyReceived);
	}

	return false;
};

// This is the callback function used after the XMLhttp request.
CannedReply.prototype.onReplyReceived = function (oXMLDoc)
{
	var sQuoteText = '';

	for (var i = 0; i < oXMLDoc.getElementsByTagName('quote')[0].childNodes.length; i++)
		sQuoteText += oXMLDoc.getElementsByTagName('quote')[0].childNodes[i].nodeValue;

	oEditorHandle_shd_message.insertText(sQuoteText, false, true);

	ajax_indicator(false);
};

// The quick jump function
function shd_quickTicketJump(id_ticket)
{
	location = we_prepareScriptUrl(we_script) + '?action=helpdesk;sa=ticket;ticket=' + id_ticket;
	return false;
}

function AjaxSelector(oOptions)
{
	this.opt = oOptions;
	this.bCollapsed = true;

	this.sQueryUrl = we_prepareScriptUrl() + this.opt.sQueryUrl + ';' + we_sessvar + '=' + we_sessid;
	this.sPostUrl = we_prepareScriptUrl() + this.opt.sPostUrl + ';' + we_sessvar + '=' + we_sessid;

	// Create some containers.
	$('#' + this.opt.sListItem + ' dl').append('<dd><img src="' + this.opt.sImagesUrl + "/" + this.opt.sImageCollapsed + '" id="' + this.opt.sSelf + '_button" class="shd_assign_button" onclick="' + this.opt.sSelf + '.click();"></dd>');
	$('#' + this.opt.sListItem).append('<ul id="' + this.opt.sListItem + '_list" class="shd_selector_list"></ul>');
	$('#' + this.opt.sListItem + '_list').hide();
}

AjaxSelector.prototype.click = function ()
{
	if (this.bCollapsed)
		this.expand();
	else
		this.collapse();
};

AjaxSelector.prototype.expand = function ()
{
	this.bCollapsed = false;
	$('#' + this.opt.sSelf + '_button').attr('src', this.opt.sImagesUrl + "/" + this.opt.sImageExpanded);

	// Fetch the list of items
	ajax_indicator(true);
	getXMLDocument.call(this, this.sQueryUrl, this.expand_callback);
};

AjaxSelector.prototype.expand_callback = function (XMLDoc)
{
	// Receive the list of assignees
	ajax_indicator(false);

	var errors = XMLDoc.getElementsByTagName('error');
	if (errors.length > 0)
	{
		alert(errors[0].childNodes[0].nodeValue);
		this.collapse();
	}
	else
	{
		var assign_list = $('#' + this.opt.sListItem + '_list');
		assign_list.show();

		var elements = XMLDoc.getElementsByTagName('item');
		// We could, in all honesty, sit and build the content normally with jQuery.
		// But really, this is quicker, not just for us but for the browser too.
		var newhtml = '';
		for (var i = 0, n = elements.length; i < n; i++)
		{
			newhtml += '<li class="shd_selector" onclick="' + this.opt.sSelf + '.itemsel(' + elements[i].getAttribute('id') + ');">';
			if (elements[i].getAttribute('img'))
				newhtml += '<img src="' + this.opt.sImagesUrl + '/' + elements[i].getAttribute('img') + '.png" class="shd_smallicon"> ';
			newhtml += elements[i].childNodes[0].nodeValue + '</li>';
		}

		assign_list.html(newhtml);
	}
};

AjaxSelector.prototype.itemsel = function (id)
{
	// Click handler for the item list, to pick the item itself
	ajax_indicator(true);
	sendXMLDocument.call(this, this.sPostUrl, 'newval=' + id, this.itemsel_callback);
};

AjaxSelector.prototype.itemsel_callback = function(XMLDoc)
{
	// Click handler callback for assignment, to handle once the request has been made
	ajax_indicator(false);
	this.collapse();
	var errors = XMLDoc.getElementsByTagName('error');
	if (errors.length > 0)
		alert(errors[0].childNodes[0].nodeValue);
	else
	{
		var elements = XMLDoc.getElementsByTagName('item');
		$('#' + this.opt.sListItem + ' dl dd').first().html(elements[0].childNodes[0].nodeValue);
	}
	this.collapse();
};

AjaxSelector.prototype.collapse = function ()
{
	this.bCollapsed = true;
	var assign_list = $('#' + this.opt.sListItem + '_list');
	assign_list.empty().hide();

	$('#' + this.opt.sSelf + '_button').attr('src', this.opt.sImagesUrl + "/" + this.opt.sImageCollapsed);
};
