<?php
/**
 * WedgeDesk
 *
 * Displays WedgeDesk's administration for canned replies, front page, creation and editing.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

/**
 *	Display the front page of the WedgeDesk departments.
 *
 *	@since 2.0
*/
function template_shd_cannedreplies_home()
{
	global $context, $txt;

	echo '
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/cannedreplies.png" class="icon">
					', $txt['shd_admin_cannedreplies_home'], '
				</we:cat>
				<p class="description">
					', $txt['shd_admin_cannedreplies_homedesc'], '
				</p>';

	if (empty($context['canned_replies']))
	{
		echo '
				<br>
				<we:cat>
					', $txt['shd_admin_cannedreplies_nocats'], '
				</we:cat>';
	}
	else
	{
		foreach ($context['canned_replies'] as $cat_id => $cat)
		{
			echo '
				<br>
				<we:cat>
					', $cat['name'], '
					', !empty($cat['move_up']) ? ('<a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=movecat;cat=' . $cat_id . ';direction=up;' . $context['session_var'] . '=' . $context['session_id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/move_up.png" alt="' . $txt['shd_admin_move_up'] . '" title="' . $txt['shd_admin_move_up'] . '"></a>') : '', '
					', !empty($cat['move_down']) ? ('<a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=movecat;cat=' . $cat_id . ';direction=down;' . $context['session_var'] . '=' . $context['session_id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/move_down.png" alt="' . $txt['shd_admin_move_down'] . '" title="' . $txt['shd_admin_move_down'] . '"></a>') : '', '
					<a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=editcat;cat=' . $cat_id . ';', $context['session_var'], '=', $context['session_id'], '"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/edit.png" class="icon" alt="', $txt['shd_ticket_edit'],'" title="', $txt['shd_ticket_edit'], '"></a>
				</we:cat>
				<table class="shd_ticketlist" cellspacing="0" width="100%">
					<tr class="titlebg">
						<td width="30%" class="shd_nowrap">', $txt['shd_admin_cannedreplies_replyname'], '</td>
						<td width="25%">', $txt['shd_departments'], '</td>
						<td>', $txt['shd_admin_cannedreplies_isactive'], '</td>
						<td>', $txt['shd_admin_cannedreplies_visibleto'], '</td>
						<td colspan="3" width="1%" class="shd_nowrap">', $txt['shd_admin_custom_fields_move'], '</td>
						<td colspan="2" width="1%" class="shd_nowrap">', $txt['shd_actions'], '</td>
					</tr>';

			if (empty($cat['replies']))
			{
				$use_bg2 = false;
				echo '
					<tr class="windowbg2">
						<td colspan="9" class="centertext">', $txt['shd_admin_cannedreplies_emptycat'], '</td>
					</tr>';
			}
			else
			{
				$use_bg2 = true;
				foreach ($cat['replies'] as $reply)
				{
					echo '
					<tr class="windowbg', $use_bg2 ? '2' : '', '">
						<td>', $reply['title'], '</td>
						<td>', $reply['depts'], '</td>
						<td><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/cf_', $reply['active_string'], '.png" alt="', $txt['shd_admin_custom_fields_' . $reply['active_string']], '" title="', $txt['shd_admin_custom_fields_' . $reply['active_string']], '"></td>
						<td>
							', !empty($reply['vis_user']) ? '<img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/user.png" class="icon">' : '', '
							', !empty($reply['vis_staff']) ? '<img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/staff.png" class="icon">' : '', '
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/admin.png" class="icon">
						</td>
						<td>', !empty($reply['move_up']) ? ('<a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=movereply;reply=' . $reply['id_reply'] . ';direction=up;' . $context['session_var'] . '=' . $context['session_id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/move_up.png" alt="' . $txt['shd_admin_move_up'] . '" title="' . $txt['shd_admin_move_up'] . '"></a>') : '', '</td>
						<td>', !empty($reply['move_down']) ? ('<a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=movereply;reply=' . $reply['id_reply'] . ';direction=down;' . $context['session_var'] . '=' . $context['session_id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/move_down.png" alt="' . $txt['shd_admin_move_down'] . '" title="' . $txt['shd_admin_move_down'] . '"></a>') : '', '</td>
						<td>', $context['move_between_cats'] ? ('<a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=movereplycat;reply=' . $reply['id_reply'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/movedept.png" alt="' . $txt['shd_admin_cannedreplies_move_between_cat'] . '" title="' . $txt['shd_admin_cannedreplies_move_between_cat'] . '"></a>') : '', '</td>
						<td><a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=editreply;reply=' . $reply['id_reply'] . ';', $context['session_var'], '=', $context['session_id'], '"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/edit.png" class="icon" alt="', $txt['shd_ticket_edit'],'" title="', $txt['shd_ticket_edit'], '"></a></td>
						<td><a href="<URL>?action=admin;area=helpdesk_cannedreplies;sa=savereply;reply=' . $reply['id_reply'] . ';delete=yes;', $context['session_var'], '=', $context['session_id'], '" onclick="return confirm(' . JavaScriptEscape($txt['shd_admin_cannedreplies_deletereply_confirm']). ');"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/delete.png" class="icon" alt="', $txt['shd_ticket_delete'],'" title="', $txt['shd_ticket_delete'], '"></a></td>
					</tr>';
					$use_bg2 = !$use_bg2;
				}
			}

			echo '
					<tr class="windowbg', $use_bg2 ? '2' : '', '">
						<td colspan="9" class="righttext">
							<form action="<URL>?action=admin;area=helpdesk_cannedreplies;sa=createreply;cat=', $cat_id, '" method="post" accept-charset="UTF-8">
								<div class="floatright">
									<div class="additional_row" style="text-align: right;">
										<input type="submit" value="', $txt['shd_admin_cannedreplies_addreply'], '" class="new">
									</div>
								</div>
							</form>
						</td>
					</tr>
				</table>';
		}
	}

	echo '
				<form action="<URL>?action=admin;area=helpdesk_cannedreplies;sa=createcat" method="post" accept-charset="UTF-8">
					<div class="floatright">
						<div class="additional_row" style="text-align: right;">
							<input type="submit" value="', $txt['shd_admin_cannedreplies_createcat'], '" class="new">
						</div>
					</div>
				</form>';
}

