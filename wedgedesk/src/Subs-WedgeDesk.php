<?php
/**
 * WedgeDesk
 *
 * This file handles key functions for WedgeDesk that can be called on every page load, such as
 * the counter for active tickets in the menu header, or the action log.
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
 *	Initialises key values for WedgeDesk.
 *
 *	This function initialises certain key constructs for WedgeDesk, such as constants, that are used throughout
 *	WedgeDesk. It should be called first right up in Load.php anyway.
 *
 *	Calling multiple times is not significantly detrimental to performance; the function is aware if it has been
 *	called previously.
 *
 *	@since 2.0
*/
function shd_init()
{
	global $settings, $context;
	static $called = null;

	if (!empty($called))
		return;

	$called = true;
	$context['shd_home'] = 'action=helpdesk;sa=main';

	// What WD version are we on?
	define('SHD_VERSION', 'WedgeDesk 1.0');

	// This isn't the Wedge way. But for something like this, it's way way more logical and readable.
	define('TICKET_STATUS_NEW', 0);
	define('TICKET_STATUS_PENDING_STAFF', 1);
	define('TICKET_STATUS_PENDING_USER', 2);
	define('TICKET_STATUS_CLOSED', 3);
	define('TICKET_STATUS_WITH_SUPERVISOR', 4);
	define('TICKET_STATUS_ESCALATED', 5);
	define('TICKET_STATUS_DELETED', 6);

	define('TICKET_URGENCY_LOW', 0);
	define('TICKET_URGENCY_MEDIUM', 1);
	define('TICKET_URGENCY_HIGH', 2);
	define('TICKET_URGENCY_VHIGH', 3);
	define('TICKET_URGENCY_SEVERE', 4);
	define('TICKET_URGENCY_CRITICAL', 5);

	define('MSG_STATUS_NORMAL', 0);
	define('MSG_STATUS_DELETED', 1);

	// Relationship types
	define('RELATIONSHIP_LINKED', 0);
	define('RELATIONSHIP_DUPLICATED', 1);
	define('RELATIONSHIP_ISPARENT', 2);
	define('RELATIONSHIP_ISCHILD', 3);

	// Custom fields, their types, positions, content type
	define('CFIELD_TICKET', 1);
	define('CFIELD_REPLY', 2);

	define('CFIELD_PLACE_DETAILS', 1);
	define('CFIELD_PLACE_INFO', 2);
	define('CFIELD_PLACE_PREFIX', 3);
	define('CFIELD_PLACE_PREFIXFILTER', 4);

	define('CFIELD_TYPE_TEXT', 1);
	define('CFIELD_TYPE_LARGETEXT', 2);
	define('CFIELD_TYPE_INT', 3);
	define('CFIELD_TYPE_FLOAT', 4);
	define('CFIELD_TYPE_SELECT', 5);
	define('CFIELD_TYPE_CHECKBOX', 6);
	define('CFIELD_TYPE_RADIO', 7);
	define('CFIELD_TYPE_MULTI', 8);

	// Ticket notification options
	define('NOTIFY_PREFS', 0);
	define('NOTIFY_ALWAYS', 1);
	define('NOTIFY_NEVER', 2);

	// Roles and permissions
	define('ROLE_USER', 1);
	define('ROLE_STAFF', 2);
	//define('ROLE_SUPERVISOR', 3);
	define('ROLE_ADMIN', 4);

	define('ROLEPERM_DISALLOW', 0);
	define('ROLEPERM_ALLOW', 1);
	define('ROLEPERM_DENY', 2);

	// How many digits should we show for ticket numbers? Normally we pad to 5 digits, e.g. 00001 - this is how we set that width.
	if (empty($settings['shd_zerofill']) || $settings['shd_zerofill'] < 0)
		$settings['shd_zerofill'] = 0;

	// Load some stuff
	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDesk');
	loadPluginSource('Arantor:WedgeDesk', 'src/Subs-WedgeDeskPermissions');
	if (WEDGE == 'SSI')
		loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk-SSI');

	// Set up defaults
	$defaults = array(
		'shd_attachments_mode' => 'ticket',
		'shd_staff_badge' => 'nobadge',
		'shd_privacy_display' => 'smart',
	);

	foreach ($defaults as $var => $val)
	{
		if (empty($settings[$var]))
			$settings[$var] = $val;
	}

	// Make sure it's set, and is boolean.
	$settings['helpdesk_active'] = true;

	if ($settings['helpdesk_active'])
	{
		call_hook('shd_hook_init');
		call_lang_hook('shd_lang_init');
	}

	shd_load_user_perms();

	if (!empty($settings['shd_maintenance_mode']))
	{
		if (!empty($settings['shd_helpdesk_only']) && !we::$is_admin && !shd_allowed_to('admin_helpdesk', 0))
		{
			// You can only login.... otherwise, you're getting the "maintenance mode" display. Except we have to boot up a decent amount of Wedge.
			if (empty($_REQUEST['action']) || ($_REQUEST['action'] != 'login2' && $_REQUEST['action'] != 'logout'))
			{
				$_GET['action'] = '';
				$_REQUEST['action'] = '';
				$context['shd_maintenance_mode'] = true;
				loadBoard();
				loadPermissions();
				loadTheme();
				is_not_banned();
				loadSource('Subs-Auth');
				InMaintenance();
				obExit(null, null, false);
			}
		}
		else
			$settings['helpdesk_active'] &= (we::$is_admin || shd_allowed_to('admin_helpdesk', 0));
	}

	// Last minute stuff
	if ($settings['helpdesk_active'])
	{
		// Are they actually going into the helpdesk? If they are, do we need to deal with their theme?
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'helpdesk')
		{
			// First figure out what department they're in.
			$this_dept = 0;
			$depts = shd_allowed_to('access_helpdesk', false);
			// Do they only have one dept? If so, that's the one.
			if (count($depts) == 1)
				$this_dept = $depts[0];
			// They might explicitly say it on the request.
			elseif (isset($_REQUEST['dept']))
			{
				$_REQUEST['dept'] = (int) $_REQUEST['dept'];
				if (in_array($_REQUEST['dept'], $depts))
					$this_dept = $_REQUEST['dept'];
			}
			// They might explicitly be posting into a dept from nowhere-land
			elseif (isset($_REQUEST['newdept']))
			{
				$_REQUEST['newdept'] = (int) $_REQUEST['newdept'];
				if (in_array($_REQUEST['newdept'], $depts))
					$this_dept = $_REQUEST['newdept'];
			}
			// They might specify a ticket, see if we can get the dept from that. Validate we can see it and get the dept from there.
			elseif (isset($_REQUEST['ticket']))
			{
				$ticket = (int) $_REQUEST['ticket'];
				if (!empty($ticket))
				{
					$query = wesql::query('
						SELECT hdt.id_dept, dept_name, dept_theme
						FROM {db_prefix}helpdesk_tickets AS hdt
							INNER JOIN {db_prefix}helpdesk_depts AS hdd ON (hdt.id_dept = hdd.id_dept)
						WHERE id_ticket = {int:ticket}
							AND {query_see_ticket}',
						array(
							'ticket' => $ticket,
						)
					);
					if ($row = wesql::fetch_row($query))
						if (in_array($row[0], $depts))
							list($this_dept, $context['shd_dept_name'], $th) = $row;
					wesql::free_result($query);
				}
			}

			if (!empty($this_dept) && !isset($th))
			{
				$context['queried_dept'] = $this_dept;
				$query = wesql::query('
					SELECT dept_theme
					FROM {db_prefix}helpdesk_depts
					WHERE id_dept = {int:dept}',
					array(
						'dept' => $this_dept,
					)
				);
				if ($row = wesql::fetch_row($query))
					$th = $row[0];
				wesql::free_result($query);
			}

			// If for whatever reason we didn't establish a theme, see if there's a forum default one.
			if (empty($th) && !empty($settings['shd_theme']))
				$th = $settings['shd_theme'];
			// Action.
			if (!empty($th))
			{
				// This is ever so slightly hacky. But as this function is called sufficiently early we can get away with it.
				unset($_REQUEST['theme'], $settings['theme_allow']);
				$settings['theme_guests'] = $th;
			}
		}
	}
}

