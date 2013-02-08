<?php
/**
 * WedgeDesk
 *
 * This file serves as the entry point for WedgeDesk generally, as well as the home of the ticket listing
 * code, for open, closed and deleted tickets.
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
 *	Begins WedgeDesk general processing.
 *
 *	Several things are done here, the results of which are unilaterally assumed by all other WedgeDesk functions.
 *	- work out which departments are applicable, and which department we are currently in (as far as possible)
 *	- set up general navigation
 *	- see if the URL or POST data contains a ticket, if so sanitise and store that value
 *	- see if a msg was specified in the URL, if so identify the relevant ticket
 *	- add in the helpdesk CSS file
 *	- identify the sub action to direct them to, then send them on their way.
 *
 *	@since 1.0
*/
function shd_main()
{
	global $context, $txt, $settings, $user_profile;

	// Basic sanity stuff
	if (!$settings['helpdesk_active'])
		fatal_lang_error('shd_inactive', false);

	// Let's be sneaky. Can they only access one department? If they can only access one department, put them there and make a note of it for later.
	$depts = shd_allowed_to('access_helpdesk', false);
	$context['shd_multi_dept'] = true;
	if (count($depts) == 1)
	{
		$_REQUEST['dept'] = $depts[0];
		$context['shd_multi_dept'] = false;
	}
	elseif (empty($_REQUEST['dept']) && !empty($context['queried_dept']) && in_array($context['queried_dept'], $depts))
		$_REQUEST['dept'] = $context['queried_dept'];

	$context['shd_department'] = isset($_REQUEST['dept']) && in_array($_REQUEST['dept'], $depts) ? (int) $_REQUEST['dept'] : 0;
	$context['shd_dept_link'] = !empty($context['shd_department']) && $context['shd_multi_dept'] ? ';dept=' . $context['shd_department'] : '';
	shd_is_allowed_to('access_helpdesk', $context['shd_department']);

	// If we know the department up front, we probably should get it now. Tickets themselves will deal with this but most other places won't.
	// Note that we may already have loaded this if we went and got the department id earlier, but not always.
	if (!empty($context['shd_department']) && $context['shd_multi_dept'] && empty($context['shd_dept_name']))
	{
		$query = wesql::query('
			SELECT dept_name
			FROM {db_prefix}helpdesk_depts
			WHERE id_dept = {int:dept}',
			array(
				'dept' => $context['shd_department'],
			)
		);
		list($context['shd_dept_name']) = wesql::fetch_row($query);
		wesql::free_result($query);
	}

	// Load stuff: preferences the core template - and any hook-required files
	$context['shd_preferences'] = shd_load_user_prefs();
	$context['shd_home'] = 'action=helpdesk;sa=main';
	loadPluginTemplate('Arantor:WedgeDesk', 'tpl/WedgeDesk');
	call_lang_hook('shd_lang_helpdesk');

	// Fudge the time format.
	we::$user['time_format'] = strtr(we::$user['time_format'], array('%B' => '%b', ':%S' => ''));

	// List of sub actions.
	$subactions = array(
		'main' => array('WedgeDesk-TicketGrids', 'shd_main_helpdesk'),
		'dept' => array('WedgeDesk-DeptList', 'shd_main_dept'),
		'viewblock' => array('WedgeDesk-TicketGrids', 'shd_view_block'),
		'trackerview' => array('WedgeDesk-TicketTracker', 'shd_view_tracker'),
		'ticket' => array('WedgeDesk-Display', 'shd_view_ticket'),
		'newticket' => array('WedgeDesk-Post', 'shd_post_ticket'),
		'editticket' => array('WedgeDesk-Post', 'shd_post_ticket'),
		'saveticket' => array('WedgeDesk-Post', 'shd_save_post'), // this is the equivalent of post2
		'reply' => array('WedgeDesk-Post', 'shd_post_reply'),
		'savereply' => array('WedgeDesk-Post', 'shd_save_post'),
		'editreply' => array('WedgeDesk-Post', 'shd_post_reply'),
		'markunread' => array('WedgeDesk-MiscActions', 'shd_ticket_unread'),
		'assign' => array('WedgeDesk-Assign', 'shd_assign'),
		'assign2' => array('WedgeDesk-Assign', 'shd_assign2'),
		'movedept' => array('WedgeDesk-MoveDept', 'shd_movedept'),
		'movedept2' => array('WedgeDesk-MoveDept', 'shd_movedept2'),
		'resolveticket' => array('WedgeDesk-MiscActions', 'shd_ticket_resolve'),
		'relation' => array('WedgeDesk-MiscActions', 'shd_ticket_relation'),
		'ajax' => array('WedgeDesk-AjaxHandler', 'shd_ajax'),
		'privacychange' => array('WedgeDesk-MiscActions', 'shd_privacy_change_noajax'),
		'urgencychange' => array('WedgeDesk-MiscActions', 'shd_urgency_change_noajax'),
		'closedtickets' => array('WedgeDesk-TicketGrids', 'shd_closed_tickets'),
		'recyclebin' => array('WedgeDesk-TicketGrids', 'shd_recycle_bin'),
		'tickettotopic' => array('WedgeDesk-TicketTopicMove', 'shd_tickettotopic'),
		'tickettotopic2' => array('WedgeDesk-TicketTopicMove', 'shd_tickettotopic2'),
		'topictoticket' => array('WedgeDesk-TicketTopicMove', 'shd_topictoticket'),
		'topictoticket2' => array('WedgeDesk-TicketTopicMove', 'shd_topictoticket2'),
		'permadelete' => array('WedgeDesk-Delete', 'shd_perma_delete'),
		'deleteticket' => array('WedgeDesk-Delete', 'shd_ticket_delete'),
		'deletereply' => array('WedgeDesk-Delete', 'shd_reply_delete'),
		'deleteattach' => array('WedgeDesk-Delete', 'shd_attach_delete'),
		'restoreticket' => array('WedgeDesk-Delete', 'shd_ticket_restore'),
		'restorereply' => array('WedgeDesk-Delete', 'shd_reply_restore'),
		'emaillog' => array('WedgeDesk-Notifications', 'shd_notify_popup'),
		'notify' => array('WedgeDesk-Notifications', 'shd_notify_ticket_options'),
		'search' => array('WedgeDesk-Search', 'shd_search'),
		'search2' => array('WedgeDesk-Search', 'shd_search2'),
	);

	// Navigation menu
	$context['navigation'] = array(
		'main' => array(
			'text' => 'shd_home',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=main',
		),
		'dept' => array(
			'text' => 'shd_departments',
			'test' => 'shd_multi_dept',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=dept',
		),
		'newticket' => array(
			'text' => 'shd_new_ticket',
			'test' => 'can_new_ticket',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=newticket' . $context['shd_dept_link'],
		),
		'newticketproxy' => array(
			'text' => 'shd_new_ticket_proxy',
			'test' => 'can_proxy_ticket',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=newticket;proxy' . $context['shd_dept_link'],
		),
		'closedtickets' => array(
			'text' => 'shd_tickets_closed',
			'test' => 'can_view_closed',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=closedtickets' . $context['shd_dept_link'],
		),
		'recyclebin' => array(
			'text' => 'shd_recycle_bin',
			'test' => 'can_view_recycle',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=recyclebin' . $context['shd_dept_link'],
		),
		'search' => array(
			'text' => 'shd_search_menu',
			'test' => 'can_shd_search',
			'lang' => true,
			'url' => '<URL>?action=helpdesk;sa=search',
		),
		// Only for certain sub areas.
		'back' => array(
			'text' => 'shd_back_to_hd',
			'test' => 'display_back_to_hd',
			'lang' => true,
			'url' => '<URL>?' . $context['shd_home'] . $context['shd_dept_link'],
		),
		'options' => array(
			'text'=> 'shd_options',
			'test' => 'can_view_options',
			'lang' => true,
			'url' => '<URL>?action=profile;area=hd_prefs',
		),
	);

	// Build the link tree.
	$context['linktree'][] = array(
		'url' => '<URL>?action=helpdesk;sa=main',
		'name' => $txt['shd_helpdesk'],
	);

	if (!$context['shd_multi_dept'])
		$context['linktree'][] = array(
			'url' => '<URL>?' . $context['shd_home'],
			'name' => $txt['shd_linktree_tickets'],
		);

	// See if a ticket has been specified, like $topic can be.
	if (!empty($_REQUEST['ticket']))
	{
		if (strpos($_REQUEST['ticket'], '.') === false)
		{
			$context['ticket_id'] = (int) $_REQUEST['ticket'];
			$context['ticket_start'] = 0;
		}
		else
		{
			list ($context['ticket_id'], $context['ticket_start']) = explode('.', $_REQUEST['ticket']);
			$context['ticket_id'] = (int) $context['ticket_id'];
			if (!is_numeric($context['ticket_start']))
			{
				// Let's see if it's 'new' first. If it is, great, we'll figure out the new point then throw it at the next one.
				if (substr($context['ticket_start'], 0, 3) == 'new')
				{
					$query = wesql::query('
						SELECT IFNULL(hdlr.id_msg, -1) + 1 AS new_from
						FROM {db_prefix}helpdesk_tickets AS hdt
							LEFT JOIN {db_prefix}helpdesk_log_read AS hdlr ON (hdlr.id_ticket = {int:ticket} AND hdlr.id_member = {int:member})
						WHERE {query_see_ticket}
							AND hdt.id_ticket = {int:ticket}
						LIMIT 1',
						array(
							'member' => we::$id,
							'ticket' => $context['ticket_id'],
						)
					);
					list ($new_from) = wesql::fetch_row($query);
					wesql::free_result($query);
					$context['ticket_start'] = 'msg' . $new_from;
					$context['ticket_start_newfrom'] = $new_from;
				}

				if (substr($context['ticket_start'], 0, 3) == 'msg')
				{
					$virtual_msg = (int) substr($context['ticket_start'], 3);
					$query = wesql::query('
						SELECT COUNT(hdtr.id_msg)
						FROM {db_prefix}helpdesk_ticket_replies AS hdtr
							INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdtr.id_ticket = hdt.id_ticket)
						WHERE {query_see_ticket}
							AND hdtr.id_ticket = {int:ticket}
							AND hdtr.id_msg > hdt.id_first_msg
							AND hdtr.id_msg < {int:virtual_msg}' . (!isset($_GET['recycle']) ? '
							AND hdtr.message_status = {int:message_notdel}' : ''),
						array(
							'ticket' => $context['ticket_id'],
							'virtual_msg' => $virtual_msg,
							'message_notdel' => MSG_STATUS_NORMAL,
						)
					);
					list ($context['ticket_start']) = wesql::fetch_row($query);
					wesql::free_result($query);
				}
			}
			else
			{
				$context['ticket_start'] = (int) $context['ticket_start']; // it IS numeric but let's make sure it's the right kind of number
				$context['ticket_start_natural'] = true;
			}
		}
	}
	if (empty($context['ticket_start_newfrom']))
		$context['ticket_start_newfrom'] = empty($context['ticket_start']) ? 0 : $context['ticket_start'];

	// Do we have just a message id? We can get the ticket from that - but only if we don't already have a ticket id!
	$_REQUEST['msg'] = !empty($_REQUEST['msg']) ? (int) $_REQUEST['msg'] : 0;
	if (!empty($_REQUEST['msg']) && empty($context['ticket_id']))
	{
		$query = wesql::query('
			SELECT hdt.id_ticket, hdtr.id_msg
			FROM {db_prefix}helpdesk_ticket_replies AS hdtr
				INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdtr.id_ticket = hdt.id_ticket)
			WHERE {query_see_ticket}
				AND hdtr.id_msg = {int:msg}',
			array(
				'msg' => $_REQUEST['msg'],
			)
		);

		if ($row = wesql::fetch_row($query))
			$context['ticket_id'] = (int) $row[0];

		wesql::free_result($query);
	}

	$context['items_per_page'] = 10;
	$context['start'] = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;

	// Load the custom CSS and JS.
	add_plugin_css_file('Arantor:WedgeDesk', 'css/helpdesk', true);
	add_plugin_js_file('Arantor:WedgeDesk', 'js/helpdesk.js');

	// Int hooks - after we basically set everything up (so it's manipulatable by the hook, but before we do the last bits of finalisation)
	call_hook('shd_hook_helpdesk', array(&$subactions));

	// What are we doing?
	$_REQUEST['sa'] = (!empty($_REQUEST['sa']) && isset($subactions[$_REQUEST['sa']])) ? $_REQUEST['sa'] : 'main';
	$context['sub_action'] = $subactions[$_REQUEST['sa']];

	$context['can_new_ticket'] = shd_allowed_to('shd_new_ticket', $context['shd_department']);
	$context['can_proxy_ticket'] = $context['can_new_ticket'] && shd_allowed_to('shd_post_proxy', $context['shd_department']);
	$context['can_view_closed'] = shd_allowed_to(array('shd_view_closed_own', 'shd_view_closed_any'), $context['shd_department']);
	$context['can_view_recycle'] = shd_allowed_to('shd_access_recyclebin', $context['shd_department']);
	$context['display_back_to_hd'] = !in_array($_REQUEST['sa'], array('main', 'viewblock', 'recyclebin', 'closedtickets', 'dept', 'trackerview'));
	$context['can_shd_search'] = shd_allowed_to('shd_search', 0);
	$context['can_view_options'] = shd_allowed_to(array('shd_view_preferences_own', 'shd_view_preferences_any'), 0);

	// Highlight the correct button.
	if (isset($context['navigation'][$_REQUEST['sa']]))
		$context['navigation'][$_REQUEST['sa']]['active'] = true;

	// Send them away.
	if ($context['sub_action'][0] !== null)
		loadPluginSource('Arantor:WedgeDesk', 'src/' . $context['sub_action'][0]);

	$context['sub_action'][1]();

	// Maintenance mode? If it were, the helpdesk is considered inactive for the purposes of everything to all but those without admin-helpdesk rights - but we must have them if we're here!
	if (!empty($settings['shd_maintenance_mode']) && $_REQUEST['sa'] != 'ajax')
		wetem::load('shd_maintenance', 'default', 'first');

	call_hook('shd_hook_after_main');
}

?>