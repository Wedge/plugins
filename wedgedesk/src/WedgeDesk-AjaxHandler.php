<?php
/**
 * WedgeDesk
 *
 * This file handles all the actions that can be carried out through AJAX methods, performing them and providing adequate feedback/information.
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

// This file handles AJAX actions. This file accepts params and only dumps XML out, much like News.php we do it all here.
// What happens is we expect $context['ajax_return'], an array of responses, to be populated.
// key => value for single XML tag, key => array (values) for multiple tags
// e.g. array('errors' => 'You do not have permission') => <errors><![CDATA[You do not have permission]]></errors>
// or array('data' => array('myval1', 'myval2')) => <data><![CDATA[myval1]]></data> <data><![CDATA[myval2]]></data>

/**
 *	Receives AJAX requests and facilitates replying to them.
 *
 *	This function sets up and calls the AJAX handlers; it is the primary receiver for action=helpdesk;sa=ajax.
 *
 *	Primarily, it expects $context['ajax_return'] to be populated as an array of tags to be returned as XML items to the user, with this element
 *	being a key-value pair (e.g. $context['ajax_return']['message'] = 'Success!') to be returned as a literal tag, element message, value of
 *	Success!, wrapped in the character data block.
 *
 *	An AJAX handler function may also return content in $context['ajax_raw'], this is when the function has prepared its own XML block to return.
 *	In such an instance, this function simply outputs the xml headers and assumes the return value is otherwise value.
 *
 *	For example, if the error subarray is populated (['error'] = 'Insufficient permission'), the XML block will contain a tag called error,
 *	with a CDATA block containing 'Insufficient permission'.
 *
 *	@since 1.0
*/
function shd_ajax()
{
	global $context, $txt;

	// Just in case
	loadLanguage('Errors');

	$subactions = array(
		'quote' => array('shd_ajax_quote', false),
		'canned' => array('shd_ajax_canned', false),
		'assign' => array('shd_ajax_assign', true),
		'assign2' => array('shd_ajax_assign2', true),
		'notify' => array('shd_ajax_notify', true),
		'ajax_privacy' => array('shd_ajax_ajax_privacy', true),
		'ajax_privacy2' => array('shd_ajax_ajax_privacy2', true),
		'ajax_urgency' => array('shd_ajax_ajax_urgency', true),
		'ajax_urgency2' => array('shd_ajax_ajax_urgency2', true),
	);

	$context['ajax_return'] = array();
	$context['ajax_raw'] = '';

	if (!empty($_REQUEST['op']) && !empty($subactions[$_REQUEST['op']]))
	{
		if (!empty($subactions[$_REQUEST['op']][1]))
		{
			$session_check = checkSession('get', '', false); // check the session but don't die fatally.
			if (!empty($session_check))
				$context['ajax_return'] = array('error' => $txt[$session_check]);
		}

		if (empty($context['ajax_return']))
			$subactions[$_REQUEST['op']][0]();
	}

	if (!empty($context['use_rewrite_buffer']))
		ob_start('ob_sessrewrite');

	header('Content-Type: text/xml; charset=UTF-8');
	echo '<?xml version="1.0" encoding="UTF-8"?' . '>';

	if (empty($context['ajax_raw'])) // if something wants to do something funky, let it otherwise use the standard format
	{
		echo '<response>';

		if (!empty($context['ajax_return']))
		{
			foreach ($context['ajax_return'] as $key => $value)
			{
				if (empty($value)) // for <tag>
					echo '
	<', $key, '>';
				else
				{
					$value = (array) $value;
					foreach ($value as $thisvalue)
						echo '
	<', $key, '><![CD', 'ATA[', $thisvalue, ']', ']></', $key, '>';
				}
			}
		}

		echo '
</response>';
	}
	else
	{
		echo $context['ajax_raw']; // assumed to be just well formed XML sans the header
	}
	obExit(false);
}