/**
 *	Defines the helpdesk menu item, including the number of active tickets to be displayed to the user.
 *
 *	Identifies the number of tickets that a user might be interested in, and generates the menu text for the main menu
 *	to include this; note that the value should be cached through Wedge's functions. The cache is also clearable, through
 *	the {@link shd_clear_active_tickets()} function.
 *
 *	@return string A string containing either the number of items, or an empty string if not.
 *	@see shd_clear_active_tickets()
 *	@since 1.0
*/
function shd_get_active_tickets()
{
	global $settings, $context;

	if (!$settings['helpdesk_active'] || we::$is_guest || !empty($context['shd_maintenance_mode']) || !empty($settings['shd_hidemenuitem']))
		return '';

	// Have we already run on this page? If so we already have the answer.
	if (!empty($context['active_tickets']))
		return $context['active_tickets'];

	// Can we get it from the cache?
	$temp = cache_get_data('shd_active_tickets_' . MID, 120);
	if ($temp !== null)
		return $context['active_tickets'] = $temp;

	shd_init();
	// Figure out the status(es) that the ticket could be.
	if (shd_allowed_to('shd_staff', 0))
		$status = array(TICKET_STATUS_NEW, TICKET_STATUS_PENDING_STAFF); // staff actually need to deal with these
	else
		$status = array(TICKET_STATUS_PENDING_USER); // user actually needs to deal with this

	$query = wesql::query('
		SELECT COUNT(id_ticket)
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket} AND status IN ({array_int:status})',
		array(
			'status' => $status,
		)
	);

	$context['active_tickets_raw'] = 0;

	$row = wesql::fetch_row($query);
	if (!empty($row[0]))
		$context['active_tickets'] = $row[0];
	else
		$context['active_tickets'] = '';

	cache_put_data('shd_active_tickets_' . MID, $context['active_tickets'], 120);

	return $context['active_tickets'];
}

/**
 *	Clears the cache of active tickets for the menu item.
 *
 *	{@link shd_get_active_tickets()} generates the number of active tickets for the user display, and caches it for 120 seconds
 *	normally. This function clears the cache and should be called whenever any operation modifies the state of a ticket.
 *
 *	@param int $dept The department that is being affected by this change. Users who cannot see this department will not be affected.
 *	@see shd_get_active_tickets()
 *	@since 1.0
*/
function shd_clear_active_tickets($dept = 0)
{
	global $settings;
	static $done_all = false;

	// This isn't very nice, unfortunately. But it's the only way to ensure that caches are flushed as necessary and to prevent us having to query so much more on every page.
	// Firstly, the active ticket count. Needs to be for every person that can see this department.
	$members = shd_members_allowed_to('access_helpdesk', $dept);
	if (!$done_all)
	{
		foreach ($members as $member)
		{
			cache_put_data('shd_active_tickets_' . $member, null, 120);
			cache_put_data('shd_ticket_count_' . $member, null, 120);
		}
		$done_all = true;
	}

	// This is going to hurt.
	if ($dept == 0)
	{
		$depts = shd_allowed_to('access_helpdesk', false);
		foreach ($depts as $dept)
			foreach ($members as $member)
				cache_put_data('shd_ticket_count_dept' . $dept . '_' . $member, null, 120);
	}
	// Not so much?
	else
	{
		foreach ($members as $member)
			cache_put_data('shd_ticket_count_dept' . $dept . '_' . $member, null, 120);
	}

	// If we've updated this count, and the menu's cached, which it will be under the following conditions, update the 'settings'.
	if (!empty($settings['cache_enable']) && $settings['cache_enable'] >= 2)
		updateSettings(
			array('settings_updated' => time())
		);
}

/**
 *	Adds an action to the helpdesk internal action log.
 *
 *	This function deals with adding items to the action log maintained by the helpdesk.
 *
 *	@param string $action Specifies the name of the action to log, which implies the image and language string (log_$action is the name of the image, and $txt['shd_log_$action'] is the string used to express the action, as listed in {@link WedgeDeskLogAction.english.php}.
 *	Note that since 1.1, the list of actions is looked up against the options in Admin / Helpdesk / Options / Action Log Options as to whether they should be logged or not
 *	@param array $params This is a list of named parameters in a hash array to be used in the language string later.
 *
 *	@see shd_load_action_log_entries()
 *	@since 1.0
*/
function shd_log_action($action, $params, $do_last_update = true)
{
	global $settings;
	static $last_cache;

	// Before we go any further, we use this function to globally update tickets' last updated time (since every ticket action should potentially
	// be logged) - but we don't do the query *every* time if we don't need to. Allows a two second leeway.

	if ($do_last_update && isset($params['ticket']) && ((int) $params['ticket'] != 0) && (empty($last_cache[$params['ticket']]) || $last_cache[$params['ticket']] < time() - 2))
	{
		$last_cache[$params['ticket']] = time();
		wesql::query('
			UPDATE {db_prefix}helpdesk_tickets
			SET last_updated = {int:new_time}
			WHERE id_ticket = {int:ticket}',
			array(
				'new_time' => $last_cache[$params['ticket']],
				'ticket' => $params['ticket'],
			)
		);
	}

	if (!empty($settings['shd_disable_action_log']))
		return;

	// Check to see if we should actually log this action or not.
	$logopt = array(
		'newticket' => 'shd_logopt_newposts',
		'newticketproxy' => 'shd_logopt_newposts',
		'editticket' => 'shd_logopt_editposts',
		'newreply' => 'shd_logopt_newposts',
		'editreply' => 'shd_logopt_editposts',
		'resolve' => 'shd_logopt_resolve',
		'unresolve' => 'shd_logopt_resolve',
		'assign' => 'shd_logopt_assign',
		'unassign' => 'shd_logopt_assign',
		'markprivate' => 'shd_logopt_privacy',
		'marknotprivate' => 'shd_logopt_privacy',
		'urgency_increase' => 'shd_logopt_urgency',
		'urgency_decrease' => 'shd_logopt_urgency',
		'tickettotopic' => 'shd_logopt_tickettopicmove',
		'topictoticket' => 'shd_logopt_tickettopicmove',
		'delete' => 'shd_logopt_delete',
		'delete_reply' => 'shd_logopt_delete',
		'restore' => 'shd_logopt_restore',
		'restore_reply' => 'shd_logopt_restore',
		'permadelete' => 'shd_logopt_permadelete',
		'permadelete_reply' => 'shd_logopt_permadelete',
		'rel_linked' => 'shd_logopt_relationships',
		'rel_duplicated' => 'shd_logopt_relationships',
		'rel_parent' => 'shd_logopt_relationships',
		'rel_child' => 'shd_logopt_relationships',
		'rel_re_linked' => 'shd_logopt_relationships',
		'rel_re_duplicated' => 'shd_logopt_relationships',
		'rel_re_parent' => 'shd_logopt_relationships',
		'rel_re_child' => 'shd_logopt_relationships',
		'rel_delete' => 'shd_logopt_relationships',
		'cf_tktchange_admin' => 'shd_logopt_cfchanges',
		'cf_tktchange_staffadmin' => 'shd_logopt_cfchanges',
		'cf_tktchange_useradmin' => 'shd_logopt_cfchanges',
		'cf_tktchange_userstaffadmin' => 'shd_logopt_cfchanges',
		'cf_rplchange_admin' => 'shd_logopt_cfchanges',
		'cf_rplchange_staffadmin' => 'shd_logopt_cfchanges',
		'cf_rplchange_useradmin' => 'shd_logopt_cfchanges',
		'cf_rplchange_userstaffadmin' => 'shd_logopt_cfchanges',
		'cf_tktchgdef_admin' => 'shd_logopt_cfchanges',
		'cf_tktchgdef_staffadmin' => 'shd_logopt_cfchanges',
		'cf_tktchgdef_useradmin' => 'shd_logopt_cfchanges',
		'cf_tktchgdef_userstaffadmin' => 'shd_logopt_cfchanges',
		'cf_rplchgdef_admin' => 'shd_logopt_cfchanges',
		'cf_rplchgdef_staffadmin' => 'shd_logopt_cfchanges',
		'cf_rplchgdef_useradmin' => 'shd_logopt_cfchanges',
		'cf_rplchgdef_userstaffadmin' => 'shd_logopt_cfchanges',
		'move_dept' => 'shd_logopt_move_dept',
		'monitor' => 'shd_logopt_monitor',
		'unmonitor' => 'shd_logopt_monitor',
		'ignore' => 'shd_logopt_monitor',
		'unignore' => 'shd_logopt_monitor',
	);

	if (empty($logopt[$action]) || empty($settings[$logopt[$action]]))
		return;

	// Some parts of $params we will want in the main row for sorting + lookups later. Let's see if they're here.
	if (!empty($params['ticket']))
	{
		$ticket_id = (int) $params['ticket'];
		if ($ticket_id == 0)
			trigger_error('log_action(): received data with non-numeric ticket', E_USER_NOTICE);
		else
			unset($params['ticket']);
	}
	else
		$ticket_id = 0;

	if (!empty($params['msg']))
	{
		$msg_id = (int) $params['msg'];
		if ($msg_id == 0)
			trigger_error('log_action(): received data with non-numeric msg', E_USER_NOTICE);
		else
			unset($params['msg']);
	}
	else
		$msg_id = 0;

	wesql::insert('',
		'{db_prefix}helpdesk_log_action',
		array(
			'log_time' => 'int', 'id_member' => 'int', 'ip' => 'int', 'action' => 'string', 'id_ticket' => 'int', 'id_msg' => 'int', 'extra' => 'string-65534',
		),
		array(
			time(), MID, get_ip_identifier(we::$user['ip']), $action, $ticket_id, $msg_id, serialize($params),
		),
		array('id_action')
	);
}

