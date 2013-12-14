<?php
/**
 * WedgeDesk
 *
 * Displays WedgeDesk's administration panel, options pages, action log and the get-support page.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

/**
 *	Display the main information center for the administration panel.
 *
 *	This function handles output of data populated by {@link shd_admin_info()}:
 *	- upgraded SD version advisory
 *	- latest news from WedgeDesk.com
 *	- basic version check
 *	- count of open/closed/recycled tickets in the helpdesk in total
 *	- list of current helpdesk staff
 *	- credits
 *
 *	@see shd_admin_info()
 *	@since 1.0
*/
function template_shd_admin()
{
	global $context, $txt, $settings;

	// Make our admin feel welcome
	echo '
	<div id="admincenter">';

	// Update?
	echo '
			<div id="sd_update_section" class="tborder" style="display: none;"></div>';

	echo '
		<div id="admin_main_section">';

	// Display the "live news" from WedgeDesk's site
	echo '
			<div id="sd_live_news" class="floatleft">
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/live.png">
					', $txt['shd_live_from'], '
					<span class="righttext"><a href="<URL>?action=helpadmin;help=shd_admin_help_live" onclick="return reqWin(this);" class="help"></a></span>
				</we:cat>
				<div class="windowbg wrc">
					<div class="content">
						<div id="sdAnnouncements">', $txt['shd_no_connect'], '</div>
					</div>
				</div>
			</div>';

	// Show the user version information from their server.
	echo '
			<div id="sd_supportVersionsTable" class="floatright">
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/modification.png">
					', $txt['shd_mod_information'], '
					<span class="righttext"><a href="<URL>?action=helpadmin;help=shd_admin_help_modification" onclick="return reqWin(this);" class="help"></a></span>
				</we:cat>
				<div class="windowbg wrc">
					<div class="content">
						<div id="sd_version_details">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/versions.png" class="shd_icon_minihead"> <strong>', $txt['support_versions'], ':</strong><br>
							', $txt['shd_your_version'], ':
							<em id="yourVersion" class="shd_nowrap">', SHD_VERSION, '</em><br>
							', $txt['shd_current_version'], ':
							<em id="sdVersion" class="shd_nowrap">??</em><br><br>
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/ticket.png" class="shd_icon_minihead"> <strong>', $txt['shd_ticket_information'], ':</strong><br>
							', $txt['shd_total_tickets'], ':
							<em id="totalTickets" class="shd_nowrap">
								<a href="javascript: shd_ticket_total_information();" >', $context['total_tickets'], '</a>
							</em>
							<div id="shd_ticket_total_information" style="display: none;">
								&nbsp;&nbsp;&nbsp;', $txt['shd_open_tickets'], ': <em>', $context['open_tickets'], '</em><br>
								&nbsp;&nbsp;&nbsp;', $txt['shd_closed_tickets'], ': <em>', $context['closed_tickets'], '</em><br>
								&nbsp;&nbsp;&nbsp;', $txt['shd_recycled_tickets'], ': <em>', $context['recycled_tickets'], '</em><br>
							</div>
							<br>';

	// Display all the members who can manage the helpdesk.
	// NOTE: This is currently (15/1/10) uncapped, meaning it's just the full list direct from WedgeDesk-Admin.php.
	// That gets the data. Up to here how it should be displayed.
	echo '
							<br>
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/staff.png" class="shd_icon_minihead"> <strong>', $txt['shd_staff_list'], ':</strong>
							', implode(', ', $context['staff']);

	echo '
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="shd_credits_break">&nbsp;</div>';


	echo '
		<we:title>
			<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/credits.png">
			', $txt['shd_credits'], '
			<span class="righttext"><a href="<URL>?action=helpadmin;help=shd_admin_help_credits" onclick="return reqWin(this);" class="help"></a></span>
		</we:title>';

	foreach ($context['shd_credits'] as $section)
	{
		if (isset($section['pretext']))
			echo '
		<div class="windowbg wrc intro">
			', $section['pretext'], '
		</div>';

		echo '
		<div style="width: 49%; float: left; margin: 0 .5%">';

		echo '
			<we:block class="windowbg2 wrc" header="', westr::safe($section['title']), '">';

		$i = 0;
		$max = count($section['groups']);
		foreach ($section['groups'] as $group)
		{
			$i++;
			if (empty($group['members']))
				continue;

			if (isset($group['title']))
				echo '
				<h6', $i === 1 ? ' class="top"' : '', '>', $group['title'], '</h6>
				<ul', $i === $max ? ' class="last"' : '', '>
					<li>', implode('</li>
					<li>', $group['members']), '</li>
				</ul>';

			$top = false;
		}

		if (isset($section['posttext']))
			echo '
				<p class="posttext">', $section['posttext'], '</p>';

		echo '
			</we:block>
		</div>';
	}

	echo '
	<br class="clear">';

	// The below functions include all the scripts needed from the wedgedesk.com site. The language and format are passed for internationalization.
	// !!! This should become phased out in time with something else.
	if (empty($settings['disable_wedge_js']))
		echo '
		<script type="text/javascript" src="http://www.simpledesk.net/sd/current-version.js"></script>
		<script type="text/javascript" src="http://www.simpledesk.net/sd/latest-news.js"></script>';

	// This sets the announcements and current versions themselves ;).
	echo '
		<script type="text/javascript"><!-- // --><![CDATA[

			var oAdminIndex = new sd_AdminIndex({
				sSelf: \'oAdminCenter\',

				bLoadAnnouncements: true,
				sAnnouncementTemplate: ', JavaScriptEscape('
					<dl>
						%content%
					</dl>
				'), ',
				sAnnouncementMessageTemplate: ', JavaScriptEscape('
					<dt><a href="%href%" target="_blank">%subject%</a> ' . $txt['on'] . ' %time% ' . $txt['by'] . ' %author%</dt>
					<dd>
						%message%<br>
						<a href="%readmore%" class="smalltext" target="_blank">' . $txt['shd_admin_readmore'] . '</a>
					</dd>
				'), ',
				sAnnouncementContainerId: \'sdAnnouncements\',

				bLoadVersions: true,
				sSdVersionContainerId: \'sdVersion\',
				sYourVersionContainerId: \'yourVersion\',
				sVersionOutdatedTemplate: ', JavaScriptEscape('
					<span class="alert">%currentVersion%</span>
				'), ',

				bLoadUpdateNotification: true,
				sUpdateNotificationContainerId: \'sd_update_section\',
				sUpdateNotificationDefaultTitle: ', JavaScriptEscape($txt['shd_update_available']), ',
				sUpdateNotificationDefaultMessage: ', JavaScriptEscape($txt['shd_update_message']), ',
				sUpdateNotificationTemplate: ', JavaScriptEscape('
					<div class="cat_bar grid_header" id="update_title">
						<h3 class="catbg">
							<img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/update.png">
							%title%
							<span class="righttext"><a href="<URL>?action=helpadmin;help=shd_admin_help_update" onclick="return reqWin(this);" class="help"></a></span>
						</h3>
					</div>
					<div class="windowbg" id="update_container">
						<div class="content" id="update_content">
							<p id="update_critical_alert" class="alert" style="display: none;">!!</p>
							<h3 id="update_critical_title" class="alert" style="display: none;">%criticaltitle%</h3>
							<div id="update_message" class="smalltext">
								<p>
									%message%
								</p>
							</div>
						</div>
					</div>
				'), ',
				sUpdateNotificationLink: ', JavaScriptEscape('<URL>?action=admin;area=packages;pgdownload;auto;package=%package%;' . $context['session_var'] . '=' . $context['session_id']), ',
				sUpdateInformationLink: \'%information%\',
			});
		// ]]></script>';
}