/**
 *	Collects ticket post data for quoting posts through AJAX (i.e. inserting a quote live into the postbox)
 *
 *	Operations:
 *	- Session check; failing in a regular fashion (as opposed to normal return since we're using ;xml in the URL; the Wedge handler can deal with that)
 *	- If a message id is provided, query for it. If not found (or not provided), die, otherwise continue.
 *	- Call un_preparsecode to remove extraneous sanity encoding.
 *	- Build the [quote] bbcode around the post body.
 *	- Convert to Wedge style BBC-to-HTML if using WYSIWYG
 *	- Do other XML sanitising
 *	- Return via $context['ajax_raw'] for {@link shd_ajax()} to output
*/
function shd_ajax_quote()
{
	global $context;

	loadLanguage('Post');
	checkSession('get');

	loadSource('Class-Editor');

	$_REQUEST['quote'] = !empty($_REQUEST['quote']) ? (int) $_REQUEST['quote'] : 0;
	$message = '';
	if (!empty($_REQUEST['quote']))
	{
		$query = wesql::query('
			SELECT hdtr.body, IFNULL(mem.real_name, hdtr.poster_name) AS poster_name, hdtr.poster_time, hdt.id_ticket, hdt.id_first_msg
			FROM {db_prefix}helpdesk_ticket_replies AS hdtr
				INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdtr.id_ticket = hdt.id_ticket)
				LEFT JOIN {db_prefix}members AS mem ON (hdtr.id_member = mem.id_member)
			WHERE {query_see_ticket}
				AND id_msg = {int:msg}',
			array(
				'msg' => $_REQUEST['quote'],
			)
		);

		if ($row = wesql::fetch_assoc($query))
		{
			wesql::free_result($query);
			$row['body'] = wedit::un_preparsecode($row['body']);

			// Censor the message!
			censorText($row['body']);
			$row['body'] = preg_replace('~<br ?/?' . '>~i', "\n", $row['body']);

			if (strpos($row['poster_name'], '[') !== false || strpos($row['poster_name'], ']') !== false)
				$row['poster_name'] = '"' . $row['poster_name'] . '"';

			// Make the body HTML if need be.
			if (!empty($_REQUEST['mode']))
			{
				$row['body'] = strtr($row['body'], array('&lt;' => '#smlt#', '&gt;' => '#smgt#', '&amp;' => '#smamp#'));
				$row['body'] = wedit::bbc_to_html($row['body']);
				$lb = '<br>';
			}
			else
				$lb = "\n";

			$message = '[quote author=' . $row['poster_name'] . ' link=action=helpdesk;sa=ticket;ticket=' . $row['id_ticket'];
			if ($row['id_first_msg'] != $_REQUEST['quote']) // don't add the msg if we're quoting the ticket itself
				$message .= '.msg' . $_REQUEST['quote'] . '#msg' . $_REQUEST['quote'];

			$message .= ' date=' . $row['poster_time'] . ']' . $lb . $row['body'] . $lb . '[/quote]';
		}
	}

	$message = strtr($message, array('&nbsp;' => '&#160;', '<' => '&lt;', '>' => '&gt;'));

	$context['ajax_raw'] = '<quote>' . $message . '</quote>';
}

