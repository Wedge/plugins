<?php
/**
 * WedgeDesk
 *
 * Displays WedgeDesk's administration for departments - front page, listing the departments, plus the create/edit dialogs.
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
function template_shd_departments_home()
{
	global $context, $txt;

	echo '
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/departments.png" class="icon">
					', $txt['shd_admin_departments_home'], '
				</we:cat>
				<p class="description">
					', $txt['shd_admin_departments_homedesc'], '
				</p>
				<br>
				<table class="shd_ticketlist" cellspacing="0" width="100%">
					<tr class="titlebg">
						<td width="1%"></td>
						<td width="25%" class="shd_nowrap">
							', $txt['shd_department_name'], '
						</td>
						<td>', $txt['shd_dept_boardindex'], '</td>
						<td width="40%" class="shd_nowrap">
							', $txt['shd_roles_in_dept'], '
						</td>
						<td colspan="3" width="1%" class="shd_nowrap">', $txt['shd_actions'], '</td>
					</tr>';

	$use_bg2 = true;
	foreach ($context['shd_departments'] as $department)
	{
		echo '
					<tr class="windowbg', $use_bg2 ? '2' : '', '">
						<td></td>
						<td>
							', $department['dept_name'], '
							<div class="smalltext">', $department['description'], '</div>
						</td>
						<td>';

		if (!empty($department['cat_name']))
			echo '
							', $txt['shd_dept_inside_category'], ': ', $department['cat_name'], '
							<div class="smalltext">', empty($department['before_after']) ? $txt['shd_dept_cat_before_boards'] : $txt['shd_dept_cat_after_boards'], '</div>';
		else
			echo '
							', $txt['shd_dept_no_boardindex'];

		echo '
						</td>
						<td>';

		if (!empty($department['roles']))
		{
			$first = true;
			foreach ($department['roles'] as $role)
			{
				if (!$first)
					echo ', ';
				else
					$first = false;

				echo '
							<span class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $context['shd_permissions']['roles'][$role['template']]['icon'], '" class="icon">
							<a href="<URL>?action=admin;area=helpdesk_permissions;sa=editrole;role=', $role['id_role'], '">', $role['role_name'], '</a></span>';
			}
		}
		else
			echo '
							', $txt['shd_no_roles_in_dept'];

		echo '
						</td>
						<td>', empty($department['is_first']) ? ('<a href="<URL>?action=admin;area=helpdesk_depts;sa=move;dept=' . $department['id_dept'] . ';direction=up;' . $context['session_var'] . '=' . $context['session_id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/move_up.png" alt="' . $txt['shd_admin_move_up'] . '" title="' . $txt['shd_admin_move_up'] . '"></a>') : '', '</td>
						<td>', empty($department['is_last']) ? ('<a href="<URL>?action=admin;area=helpdesk_depts;sa=move;dept=' . $department['id_dept'] . ';direction=down;' . $context['session_var'] . '=' . $context['session_id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/move_down.png" alt="' . $txt['shd_admin_move_down'] . '" title="' . $txt['shd_admin_move_down'] . '"></a>') : '', '</td>
						<td><a href="<URL>?action=admin;area=helpdesk_depts;sa=editdept;dept=', $department['id_dept'], '"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/edit.png" class="icon" alt="', $txt['shd_edit_dept'],'" title="', $txt['shd_edit_dept'], '"></a></td>';

		echo '
					</tr>';
		$use_bg2 = !$use_bg2;
	}

	echo '
				</table>
				<form action="<URL>?action=admin;area=helpdesk_depts;sa=createdept" method="post" accept-charset="UTF-8">
					<div class="floatright">
						<div class="additional_row" style="text-align: right;">
							<input type="submit" value="', $txt['shd_create_dept'], '" class="new">
						</div>
					</div>
				</form>';
}

function template_shd_create_dept()
{
	global $context, $txt;

	echo '
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/departments.png" class="icon">
					', $txt['shd_admin_departments_home'], '
				</we:cat>
				<p class="description">
					', $txt['shd_admin_departments_homedesc'], '
				</p>
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/position.png">
					', $txt['shd_create_dept'], '
				</we:cat>
				<div class="roundframe">
					<form action="<URL>?action=admin;area=helpdesk_depts;sa=createdept;part=2" method="post">
						<div class="content">
							<dl class="settings">
								<dt><strong>', $txt['shd_new_dept_name'], '</strong></dt>
								<dd><input type="text" name="dept_name" id="dept_name" value="" class="input_text" size="30"></dd>
								<dt><strong>', $txt['shd_dept_description'], '</strong></dt>
								<dd><textarea name="dept_desc" rows="3" cols="35" style="width: 99%"></textarea></dd>
								<dt><strong>', $txt['shd_dept_boardindex_cat'], '</strong></dt>
								<dd>
									<select name="dept_cat" id="dept_cat" onchange="document.getElementById(\'dept_beforeafter\').disabled = (this.value == 0);">';
	foreach ($context['shd_cat_list'] as $id_cat => $cat_name)
		echo '
										<option value="', $id_cat, '">', $cat_name, '</option>';

	echo '
									</select>
								</dd>
								<dt><strong>', $txt['shd_boardindex_cat_where'], '</strong></dt>
								<dd>
									<select name="dept_beforeafter" id="dept_beforeafter" disabled="disabled">
										<option value="0">', $txt['shd_boardindex_cat_before'], '</option>
										<option value="1">', $txt['shd_boardindex_cat_after'], '</option>
									</select>
								</dd>
							</dl>
						</div>
						<input type="submit" value="', $txt['shd_create_dept'], '" onclick="return submitThisOnce(this);" accesskey="s" class="submit">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '">
					</form>
				</div>';
}

function template_shd_edit_dept()
{
	global $context, $txt;

	echo '
				<form action="<URL>?action=admin;area=helpdesk_depts;sa=savedept" method="post">
					<we:cat>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/departments.png" class="icon">
						', $txt['shd_admin_departments_home'], '
					</we:cat>
					<p class="description">
						', $txt['shd_admin_departments_homedesc'], '
					</p>
					<we:cat>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/position.png">
						', $txt['shd_edit_dept'], '
					</we:cat>

					<div class="roundframe">
						<div class="content">
							<dl class="settings">
								<dt><strong>', $txt['shd_department_name'], '</strong></dt>
								<dd><input type="text" name="dept_name" id="dept_name" value="', $context['shd_dept']['dept_name'], '" class="input_text" size="30"></dd>
								<dt><strong>', $txt['shd_dept_description'], '</strong></dt>
								<dd><textarea name="dept_desc" rows="3" cols="35" style="width: 99%">', $context['shd_dept']['description'], '</textarea></dd>
								<dt><strong>', $txt['shd_dept_boardindex_cat'], '</strong></dt>
								<dd>
									<select name="dept_cat" id="dept_cat" onchange="document.getElementById(\'dept_beforeafter\').disabled = (this.value == 0);">';
	foreach ($context['shd_cat_list'] as $id_cat => $cat_name)
		echo '
										<option value="', $id_cat, '"', $context['shd_dept']['board_cat'] == $id_cat ? ' selected="selected"' : '', '>', $cat_name, '</option>';

	echo '
									</select>
								</dd>
								<dt><strong>', $txt['shd_boardindex_cat_where'], '</strong></dt>
								<dd>
									<select name="dept_beforeafter" id="dept_beforeafter"', $context['shd_dept']['board_cat'] == 0 ? ' disabled="disabled"' : '', '>
										<option value="0"', $context['shd_dept']['before_after'] == 0 ? ' selected="selected"' : '', '>', $txt['shd_boardindex_cat_before'], '</option>
										<option value="1"', $context['shd_dept']['before_after'] == 1 ? ' selected="selected"' : '', '>', $txt['shd_boardindex_cat_after'], '</option>
									</select>
								</dd>
								<dt><strong>', $txt['shd_dept_theme'], '</strong></dt>
								<dd>';

	if (empty($context['dept_theme_list']) || count($context['dept_theme_list']) == 1)
	{
		echo '
									<input type="hidden" name="dept_theme" value="0">', $txt['shd_dept_theme_use_default'];
	}
	else
	{
		echo '
									<select name="dept_theme">';

		foreach ($context['dept_theme_list'] as $id => $name)
			echo '
										<option value="', $id, '"', $context['shd_dept']['dept_theme'] == $id ? ' selected="selected"' : '', '>', $name, '</option>';

		echo '
									</select>';
	}

	echo '
								</dd>
								<dt>
									<strong>', $txt['shd_dept_autoclose_days'], '</strong>
									<div class="smalltext">', $txt['shd_dept_autoclose_days_note'], '</div>
								</dt>
								<dd>
									<input type="text" name="autoclose_days" id="autoclose_days" value="', $context['shd_dept']['autoclose_days'], '" class="input_text" size="5"></dd>
								</dd>
							</dl>
						</div>
					</div>
					<br>
					<we:cat>
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/roles.png">
							', $txt['shd_roles_in_dept'], '
					</we:cat>
					<p class="description">
						', $txt['shd_roles_in_dept_desc'], '
					</p>
					<table class="shd_ticketlist" cellspacing="0" width="100%">
						<tr class="titlebg">
							<td width="50%">', $txt['shd_role'], '</td>
							<td>', $txt['shd_assign_dept'], '</td>
						</tr>';

	$use_bg2 = true;
	if (!empty($context['shd_roles']))
	{
		foreach ($context['shd_roles'] as $id_role => $role)
		{
			echo '
						<tr class="', ($use_bg2 ? 'windowbg2' : 'windowbg'), '">
							<td><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $context['shd_permissions']['roles'][$role['template']]['icon'], '"> <a href="<URL>?action=admin;area=helpdesk_permissions;sa=editrole;role=', $role['id_role'], '">', $role['role_name'], '</a></td>
							<td><input type="checkbox" class="input_check" name="role', $id_role, '"', !empty($role['in_dept']) ? ' checked="checked"' : '', '></td>
						</tr>';

			$use_bg2 = !$use_bg2;
		}
	}
	else
		echo '
						<tr class="windowbg2">
							<td colspan="2">', $txt['shd_no_defined_roles'], '</td>
						</tr>';

	echo '
					</table>
					<br>

					<div class="floatleft">
						<input type="submit" value="', $txt['shd_edit_dept'], '" onclick="return submitThisOnce(this);" accesskey="s" class="submit">
						<input type="submit" value="', $txt['shd_delete_dept'], '" onclick="return confirm(' . JavaScriptEscape($txt['shd_delete_dept_confirm']) . ');" name="delete" class="delete">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<input type="hidden" name="dept" value="', $context['shd_dept']['id_dept'], '">
					</div>
				</form>
				<br class="clear">';
}

?>