/**
 *	Determines if the current user can raise/lower the urgency of a ticket.
 *
 *	This function identifies whether the current user can raise or lower the urgency of a ticket based on the current urgency
 *	of the ticket and whether it is their ticket; this is used in the ticket display as well as the actions linked directly to
 *	modifying urgency (both AJAXively and non AJAXively)
 *
 *	@param int $urgency The current urgency of a ticket as an integer
 *	@param bool $ticket_starter Whether the user in question is the starter of the ticket (instead of querying to establish that,
 *	that detail should already be known to the calling function)
 *	@param bool $closed Whether the ticket is currently closed or not
 *	@param bool $deleted Whether the ticket is currently closed or not
 *
 *	@see shd_urgency_change_noajax()
 *	@since 1.0
*/
function shd_can_alter_urgency($urgency, $ticket_starter, $closed, $deleted, $dept)
{
	$can_urgency = array(
		'increase' => false,
		'decrease' => false,
	);

	if ($closed || $deleted)
		return $can_urgency;

	if (shd_allowed_to('shd_alter_urgency_any', $dept))
	{
		if (shd_allowed_to('shd_alter_urgency_higher_any', $dept) || (shd_allowed_to('shd_alter_urgency_higher_own', $dept) && $ticket_starter == MID))
			$can_urgency = array( // can alter any urgency and can alter this one's higher urgency too
				'increase' => ($urgency < TICKET_URGENCY_CRITICAL),
				'decrease' => ($urgency > TICKET_URGENCY_LOW),
			);
		else
			$can_urgency = array( // can alter any base urgency - just not this one's higher urgency
				'increase' => ($urgency < TICKET_URGENCY_HIGH),
				'decrease' => ($urgency > TICKET_URGENCY_LOW && $urgency < TICKET_URGENCY_VHIGH),
			);
	}
	elseif (shd_allowed_to('shd_alter_urgency_own', $dept) && $ticket_starter == MID)
		$can_urgency = array( // ok, so this is our ticket and we can change it
			'increase' => ($urgency < (shd_allowed_to('shd_alter_urgency_higher_own', $dept) ? TICKET_URGENCY_CRITICAL : TICKET_URGENCY_HIGH)),
			'decrease' => ($urgency > TICKET_URGENCY_LOW && $urgency <= (shd_allowed_to('shd_alter_urgency_higher_own', $dept) ? TICKET_URGENCY_CRITICAL : TICKET_URGENCY_VHIGH)),
		);

	return $can_urgency;
}

/**
 *	Queries the database to find the number of applicable tickets
 *
 *	This function collects counts for the different states of tickets (new, with staff, with user, etc) of all the tickets
 *	visible to the user, and returns a selection of that dataset based on the values provided to $status and $is_staff.
 *
 *	@param string $status The relevant count of tickets to return:
 *	<ul>
 *	<li>'open': All tickets currently open that the user can see</li>
 *	<li>'assigned': All tickets assigned to the current user</li>
 *	<li>'new': All the new tickets that the user can see</li>
 *	<li>'staff': All the tickets currently with staff (varies for staff vs user; user count here includes 'new' tickets)</li>
 *	<li>'with_user': All the tickets pending user comment</li>
 *	<li>'closed': All the tickets the user can see that are resolved</li>
 *	<li>'recycled': All the tickets the user can see that are currently in the recycle bin</li>
 *	<li>'withdeleted': All the tickets that have at least one deleted reply</li>
 *	<li>'' or unspecified: Return the total of all tickets in the helpdesk (subject to visibility)</li>
 *	</ul>
 *	@param bool $is_staff If the user in question is staff or not.
 *
 *	@return int Number of applicable tickets.
 *	@since 1.0
*/
function shd_count_helpdesk_tickets($status = '', $is_staff = false)
{
	global $context;

	if (empty($context['ticket_count']))
	{
		$context['ticket_count'] = array();
		for ($i = 0; $i <= 6; $i++)
			$context['ticket_count'][$i] = 0; // set the count to zero for all known states

		$cache_id = 'shd_ticket_count_' . (!empty($context['shd_department']) ? 'dept' . $context['shd_department'] . '_' : '') . MID;

		$temp = cache_get_data($cache_id, 180);
		if ($temp !== null)
		{
			$context['ticket_count'] = $temp;
		}
		else
		{
			$query = wesql::query('
				SELECT status, COUNT(status) AS tickets
				FROM {db_prefix}helpdesk_tickets AS hdt
				WHERE {query_see_ticket}' . (!empty($context['shd_department']) ? '
					AND id_dept = ' . $context['shd_department'] : '') . '
				GROUP BY status
				ORDER BY null',
				array()
			);

			while ($row = wesql::fetch_assoc($query))
				$context['ticket_count'][$row['status']] = $row['tickets'];

			wesql::free_result($query);

			$context['ticket_count']['assigned'] = 0;
			if (shd_allowed_to('shd_staff', 0))
			{
				$query = wesql::query('
					SELECT status, COUNT(status) AS tickets
					FROM {db_prefix}helpdesk_tickets AS hdt
					WHERE {query_see_ticket}
						AND id_member_assigned = {int:user}' . (!empty($context['shd_department']) ? '
						AND id_dept = ' . $context['shd_department'] : '') . '
					GROUP BY status
					ORDER BY null',
					array(
						'user' => MID,
					)
				);

				while ($row = wesql::fetch_assoc($query))
				{
					if (!in_array($row['status'], array(TICKET_STATUS_CLOSED, TICKET_STATUS_DELETED)))
					{
						$context['ticket_count']['assigned'] += $row['tickets'];
						$context['ticket_count'][$row['status']] -= $row['tickets'];
					}
				}

				wesql::free_result($query);
			}

			if (shd_allowed_to('shd_access_recyclebin'))
			{
				$query = wesql::query('
					SELECT COUNT(id_ticket) AS tickets
					FROM {db_prefix}helpdesk_tickets AS hdt
					WHERE {query_see_ticket}' . (!empty($context['shd_department']) ? '
						AND id_dept = ' . $context['shd_department'] : '') . '
						AND hdt.withdeleted = {int:has_deleted}
						AND hdt.status != {int:ticket_deleted}',
					array(
						'has_deleted' => MSG_STATUS_DELETED,
						'ticket_deleted' => TICKET_STATUS_DELETED, // we want all non deleted tickets with deleted replies
					)
				);
				list($count) = wesql::fetch_row($query);
				wesql::free_result($query);

				$context['ticket_count']['withdeleted'] = $count;
			}
			else
				$context['ticket_count']['withdeleted'] = 0;

			cache_put_data($cache_id, $context['ticket_count'], 180);
		}
	}

	switch($status)
	{
		case 'open':
			return (
				$context['ticket_count'][TICKET_STATUS_NEW] +
				$context['ticket_count'][TICKET_STATUS_PENDING_STAFF] +
				$context['ticket_count'][TICKET_STATUS_PENDING_USER] +
				$context['ticket_count'][TICKET_STATUS_WITH_SUPERVISOR] +
				$context['ticket_count'][TICKET_STATUS_ESCALATED] +
				$context['ticket_count']['assigned']
			);
		case 'assigned':
			return $context['ticket_count']['assigned'];
		case 'new':
			return $context['ticket_count'][TICKET_STATUS_NEW];
		case 'staff':
			return $is_staff ? $context['ticket_count'][TICKET_STATUS_PENDING_STAFF] : ($context['ticket_count'][TICKET_STATUS_NEW] + $context['ticket_count'][TICKET_STATUS_PENDING_STAFF]); // both "new" and "with staff" should appear as 'with staff' to non staff
		case 'with_user':
			return $context['ticket_count'][TICKET_STATUS_PENDING_USER];
		case 'closed':
			return $context['ticket_count'][TICKET_STATUS_CLOSED];
		case 'recycled':
			return $context['ticket_count'][TICKET_STATUS_DELETED];
		case 'withdeleted':
			return $context['ticket_count']['withdeleted'];
		default:
			return array_sum($context['ticket_count']) - $context['ticket_count']['withdeleted']; // since withdeleted is the only duplicate information, all the rest is naturally self-exclusive
	}
}

