<?php
/**
 * WedgeDesk
 *
 * This file deals with the action log handling.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/**
 *	Load the items from the helpdesk action log
 *
 *	It is subject to given parameters (start, number of items, order/sorting), parses the language strings and adds the
 *	parameter information provided.
 *
 *	@param int $start Number of items into the log to start (for pagination). If nothing is given, fall back to 0, i.e the first log item.
 *	@param int $items_per_page How many items to load. Default to 10 items.
 *	@param string $sort SQL clause to state which column(s) to order the data by. By default it orders by log_time.
 *	@param string $order SQL clause to state whether the order is ascending or descending. Defaults to descending.
 *	@param string $clause An SQL fragment that forms a WHERE clause to limit log items, e.g. to load a specific ticket or specific member's log items.
 *
 *	@return array A hash array of the log items, with the auto-incremented id being the key:
 *	<ul>
 *	<li>id: Numeric identifier of the log item</li>
 *	<li>time: Formatted time of the event (as per usual for Wedge, formatted for user's timezone)</li>
 *	<li>member: hash array:
 *		<ul>
 *			<li>id: Id of the user that committed the action</li>
 *			<li>name: Name of the user</li>
 *			<li>link: Link to the profile of the user that committed the action</li>
 *			<li>ip: User IP address recorded when the action was carried out</li>
 *			<li>group: Name of the group of the user (uses primary group, failing that post count group)</li>
 *		</ul>
 *	</li>
 *	<li>action: Raw name of the action (for use with collecting the image later)</li>
 *	<li>id_ticket: Numeric id of the ticket this action refers to</li>
 *	<li>id_msg: Numeric id of the individual reply this action refers to</li>
 *	<li>extra: Array of extra parameters for the log action</li>
 *	<li>action_text: Formatted text of the log item (parsed with parameters)</li>
 *	</ul>
 *
 *	@see shd_log_action()
 *	@see shd_count_action_log_entries()
 *	@since 1.0
*/
function shd_load_action_log_entries($start = 0, $items_per_page = 10, $sort = 'la.log_time', $order = 'DESC', $clause = '')
{
	global $txt, $context, $user_profile;

	// Load languages incase they aren't there (Read: ticket-specific logs)
	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskAdmin');
	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskLogAction');
	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskNotifications');

	$loaded_users = array();

	// We may have to exclude some items from this depending on who the user is or is not. Forum/HD admins can always see everything.
	$exclude = shd_action_log_exclusions();

	if (!empty($exclude))
		$clause = empty($clause) ? 'la.action NOT IN ({array_string:exclude})' : $clause . ' AND la.action NOT IN ({array_string:exclude})';

	// Without further screaming and waving, fetch the actions.
	$request = wesql::query('
		SELECT la.id_action, la.log_time, IFNULL(li.member_ip, 0) AS member_ip, la.action, la.id_ticket, la.id_msg, la.extra,
		IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, {string:blank}) AS real_name, IFNULL(mg.group_name, {string:na}) AS group_name
		FROM {db_prefix}helpdesk_log_action AS la
			LEFT JOIN {db_prefix}log_ips AS li ON (la.ip = li.id_ip)
			LEFT JOIN {db_prefix}members AS mem ON(mem.id_member = la.id_member)
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = CASE WHEN mem.id_group = {int:reg_group_id} THEN mem.id_post_group ELSE mem.id_group END)' . (empty($clause) ? '' : '
		WHERE ' . $clause) . '
		ORDER BY ' . ($sort != '' ? '{raw:sort} {raw:order}, la.id_action {raw:order}' : 'la.log_time DESC, la.id_action DESC') . '
		LIMIT {int:start}, {int:items_per_page}',
		array(
			'reg_group_id' => 0,
			'sort' => $sort,
			'start' => $start,
			'items_per_page' => $items_per_page,
			'order' => $order,
			'na' => $txt['not_applicable'],
			'blank' => '',
			'exclude' => $exclude,
		)
	);

	$actions = array();
	$notify_members = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$row['extra'] = @unserialize($row['extra']);
		$row['extra'] = is_array($row['extra']) ? $row['extra'] : array();

		// Uhoh, we don't know who this is! Check it's not automatically by the system. If it is... mark it so.
		if (empty($row['id_member']))
		{
			if (isset($row['extra']['auto']) && $row['extra']['auto'] === true)
				$row['real_name'] = $txt['shd_helpdesk'];
			else
				$row['real_name'] = $txt['shd_admin_actionlog_unknown'];
		}

		$actions[$row['id_action']] = array(
			'id' => $row['id_action'],
			'time' => timeformat($row['log_time']),
			'member' => array(
				'id' => $row['id_member'],
				'name' => $row['real_name'],
				'link' => shd_profile_link($row['real_name'], $row['id_member']),
				'group' => $row['group_name'],
			),
			'action' => $row['action'],
			'id_ticket' => $row['id_ticket'],
			'id_msg' => $row['id_msg'],
			'extra' => $row['extra'],
			'action_text' => '',
			'action_icon' => 'log_' . $row['action'] . '.png',
			'can_remove' => empty($context['waittime']) ? false : ($row['log_time'] < $context['waittime']),
		);

		// We want to use a generic icon for custom fields changes.
		if (strpos($row['action'], 'cf_') === 0)
			$actions[$row['id_action']]['action_icon'] = 'log_cfchange.png';

		if (shd_allowed_to('shd_view_ip_any', 0) || ($row['id_member'] == MID && shd_allowed_to('shd_view_ip_own', 0)))
		{
			$ip = format_ip($row['member_ip']);
			$actions[$row['id_action']]['member']['ip'] = !empty($ip) ? $ip : $txt['shd_admin_actionlog_unknown'];
		}

		// Notifications require us to collate all the user ids as we go along.
		if ($row['action'] == 'notify' && !empty($row['extra']['emails']))
		{
			foreach ($row['extra']['emails'] as $email_type => $recipients)
				if (!empty($recipients['u']))
					$notify_members = array_merge($notify_members, explode(',', $recipients['u']));
		}
	}
	wesql::free_result($request);

	if (!empty($notify_members))
		$notify_members = loadMemberData(array_unique($notify_members));

	// Do some formatting of the action string.
	foreach ($actions as $k => $action)
	{
		if (empty($actions[$k]['action_text']))
			$actions[$k]['action_text'] = isset($txt['shd_log_' . $action['action']]) ? $txt['shd_log_' . $action['action']] : $action['action'];

		$actions[$k]['action_text'] = str_replace('{shd_home}', $context['shd_home'], $actions[$k]['action_text']);

		if (isset($action['extra']['subject']))
		{
			$actions[$k]['action_text'] = str_replace('{ticket}', $actions[$k]['id_ticket'], $actions[$k]['action_text']);
			$actions[$k]['action_text'] = str_replace('{msg}', $actions[$k]['id_msg'], $actions[$k]['action_text']);

			if (isset($actions[$k]['extra']['subject']))
				$actions[$k]['action_text'] = str_replace('{subject}', $actions[$k]['extra']['subject'], $actions[$k]['action_text']);

			if (isset($actions[$k]['extra']['urgency']))
				$actions[$k]['action_text'] = str_replace('{urgency}', $txt['shd_urgency_' . $actions[$k]['extra']['urgency']], $actions[$k]['action_text']);
		}

		// Notifications are pretty tricky. So let's take care of all of it at once, and skip the rest if we're doing that.
		if ($action['action'] == 'notify' && isset($action['extra']['emails']))
		{
			// Because this could be a lot of people etc., we compact its storage heavily compared to a conventional serialize().
			// See shd_notify_users in WedgeDesk-Notifications.php for what this is.

			// Now we have all the usernames for this instance, let's go and build this entry.
			$content = '';
			foreach ($action['extra']['emails'] as $email_type => $recipients)
			{
				$this_content = '<br><a href="<URL>?action=helpdesk;sa=emaillog;log=' . $action['id'] . ';template=' . $email_type . '" onclick="return reqWin(this);">' . $txt['template_log_notify_' . $email_type] . '</a> - ';

				$new_content = '';
				if (!empty($recipients['u']))
				{
					$first = true;
					$users = explode(',', $recipients['u']);
					$unknown_users = 0;
					foreach ($users as $user)
					{
						if (empty($user_profile[$user]))
						{
							$unknown_users++;
							continue;
						}

						$new_content .= ($first ? $txt['shd_log_notify_users'] . ': ' : ', ') . shd_profile_link($user_profile[$user]['real_name'], $user);
						$first = false;
					}
					if ($unknown_users > 0)
						$new_content .= ($first ? $txt['shd_log_notify_users'] . ': ' : ', ') . ($unknown_users == 1 ? $txt['shd_log_unknown_user_1'] : sprintf($txt['shd_log_unknown_user_n'], $unknown_users));
				}

				if (!empty($new_content))
					$content .= $this_content . $new_content;
			}
			if (!empty($content))
				$actions[$k]['action_text'] .= $txt['shd_log_notify_to'] . $content;
			continue;
		}

		if (isset($action['extra']['user_name']))
		{
			$actions[$k]['action_text'] = str_replace('{profile_link}', shd_profile_link($actions[$k]['extra']['user_name'], (isset($actions[$k]['extra']['user_id']) ? $actions[$k]['extra']['user_id'] : 0)), $actions[$k]['action_text']);
			$actions[$k]['action_text'] = str_replace('{user_name}', $actions[$k]['extra']['user_name'], $actions[$k]['action_text']);
		}
		if (isset($action['extra']['user_id']))
			$actions[$k]['action_text'] = str_replace('{user_id}', $actions[$k]['extra']['user_id'], $actions[$k]['action_text']);
		if (isset($actions[$k]['extra']['board_name']))
			$actions[$k]['action_text'] = str_replace('{board_name}', $actions[$k]['extra']['board_name'], $actions[$k]['action_text']);
		if (isset($actions[$k]['extra']['board_id']))
			$actions[$k]['action_text'] = str_replace('{board_id}', $actions[$k]['extra']['board_id'], $actions[$k]['action_text']);
		if (isset($action['extra']['othersubject']))
		{
			$actions[$k]['action_text'] = str_replace('{othersubject}', $actions[$k]['extra']['othersubject'], $actions[$k]['action_text']);
			$actions[$k]['action_text'] = str_replace('{otherticket}', $actions[$k]['extra']['otherticket'], $actions[$k]['action_text']);
		}

		if (isset($action['extra']['old_dept_id']))
		{
			$replace = array(
				'{old_dept_id}' => $action['extra']['old_dept_id'],
				'{old_dept_name}' => $action['extra']['old_dept_name'],
				'{new_dept_id}' => $action['extra']['new_dept_id'],
				'{new_dept_name}' => $action['extra']['new_dept_name'],
			);
			$actions[$k]['action_text'] = str_replace(array_keys($replace), array_values($replace), $actions[$k]['action_text']);
		}

		// Custom fields?
		if (isset($action['extra']['fieldname']))
		{
			if ($action['extra']['fieldtype'] == CFIELD_TYPE_CHECKBOX)
			{
				$action['extra']['oldvalue'] = !empty($action['extra']['oldvalue']) ? $txt['yes'] : $txt['no'];
				$action['extra']['newvalue'] = !empty($action['extra']['newvalue']) ? $txt['yes'] : $txt['no'];
			}
			elseif ($action['extra']['fieldtype'] == CFIELD_TYPE_RADIO || $action['extra']['fieldtype'] == CFIELD_TYPE_SELECT || $action['extra']['fieldtype'] == CFIELD_TYPE_MULTI)
			{
				if (empty($action['extra']['oldvalue']))
					$action['extra']['oldvalue'] = $txt['shd_none_selected'];
				if (empty($action['extra']['newvalue']))
					$action['extra']['newvalue'] = $txt['shd_none_selected'];
			}
			else
			{
				if (empty($action['extra']['oldvalue']))
					$action['extra']['oldvalue'] = $txt['shd_empty_item'];
				if (empty($action['extra']['newvalue']))
					$action['extra']['newvalue'] = $txt['shd_empty_item'];
			}
			$actions[$k]['action_text'] = str_replace('{fieldname}', $action['extra']['fieldname'], $actions[$k]['action_text']);
			$actions[$k]['action_text'] = str_replace('{oldvalue}', $action['extra']['oldvalue'], $actions[$k]['action_text']);
			$actions[$k]['action_text'] = str_replace('{newvalue}', $action['extra']['newvalue'], $actions[$k]['action_text']);
		}

		// This should be the last pair of ops - always.
		if (isset($action['extra']['att_added']))
			$actions[$k]['action_text'] .= ' ' . $txt['shd_logpart_att_added'] . ': ' . implode(', ', $action['extra']['att_added']);
		if (isset($action['extra']['att_removed']))
			$actions[$k]['action_text'] .= ' ' . $txt['shd_logpart_att_removed'] . ': ' . implode(', ', $action['extra']['att_removed']);

	}
	return $actions;
}

