<?php
/**
 * WedgeDesk
 *
 * This file handles everything concerning posting, including displaying the display of ticket facia
 * around a post box, display of replies with insert-quote links, the postbox, attachments, errors etc.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

/**
 *	Entry point for displaying the post new ticket/edit ticket UI.
 *
 *	@since 1.0
*/

function template_post_navigation()
{
	global $context;

	// Back to the helpdesk.
	echo '
		<div class="pagesection">
			', template_button_strip(array($context['navigation']['back']), 'left'), '
		</div>';
}

// yes, this isn't strictly conventional Wedge style
function template_ticket_option($option)
{
	global $context, $txt;

	if (!empty($context['ticket_form'][$option]['can_change']))
	{
		echo '
								<select name="shd_' . $option . '">';
		foreach ($context['ticket_form'][$option]['options'] as $value => $caption)
		{
			echo '
									<option value="', $value, '"', ($value == $context['ticket_form'][$option]['setting'] ? ' selected="selected"' : ''), '>', $txt[$caption], '</option>';
		}
		echo '
								</select>';
	}
	else
		echo $txt[$context['ticket_form'][$option]['options'][$context['ticket_form'][$option]['setting']]];
}

function template_ticket_info()
{
	global $context, $txt, $settings, $options;

	echo '
			<form action="', $context['ticket_form']['form_action'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="', 'submitonce(this);weSaveEntities(\'postmodify\', [\'subject\', \'', $context['postbox']->id, '\'], \'field\');" enctype="multipart/form-data" style="margin: 0;">
			<we:cat>
				<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/ticket.png" > ', $context['ticket_form']['form_title'], '
			</we:cat>
			<div class="windowbg">
				<div class="content shd_ticket">
					<div class="shd_ticket_side_column">';

	// General ticket details
	echo '
					<div class="information shd_ticketdetails">
						<strong><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/details.png" class="shd_smallicon"> ', $txt['shd_ticket_details'], '</strong>
						<hr>
						<ul class="reset">';

	if (!empty($context['ticket_form']['display_id']))
		echo '
							<li><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/id.png" class="shd_smallicon"> ', $txt['shd_ticket_id'], ': ', $context['ticket_form']['display_id'], '</li>';

	if (!empty($context['ticket_form']['member']['link']))
		echo '
							<li><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/user.png" class="shd_smallicon"> ', $txt['shd_ticket_user'], ': ', $context['ticket_form']['member']['link'], '</li>';

	echo '
							<li>
								<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/urgency.png" class="shd_smallicon">
								', $txt['shd_ticket_urgency'], ': ', template_ticket_option('urgency'), '
							</li>';

	// New tickets aren't assigned - ever - but existing ones might be
	if (!empty($context['ticket_form']['ticket']))
		echo '
							<li><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/staff.png" class="shd_smallicon"> ', $txt['shd_ticket_assignedto'], ': ', !empty($context['ticket_form']['assigned']['link']) ? $context['ticket_form']['assigned']['link'] : '<span class="error">' . $txt['shd_unassigned'] . '</span>', '</li>';

	echo '
							<li><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/status.png" class="shd_smallicon"> ', $txt['shd_ticket_status'], ': ', $txt['shd_status_' . $context['ticket_form']['status']], '</li>';

	if (!empty($context['display_private']))
		echo '
							<li>
								<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/private.png" class="shd_smallicon">
								', $txt['shd_ticket_privacy'], ': ', template_ticket_option('private'), '
							</li>
						</ul>';

	// Display ticket poster avatar?
	if (!empty($settings['shd_display_avatar']) && empty($options['show_no_avatars']) && !empty($context['ticket_form']['member']['avatar']['image']))
		echo '
						<div class="shd_ticket_avatar">
							', shd_profile_link($context['ticket_form']['member']['avatar']['image'], $context['ticket_form']['member']['id']), '
						</div>';

	echo '
					</div>
				</div>';
}

function template_ticket_custom_fields()
{
	global $context, $txt;

	// No point showing the box if there's nothing to show in it
	if (empty($context['ticket_form']['custom_fields']))
		return;

	echo '
				<div class="information shd_customfields" id="shd_customfields"', empty($context['ticket_form']['dept']) ? ' style="display:none;"' : '', '>';

		// Loop through each custom field
		// See also template_ticket_subjectbox() for the department selector which affects these.
		foreach ($context['ticket_form']['custom_fields'][$context['ticket_form']['custom_fields_context']] as $field)
		{
			if (!$field['editable'])
				continue;

			$field['hidden'] = (!empty($context['ticket_form']['selecting_dept']) && !in_array($context['ticket_form']['custom_field_dept'], $field['depts']));
			if (empty($context['ticket_form']['dept']))
				$field['hidden'] = true;

			if (isset($field['new_value']))
				$field['value'] = $field['new_value'];
			elseif (!isset($field['value']))
				$field['value'] = $field['default_value'];

			if ($field['type'] == CFIELD_TYPE_LARGETEXT)
			{
				// Textareas are big and special and scary. They get their own template to cope with it.
				if ($field['value'] == $field['default_value'])
					$field['value'] = '';
				echo '
					<div id="field_', $field['id'], '_container"', $field['hidden'] ? ' style="display:none;"' : '', '>
						<dl class="settings">
							<dt id="field-' . $field['id'] . '" style="width:98%;">
								', !empty($field['icon']) ? '<img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/cf/' . $field['icon'] . '">' : '', '
								<strong>' . $field['name'] . ': </strong><br>
								<span class="smalltext">' . $field['desc'] . '</span><br>
								<textarea name="field-', $field['id'], '"', !empty($field['default_value']) ? ' rows="' . $field['default_value'][0] . '" cols="' . $field['default_value'][1] . '" ' : '', ' style="width:auto; height:auto;">', $field['value'], '</textarea>
							</dt>
						</dl>
						<hr>
					</div>';
			}
			else
			{
				echo '
					<div id="field_', $field['id'], '_container"', $field['hidden'] ? ' style="display:none;"' : '', '>
						<dl class="settings">
							<dt id="field-' . $field['id'] . '">
								', !empty($field['icon']) ? '<img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/cf/' . $field['icon'] . '">' : '', '
								<strong>' . $field['name'] . ': </strong><br>
								<span class="smalltext">' . $field['desc'] . '</span>
							</dt>';

				// Text
				if ($field['type'] == CFIELD_TYPE_TEXT)
				{
					echo '
							<dd><input type="text" name="field-', $field['id'], '" value="', $field['value'], '" class="input_text"></dd>';
				}
				// Integers only
				elseif ($field['type'] == CFIELD_TYPE_INT)
				{
					echo '
							<dd><input name="field-', $field['id'], '" value="', $field['value'], '" size="10" class="input_text"></dd>';
				}
				// Floating numbers
				elseif ($field['type'] == CFIELD_TYPE_FLOAT)
				{
					echo '
							<dd><input name="field-', $field['id'], '" value="', $field['value'], '" size="10" class="input_text"></dd>';
				}
				// Select boxes
				elseif ($field['type'] == CFIELD_TYPE_SELECT)
				{
					echo '
							<dd>
								<select name="field-', $field['id'], '">
									<option value="0"', $field['value'] == 0 ? ' selected="selected"' : '', !empty($field['is_required']) ? ' disabled="disabled"' : '', '>', $txt['shd_choose_one'], '&nbsp;</option>';

					foreach ($field['options'] as $key => $option)
					{
						if ($key == 'inactive' || in_array($key, $field['options']['inactive']))
							continue;

						echo '
									<option value="', $key, '"', $field['value'] == $key ? ' selected="selected"' : '', '>', $option, '&nbsp;</option>';
					}

					echo '
								</select>
							</dd>';
				}
				// Checkboxes!
				elseif ($field['type'] == CFIELD_TYPE_CHECKBOX)
				{
					echo '
							<dd><input name="field-', $field['id'], '" type="checkbox"', !empty($field['value']) ? ' checked="checked"' : '', ' class="input_check"></dd>';
				}
				// Magical multi-select!
				elseif ($field['type'] == CFIELD_TYPE_MULTI)
				{
					echo '
							<dd>';
					if (!empty($field['value']) && !is_array($field['value']))
						$field['value'] = explode(',', $field['value']);
					elseif (empty($field['value']))
						$field['value'] = array();

					foreach ($field['options'] as $key => $option)
					{
						if ($key == 'inactive')
							continue;

						// If the field is active, display it normally
						if (!in_array($key, $field['options']['inactive']))
							echo '
								<input name="field-', $field['id'], '-', $key, '" type="checkbox" value="', $key, '"', in_array($key, $field['value']) ? ' checked="checked"' : '', '> <span>', $option, '</span><br>';
						// If it's not required and inactive and present, display a hidden form item for it.
						elseif (empty($field['is_required']) && in_array($key, $field['options']['inactive']) && in_array($key, $field['value']))
							echo '
								<input type="hidden" name="field-', $field['id'], '-', $key, '" value="', $key, '">';
					}

					echo '
							</dd>';
				}
				// Last one, radio buttons
				elseif ($field['type'] == CFIELD_TYPE_RADIO)
				{
					echo '
							<dd>';
					if (empty($field['is_required']))
						echo '
								<input name="field-', $field['id'], '" type="radio" value="0"', $field['value'] == 0 ? ' checked="checked"' : '', ' class="input_radio"> <span>', $txt['shd_no_value'], '</span><br>';

					foreach ($field['options'] as $key => $option)
					{
						if ($key == 'inactive' || in_array($key, $field['options']['inactive']))
							continue;

						echo '
								<input name="field-', $field['id'], '" type="radio" value="', $key, '"', $field['value'] == $key ? ' checked="checked"' : '', '> <span>', $option, '</span><br>';
					}

					echo '
							</dd>';
				}
				// Default to a text input field
				else
					echo '
							<dd><input type="text" name="field-' . $field['id'] . '" value="' . $field['value'] . '" size="50"></dd>';

				echo '
						</dl>
						<hr>
					</div>';
			}
		}

	echo '
				</div>';
}

function template_ticket_posterrors()
{
	global $context, $txt;

	// Did anything go wrong?
	if (!isset($context['shd_errors']))
		$context['shd_errors'] = array();

	echo '
						<div class="errorbox" id="errors"', empty($context['shd_errors']) ? ' style="display:none;"' : '', '>
							<dl>
								<dt>
									<strong style="" id="error_serious">', $txt['shd_ticket_post_error'], ':</strong>
								</dt>
								<dt class="error" id="error_list">';

	foreach ($context['shd_errors'] as $error)
		echo '
									', $txt['error_' . $error], '<br>';

	echo '
								</dt>
							</dl>
						</div>';
}

function template_ticket_subjectbox()
{
	global $context, $txt;

	echo '
					<div class="shd_ticket_description">';

	template_ticket_posterrors();
	echo '
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/name.png" class="shd_smallicon"> <strong>', $txt['shd_ticket_subject'], ':</strong>
						<input type="text" name="subject" size="50" maxlength="100" class="input_text" value="', $context['ticket_form']['subject'], '" tabindex="', $context['tabindex']++, '">';

	if (!empty($context['ticket_form']['selecting_dept']) && !empty($context['postable_dept_list']))
	{
		echo '
						<br>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/departments.png" class="shd_smallicon"> <strong>', $txt['shd_ticket_dept'], '</strong>
						<select name="newdept" onchange="updateDeptCFs(this.value)">
							<option value="0">', $txt['shd_select_dept'], '</option>';
		foreach ($context['postable_dept_list'] as $id => $dept)
			echo '
							<option value="', $id, '"', $context['ticket_form']['dept'] == $id ? ' selected="selected"' : '', '>', $dept, '</option>';

		echo '
						</select>';

		if (!empty($context['ticket_form']['custom_fields'][$context['ticket_form']['custom_fields_context']]))
		{
			echo '
						<script type="text/javascript"><!-- // --><![CDATA[
						var fields = new Array();';
			foreach ($context['ticket_form']['custom_fields'][$context['ticket_form']['custom_fields_context']] as $field)
			{
				if (!$field['editable'])
					continue;
				echo '
						fields[', $field['id'], '] = [', implode(',', $field['depts']), '];';
			}
			echo '
						function updateDeptCFs(dept)
						{
							var displayed = 0;
							for (i in fields)
							{
								if (dept != 0 && in_array(dept, fields[i]))
								{
									document.getElementById("field_" + i + "_container").style.display = "";
									displayed++;
								}
								else
								{
									document.getElementById("field_" + i + "_container").style.display = "none";
								}
								document.getElementById("shd_customfields").style.display = (displayed == 0) ? "none" : "";
							}
						}
						// ]', ']></script>';
		}
	}

	// Are we dealing with proxy tickets?
	if (!empty($context['can_post_proxy']))
	{
		echo '
						<br>
						<input type="hidden" name="proxy" value="">
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/proxy.png" class="shd_smallicon"> <strong>', $txt['shd_ticket_proxy'], ':</strong>
						<input type="text" name="proxy_author" id="proxy_author" size="50" maxlength="100" class="input_text" value="', (empty($context['ticket_form']['proxy']) ? '' : $context['ticket_form']['proxy']), '" tabindex="', $context['tabindex']++, '">';
	}

	echo '
						<hr><br>
							';
}

function template_ticket_proxy_js()
{
	global $context;
	if (empty($context['can_post_proxy']))
		return;

	add_js_file('scripts/suggest.js');
	add_js('
	var oProxyAutoSuggest = new weAutoSuggest({
		', min_chars(), ',
		sControlId: \'proxy_author\',
		sPostName: \'proxy_author_form\',
		sURLMask: \'action=profile;u=%item_id%\',
		bItemList: false
	});');
}

function template_ticket_content()
{
	global $context, $txt;

	echo '
					<div class="shd_ticket_description">';

	template_ticket_posterrors();
	echo '
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/name.png" class="shd_smallicon"> <strong>', $context['ticket_form']['subject'], '</strong>
						<hr><br>
							', $context['ticket_form']['message'];

	if (!empty($context['ticket']['modified']))
	{
		echo '
						<div class="smalltext shd_modified" style="margin-top:20px;">
							&#171; <em>', $txt['last_edit'], ': ', $context['ticket']['modified']['time'], ' ', $txt['by'], ' ', $context['ticket']['modified']['link'], '</em> &#187;
						</div>';
	}
}

function template_ticket_meta()
{
	global $context;

	// Management/meta information
	echo '
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '">';

	if (!empty($context['ticket_form']['ticket']))
		echo '
						<input type="hidden" name="ticket" value="', $context['ticket_form']['ticket'], '">';

	if (!empty($context['ticket_form']['msg']))
		echo '
						<input type="hidden" name="msg" value="', $context['ticket_form']['msg'], '">';

	if (!empty($context['ticket_form']['num_replies']))
		echo '
						<input type="hidden" name="num_replies" value="', $context['ticket_form']['num_replies'], '">';

	if (!empty($context['ticket_form']['dept']) && empty($context['ticket_form']['selecting_dept']))
		echo '
						<input type="hidden" name="dept" value="', $context['ticket_form']['dept'], '">';
}

function template_ticket_shd_replyarea()
{
	global $context, $txt;
	echo '
			<we:title>
				<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/respond.png" >
				', !empty($context['ticket_form']['form_title']) ? $context['ticket_form']['form_title'] : $txt['shd_reply_ticket'], '
			</we:title>
			<div class="roundframe">
				<div class="content">';

		template_ticket_postbox();

		echo '
				</div>
			</div>
			<br>';
}

function template_ticket_postbox()
{
	global $settings, $context;

	// The postbox
	echo '
						<div id="shd_bbcbox"', ((empty($settings['shd_allow_ticket_bbc']) || !empty($context['shd_display'])) ? ' style="display:none;"' : ''), '></div>
						<div id="shd_smileybox"', ((empty($settings['shd_allow_ticket_smileys']) || !empty($context['shd_display'])) ? ' style="display:none;"' : ''), '></div>';

	$context['postbox']->outputEditor();

	// Custom fields
	template_ticket_custom_fields();

	// Additional ticket options (attachments, smileys, etc)
	template_ticket_additional_options();

	// Canned replies
	template_ticket_cannedreplies();

	echo '
						<br class="clear">';

	$context['postbox']->outputButtons();
}

function template_ticket_cannedreplies()
{
	global $context, $txt;

	if (empty($context['canned_replies']))
		return;

	echo '
					<div id="canned_replies" style="display:none;">
						<div style="font-weight:bold; padding: 0.5em;">', $txt['canned_replies'], '</div>
						<select id="canned_replies_select">
							<option value="0">', $txt['canned_replies_select'], '</option>';

	foreach ($context['canned_replies'] as $cat_id => $cat_details)
	{
		echo '
							<optgroup label="', $cat_details['name'], '">';

		foreach ($cat_details['replies'] as $reply_id => $reply_title)
			echo '
								<option value="', $reply_id, '">', $reply_title, '</option>';

		echo '
							</optgroup>';
	}

	echo '
						</select>
						<input type="button" value="', $txt['canned_replies_insert'], '" onclick="oCannedReplies.getReply();">
					</div>
					<script type="text/javascript"><!-- // --><![CDATA[
					var oCannedReplies = new CannedReply({
						iTicketId: ', $context['ticket_id'], ',
						sScriptUrl: we_script,
						sSessionVar: "', $context['session_var'], '",
						sSessionId: "', $context['session_id'], '"
					});
					// ]]></script>';
}

function template_ticket_footer()
{
	global $context, $txt;

	if (!empty($context['ticket_form']['modified']))
	{
		echo '
						<div class="smalltext shd_modified" style="margin-top:20px;">
							&#171; <em>', $txt['last_edit'], ': ', $context['ticket_form']['modified']['time'], ' ', $txt['by'], ' ', $context['ticket_form']['modified']['link'], '</em> &#187;
						</div>';
	}

	echo '
					</div>
				</div>
				<br class="clear">
			</div>
			<br>';
}

function template_preview()
{
	global $context, $txt;

	if (!empty($context['ticket_form']['preview']))
	{
		echo '
			<we:title>
				<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/preview.png" >
				', !empty($context['ticket_form']['preview']['title']) ? $context['ticket_form']['preview']['title'] : $txt['preview'], '
			</we:title>
			<div class="roundframe">
				<div class="content">
					', $context['ticket_form']['preview']['body'], '
				</div>
			</div>
			<br>';
	}
}

function template_ticket_additional_options()
{
	global $context, $txt, $settings;

	echo '
					<div class="information shd_reply_attachments" id="shd_attach_container"', !empty($context['shd_display']) ? ' style="display: none"' : '', '>
						<ul id="postOptions">';

	foreach ($context['ticket_form']['additional_opts'] as $key => $details)
	{
		if (!empty($details['show']))
			echo '
							<li><label for="', $key, '"><input type="checkbox" name="', $key, '" id="', $key, '"', (!empty($details['checked']) ? ' checked="checked"' : ''), ' value="1" class="input_check"> ', $details['text'], '</label></li>';
	}

	echo '
						</ul>
						<hr>';

	if (empty($context['current_attachments']) && empty($context['ticket_form']['do_attach']))
		return;

	if (!empty($context['current_attachments']))
	{
		echo '
						<dl id="postAttachment">
							<dt>
								', $txt['attached'], ':
							</dt>';

		$can_delete = false;
		foreach ($context['current_attachments'] as $attachment)
		{
			if (!empty($attachment['can_delete']))
				$can_delete = true;
			break;
		}

		if ($can_delete)
		{
			echo '
							<dd class="smalltext">
								<input type="hidden" name="attach_del[]" value="0">
								', $txt['uncheck_unwatchd_attach'], ':
							</dd>';
			foreach ($context['current_attachments'] as $attachment)
				echo '
							<dd class="smalltext">
								<label for="attachment_', $attachment['id'], '"><input type="checkbox" id="attachment_', $attachment['id'], '" name="attach_del[]" value="', $attachment['id'], '"', empty($attachment['unchecked']) ? ' checked="checked"' : '', ' class="input_check" onclick="javascript:oAttach.checkActive();"> ', $attachment['name'], '</label>
							</dd>';
		}
		else
		{
			foreach ($context['current_attachments'] as $attachment)
				echo '
							<dd class="smalltext">', $attachment['name'], '</dd>';
		}

		echo '
						</dl>';
	}

	if (!empty($context['ticket_form']['do_attach']))
	{
		// JS for our pretty widget
		echo '
						<dl id="postAttachment2">
							<dt>
								', $txt['attach'], ':
							</dt>
							<dd class="smalltext">
								<input type="file" size="60" name="attachment" id="shd_attach" class="input_file">
								<div id="shd_attachlist_container"></div>
							</dd>
							<dd class="smalltext">';

		// Show some useful information such as allowed extensions, maximum size and amount of attachments allowed.
		if (!empty($settings['attachmentCheckExtensions']))
			echo '
								', $txt['allowed_types'], ': ', $context['allowed_extensions'], '<br>';

		if (!empty($context['attachment_restrictions']))
			echo '
								', $txt['attach_restrictions'], ' ', implode(', ', $context['attachment_restrictions']), '<br>';

		echo '
							</dd>
						</dl>';
		template_singleton_email();

		echo '
					</div>';

		add_js('
	var oAttach = new shd_attach_select({
		file_item: "shd_attach",
		file_container: "shd_attachlist_container",
		max: ' . $context['ticket_form']['num_allowed_attachments'] . ',
		message_txt_delete: ' . JavaScriptEscape($txt['remove']));

		if (!empty($settings['attachmentExtensions']) && !empty($settings['attachmentCheckExtensions']))
		{
			$ext = explode(',', $settings['attachmentExtensions']);
			foreach ($ext as $k => $v)
				$ext[$k] = JavaScriptEscape($v);

			add_js(',
		message_ext_error: ' . JavaScriptEscape(str_replace('{attach_exts}', $context['allowed_extensions'], $txt['shd_cannot_attach_ext'])) . ',
		attachment_ext: [' . implode(',', $ext) . ']');
		}

		add_js('
	});');
	}
}

function template_singleton_email()
{
	global $context, $txt;

	if (!empty($context['can_ping']))
	{
		echo '
						<div id="shd_notifications_div" style="display:none;">
							<a href="#" onclick="getAjaxNotifications(); return false;">', $txt['shd_select_notifications'], '</a>';

		if (!empty($context['notification_ping_list']))
			echo '
							<input type="hidden" name="list" value="', $context['notification_ping_list'], '">';

		echo '
						</div>
						<script type="text/javascript"><!-- // --><![CDATA[
	document.getElementById("shd_notifications_div").style.display = "";

	function getAjaxNotifications()
	{
		ajax_indicator(true);
		getXMLDocument(we_prepareScriptUrl(we_script) + "action=helpdesk;sa=ajax;op=notify;ticket=', $context['ticket_id'], ';', $context['session_var'], '=', $context['session_id'], !empty($context['notification_ping_list']) ? ';list=' . $context['notification_ping_list'] : '', '", handleAjaxNotifications);
	}

	function handleAjaxNotifications(XMLDoc)
	{
		ajax_indicator(false);
		var notify = XMLDoc.getElementsByTagName("notify");
		if (notify.length == 1)
			document.getElementById("shd_notifications_div").innerHTML = notify[0].firstChild.nodeValue;
	}
						// ]]></script>';
	}
}

function template_ticket_replies_before()
{
	// The replies column
	echo '
					<div class="shd_ticket_rightcolumn floatleft" style="width: 100%;">';
}

function template_ticket_do_replies()
{
	global $context, $txt, $options, $settings, $reply_request;

	echo '
		<we:title>
			<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/replies.png" > ', $txt['shd_ticket_replies'], '
		</we:title>
		<div class="roundframe" id="replies">
			<div class="content">';

	if (!empty($reply_request))
	{
		while ($reply = $context['get_replies']())
		{
			echo '
				<div class="description shd_reply" id="reply', $reply['id'], '">
					<span class="floatleft shd_posterinfo">
						<strong class="shd_postername">
							', $reply['member']['link'], '
						</strong>
						<br>
						', $reply['member']['group'], '<br class="shd_groupmargin">';

			if (!empty($settings['shd_display_avatar']) && empty($options['show_no_avatars']) && !empty($reply['member']['avatar']['image']))
					echo '
						', shd_profile_link($reply['member']['avatar']['image'], $reply['member']['id']);

			if ($settings['shd_staff_badge'] == (!empty($reply['is_staff']) ? 'staffbadge' : 'userbadge') || $settings['shd_staff_badge'] == 'bothbadge')
				echo '<br>
						', $reply['member']['group_stars'];
			elseif (!empty($reply['is_staff']) && $settings['shd_staff_badge'] == 'nobadge')
				echo '<br>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'] . '/images/staff.png" class="shd_smallicon" title="', $txt['shd_ticket_staff'], '" alt="', $txt['shd_ticket_staff'], '">';

			echo '
					</span>
					<div class="shd_replyarea">
						<div class="smalltext">
							<span class="floatright shd_ticketlinks">';

			if ($context['can_quote'])
				echo '
										<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/quote.png" class="shd_smallicon"><a href="<URL>?action=helpdesk;sa=reply;ticket=', $context['ticket_id'], ';quote=', $reply['id'], ';', $context['session_var'], '=', $context['session_id'], '" onclick="return oQuickReply.quote(', $reply['id'], ', \'', $context['session_id'], '\', \'', $context['session_var'], '\', true);">', $txt['shd_ticket_quote_short'], '</a>';

			echo '
							</span>
							', sprintf($txt['shd_reply_written'], empty($reply['reply_num']) ? '' : '#' . $reply['reply_num'], $reply['time']), '
						</div>
						<hr class="clearfix">
						', $reply['body'], '
						<br><br>';

			if (!empty($reply['modified']))
			{
				echo '
						<div class="smalltext shd_modified" style="margin-top:20px;">
							&#171; <em>', $txt['last_edit'], ': ', $reply['modified']['time'], ' ', $txt['by'], ' ', $reply['modified']['link'], '</em> &#187;
						</div>';
			}

			if (!empty($context['ticket_attach']['reply'][$reply['id']]))
			{
				echo '
						<div class="smalltext">
							<strong>', $txt['shd_ticket_attachments'], '</strong><br>
							<ul class="shd_replyattachments">';

				foreach ($context['ticket_attach']['reply'][$reply['id']] as $attach)
					echo '
								<li>', $attach['link'], '</li>';

				echo '
							</ul>
						</div>';
			}

			echo '
					</div>';

			if (!empty($context['can_see_ip']) && !empty($reply['ip_address']))
				echo '
					<span class="floatright"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/ip.png" class="shd_smallicon"> ', $txt['shd_ticket_ip'], ': ', $reply['ip_address'], '</span>';

			echo '
					<br class="clear">
				</div>';
		}
	}

	echo '
			</div>
		</div>';
}

function template_ticket_replies_after()
{

	// Close the table
	echo '
		</div>
		<br class="clear">';
}

function template_ticket_pageend()
{
	// And finally, good night, sweet form
	echo '
			</form>';
}

/**
 *	Display the message thanking the user for posting.
 *
 *	@see shd_done_posting()
 *	@since 2.0
*/
function template_shd_thank_posting()
{
	global $context;

	echo '
	<div id="fatal_error">
		<we:cat>
			<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $context['page_icon'], '" class="shd_icon_minihead"> ', $context['page_title'], '
		</we:cat>
		<div class="windowbg wrc">
			<div class="padding">', $context['page_body'], '</div>
		</div>
	</div>
	<br class="clear">';
}

/**
 *	Displays a header that Javascript should be enabled while in the administration panel area of WedgeDesk.
 *
 *	The helpdesk is disabled to non admins while in maintenance mode, but this template is added to the template layers if the user is an admin and it's in maintenance mode.
 *	@since 2.0
*/
function template_shd_post_nojs()
{
	global $txt, $context;
	echo '<noscript><div class="errorbox"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/warning.png" class="shd_icon_minihead"> &nbsp;', $txt['shd_display_nojs'], '</div></noscript>';
}

?>