/**
 *	Attempts to load a given ticket's data.
 *
 *	This function permission-checks, and throws appropriate errors if no ticket is specified either directly or through URL,
 *	or if the ticket is not accessible either through deletion or lack of permissions.
 *
 *	@param int $ticket The ticket to use; if none is specified, use the one from $_REQUEST['ticket'], which will have been processed
 *	into $context['ticket_id'] if it is available.
 *
 *	@return array A large hash map stating many ticket details
 *	@since 1.0
*/
function shd_load_ticket($ticket = 0)
{
	global $context;

	// Make sure they set a ticket ID.
	if ($ticket == 0 && empty($context['ticket_id']))
		fatal_lang_error('shd_no_ticket', false);

	// Get the ticket data. Note this implicitly checks perms too.
	$query = wesql::query('
		SELECT hdt.id_first_msg, hdt.id_last_msg, hdt.id_member_started, hdt.subject, hdt.urgency, hdt.status,
			hdt.num_replies, hdt.deleted_replies, hdt.private, hdtr.body, hdtr.id_member, hdtr.poster_time,
			hdtr.modified_time, hdtr.smileys_enabled, hdt.id_dept AS dept, hdd.dept_name,
			IFNULL(mem.real_name, hdtr.poster_name) AS starter_name, IFNULL(mem.id_member, 0) AS starter_id, li.member_ip AS starter_ip,
			IFNULL(ma.real_name, 0) AS assigned_name, IFNULL(ma.id_member, 0) AS assigned_id,
			IFNULL(mm.real_name, hdtr.modified_name) AS modified_name, IFNULL(mm.id_member, 0) AS modified_id
		FROM {db_prefix}helpdesk_tickets AS hdt
			INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr ON (hdt.id_first_msg = hdtr.id_msg)
			INNER JOIN {db_prefix}helpdesk_depts AS hdd ON (hdt.id_dept = hdd.id_dept)
			LEFT JOIN {db_prefix}members AS mem ON (hdt.id_member_started = mem.id_member)
			LEFT JOIN {db_prefix}members AS ma ON (hdt.id_member_assigned = ma.id_member)
			LEFT JOIN {db_prefix}members AS mm ON (hdtr.modified_member = mm.id_member)
			LEFT JOIN {db_prefix}log_ips AS li ON (hdtr.poster_ip = li.id_ip)
		WHERE {query_see_ticket} AND hdt.id_ticket = {int:ticket}',
		array(
			'ticket' => $ticket == 0 ? $context['ticket_id'] : $ticket,
		)
	);

	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_no_ticket', false);
	}

	$ticketinfo = wesql::fetch_assoc($query);
	wesql::free_result($query);

	// Anything else we'll use a lot?
	$ticketinfo['is_own'] = (MID == $ticketinfo['starter_id']);
	$ticketinfo['closed'] = $ticketinfo['status'] == TICKET_STATUS_CLOSED;
	$ticketinfo['deleted'] = $ticketinfo['status'] == TICKET_STATUS_DELETED;
	$ticketinfo['starter_ip'] = format_ip($ticketinfo['starter_ip']);

	return $ticketinfo;
}

/**
 *	Formats a string for bbcode and/or smileys.
 *
 *	Formatting is done according to the supplied settings and the master administration settings.
 *
 *	@param string $text Raw text with optional bbcode formatting
 *	@param bool $smileys Whether smileys should be used; this is not an override to the master administration setting of
 *	whether to use smileys or not, and that takes precedence.
 *	@param string $cache If specified, this will provide the cache'd id that Wedge should use to cache the output if it is suitably large.
 *
 *	@return string Will return $text as processed for bbcode (if $settings['shd_allow_ticket_bbc'] permits) and smileys (if
 *	$settings['shd_allow_ticket_smileys'] and $smileys permits)
 *	@since 1.0
*/
function shd_format_text($text, $smileys = true, $cache = '')
{
	global $settings;

	if (empty($settings['shd_allow_ticket_bbc']))
	{
		if (!empty($settings['shd_allow_wikilinks']))
			shd_parse_wikilinks($text, $smileys, $cache);
		if (!empty($settings['shd_allow_ticket_smileys']) && $smileys)
			parsesmileys($text);
	}
	else
		$text = parse_bbc($text, 'wedgedesk-format', array('smileys' => !empty($settings['shd_allow_ticket_smileys']) ? $smileys : false, 'cache' => $cache));

	return $text;
}

/**
 *	Processes the incoming message for wiki-links.
 *
 *	@param string &$message The message to be parsed.
 *	@since 2.0
*/
function shd_parse_wikilinks(&$message, &$smileys, $cache_id)
{
	global $settings;
	static $wikilinks = array();

	if (!empty($settings['helpdesk_active']) && !empty($settings['shd_allow_wikilinks']))
		return;

	// We need to check we're not coming from the convert-to-WYSIWYG context. If we are, we must not parse wikilinks.
	// If we're doing the WYSIWYG thing, bbc_to_html() will be in the backtrace somewhere.
	$backtrace = debug_backtrace();
	for ($i = 0, $n = count($backtrace); $i < $n; $i++)
		if (isset($backtrace[$i]['function']) && $backtrace[$i]['function'] == 'bbc_to_html')
			return;
	unset($backtrace); // This might be quite heavy so get rid of it if we're still here.

	if (preg_match_all('~\[\[ticket\:([0-9]+)\]\]~iU', $message, $matches, PREG_SET_ORDER))
	{
		// Step through the matches, check if it's one we already had in $wikilinks (where persists through the life of this page)
		$ticketlist = array();
		$ticketcount = count($matches);
		for ($i = 0; $i < $ticketcount; $i++)
		{
			$id = (int) $matches[$i][1];
			if (!isset($wikilinks[$id]))
				$ticketlist[$id] = false;
		}

		// Anything we didn't get from $wikilinks we now need to look up
		if (!empty($ticketlist))
		{
			$query = wesql::query('
				SELECT id_ticket, subject
				FROM {db_prefix}helpdesk_tickets AS hdt
				WHERE {query_see_ticket}
					AND id_ticket IN ({array_int:tickets})',
				array(
					'tickets' => array_keys($ticketlist),
				)
			);
			while ($row = wesql::fetch_assoc($query))
			{
				$row['id_ticket'] = (int) $row['id_ticket'];
				$ticketlist[$row['id_ticket']] = $row['subject'];
			}

			// Attach the list we've just made to the master list.
			$wikilinks += $ticketlist;
		}

		// Now, go back through the list of matches again, this time we've got all the tickets we can actually display, so build the final replacement list
		$replacements = array();
		for ($i = 0; $i < $ticketcount; $i++)
		{
			$id = (int) $matches[$i][1];
			if (!empty($wikilinks[$id]))
				$replacements[$matches[$i][0]] = '<a href="<URL>?action=helpdesk;sa=ticket;ticket=' . $id . '.0">[' . str_pad($id, $settings['shd_zerofill'], '0', STR_PAD_LEFT) . '] ' . $wikilinks[$id] . '</a>';
		}

		$message = str_replace(array_keys($replacements), array_values($replacements), $message);
	}
}