/**
 *	Returns the total number of items in the helpdesk log.
 *
 *	This function gets the total number of items logged in the helpdesk log, for the purposes of establishing the number of
 *	pages there should be in the page-index.
 *
 *	@param string $clause An SQL fragment that forms a WHERE clause to limit log items, e.g. to load a specific ticket or specific member's log items.
 *
 *	@return int Number of entries in the helpdesk action log table.
 *	@see shd_load_action_log_entries()
 *	@since 1.0
*/
function shd_count_action_log_entries($clause = '')
{
	$exclude = shd_action_log_exclusions();

	if (!empty($exclude))
	{
		if (empty($clause))
			$clause = 'la.action NOT IN ({array_string:exclude})';
		else
			$clause .= ' AND la.action NOT IN ({array_string:exclude})';
	}

	// Without further screaming and waving, fetch the actions.
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}helpdesk_log_action AS la
		LEFT JOIN {db_prefix}members AS mem ON(mem.id_member = la.id_member)
		LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = CASE WHEN mem.id_group = {int:reg_group_id} THEN mem.id_post_group ELSE mem.id_group END)' . (empty($clause) ? '' : '
		WHERE ' . $clause),
		array(
			'reg_group_id' => 0,
			'exclude' => $exclude,
		)
	);

	list ($entry_count) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $entry_count;
}

function shd_action_log_exclusions()
{
	$exclude = array();

	if (!we::$is_admin && !shd_allowed_to('admin_helpdesk', 0))
	{
		// First, custom field changes only available to admins.
		$exclude = array('cf_tktchange_admin', 'cf_rplchange_admin', 'cf_tktchgdef_admin', 'cf_rplchgdef_admin');
		// Next, staff only things
		if (!shd_allowed_to('shd_staff', 0))
		{
			$exclude[] = 'cf_tktchange_staffadmin';
			$exclude[] = 'cf_rplchange_staffadmin';
			$exclude[] = 'cf_tktchgdef_staffadmin';
			$exclude[] = 'cf_rplchgdef_staffadmin';
		}
		else
		// Next, user only things (that staff can't see)
		{
			$exclude[] = 'cf_tktchange_useradmin';
			$exclude[] = 'cf_rplchange_useradmin';
			$exclude[] = 'cf_tktchgdef_useradmin';
			$exclude[] = 'cf_rplchgdef_useradmin';
		}

		// Can they see multiple departments? If not, exclude dept move notices too.
		$dept = shd_allowed_to('access_helpdesk', false);
		if (count($dept) == 1)
			$exclude[] = 'move_dept';
	}

	return $exclude;
}

?>