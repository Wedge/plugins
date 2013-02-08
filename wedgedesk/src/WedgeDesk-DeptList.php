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
 *	Sets up viewing the list of departments.
 *
 *	$context['dept_list'] contains the list of departments that the user can see, key/value pair, array is ordered according to the ordering specified in the Departments administration area.
 *	The key for $context['dept_list'] is the department's id, with the array content being:
 *	- id_dept - departmental id (numeric)
 *	- dept_name - department's name
 *	- tickets - an array containing three elements, open, closed and assigned - containing the counts thereof for each department for the current user.
 *	- new - boolean whether this department has some new items to be read by the current user
 *
 *	The linktree is also modified to inclue the department list.
 *
 *	@see shd_get_ticket_counts()
 *	@see shd_get_unread_departments()
 *	@since 2.0
*/
function shd_main_dept()
{
	global $context, $txt;

	$dept_list = shd_allowed_to('access_helpdesk', false);

	$context += array(
		'page_title' => $txt['shd_helpdesk'] . ' - ' . $txt['shd_departments'],
		'shd_home_view' => shd_allowed_to('shd_staff', 0) ? 'staff' : 'user',
	);
	wetem::load('shd_depts');

	// Get the departments and order them in the same order they would be on the board index.
	$context['dept_list'] = array();
	$query = wesql::query('
		SELECT hdd.id_dept, hdd.dept_name
		FROM {db_prefix}helpdesk_depts AS hdd
		WHERE hdd.id_dept IN ({array_int:depts})
		ORDER BY hdd.dept_order',
		array(
			'depts' => $dept_list,
		)
	);

	while ($row = wesql::fetch_assoc($query))
		$context['dept_list'][$row['id_dept']] = array(
			'id_dept' => $row['id_dept'],
			'dept_name' => $row['dept_name'],
			'tickets' => array(
				'open' => 0,
				'closed' => 0,
				'assigned' => 0,
			),
			'new' => false,
		);
	wesql::free_result($query);

	loadPluginSource('Arantor:WedgeDesk', 'src/Subs-WedgeDeskBoardIndex');
	shd_get_ticket_counts();
	shd_get_unread_departments();

	$context['linktree'][] = array(
		'url' => '<URL>?action=helpdesk;sa=dept',
		'name' => $txt['shd_departments'],
	);
}

?>