function template_shd_edit_canned_category()
{
	global $context, $txt;

	echo '
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/cannedreplies.png" class="icon">
					', $txt['shd_admin_cannedreplies_home'], '
				</we:cat>
				<p class="description">
					', $txt['shd_admin_cannedreplies_homedesc'], '
				</p>
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/additional_information.png">
					', $context['page_title'], '
				</we:cat>
				<div class="roundframe">
					<form action="<URL>?action=admin;area=helpdesk_cannedreplies;sa=savecat" method="post">
						<div class="content">
							<dl class="settings">
								<dt><strong>', $txt['shd_admin_cannedreplies_categoryname'], '</strong></dt>
								<dd><input type="text" name="catname" id="catname" class="input_text" size="30" value="', $context['category_name'], '"></dd>
							</dl>
						</div>
						<input type="submit" value="', $context['page_title'], '" onclick="return submitThisOnce(this);" accesskey="s" class="button_submit">';

	if ($_REQUEST['cat'] != 'new')
		echo '
						<input type="submit" name="delete" value="', $txt['shd_admin_cannedreplies_deletecat'], '" onclick="return confirm(', JavaScriptEscape($txt['shd_admin_cannedreplies_delete_confirm']), ') && submitThisOnce(this);" class="button_submit">';

	echo '
						<input type="hidden" name="cat" value="', $_REQUEST['cat'], '">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '">
					</form>
				</div>';
}