/**
 *	Collects a canned reply from the database and serves it via XML for insertion.
 *
 *	Operations:
 *	- Session check; failing in a regular fashion (as opposed to normal return since we're using ;xml in the URL; the Wedge handler can deal with that)
 *	- Checks for a department number in the URL, validates access to that department then queries for the requested template.
 *	- Call un_preparsecode to remove extraneous sanity encoding.
 *	- Convert to Wedge style BBC-to-HTML if using WYSIWYG
 *	- Do other XML sanitising
 *	- Return via $context['ajax_raw'] for {@link shd_ajax()} to output
*/
function shd_ajax_canned()
{
	global $context;

	loadLanguage('Post');
	checkSession('get');

	loadSource('Class-Editor');

	$_REQUEST['reply'] = !empty($_REQUEST['reply']) ? (int) $_REQUEST['reply'] : 0;
	$message = '';
	if (!empty($_REQUEST['reply']) && !empty($context['ticket_id']))
	{
		$query = wesql::query('
			SELECT hdt.id_member_started, hdt.id_dept, hdcr.body, hdcr.vis_user, hdcr.vis_staff
			FROM {db_prefix}helpdesk_tickets AS hdt
				INNER JOIN {db_prefix}helpdesk_cannedreplies_depts AS hdcrd ON (hdt.id_dept = hdcrd.id_dept)
				INNER JOIN {db_prefix}helpdesk_cannedreplies AS hdcr ON (hdcrd.id_reply = hdcr.id_reply)
			WHERE hdt.id_ticket = {int:ticket}
				AND hdcr.id_reply = {int:reply}
				AND hdcr.active = 1
				AND {query_see_ticket}',
			array(
				'ticket' => $context['ticket_id'],
				'reply' => $_REQUEST['reply'],
			)
		);
		if (wesql::num_rows($query) == 0)
		{
			wesql::free_result($query);
			return $context['ajax_raw'] = '<quote>' . $message . '</quote>';
		}

		$row = wesql::fetch_assoc($query);
		wesql::free_result($query);

		// Check ability to reply to this ticket. No ability to reply at all, no canned reply.
		if (!shd_allowed_to('shd_reply_ticket_own', $row['id_dept']) && !shd_allowed_to('shd_reply_ticket_any', $row['id_dept']))
			return $context['ajax_raw'] = '<quote>' . $message . '</quote>';

		// Now check for can-reply-to-own (reply to any will pass this check correctly anyway)
		if (!shd_allowed_to('shd_reply_ticket_any', $row['id_dept']) && shd_allowed_to('shd_reply_ticket_own', $row['id_dept']) && $row['id_member_started'] != MID)
			return $context['ajax_raw'] = '<quote>' . $message . '</quote>';

		// Now verify the per-reply visibility. Only applies to non admins anyway...
		if (!shd_allowed_to('admin_helpdesk', $row['id_dept']) && !we::$is_admin)
		{
			if (shd_allowed_to('shd_staff', $row['id_dept']) && empty($row['vis_staff']))
				return $context['ajax_raw'] = '<quote>' . $message . '</quote>';
			elseif (!shd_allowed_to('shd_staff', $row['id_dept']) && empty($row['vis_user']))
				return $context['ajax_raw'] = '<quote>' . $message . '</quote>';
		}

		$message = wedit::un_preparsecode($row['body']);

		// Censor the message!
		censorText($message);
		$message = preg_replace('~<br ?/?' . '>~i', "\n", $row['body']);

		// Make the body HTML if need be.
		if (!empty($_REQUEST['mode']))
		{
			$message = strtr($message, array('&lt;' => '#smlt#', '&gt;' => '#smgt#', '&amp;' => '#smamp#'));
			$message = wedit::bbc_to_html($message);
			$lb = '<br>';
		}
		else
			$lb = "\n";
	}

	$message = strtr($message, array('&nbsp;' => '&#160;', '<' => '&lt;', '>' => '&gt;'));

	$context['ajax_raw'] = '<quote>' . $message . '</quote>';
}

/**
 *	Returns the list of possible assignees for a ticket for AJAX assignment purposes.
 *
 *	Operations:
 *	- Session check
 *	- Permissions check (that you can assign a ticket to someone else); if you can't assign a ticket to someone else, bail.
 *	- Get the list of information for a ticket (which implicitly checks ticket access); if you can't see the ticket, bail.
 *	- Get the list of who can be assigned a ticket.
 *	- Return that via AJAX.
*/
function shd_ajax_assign()
{
	global $context, $txt, $user_profile;

	if (!empty($context['ticket_id']))
	{
		$query = wesql::query('
			SELECT hdt.private, hdt.id_member_started, id_member_assigned, id_dept, hdt.status, 1 AS valid
			FROM {db_prefix}helpdesk_tickets AS hdt
			WHERE {query_see_ticket}
				AND hdt.id_ticket = {int:ticket}',
			array(
				'ticket' => $context['ticket_id'],
			)
		);
		if (wesql::num_rows($query) != 0)
			list($private, $ticket_starter, $ticket_assigned, $dept, $status, $valid) = wesql::fetch_row($query);
		wesql::free_result($query);
	}
	if (empty($valid))
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk-Assign');
	$assignees = shd_get_possible_assignees($private, $ticket_starter, $dept);
	array_unshift($assignees, 0); // add the unassigned option in at the start

	if (empty($assignees))
		return $context['ajax_return'] = array('error' => $txt['shd_no_staff_assign']);

	if (!shd_allowed_to('shd_assign_ticket_any', $dept) || $status == TICKET_STATUS_CLOSED || $status == TICKET_STATUS_DELETED)
		return $context['ajax_return'] = array('error' => $txt['shd_cannot_assign']);

	// OK, so we have the general values we need. Let's get user names, and get ready to kick this back to the user. We'll build the XML here though.
	loadMemberData($assignees);

	// Just out of interest, who's an admin?
	$admins = shd_members_allowed_to('admin_helpdesk', $dept);

	$context['ajax_raw'] = '<response>';
	foreach ($assignees as $assignee)
		$context['ajax_raw'] .= '
<item id="' . $assignee . '"' . (!empty($assignee) ? (in_array($assignee, $admins) ? ' img="admin"' : ' img="staff"') : '') . '><![CD' . 'ATA[' .(empty($assignee) ? '<span class="error">' . $txt['shd_unassigned'] . '</span>' : $user_profile[$assignee]['member_name']) . ']' . ']></item>';

	$context['ajax_raw'] .= '
</response>';
}

