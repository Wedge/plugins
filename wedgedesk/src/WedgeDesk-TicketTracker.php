<?php
/**
 * WedgeDesk
 *
 * This file serves as the display code for the general ticket listings.
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
 *	Display the main front page, showing tickets waiting for staff, waiting for user feedback and so on.
 *
 *	This function sets up multiple blocks to be shown to users, defines what columns these blocks should have and states
 *	the rules to be used in getting the data.
 *
 *	Each block has multiple parameters, and is stated in $context['ticket_blocks']:
 *	<ul>
 *	<li>block_icon: which image to use in $plugindir/images/ for denoting the type of block; filename plus extension</li>
 *	<li>title: the text string to use as the block's heading</li>
 *	<li>where: an SQL clause denoting the rule for obtaining the items in this block</li>
 *	<li>display: whether the block should be processed and prepared for display</li>
 *	<li>count: the number of items in this block, for pagination; generally should be a call to {@link shd_count_helpdesk_tickets()}</li>
 *	<li>columns: an array of columns to display in this block, in the order they should be displayed, using the following options, derived from {@link shd_get_block_columns()}:
 *		<ul>
 *			<li>ticket_id: the ticket's read status, privacy icon, and id</li>
 *			<li>ticket_name: name/link to the ticket</li>
 *			<li>starting_user: profile link to the user who opened the ticket</li>
 *			<li>replies: number of (visible) replies in the ticket</li>
 *			<li>allreplies: number of (all) replies in the ticket (includes deleted replies, which 'replies' does not)</li>
 *			<li>last_reply: the user who last replied</li>
 *			<li>status: the current ticket's status</li>
 *			<li>assigned: link to the profile of the user the ticket is assigned to, or 'Unassigned' if not assigned</li>
 *			<li>urgency: the current ticket's urgency</li>
 *			<li>updated: time of the last reply in the ticket; states Never if no replies</li>
 *			<li>actions: icons that may or may not relate to a given ticket; buttons for recycle, delete, unresolve live in this column</li>
 *		</ul>
 *	<li>required: whether the block is required to be displayed even if empty</li>
 *	<li>collapsed: whether the block should be compressed to a header with count of tickets or not (mostly for {@link shd_view_block()}'s benefit)</li>
 *	</ul>
 *
 *	This function declares the following blocks:
 *	<ul>
 *	<li>Assigned to me (staff only)</li>
 *	<li>New tickets (staff only)</li>
 *	<li>Pending with staff (for staff, this is just tickets with that status, for regular users this is both pending staff and new unreplied to tickets)</li>
 *	<li>Pending with user (both)</li>
 *	</ul>
 *
 *	@see shd_count_helpdesk_tickets()
 *	@since 1.0
*/
function shd_view_tracker()
{
	global $context, $txt, $user_profile;

	$is_staff = shd_allowed_to('shd_staff', 0);
	// Stuff we need to add to $context, page title etc etc
	$context += array(
		'page_title' => $txt['shd_helpdesk'],
		'tracker_blocks' => array(
			array(
				'title' => $txt['shd_tickets_recently_updated'],
				'num_items' => 10,
				'criteria' => array(),
				'sort' => 'hdt.last_updated DESC, hdt.id_ticket ASC',
				'tickets' => array(),
			),
			array(
				'title' => $txt['shd_tickets_unassigned'],
				'num_items' => 10,
				'criteria' => array('hdt.id_member_assigned = 0'),
				'sort' => 'hdt.last_updated DESC, hdt.id_ticket ASC',
				'tickets' => array(),
			),
			array(
				'title' => $txt['shd_tickets_closed'],
				'num_items' => 10,
				'criteria' => array('hdt.status = ' . TICKET_STATUS_CLOSED),
				'sort' => 'hdt.last_updated DESC, hdt.id_ticket ASC',
				'tickets' => array(),
			),
			array(
				'title' => $txt['shd_tickets_mine'],
				'num_items' => 10,
				'criteria' => array('hdt.id_member_started = {user_info_id}'),
				'sort' => 'hdt.last_updated DESC, hdt.id_ticket ASC',
				'tickets' => array(),
			),
			array(
				'title' => $txt['shd_tickets_mymonitored'],
				'num_items' => 10,
				'joins' => 'INNER JOIN {db_prefix}helpdesk_notify_override AS hdno ON (hdt.id_ticket = hdno.id_ticket)',
				'criteria' => array('hdno.id_member = {user_info_id}', 'hdno.notify_state = ' . NOTIFY_ALWAYS),
				'sort' => 'hdt.last_updated DESC, hdt.id_ticket ASC',
				'tickets' => array(),
			),
		),

		'shd_home_view' => $is_staff ? 'staff' : 'user',
	);

	// Now we go get the actual tickets.
	foreach ($context['tracker_blocks'] as $block_id => $block)
	{
		array_unshift($block['criteria'], '{query_see_ticket}');
		$query = wesql::query('
			SELECT hdt.id_ticket, hdt.subject, hdt.status, hdt.id_dept, hdd.dept_name, hdt.urgency, hdt.last_updated, hdt.private,
				hdt.id_member_assigned, hdt.num_replies
			FROM {db_prefix}helpdesk_tickets AS hdt
				INNER JOIN {db_prefix}helpdesk_depts AS hdd ON (hdd.id_dept = hdt.id_dept)' . (!empty($block['joins']) ? '
				' . $block['joins'] : '') . '
			WHERE ' . implode(' AND ', $block['criteria']) . '
			ORDER BY ' . $block['sort'] . '
			LIMIT {int:limit}',
			array(
				'limit' => $block['num_items'],
			)
		);
		while ($row = wesql::fetch_assoc($query))
		{
			if (!empty($row['id_member_assigned']))
				$row['class'] = 'assigned';
			elseif (empty($row['num_replies']))
				$row['class'] = 'new';
			elseif ($row['status'] == TICKET_STATUS_CLOSED)
				$row['class'] = 'resolved';
			else
				$row['class'] = 'feedback';
			$context['tracker_blocks'][$block_id]['tickets'][$row['id_ticket']] = $row;
		}
		wesql::free_result($query);
	}

	wetem::load('tracker_view');
	wetem::load('tracker_legend', 'sidebar', 'add');

	if (!empty($context['shd_dept_name']) && $context['shd_multi_dept'])
		$context['linktree'][] = array(
			'url' => '<URL>?' . $context['shd_home'] . $context['shd_dept_link'],
			'name' => $context['shd_dept_name'],
		);
}

?>