/**
 *	Generates a profile link given user id and name.
 *
 *	@param string $name The name to display. This should be a standard Wedge type name, which means already sanitised for HTML.
 *	@param int $id The numeric id of the user we are linking to.
 *
 *	@return string Returns an HTML link to user profile if both a name and id are supplied. Otherwise just the name is.
 *	@since 1.0
*/
function shd_profile_link($name, $id = 0)
{
	return empty($id) ? $name : '<a href="<URL>?action=profile;u=' . $id . '">' . $name . '</a>';
}

/**
 *	Establishes the next change of status of a ticket.
 *
 *	Tickets invariably have multiple changes of status during their life. All actions that could change
 *	a ticket's status should call here, even if it is a straight forward, one-route-only change of status
 *	since it is possible we could end up giving the user a choice one day over how statuses work, so
 *	we should route everything through here all the time.
 *
 *	@param string $action (required), represents the action carried out by the calling function
 *			Known values: new, resolve, unresolve, deleteticket, restoreticket, deletereply, restorereply, reply, topictoticket (new is default)
 *	@param int $starter_id Numeric id of the ticket's starter (should be provided)
 *	@param int $replier_id Numeric id of the ticket's last reply author (should be provided)
 *	@param int $replies Number of replies in the ticket (should be provided)
 *
 *	@return int Returns an integer value that corresponds to the ticket's status, relating to one of the TICKET_STATUS states.
 *	@since 1.0
*/
function shd_determine_status($action, $starter_id = 0, $replier_id = 0, $replies = -1, $dept = -1)
{
	static $staff = null;

	if (!isset($staff[$dept]))
		$staff[$dept] = shd_members_allowed_to('shd_staff', $dept);

	$known_states = array(
		'new',
		'resolve',
		'unresolve',
		'deleteticket',
		'restoreticket',
		'deletereply',
		'restorereply',
		'reply',
		'topictoticket',
	);

	if (!in_array($action, $known_states))
		$action = 'new';

	switch ($action)
	{
		case 'new':
			return TICKET_STATUS_NEW; // it's a new ticket, what more can I say?
		case 'resolve':
			return TICKET_STATUS_CLOSED; // yup, all done
		case 'deleteticket':
			return TICKET_STATUS_DELETED; // bye bye
		case 'deletereply':
		case 'restorereply':
		case 'unresolve':
		case 'restoreticket':
		case 'reply':
		case 'topictoticket':
			if ($replies == 0)
				return TICKET_STATUS_NEW;
			else
			{
				if (in_array($replier_id, $staff[$dept]))
					$new_status = $starter_id == $replier_id ? TICKET_STATUS_PENDING_STAFF : TICKET_STATUS_PENDING_USER; // i.e. if they're staff but it's their own ticket they're replying to, it's not with user.
				else
					$new_status = TICKET_STATUS_PENDING_STAFF;

				return $new_status;
			}
	}
}

/**
 *	Wrapper function for constructPageIndex to forcibly block the extensible ... item in page indexes
 *
 *	WedgeDesk uses the core page index function in numerous places, but unlike Wedge, it often places it in containers
 *	that have backgrounds driven by menu_block.png, meaning that they are often fixed in height. Under some circumstances
 *	layout can be broken, so this function forcibly ensures the block can never expand to force wrapping.
 *
 *	@param string $base_url Form of URL pageindex links should take, using $1%d to represent the start point identifier.
 *	@param int &$start Position to start. If not a multiple of the number per page, it will be forced to become a multiple.
 *	@param int $max_value Number of items in total to paginate for.
 *	@param int $num_per_page Number of items to be shown on a page.
 *	@param bool $flexible_start Whether a more flexible % option is to be used in the base URL.
 *
 *	@return string The constructed page index, without Javascript expander(s).
 *	@since 1.0
*/
function shd_no_expand_pageindex($base_url, &$start, $max_value, $num_per_page, $flexible_start = false)
{
	return preg_replace('~<span([^<]+)~i', '<span style="font-weight: bold;"> ... ', constructPageIndex($base_url, $start, $max_value, $num_per_page, $flexible_start));
}

/**
 *	Clean up tickets that have been modified by replies being altered through restore, delete, and possibly other operations.
 *
 *	Operations:
 *	- Identify how many deleted and non deleted replies there are in the ticket.
 *	- Identify the last non deleted reply in the ticket (if there are no undeleted replies, use the ticket post itself for cohesion)
 *	- Update the ticket's record with the first and last posters, as well as the correct number of active and deleted replies, and whether there are any deleted replies on the ticket generally
 *
 *	@param int $ticket The ticket id to recalculate.
 *
 *	@return array An array detailing the user id of the starter, the last replier and the number of active replies in a ticket.
 *
 *	@since 1.0
*/
function shd_recalc_ids($ticket)
{
	$query = wesql::query('
		SELECT hdt.id_first_msg
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE hdt.id_ticket = {int:ticket}',
		array(
			'ticket' => $ticket,
		)
	);
	list($first_msg) = wesql::fetch_row($query);
	wesql::free_result($query);

	$query = wesql::query('
		SELECT hdtr.message_status, COUNT(hdtr.message_status) AS messages, hdt.id_first_msg, MAX(hdtr.id_msg) AS id_last_msg
		FROM {db_prefix}helpdesk_ticket_replies AS hdtr
			INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdt.id_ticket = hdtr.id_ticket)
		WHERE hdtr.id_msg > hdt.id_first_msg
			AND hdt.id_ticket = {int:ticket}
		GROUP BY hdtr.message_status',
		array(
			'ticket' => $ticket,
		)
	);

	$messages = array(
		MSG_STATUS_NORMAL => 0, // message_status = 0
		MSG_STATUS_DELETED => 0, // message_status = 1
	);

	$last_msg = 0;
	while ($row = wesql::fetch_assoc($query))
	{
		$first_msg = $row['id_first_msg'];
		$messages[$row['message_status']] = $row['messages'];
		if ($row['message_status'] == MSG_STATUS_NORMAL)
			$last_msg = $row['id_last_msg'];
	}

	wesql::free_result($query);

	if (empty($last_msg))
		$last_msg = $first_msg;

	// OK, so we have the last message id and correct number of replies, which is awesome. Now we need to ensure user ids are right
	$query = wesql::query('
		SELECT hdtr_first.id_member, hdtr_last.id_member
		FROM {db_prefix}helpdesk_tickets AS hdt
			INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr_first ON (hdtr_first.id_msg = {int:first_msg})
			INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr_last ON (hdtr_last.id_msg = {int:last_msg})
		WHERE hdt.id_ticket = {int:ticket}',
		array(
			'first_msg' => $first_msg,
			'last_msg' => $last_msg,
			'ticket' => $ticket,
		)
	);
	list($starter, $replier) = wesql::fetch_row($query);
	wesql::free_result($query);

	wesql::query('
		UPDATE {db_prefix}helpdesk_tickets
		SET num_replies = {int:num_replies},
			deleted_replies = {int:deleted_replies},
			id_last_msg = {int:last_msg},
			id_member_started = {int:starter},
			id_member_updated = {int:replier},
			withdeleted = {int:has_deleted}
		WHERE id_ticket = {int:ticket}',
		array(
			'num_replies' => $messages[MSG_STATUS_NORMAL],
			'deleted_replies' => $messages[MSG_STATUS_DELETED],
			'last_msg' => $last_msg,
			'starter' => $starter,
			'replier' => $replier,
			'has_deleted' => ($messages[MSG_STATUS_DELETED] > 0) ? 1 : 0,
			'ticket' => $ticket,
		)
	);

	return array($starter, $replier, $messages[MSG_STATUS_NORMAL]);
}