/**
 *	Display options as set up by the options functions.
 *
 *	This is a modified version of the standard Wedge template for displaying settings, mostly so we have access to a custom BBC template.
 *
 *	In short, Wedge's functions call the relevant function in {@link WedgeDesk-Admin.php} to gather which options should be displayed, store in $context, then pass it here.
 *
 *	The same template services all of the defined areas inside Admin: Helpdesk: Options.
 *
 *	@see shd_admin_options()
 *	@since 1.0
*/
function template_shd_show_settings()
{
	global $context, $txt;

	add_js('
		function invertList(state, id_list)
		{
			for (i in id_list)
			{
				var chk = document.getElementById(id_list[i]);
				if (chk && chk.disabled == false)
					chk.checked = state;
			}
		}');

	if (!empty($context['settings_insert_above']))
		echo $context['settings_insert_above'];

	echo '
	<div id="admincenter">
		<form name="adminform" action="', $context['post_url'], '" method="post" accept-charset="UTF-8"', !empty($context['force_form_onsubmit']) ? ' onsubmit="' . $context['force_form_onsubmit'] . '"' : '', '>';

	// Is there a custom title?
	if (isset($context['settings_title']))
		echo '
			<we:cat>
				<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $context['settings_icon'], '" class="icon"> ', $context['settings_title'], '
			</we:cat>';

	// Have we got some custom code to insert?
	if (!empty($context['settings_message']))
		echo '
			<div class="information">', $context['settings_message'], '</div>';

	// Now actually loop through all the variables.
	$is_open = false;
	foreach ($context['config_vars'] as $config_var)
	{
		// Is it a title or a description?
		if (is_array($config_var) && ($config_var['type'] == 'title' || $config_var['type'] == 'desc'))
		{
			// Not a list yet?
			if ($is_open)
			{
				$is_open = false;
				echo '
					</dl>
				</div>
			</div>';
			}


			// A title?
			if ($config_var['type'] == 'title')
			{
				echo '
					<we:cat>
						', ($config_var['help'] ? '<a href="<URL>?action=helpadmin;help=' . $config_var['help'] . '" onclick="return reqWin(this);" class="help"></a>' : ''), '
						', $config_var['label'], '
					</we:cat>';
			}
			// A description?
			else
			{
				echo '
					<p class="description">
						', $config_var['label'], '
					</p>';
			}

			continue;
		}

		// Not a list yet?
		if (!$is_open)
		{
			$is_open = true;

			echo '
			<div class="windowbg2 wrc">
				<div class="content">
					<dl class="permsettings">';
		}

		// Hang about? Are you pulling my leg - a callback?!
		if (is_array($config_var) && $config_var['type'] == 'callback')
		{
			if (function_exists('template_callback_' . $config_var['name']))
				call_user_func('template_callback_' . $config_var['name']);

			continue;
		}

		if (is_array($config_var))
		{
			// Sometimes we just gotta have some hidden stuff passed back
			if ($config_var['type'] == 'hidden')
			{
				echo '
						<input type="hidden" name="', $config_var['name'], '" value="', $config_var['value'], '">';
			}
			// A check-all option?
			elseif ($config_var['type'] == 'checkall')
			{
				$array = array();
				foreach ($config_var['data'] as $k => $v)
					$array[] = JavaScriptEscape($v[1]);

				echo '
					<dt></dt>
					<dd>
						<input type="checkbox" name="all" id="', $config_var['name'], '" value="" onclick="invert_', $config_var['name'], '(this);" class="input_check floatleft">
						<label for="check_all" class="floatleft">', $txt['check_all'], '</label>
					</dd>
					<script type="text/javascript"><!-- // --><![CDATA[
					function invert_', $config_var['name'], '(obj)
					{
						var checks = [' . implode(',', $array), '];
						invertList(obj.checked, checks);
					}
					// ]]></script>';
			}
			// Is this a span like a message?
			elseif (in_array($config_var['type'], array('message', 'warning')))
			{
				echo '
						<dd', $config_var['type'] == 'warning' ? ' class="alert"' : '', (!empty($config_var['force_div_id']) ? ' id="' . $config_var['force_div_id'] . '_dd"' : ''), '>
							', $config_var['label'], '
						</dd>';
			}
			// Otherwise it's an input box of some kind.
			else
			{
				echo '
						<dt', is_array($config_var) && !empty($config_var['force_div_id']) ? ' id="' . $config_var['force_div_id'] . '"' : '', is_array($config_var) && !empty($config_var['invisible']) ? ' style="display:none;"' : '', '>';

				// Some quick helpers...
				$javascript = $config_var['javascript'];
				$disabled = !empty($config_var['disabled']) ? ' disabled="disabled"' : '';
				$subtext = !empty($config_var['subtext']) ? '<br><dfn> ' . $config_var['subtext'] . '</dfn>' : '';

				// Show the [?] button.
				if ($config_var['help'])
					echo '
							<a id="setting_', $config_var['name'], '" href="<URL>?action=helpadmin;help=', $config_var['help'], '" onclick="return reqWin(this);" class="help"></a><span', ($config_var['disabled'] ? ' class="disabled"' : ($config_var['invalid'] ? ' class="error"' : '')), '><label id="label_', $config_var['name'], '" for="', $config_var['name'], '">', $config_var['label'], '</label>', $subtext, ($config_var['type'] == 'password' ? '<br><em>' . $txt['admin_confirm_password'] . '</em>' : ''), '</span>
						</dt>';
				else
					echo '
							<a id="setting_', $config_var['name'], '"></a> <span', ($config_var['disabled'] ? ' class="disabled"' : ($config_var['invalid'] ? ' class="error"' : '')), '><label id="label_', $config_var['name'], '" for="', $config_var['name'], '">', $config_var['label'], '</label>', $subtext, ($config_var['type'] == 'password' ? '<br><em>' . $txt['admin_confirm_password'] . '</em>' : ''), '</span>
						</dt>';

				echo '
						<dd', (!empty($config_var['force_div_id']) ? ' id="' . $config_var['force_div_id'] . '_dd"' : ''), (is_array($config_var) && !empty($config_var['invisible']) ? ' style="display:none;"' : ''), '>',
							$config_var['preinput'];

				// Show a check box.
				if ($config_var['type'] == 'check')
					echo '
							<input type="checkbox"', $javascript, $disabled, ' name="', $config_var['name'], '" id="', $config_var['name'], '"', ($config_var['value'] ? ' checked="checked"' : ''), ' value="1" class="input_check">';
				// Escape (via htmlspecialchars.) the text box.
				elseif ($config_var['type'] == 'password')
					echo '
							<input type="password"', $disabled, $javascript, ' name="', $config_var['name'], '[0]"', ($config_var['size'] ? ' size="' . $config_var['size'] . '"' : ''), ' value="*#fakepass#*" onfocus="this.value = \'\'; this.form.', $config_var['name'], '.disabled = false;" class="input_password"><br>
							<input type="password" disabled="disabled" id="', $config_var['name'], '" name="', $config_var['name'], '[1]"', ($config_var['size'] ? ' size="' . $config_var['size'] . '"' : ''), ' class="input_password">';
				// Show a selection box.
				elseif ($config_var['type'] == 'select')
				{
					echo '
							<select name="', $config_var['name'], '" id="', $config_var['name'], '" ', $javascript, $disabled, (!empty($config_var['multiple']) ? ' multiple="multiple"' : ''), '>';
					foreach ($config_var['data'] as $option)
						echo '
								<option value="', $option[0], '"', (($option[0] == $config_var['value'] || (!empty($config_var['multiple']) && in_array($option[0], $config_var['value']))) ? ' selected="selected"' : ''), '>', $option[1], '</option>';
					echo '
							</select>';
				}
				// Text area?
				elseif ($config_var['type'] == 'large_text')
				{
					echo '
							<textarea rows="', ($config_var['size'] ? $config_var['size'] : 4), '" cols="40" ', $javascript, $disabled, ' name="', $config_var['name'], '" id="', $config_var['name'], '">', $config_var['value'], '</textarea>';
				}
				// Permission group?
				elseif ($config_var['type'] == 'permissions')
				{
					theme_inline_permissions($config_var['name']);
				}
				// A simple message?
				elseif ($config_var['type'] == 'var_message')
					echo '
							<div', !empty($config_var['name']) ? ' id="' . $config_var['name'] . '"' : '', '>', $config_var['var_message'], '</div>';
				// Assume it must be a text box.
				else
					echo '
							<input type="text"', $javascript, $disabled, ' name="', $config_var['name'], '" id="', $config_var['name'], '" value="', $config_var['value'], '"', ($config_var['size'] ? ' size="' . $config_var['size'] . '"' : ''), ' class="input_text">';

				echo isset($config_var['postinput']) ? '
							' . $config_var['postinput'] : '',
					'</dd>';
			}
		}

		else
		{
			// Just show a separator.
			if ($config_var == '')
				echo '
					</dl>
					<hr>
					<dl class="permsettings">';
			else
				echo '
						<dd>
							<strong>' . $config_var . '</strong>
						</dd>';
		}
	}

	if ($is_open)
		echo '
						</dl>';

	if (empty($context['settings_save_dont_show']))
		echo '
						<hr>
						<div class="righttext">
							<input type="submit" value="', $txt['save'], '"', (!empty($context['save_disabled']) ? ' disabled="disabled"' : ''), (!empty($context['settings_save_onclick']) ? ' onclick="' . $context['settings_save_onclick'] . '"' : ''), ' class="submit">
						</div>';

	if ($is_open)
		echo '
				</div>
			</div>';

	echo '
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		</form>
	</div>
	<br class="clear">';

	if (!empty($context['settings_post_javascript']))
		echo '
	<script type="text/javascript"><!-- // --><![CDATA[
	', $context['settings_post_javascript'], '
	// ]]></script>';

	if (!empty($context['settings_insert_below']))
		echo $context['settings_insert_below'];

}

/**
 *	Display the action log.
 *
 *	Little real work is done in this template; mostly is just iterating through the already-processed contents of the action log as done by {@link shd_admin_action_log()}.
 *
 *	@see shd_admin_action_log()
 *	@since 1.0
*/
function template_shd_action_log()
{
	global $txt, $context, $sort_types;

	// The sort stuff here is huge.
	echo '
				<we:cat>
					<span class="floatright smalltext">', $txt['pages'], ': ', $context['page_index'], '</span>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/log.png" class="icon">
					', $txt['shd_admin_actionlog_title'], '
				</we:cat>
				<table class="shd_ticketlist" cellspacing="0" width="100%">
					<tr class="titlebg">
						<td width="38%" colspan="2">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/action.png" class="shd_smallicon">
							<a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog', $context['sort'] == $sort_types['action'] && !isset($_REQUEST['asc']) ? ';sort=action;asc' : ';sort=action', '">
								', $txt['shd_admin_actionlog_action'], '
							</a>
							', ($context['sort'] == $sort_types['action'] ? '<img src="' . ASSETS . '/' . (isset($_REQUEST['asc']) ? 'sort_up.gif' : 'sort_down.gif' ). '">' : ''), '
						</td>
						<td width="20%">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/time.png" class="shd_smallicon">
							<a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog', $context['sort'] == $sort_types['time'] && !isset($_REQUEST['asc']) ? ';sort=time;asc' : ';sort=time', '">
								', $txt['shd_admin_actionlog_date'], '
							</a>
							', ($context['sort'] == $sort_types['time'] ? '<img src="' . ASSETS . '/' . (isset($_REQUEST['asc']) ? 'sort_up.gif' : 'sort_down.gif' ). '">' : ''), '
						</td>
						<td width="16%">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/user.png" class="shd_smallicon">
							<a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog', $context['sort'] == $sort_types['member'] && !isset($_REQUEST['asc']) ? ';sort=member;asc' : ';sort=member', '">
								', $txt['shd_admin_actionlog_member'], '
							</a>
							', ($context['sort'] == $sort_types['member'] ? '<img src="' . ASSETS . '/' . (isset($_REQUEST['asc']) ? 'sort_up.gif' : 'sort_down.gif' ). '">' : ''), '
						</td>
						<td width="16%">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/position.png" class="shd_smallicon">
							<a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog', $context['sort'] == $sort_types['position'] && !isset($_REQUEST['asc']) ? ';sort=position;asc' : ';sort=position', '">
								', $txt['shd_admin_actionlog_position'], '
							</a>
							', ($context['sort'] == $sort_types['position'] ? '<img src="' . ASSETS . '/' . (isset($_REQUEST['asc']) ? 'sort_up.gif' : 'sort_down.gif' ). '">' : ''), '
						</td>
						<td width="10%">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/ip.png" class="shd_smallicon">
							<a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog', $context['sort'] == $sort_types['ip'] && !isset($_REQUEST['asc']) ? ';sort=ip;asc' : ';sort=ip', '">
								', $txt['shd_admin_actionlog_ip'], '
							</a>
							', ($context['sort'] == $sort_types['ip'] ? '<img src="' . ASSETS . '/' . (isset($_REQUEST['asc']) ? 'sort_up.gif' : 'sort_down.gif' ). '">' : ''), '
						</td>
						<td width="2%">&nbsp;</td>
					</tr>';

			if (empty($context['actions']))
				echo '
					<tr class="windowbg2">
						<td colspan="7" class="shd_noticket">', $txt['shd_admin_actionlog_none'], '</td>
					</tr>';
			else
			{
				$use_bg2 = true; // start with windowbg2 to differentiate between that and windowbg2
				foreach ($context['actions'] AS $action)
				{
					echo '
					<tr class="', ($use_bg2 ? 'windowbg2' : 'windowbg'), '">
						<td width="1%" class="shd_nowrap">
							<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $action['action_icon'], '" class="shd_smallicon">
						</td>
						<td class="smalltext">', $action['action_text'], '</td>
						<td>', $action['time'], '</td>
						<td>', $action['member']['link'], '</td>
						<td>', $action['member']['group'], '</td>
						<td>', !empty($action['member']['ip']) ? $action['member']['ip'] : $txt['shd_admin_actionlog_hidden'], '</td>
						<td>', $action['can_remove'] && $context['can_delete'] ? '<a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog;remove='. $action['id'] . '"><img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/delete.png" alt="' . $txt['shd_delete_item'] . '"></a>' : '', '</td>
					</tr>';

					$use_bg2 = !$use_bg2;
				}
			}

	echo '
				<tr class="titlebg">
					<td colspan="7">
						<span class="floatright smalltext">', $txt['pages'], ': ', $context['page_index'], '</span>
						<span class="smalltext shd_empty_log"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/delete.png"> <a href="<URL>?action=admin;area=helpdesk_info;sa=actionlog', $context['url_sort'], $context['url_order'], ';removeall" onclick="return confirm(', JavaScriptEscape(sprintf($txt['shd_admin_actionlog_removeall_confirm'], $context['hoursdisable'])), ');">', $txt['shd_admin_actionlog_removeall'], '</a></span>
					</td>
				</tr>
				</table>';
}

/**
 *	Displays a header that Javascript should be enabled while in the administration panel area of WedgeDesk.
 *
 *	The helpdesk is disabled to non admins while in maintenance mode, but this template is added to the template layers if the user is an admin and it's in maintenance mode.
 *	@since 2.0
*/
function template_shd_nojs()
{
	global $txt, $context;
	echo '<noscript><div class="errorbox"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/warning.png" class="shd_icon_minihead"> &nbsp;', $txt['shd_helpdesk_nojs'], '</div></noscript>';
}

?>