/**
 *	Action a new assignment via AJAX.
 *
 *	Operations:
 *	- Session check
 *	- Permissions check (that you can assign a ticket to someone else); if you can't assign a ticket to someone else, bail.
 *	- Get the list of information for a ticket (which implicitly checks ticket access); if you can't see the ticket, bail.
 *	- Get the list of who can be assigned a ticket; if requested user not on that list, bail.
 *	- Update and build return status, and return via AJAX.
 */
function shd_ajax_assign2()
{
	global $context, $txt, $user_profile;

	if (!empty($context['ticket_id']))
	{
		$query = wesql::query('
			SELECT hdt.private, hdt.id_member_started, id_member_assigned, subject, id_dept, hdt.status, 1 AS valid
			FROM {db_prefix}helpdesk_tickets AS hdt
			WHERE {query_see_ticket}
				AND hdt.id_ticket = {int:ticket}',
			array(
				'ticket' => $context['ticket_id'],
			)
		);
		if (wesql::num_rows($query) != 0)
			list($private, $ticket_starter, $ticket_assigned, $subject, $dept, $status, $valid) = wesql::fetch_row($query);
		wesql::free_result($query);
	}
	if (empty($valid))
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	if (!isset($_POST['newval']) || !is_numeric($_POST['newval']))
		return $context['ajax_return'] = array('error' => $txt['shd_assigned_not_permitted']);

	if (!shd_allowed_to('shd_assign_ticket_any', $dept) || $status == TICKET_STATUS_CLOSED || $status == TICKET_STATUS_DELETED)
		return $context['ajax_return'] = array('error' => $txt['shd_cannot_assign']);

	$_POST['newval'] = isset($_POST['newval']) ? (int) $_POST['newval'] : 0;

	loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk-Assign');
	$assignees = shd_get_possible_assignees($private, $ticket_starter, $dept);
	array_unshift($assignees, 0); // add the unassigned option in at the start

	if (!in_array($_POST['newval'], $assignees))
		return $context['ajax_return'] = array('error' => $txt['shd_assigned_not_permitted']);

	if (!empty($_POST['newval']))
		loadMemberData($_POST['newval']);

	$user_name = shd_profile_link(empty($_POST['newval']) ? '<span class="error">' . $txt['shd_unassigned'] . '</span>' : $user_profile[$_POST['newval']]['member_name'], $_POST['newval']);

	// Make sure the name is properly formatted.
	$context['use_rewrite_buffer'] = true;

	// If it's being assigned to the current assignee, don't bother actually requesting the change.
	if ($_POST['newval'] != $ticket_assigned)
	{
		$log_params = array(
			'subject' => $subject,
			'ticket' => $context['ticket_id'],
			'user_id' => $_POST['newval'],
			'user_name' => $user_name,
		);
		shd_log_action('assign', $log_params);
		shd_commit_assignment($context['ticket_id'], $_POST['newval'], true);
	}

	return $context['ajax_return'] = array('item' => $user_name);
}