/**
 *	Load the user preferences for the given user.
 *
 *	@param mixed $user Normally, an int being the user id of the user whose preferences should be attempted to be loaded. If === false, return the list of default prefs (for the pref UI), or if 0 or omitted, load the current user.
 *
 *	@return array If $user === false, the list of options, their types and default values is returned. Otherwise, return an array of prefs (adjusted for this user)
 *	@since 2.0
*/
function shd_load_user_prefs($user = 0)
{
	global $settings;
	static $pref_groups = null, $base_prefs = null;

	if ($pref_groups === null)
	{
		$pref_groups = array(
			'display' => array(
				'icon' => 'preferences.png',
				'enabled' => true,
			),
			'notify' => array(
				'icon' => 'email.png',
				'enabled' => true,
				'check_all' => true,
			),
			'blocks' => array(
				'icon' => 'log.png',
				'enabled' => true,
			),
			'block_order' => array(
				'icon' => 'move_down.png',
				'enabled' => true,
			),
		);

		$base_prefs = array(
			'display_unread_type' => array(
				'options' => array(
					'none' => 'shd_pref_display_unread_none',
					'unread' => 'shd_pref_display_unread_unread',
					'outstanding' => 'shd_pref_display_unread_outstanding',
				),
				'default' => 'outstanding',
				'type' => 'select',
				'icon' => 'unread.png',
				'group' => 'display',
				'permission' => 'shd_staff',
				'show' => empty($settings['shd_helpdesk_only']) && empty($settings['shd_disable_unread']),
			),
			'display_order' => array(
				'options' => array(
					'asc' => 'shd_pref_display_order_asc',
					'desc' => 'shd_pref_display_order_desc',
				),
				'default' => 'asc',
				'type' => 'select',
				'icon' => 'move_down.png',
				'group' => 'display',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'blocks_assigned_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'assign.png',
				'group' => 'blocks',
				'permission' => 'shd_staff',
				'show' => true,
			),
			'blocks_new_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'status.png',
				'group' => 'blocks',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'blocks_staff_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'staff.png',
				'group' => 'blocks',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'blocks_user_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'user.png',
				'group' => 'blocks',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'blocks_closed_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'resolved.png',
				'group' => 'blocks',
				'permission' => array('shd_view_closed_own', 'shd_view_closed_any'),
				'show' => true,
			),
			'blocks_recycle_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'recycle.png',
				'group' => 'blocks',
				'permission' => 'shd_access_recyclebin',
				'show' => true,
			),
			'blocks_withdeleted_count' => array(
				'default' => 10,
				'type' => 'int',
				'icon' => 'recycle.png',
				'group' => 'blocks',
				'permission' => 'shd_access_recyclebin',
				'show' => true,
			),
			'notify_new_ticket' => array(
				'default' => 0,
				'type' => 'check',
				'icon' => 'log_newticket.png',
				'group' => 'notify',
				'permission' => 'shd_staff',
				'show' => !empty($settings['shd_notify_new_ticket']),
			),
			'notify_new_reply_own' => array(
				'default' => 1,
				'type' => 'check',
				'icon' => 'log_newreply.png',
				'group' => 'notify',
				'permission' => 'shd_new_ticket',
				'show' => !empty($settings['shd_notify_new_reply_own']),
			),
			'notify_new_reply_assigned' => array(
				'default' => 0,
				'type' => 'check',
				'icon' => 'log_assign.png',
				'group' => 'notify',
				'permission' => 'shd_staff',
				'show' => !empty($settings['shd_notify_new_reply_assigned']),
			),
			'notify_new_reply_previous' => array(
				'default' => 0,
				'type' => 'check',
				'icon' => 'log_newreply.png',
				'group' => 'notify',
				'permission' => 'shd_staff',
				'show' => !empty($settings['shd_notify_new_reply_previous']),
			),
			'notify_new_reply_any' => array(
				'default' => 0,
				'type' => 'check',
				'icon' => 'log_newreply.png',
				'group' => 'notify',
				'permission' => 'shd_staff',
				'show' => !empty($settings['shd_notify_new_reply_any']),
			),
			'notify_assign_me' => array(
				'default' => 0,
				'type' => 'check',
				'icon' => 'assign.png',
				'group' => 'notify',
				'permission' => 'shd_staff',
				'show' => !empty($settings['shd_notify_assign_me']),
			),
			'notify_assign_own' => array(
				'default' => 0,
				'type' => 'check',
				'icon' => 'assign.png',
				'group' => 'notify',
				'permission' => 'shd_new_ticket',
				'show' => !empty($settings['shd_notify_assign_own']),
			),
			'block_order_assigned_block' => array(
				'default' => 'updated_asc',
				'type' => 'select',
				'icon' => 'assign.png',
				'group' => 'block_order',
				'permission' => 'shd_staff',
				'show' => true,
			),
			'block_order_new_block' => array(
				'default' => 'updated_asc',
				'type' => 'select',
				'icon' => 'status.png',
				'group' => 'block_order',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'block_order_staff_block' => array(
				'default' => 'updated_asc',
				'type' => 'select',
				'icon' => 'staff.png',
				'group' => 'block_order',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'block_order_user_block' => array(
				'default' => 'updated_asc',
				'type' => 'select',
				'icon' => 'user.png',
				'group' => 'block_order',
				'permission' => 'access_helpdesk',
				'show' => true,
			),
			'block_order_closed_block' => array(
				'default' => 'updated_desc',
				'type' => 'select',
				'icon' => 'resolved.png',
				'group' => 'block_order',
				'permission' => array('shd_view_closed_own', 'shd_view_closed_any'),
				'show' => true,
			),
			'block_order_recycle_block' => array(
				'default' => 'updated_desc',
				'type' => 'select',
				'icon' => 'recycle.png',
				'group' => 'block_order',
				'permission' => 'shd_access_recyclebin',
				'show' => true,
			),
			'block_order_withdeleted_block' => array(
				'default' => 'updated_desc',
				'type' => 'select',
				'icon' => 'recycle.png',
				'group' => 'block_order',
				'permission' => 'shd_access_recyclebin',
				'show' => true,
			),
		);

		// We want to add the preferences per block. Because we already know what options there are per block elsewhere, let's reuse that.
		if (!function_exists('shd_get_block_columns'))
			loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk-TicketGrids');
		$blocks = array('assigned', 'new', 'staff', shd_allowed_to('shd_staff', 0) ? 'user_staff' : 'user_user', 'closed', 'recycled', 'withdeleted');
		foreach ($blocks as $block)
		{
			$items = shd_get_block_columns($block);
			if (empty($items))
				continue;

			if ($block == 'user_staff' || $block == 'user_user')
				$block = 'user';
			elseif ($block == 'recycled')
				$block = 'recycle';
			$block_id = 'block_order_' . $block . '_block';
			$base_prefs[$block_id]['options'] = array();
			foreach ($items as $item)
			{
				if ($item != 'actions')
				{
					$item = str_replace(array('_', 'startinguser'), array('', 'starter'), $item);
					$base_prefs[$block_id]['options'][$item . '_asc'] = 'shd_pref_block_order_' . $item . '_asc';
					$base_prefs[$block_id]['options'][$item . '_desc'] = 'shd_pref_block_order_' . $item . '_desc';
				}
			}
		}

		// Now engage any hooks.
		call_hook('shd_hook_prefs', array(&$pref_groups, &$base_prefs));

		foreach ($base_prefs as $pref => $details)
		{
			if (empty($pref_groups[$details['group']]['enabled']) || empty($details['show']))
				unset($base_prefs[$pref]);
		}
	}

	// Do we just want the prefs list?
	if ($user === false)
		return array(
			'groups' => $pref_groups,
			'prefs' => $base_prefs,
		);

	$prefs = array();
	if ($user == 0 || $user == MID)
	{
		$user = MID;

		// Start with the defaults, but dealing with permissions as we go
		foreach ($base_prefs as $pref => $details)
		{
			if (empty($details['permission']) || shd_allowed_to($details['permission'], 0))
				$prefs[$pref] = $details['default'];
		}
	}
	else
	{
		foreach ($base_prefs as $pref => $details)
		{
			if (empty($details['permission']))
				continue;

			if (is_array($details['permission']))
			{
				foreach ($details['permission'] as $perm)
					if (in_array($user, shd_members_allowed_to($perm)))
					{
						$prefs[$pref] = $details['default'];
						break;
					}
			}
			else
			{
				if (in_array($user, shd_members_allowed_to($details['permission'])))
					$prefs[$pref] = $details['default'];
			}
		}
	}

	// Now, the database
	$query = wesql::query('
		SELECT variable, value
		FROM {db_prefix}helpdesk_preferences
		WHERE id_member = {int:user}',
		array(
			'user' => (int) $user,
		)
	);

	while ($row = wesql::fetch_assoc($query))
	{
		if (isset($prefs[$row['variable']]))
			$prefs[$row['variable']] = $row['value'];
	}

	return $prefs;
}