function template_shd_edit_canned_reply()
{
	global $context, $txt;

	echo '
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/cannedreplies.png" class="icon">
					', $txt['shd_admin_cannedreplies_home'], '
				</we:cat>
				<p class="description">
					', $txt['shd_admin_cannedreplies_homedesc'], '
				</p>
				<form action="<URL>?action=admin;area=helpdesk_cannedreplies;sa=savereply" method="post" accept-charset="UTF-8" name="cannedreply" id="cannedreply" onsubmit="', 'submitonce(this);weSaveEntities(\'cannedreply\', [\'title\', \'', $context['postbox']->id, '\']);" enctype="multipart/form-data" style="margin: 0;">
					<we:cat>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/additional_information.png">
						', $context['page_title'], '
					</we:cat>
					<div class="roundframe">
						<div class="content">
							<dl class="permsettings cannedsettings">
								<dt><strong>', $txt['shd_admin_cannedreplies_replytitle'], '</strong></dt>
								<dd><input type="text" class="input_text" value="', $context['canned_reply']['title'], '" name="title"></dd>
								<dt><strong>', $txt['shd_admin_cannedreplies_content'], '</strong>
								<dd>
									<div id="bbcbox"></div>
									<div id="smileybox"></div>',
									$context['postbox']->outputEditor(), '
								</dd>
								<dt><strong>', $txt['shd_admin_cannedreplies_active'], '</strong></dt>
								<dd><input type="checkbox" name="active" class="input_check"', !empty($context['canned_reply']['active']) ? ' checked="checked"' : '', '>
								<dt><strong>', $txt['shd_admin_cannedreplies_selectvisible'], '</strong></dt>
								<dd>
									<input type="checkbox" name="vis_user" class="input_check"', !empty($context['canned_reply']['vis_user']) ? ' checked="checked"' : '', '> <img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/user.png" class="icon" alt="', $txt['shd_admin_custom_field_users'], '" title="', $txt['shd_admin_custom_field_users'], '">
									<input type="checkbox" name="vis_staff" class="input_check"', !empty($context['canned_reply']['vis_staff']) ? ' checked="checked"' : '', '> <img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/staff.png" class="icon" alt="', $txt['shd_admin_custom_field_staff'], '" title="', $txt['shd_admin_custom_field_staff'], '">
									<input type="checkbox" name="vis_admin" class="input_check" checked="checked" disabled="disabled"> <img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/admin.png" class="icon" alt="', $txt['shd_admin_custom_field_admins'], '" title="', $txt['shd_admin_custom_field_admins'], '">
								</dd>
							</dl>
						</div>
					</div>
					<br>
					<we:cat>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/departments.png">
						', $txt['shd_admin_cannedreplies_departments'], '
					</we:cat>
					<div class="roundframe">
						<div class="content">
							<dl class="permsettings cannedsettings">';

	foreach ($context['canned_reply']['depts_available'] as $dept_id => $dept_name)
	{
		echo '
								<dt><strong>', $dept_name, '</strong></dt>
								<dd><input type="checkbox" name="dept_', $dept_id, '"', in_array($dept_id, $context['canned_reply']['depts_selected']) ? ' checked="checked"' : '', ' class="input_check"></dd>';
	}

	echo '
							</dl>
						</div>
					</div>
					<br>';

	$context['postbox']->outputButtons();

	echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '">
					<input type="hidden" name="reply" value="', $context['canned_reply']['id'], '">
					<input type="hidden" name="cat" value="', $context['canned_reply']['cat'], '">
				</form>
				<br>';
}

function template_shd_move_reply_cat()
{
	global $context, $txt;

	echo '
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/cannedreplies.png" class="icon">
					', $txt['shd_admin_cannedreplies_home'], '
				</we:cat>
				<p class="description">
					', $txt['shd_admin_cannedreplies_homedesc'], '
				</p>
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/movedept.png">
					', $context['page_title'], '
				</we:cat>
				<div class="roundframe">
					<form action="<URL>?action=admin;area=helpdesk_cannedreplies;sa=movereplycat;part=2" method="post">
						<div class="content">
							<dl class="settings">
								<dt><strong>', $txt['shd_admin_cannedreplies_newcategory'], '</strong></dt>
								<dd>
									<select name="newcat">
										<option value="0">', $txt['shd_admin_cannedreplies_selectcat'], '</option>';

	foreach ($context['cannedreply_cats'] as $cat_id => $cat_name)
		echo '
										<option value="', $cat_id, '">', $cat_name, '</option>';

	echo '
									</select>
								</dd>
							</dl>
						</div>
						<input type="submit" value="', $txt['shd_admin_cannedreplies_movereply'], '" onclick="return submitThisOnce(this);" class="button_submit">';


	echo '
						<input type="hidden" name="reply" value="', $_REQUEST['reply'], '">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '">
					</form>
				</div>';
}

?>