/**
 *	Provide the list of possible notification recipients.
 *
 *	@since 2.0
*/
function shd_ajax_notify()
{
	global $txt, $context, $user_profile, $settings;

	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskNotifications');
	loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk-Notifications');

	if (!empty($context['ticket_id']))
	{
		$query = wesql::query('
			SELECT hdt.private, hdt.id_member_started, id_member_assigned, id_dept, status
			FROM {db_prefix}helpdesk_tickets AS hdt
			WHERE {query_see_ticket}
				AND hdt.id_ticket = {int:ticket}',
			array(
				'ticket' => $context['ticket_id'],
			)
		);
		if (wesql::num_rows($query) != 0)
			$ticket = wesql::fetch_assoc($query);
		wesql::free_result($query);
	}
	if (empty($ticket) || !shd_allowed_to('shd_singleton_email', $ticket['id_dept']) || $ticket['status'] == TICKET_STATUS_CLOSED || $ticket['status'] == TICKET_STATUS_DELETED)
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	// So, we need to start figuring out who's going to be notified, who won't be and who we might be interested in notifying.
	$notify_list = array(
		'being_notified' => array(),
		'optional' => array(),
		'optional_butoff' => array(),
	);

	// Let's get all the possible actual people. The possible people who can be notified... well, they're staff.
	$staff = shd_get_visible_list($ticket['id_dept'], $ticket['private'], $ticket['id_member_started'], empty($settings['shd_admins_not_assignable']), false);

	// Let's start figuring it out then! First, get the big ol' lists.
	$query = wesql::query('
		SELECT id_member, notify_state
		FROM {db_prefix}helpdesk_notify_override
		WHERE id_ticket = {int:ticket}',
		array(
			'ticket' => $context['ticket_id'],
		)
	);
	while ($row = wesql::fetch_assoc($query))
		$notify_list[$row['notify_state'] == NOTIFY_NEVER ? 'optional_butoff' : 'being_notified'][$row['id_member']] = true;

	// Did we exclude admins? If we did, we would have scooped the list of admins. If they're in the 'not being notified but you can...' list, remove them.
	if (!empty($context['list_admin_exclude']))
	{
		foreach ($context['list_admin_exclude'] as $user_id)
			if (isset($notify_list['optional_butoff'][$user_id]))
				unset($notify_list['optional_butoff'][$user_id]);
	}

	// Now we get the list by preferences. This is where it starts to get complicated.
	$possible_members = array();
	// People who want replies to their own ticket, without including the ticket starter because they'd know about it...
	if (!empty($settings['shd_notify_new_reply_own']) && MID != $ticket['id_member_started'])
		$possible_members[$ticket['id_member_started']]['new_reply_own'] = true;
	// The ticket is assigned to someone and they want to be notified if it changes.
	if (!empty($settings['shd_notify_new_reply_assigned']) && !empty($ticket['id_member_assigned']) && MID != $ticket['id_member_assigned'])
		$possible_members[$ticket['id_member_assigned']]['new_reply_assigned'] = true;
	// So, if you're staff, and you've replied to this ticket before, do you want to be notified this time?
	if (!empty($settings['shd_notify_new_reply_previous']))
	{
		$query = wesql::query('
			SELECT id_member
			FROM {db_prefix}helpdesk_ticket_replies
			WHERE id_ticket = {int:ticket}
			GROUP BY id_member',
			array(
				'ticket' => $context['ticket_id'],
			)
		);
		$responders = array();
		while ($row = wesql::fetch_row($query))
			$responders[] = $row[0]; // this shouldn't be nil, ever, because we're replying, so the topic already exists so there's at least one name in there...
		wesql::free_result($query);

		$responders = array_intersect($responders, $staff);
		foreach ($responders as $id)
			$possible_members[$id]['new_reply_previous'] = true;
	}
	// If you're staff, did you have 'spam my inbox every single time' selected?
	if (!empty($settings['shd_notify_new_reply_any']))
		foreach ($staff as $id)
			$possible_members[$id]['new_reply_any'] = true;

	// Now we have the list of possibles, exclude everyone who is either set to on, or off, since we don't need to query those for preferences.
	foreach ($possible_members as $id => $type_list)
		if (isset($notify_list['being_notified'][$id]) || isset($notify_list['optional_butoff'][$id]))
			unset($possible_members[$id]);

	if (!empty($possible_members))
	{
		// Get the default preferences
		$prefs = shd_load_user_prefs(false);
		$pref_groups = $prefs['groups'];
		$base_prefs = $prefs['prefs'];

		// Build a list of users -> default prefs. We know this is for the list of possible contenders only.
		$member_prefs = array();
		$pref_list = array();
		foreach ($possible_members as $id => $type_list)
		{
			foreach ($type_list as $type => $value)
			{
				$member_prefs[$id][$type] = $base_prefs['notify_' . $type]['default'];
				$pref_list['notify_' . $type] = true;
			}
		}

		// Grab pref list from DB for these users and update
		$query = wesql::query('
			SELECT id_member, variable, value
			FROM {db_prefix}helpdesk_preferences
			WHERE id_member IN ({array_int:members})
				AND variable IN ({array_string:variables})',
			array(
				'members' => array_keys($possible_members),
				'variables' => array_keys($pref_list),
			)
		);

		while ($row = wesql::fetch_assoc($query))
		{
			$row['id_member'] = (int) $row['id_member'];
			$variable = substr($row['variable'], 7);
			if (isset($member_prefs[$row['id_member']][$variable]))
				$member_prefs[$row['id_member']][$variable] = $row['value'];
		}
		wesql::free_result($query);

		// unset $members where member pref doesn't indicate they want it on.
		foreach ($member_prefs as $id => $value)
			foreach ($value as $pref_id => $pref_item)
				if (empty($pref_item))
					unset($possible_members[$id][$pref_id]);

		// Now, it's possible that we have a ticket that the starter can't see, but that their preferences would indicate they'd like a reply.
		// What should be done here is to remove them from the automatic list, and make them part of the ping list instead.
		if (!empty($ticket['id_member_started']) && !in_array($ticket['id_member_started'], $staff))
			$possible_members[$ticket['id_member_started']] = array();

		// Now the clever bit, we've taken everyone who wasn't on the normal notify list, and figured out what their preferences are.
		// We now traverse $possible_members by id, if the array is empty, we know none of their preferences accounted for emails - so they're possible.
		// Otherwise we add them to the list of being notified.
		foreach ($possible_members as $id => $list)
			if (empty($list))
				$notify_list['optional'][$id] = true;
			else
				$notify_list['being_notified'][$id] = true;
	}

	// By now we have three lists that include people who will be notified, people who could be notified, and people who don't really want to be.
	// Let's translate that into a list of people that we can make use of.
	$members = array_merge(array_keys($notify_list['being_notified']), array_keys($notify_list['optional']), array_keys($notify_list['optional_butoff']));

	if (!empty($members))
	{
		// Get everyone's name.
		$loaded = loadMemberData($members);
		foreach ($loaded as $user)
			if (!empty($user_profile[$user]) && $user_profile[$user]['is_activated'] > 0 && $user_profile[$user]['is_activated'] < 10) // active & not banned
				$people[$user] = array(
					'id' => $user,
					'name' => $user_profile[$user]['real_name'],
				);

		// Right, now let's step through and tidy up the three lists
		foreach ($notify_list as $list_type => $list_members)
		{
			foreach ($list_members as $id_member => $data)
			{
				if (isset($people[$id_member]) && $id_member != MID) // We really shouldn't be in this list.
					$list_members[$id_member] = $people[$id_member]['name'];
				else
					unset($list_members[$id_member]);
			}
			if (!empty($list_members))
			{
				asort($list_members);
				array_walk($list_members, 'shd_format_notify_name', $ticket['id_member_started']);
				$notify_list[$list_type] = $list_members;
			}
			else
				unset($notify_list[$list_type]);
		}
	}

	if (empty($notify_list) || empty($members))
		return $context['ajax_raw'] = '<notify><![C' . 'DATA[' . cleanXml($txt['shd_ping_none']) . ']' . ']></notify>';
	else
	{
		ob_start();
		echo '<notify><![C', 'DATA[';

		$selected = array();
		if (!empty($_GET['list']))
		{
			$_GET['list'] = explode(',', $_GET['list']);
			foreach ($_GET['list'] as $id)
				if ((int) $id > 0)
					$selected[] = (int) $id;
		}

		if (!empty($notify_list['being_notified']))
			echo '<span class="shd_ajax_head">', $txt['shd_ping_already_' . (count($notify_list['being_notified']) == 1 ? '1' : 'n')], '</span><br>', implode(', ', $notify_list['being_notified']);

		if (!empty($notify_list['optional']))
		{
			if (!empty($notify_list['being_notified']))
				echo '<br><br>';

			echo '<span class="shd_ajax_head">', $txt['shd_ping_' . (count($notify_list['optional']) == 1 ? '1' : 'n')], '</span><br>';
			foreach ($notify_list['optional'] as $id => $member)
				echo '<div class="shd_ajaxnotify"><input type="checkbox" name="notify[', $id, ']" value="', $id, '"', in_array($id, $selected) ? ' checked="checked"' : '', ' class="input_check"> ', $member, '</div>';
		}

		if (!empty($notify_list['optional_butoff']))
		{
			if (!empty($notify_list['being_notified']) || !empty($notify_list['optional_butoff']))
				echo '<br><br>';

			echo '<span class="shd_ajax_head">', $txt['shd_ping_none_' . (count($notify_list['optional_butoff']) == 1 ? '1' : 'n')], '</span><br>';
			foreach ($notify_list['optional_butoff'] as $id => $member)
				echo '<div class="shd_ajaxnotify"><input type="checkbox" name="notify[', $id, ']" value="', $id, '"', in_array($id, $selected) ? ' checked="checked"' : '', ' class="input_check"> ', $member, '</div>';
		}

		echo ']', ']></notify>';

		$content = ob_get_clean();
		return $context['ajax_raw'] = cleanXml($content);
	}
}