/**
 *	Adds the button to the thread view for moving topics into the helpdesk, if appropriate.
 *
 *	This explicitly relies on the display template hook for such things. If the theme does not provide it, the theme author needs to update their theme.
 *
 *	@since 2.0
*/
function shd_display_btn_mvtopic()
{
	global $context, $settings;

	if (!empty($settings['helpdesk_active']) && empty($settings['shd_disable_tickettotopic']) && empty($settings['shd_helpdesk_only']) && shd_allowed_to('shd_topic_to_ticket', 0))
		$context['nav_buttons']['normal']['topictoticket'] = array(
			'text' => 'shd_move_topic_to_ticket',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=topictoticket;topic=' . $context['current_topic'] . ';' . $context['session_query']
		);
}

/**
 *	Adds the WedgeDesk action to the action list, and also handles most of the shutting down of forum items in helpdesk-only mode.
 *
 *	@param string &$actionArray The master list of actions from index.php
 *
 *	@since 2.0
*/
function shd_init_actions()
{
	global $settings, $context, $action_list;

	if (empty($settings['helpdesk_active']))
		return;

	// Rewrite the array for unread purposes.
	$context['shd_unread_actions'] = array(
		'unread' => $action_list['unread'],
		'unreadreplies' => $action_list['unreadreplies'],
	);
	$action_list['unread'] = array('src/WedgeDesk-Unread', 'shd_unread_posts', 'Arantor:WedgeDesk');
	$action_list['unreadreplies'] = array('src/WedgeDesk-Unread', 'shd_unread_posts', 'Arantor:WedgeDesk');

	if (!empty($settings['shd_helpdesk_only']))
	{
		// Firstly, remove all the standard actions we neither want nor need.
		// Note we did this to prevent breakage of other mods that may be installed, e.g. gallery or portal or something.
		$unwanted_actions = array('announce', 'attachapprove', 'boards', 'buddy', 'calendar', 'collapse', 'deletemsg', 'display', 'emailuser',
			'feed', 'findmember', 'lock', 'markasread', 'mergeposts', 'mergetopics', 'moderate', 'movetopic', 'movetopic2', 'notify', 'notifyboard',
			'poll', 'post', 'post2', 'printpage', 'quotefast', 'quickmod', 'quickmod2', 'recent', 'removetopic2', 'report', 'restoretopic',
			'search', 'search2', 'sendtopic', 'splittopics', 'stats', 'sticky', 'unread', 'unreadreplies', 'viewquery', 'who', '.xml');

		// that's the generic stuff, now for specific options
		if (!empty($settings['shd_disable_pm']))
			$unwanted_actions[] = 'pm';

		if (!empty($settings['shd_disable_mlist']))
			$unwanted_actions[] = 'mlist';

		foreach ($unwanted_actions as $unwanted)
			unset($action_list[$unwanted]);

		// Secondly, rewrite the defaults to point to helpdesk, for unknown actions. I'm doing this rather than munging the main code - easier to unbreak stuff
		if (empty($action_list[$_GET['action']]))
			$_GET['action'] = 'helpdesk';
	}
}

/**
 *	Last-minute buffer replacements to be made, e.g. removing unwanted content in helpdesk-only mode.
 *
 *	@since 2.0
*/
function shd_buffer_replace($buffer)
{
	global $settings, $context;

	if (!empty($settings['helpdesk_active']))
	{
		$shd_replacements = array();
		$shd_preg_replacements = array();

		// If we're in helpdesk standalone mode, purge unread type links
		if (!empty($settings['shd_helpdesk_only']))
		{
			$shd_preg_replacements += array(
				'~<a(.+)action=unread(.+)</a>~iuU' => '',
				'~<form([^<]+)action=search2(.+)</form>~iuUs' => '',
			);
		}

		if (!empty($context['shd_buffer_replacements']))
			$shd_replacements += $context['shd_buffer_replacements'];
		if (!empty($context['shd_buffer_preg_replacements']))
			$shd_preg_replacements += $context['shd_buffer_preg_replacements'];

		if (!empty($shd_replacements)) // no sense doing preg when regular will do
			$buffer = str_replace(array_keys($shd_replacements), array_values($shd_replacements), $buffer);
		if (!empty($shd_preg_replacements))
			$buffer = preg_replace(array_keys($shd_preg_replacements), array_values($shd_preg_replacements), $buffer);
	}

	// And any replacements a buffer might want to make...
	call_hook('shd_hook_buffer', array(&$buffer));

	return $buffer;
}