function shd_format_notify_name(&$user_name, $user_id, $ticket_starter)
{
	global $txt;
	$user_name = shd_profile_link($user_name, $user_id) . ($user_id == $ticket_starter ? $txt['shd_is_ticket_opener'] : '');
}

function shd_ajax_ajax_privacy()
{
	global $context, $txt;

	// First, figure out the state of the ticket - is it private or not? Can we even see it?
	if (empty($context['ticket_id']))
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	$query = wesql::query('
		SELECT id_member_started, subject, private, status, id_dept
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket}
			AND id_ticket = {int:current_ticket}',
		array(
			'current_ticket' => $context['ticket_id'],
		)
	);

	$query = wesql::query('
		SELECT id_member_started, subject, urgency, status, id_dept
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket}
			AND id_ticket = {int:current_ticket}',
		array(
			'current_ticket' => $context['ticket_id'],
		)
	);

	// Can we do this, are we powerful enough?
	if ($row = wesql::fetch_assoc($query))
	{
		if (!shd_allowed_to('shd_alter_privacy_any', $row['id_dept']) && (!shd_allowed_to('shd_alter_privacy_own', $row['id_dept']) || $row['id_member_started'] != MID))
			return $context['ajax_return'] = array('error' => $txt['shd_cannot_change_privacy']);

		$context['ajax_raw'] = '<response>
<item id="0"><![CD' . 'ATA[' . $txt['shd_ticket_notprivate'] . ']' . ']></item>
<item id="1"><![CD' . 'ATA[' . $txt['shd_ticket_private'] . ']' . ']></item>
</response>';
	}
	else
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);
}

function shd_ajax_ajax_privacy2()
{
	global $context, $txt;

	// First, figure out the state of the ticket - is it private or not? Can we even see it?
	if (empty($context['ticket_id']))
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	if (!isset($_POST['newval']) || !is_numeric($_POST['newval']) || ($_POST['newval'] != 0 && $_POST['newval'] != 1))
		return $context['ajax_return'] = array('error' => $txt['shd_cannot_change_privacy']);
	else
		$_POST['newval'] = (int) $_POST['newval'];

	$query = wesql::query('
		SELECT id_member_started, subject, private, status, id_dept
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket}
			AND id_ticket = {int:current_ticket}',
		array(
			'current_ticket' => $context['ticket_id'],
		)
	);

	// Can we do this, are we powerful enough?
	if ($row = wesql::fetch_assoc($query))
	{
		if (!shd_allowed_to('shd_alter_privacy_any', $row['id_dept']) && (!shd_allowed_to('shd_alter_privacy_own', $row['id_dept']) || $row['id_member_started'] != MID))
			return $context['ajax_return'] = array('error' => $txt['shd_cannot_change_privacy']);

		// It's unchanged.
		if ($_POST['newval'] == $row['private'])
			return $context['ajax_return'] = array('item' => $_POST['newval'] == 0 ? $txt['shd_ticket_notprivate'] : $txt['shd_ticket_private']);

		loadPluginSource('Arantor:WedgeDesk', 'src/Subs-WedgeDeskPost');
		$msgOptions = array();
		$posterOptions = array();
		$ticketOptions = array(
			'id' => $context['ticket_id'],
			'private' => $_POST['newval'],
		);

		shd_modify_ticket_post($msgOptions, $ticketOptions, $posterOptions);

		$action = empty($row['private']) ? 'markprivate' : 'marknotprivate';
		shd_log_action($action,
			array(
				'ticket' => $context['ticket_id'],
				'subject' => $row['subject'],
				'privacy' => $_POST['newval'],
			)
		);

		return $context['ajax_return'] = array('item' => $_POST['newval'] == 0 ? $txt['shd_ticket_notprivate'] : $txt['shd_ticket_private']);
	}
	else
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);
}