/**
 *	Add the WedgeDesk options to the main site menu.
 *
 *	@param array &$menu_buttons The main menu buttons as provided by Subs.php.
 *	@since 2.0
*/
function shd_main_menu(&$menu_buttons)
{
	global $context, $txt, $settings;

	if (!empty($settings['helpdesk_active']))
	{
		// Stuff we'll always do in SD if active
		$helpdesk_admin = we::$is_admin || shd_allowed_to('admin_helpdesk', 0);

		// 1. Add the main menu if we can.
		if (shd_allowed_to(array('access_helpdesk', 'admin_helpdesk'), 0) && empty($settings['shd_hidemenuitem']))
		{
			// Try a list of possible places to put it. This is more being cautious than anything.
			$order = array('search', 'profile', 'forum', 'pm', 'help', 'home');
			$pos = null;
			foreach ($order as $item)
				if (isset($menu_buttons[$item]))
				{
					$pos = $item;
					break;
				}

			$helpdesk_menu = array(
				'helpdesk' => array(
					'title' => $txt['shd_helpdesk'],
					'href' => '<URL>?action=helpdesk;sa=main',
					'show' => true,
					'active_button' => false,
					'sub_items' => array(
						'newticket' => array(
							'title' => $txt['shd_new_ticket'],
							'href' => '<URL>?action=helpdesk;sa=newticket',
							'show' => WEDGE == 'SSI' ? false : shd_allowed_to('shd_new_ticket', 0),
						),
						'newproxyticket' => array(
							'title' => $txt['shd_new_ticket_proxy'],
							'href' => '<URL>?action=helpdesk;sa=newticket;proxy',
							'show' => WEDGE == 'SSI' ? false : shd_allowed_to('shd_new_ticket', 0) && shd_allowed_to('shd_post_proxy', 0),
						),
						'closedtickets' => array(
							'title' => $txt['shd_tickets_closed'],
							'href' => '<URL>?action=helpdesk;sa=closedtickets',
							'show' => WEDGE == 'SSI' ? false : shd_allowed_to(array('shd_view_closed_own', 'shd_view_closed_any'), 0),
						),
						'recyclebin' => array(
							'title' => $txt['shd_recycle_bin'],
							'href' => '<URL>?action=helpdesk;sa=recyclebin',
							'show' => WEDGE == 'SSI' ? false : shd_allowed_to('shd_access_recyclebin', 0),
						),
					),
				),
			);
			if ($settings['helpdesk_active'] && WEDGE != 'SSI')
				$helpdesk_menu['helpdesk']['notice'] = shd_get_active_tickets();

			if ($helpdesk_admin)
				$helpdesk_menu['helpdesk']['sub_items']['admin'] = array(
					'title' => $txt['admin'],
					'href' => '<URL>?action=admin;area=helpdesk_info',
					'show' => WEDGE == 'SSI' ? false : empty($settings['shd_hidemenuitem']) && $helpdesk_admin,
					'sub_items' => shd_main_menu_admin($helpdesk_admin),
				);

			array_insert($menu_buttons, $pos, $helpdesk_menu, 'after');
		}

		// Add the helpdesk admin option to the admin menu, if board integration is disabled.
		if (!empty($settings['shd_hidemenuitem']) && $helpdesk_admin)
		{
			// Make sure the button is visible if you can admin forum
			$menu_buttons['admin']['show'] = true;

			// Add the new button
			$menu_buttons['admin']['sub_items']['helpdesk_admin'] = array(
				'title' => $txt['shd_helpdesk'],
				'href' => '<URL>?action=admin;area=helpdesk_info',
				'show' => true,
				'sub_items' => shd_main_menu_admin($helpdesk_admin),
			);
		}

		if (shd_allowed_to(array('shd_view_profile_own', 'shd_view_profile_any'), 0))
		{
			// If we're in HD only mode, we definitely don't want the regular forum profile item.
			if (!empty($settings['shd_helpdesk_only']))
			{
				$menu_buttons['profile']['sub_items']['profile']['show'] = false;
				$menu_buttons['profile']['sub_items']['summary']['show'] = false;
			}

			// Add the helpdesk profile to the profile menu (either the original or our reconstituted one)
			$menu_buttons['profile']['show'] = true;
			$menu_buttons['profile']['sub_items']['hd_profile'] = array(
				'title' => $txt['shd_helpdesk_profile'],
				'href' => '<URL>?action=profile;area=helpdesk',
				'show' => true,
			);
		}

		// Stuff we'll only do if in standalone mode
		if (!empty($settings['shd_helpdesk_only']))
		{
			$menu_buttons['home'] = array(
				'title' => $txt['shd_helpdesk'],
				'href' => '<URL>?action=helpdesk;sa=main',
				'show' => $settings['helpdesk_active'],
				'sub_items' => array(
					'newticket' => array(
						'title' => $txt['shd_new_ticket'],
						'href' => '<URL>?action=helpdesk;sa=newticket',
						'show' => WEDGE == 'SSI' ? false : shd_allowed_to('shd_new_ticket', 0),
					),
					'newproxyticket' => array(
						'title' => $txt['shd_new_ticket_proxy'],
						'href' => '<URL>?action=helpdesk;sa=newticket;proxy',
						'show' => WEDGE == 'SSI' ? false : shd_allowed_to('shd_new_ticket', 0) && shd_allowed_to('shd_post_proxy', 0),
					),
					'closedtickets' => array(
						'title' => $txt['shd_tickets_closed'],
						'href' => '<URL>?action=helpdesk;sa=closedtickets',
						'show' => WEDGE == 'SSI' ? false : shd_allowed_to(array('shd_view_closed_own', 'shd_view_closed_any'), 0),
					),
					'recyclebin' => array(
						'title' => $txt['shd_recycle_bin'],
						'href' => '<URL>?action=helpdesk;sa=recyclebin',
						'show' => WEDGE == 'SSI' ? false : shd_allowed_to('shd_access_recyclebin', 0),
					),
				),
				'active_button' => false,
			);
			if ($settings['helpdesk_active'] && WEDGE != 'SSI')
				$menu_buttons['home']['notice'] = shd_get_active_tickets();
			if ($helpdesk_admin)
				$menu_buttons['home']['sub_items']['admin'] = array(
					'title' => $txt['admin'],
					'href' => '<URL>?action=admin;area=helpdesk_info',
					'show' => WEDGE == 'SSI' ? false : empty($settings['shd_hidemenuitem']) && $helpdesk_admin,
					'sub_items' => shd_main_menu_admin($helpdesk_admin),
				);

			$item = false;
			foreach ($menu_buttons['home']['sub_items'] as $key => $value)
				if (empty($value['show']))
					unset($menu_buttons['home']['sub_items'][$key]);

			unset($menu_buttons['helpdesk']);

			// Disable help, search, calendar, moderation center
			foreach (array('help', 'search', 'calendar') as $item)
				$menu_buttons[$item]['show'] = false;

			$context['allow_search'] = false;
			$context['allow_calendar'] = false;
			$context['allow_moderation_center'] = false;

			// Disable PMs
			if (!empty($settings['shd_disable_pm']))
			{
				$context['allow_pm'] = false;
				$menu_buttons['pm']['show'] = false;
				we::$user['unread_messages'] = 0; // to disable it trying to add to the menu item
			}

			// Disable memberlist
			if (!empty($settings['shd_disable_mlist']))
			{
				$context['allow_memberlist'] = false;
				$menu_buttons['mlist']['show'] = false;
			}
		}
	}
}

function shd_main_menu_admin($helpdesk_admin)
{
	global $txt;

	if (WEDGE == 'SSI' || !$helpdesk_admin)
		return array();

	return array(
		'information' => array(
			'title' => $txt['shd_admin_info'],
			'href' => '<URL>?action=admin;area=helpdesk_info',
			'show' => true,
		),
		'options' => array(
			'title' => $txt['shd_admin_options'],
			'href' => '<URL>?action=admin;area=helpdesk_options',
			'show' => true,
		),
		'cannedreplies' => array(
			'title' => $txt['shd_admin_cannedreplies'],
			'href' => '<URL>?action=admin;area=helpdesk_cannedreplies',
			'show' => true,
		),
		'custom_fields' => array(
			'title' => $txt['shd_admin_custom_fields'],
			'href' => '<URL>?action=admin;area=helpdesk_customfield',
			'show' => true,
		),
		'departments' => array(
			'title' => $txt['shd_admin_departments'],
			'href' => '<URL>?action=admin;area=helpdesk_depts',
			'show' => true,
		),
		'permissions' => array(
			'title' => $txt['shd_admin_permissions'],
			'href' => '<URL>?action=admin;area=helpdesk_permissions',
			'show' => true,
		),
		'maintenance' => array(
			'title' => $txt['shd_admin_maint'],
			'href' => '<URL>?action=admin;area=helpdesk_maint',
			'show' => true,
		),
	);
}
// Cause IE is being mean to meeee again...!

function shd_credits()
{
	global $context, $txt;
	$context['copyrights']['mods'][] = $txt['shd_copyright'];
	// We also use the Fugue icons.
	$context['copyrights']['images']['fugue'] = '<a href="http://p.yusukekamiyamane.com/">Fugue</a> &copy; Y&#363;suke Kamiyamane';
	$context['copyrights']['images']['crystal'] = '<a href="http://www.everaldo.com/crystal/">Crystal</a> &copy; Everaldo.com';
}

function shd_default_action()
{
	global $settings;
	if (!empty($settings['helpdesk_active']) && !empty($settings['shd_helpdesk_only']))
	{
		loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk');
		return 'shd_main';
	}
	else
		return false;
}
?>