function shd_ajax_ajax_urgency()
{
	global $context, $txt;

	// First, figure out the state of the ticket - is it private or not? Can we even see it?
	if (empty($context['ticket_id']))
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	$query = wesql::query('
		SELECT id_member_started, subject, urgency, status, id_dept
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket}
			AND id_ticket = {int:current_ticket}',
		array(
			'current_ticket' => $context['ticket_id'],
		)
	);

	// Can we do this, are we powerful enough?
	if ($row = wesql::fetch_assoc($query))
	{
		if (!shd_allowed_to('shd_alter_urgency_any', $row['id_dept']) && (!shd_allowed_to('shd_alter_urgency_own', $row['id_dept']) || $row['id_member_started'] != MID))
			return $context['ajax_return'] = array('error' => $txt['shd_cannot_change_urgency']);

		$context['ajax_raw'] = '<response>';

	foreach (range(0, 5) as $i)
		$context['ajax_raw'] .= '
<item id="' . $i . '"><![CD' . 'ATA[' . $txt['shd_urgency_' . $i] . ']' . ']></item>';

		$context['ajax_raw'] .= '
</response>';
	}
	else
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);
}

function shd_ajax_ajax_urgency2()
{
	global $context, $txt;

	// First, figure out the state of the ticket - is it private or not? Can we even see it?
	if (empty($context['ticket_id']))
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);

	if (!isset($_POST['newval']) || !is_numeric($_POST['newval']) || empty($txt['shd_urgency_' . $_POST['newval']]))
		return $context['ajax_return'] = array('error' => $txt['shd_cannot_change_urgency']);
	else
		$_POST['newval'] = (int) $_POST['newval'];

	$query = wesql::query('
		SELECT id_member_started, subject, urgency, status, id_dept
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket}
			AND id_ticket = {int:current_ticket}',
		array(
			'current_ticket' => $context['ticket_id'],
		)
	);

	// Can we do this, are we powerful enough?
	if ($row = wesql::fetch_assoc($query))
	{
		if (!shd_allowed_to('shd_alter_urgency_any', $row['id_dept']) && (!shd_allowed_to('shd_alter_urgency_own', $row['id_dept']) || $row['id_member_started'] != MID))
			return $context['ajax_return'] = array('error' => $txt['shd_cannot_change_urgency']);

		// It's unchanged.
		if ($_POST['newval'] == $row['urgency'])
			return $context['ajax_return'] = array('item' => $txt['shd_urgency_' . $_POST['newval']]);

		loadPluginSource('Arantor:WedgeDesk', 'src/Subs-WedgeDeskPost');
		$msgOptions = array();
		$posterOptions = array();
		$ticketOptions = array(
			'id' => $context['ticket_id'],
			'urgency' => $_POST['newval'],
		);

		shd_modify_ticket_post($msgOptions, $ticketOptions, $posterOptions);

		$action = $_POST['newval'] > $row['urgency'] ? 'urgency_increase' : 'urgency_decrease';
		shd_log_action($action,
			array(
				'ticket' => $context['ticket_id'],
				'subject' => $row['subject'],
				'urgency' => $_POST['newval'],
			)
		);

		return $context['ajax_return'] = array('item' => $txt['shd_urgency_' . $_POST['newval']]);
	}
	else
		return $context['ajax_return'] = array('error' => $txt['shd_no_ticket']);
}

?>