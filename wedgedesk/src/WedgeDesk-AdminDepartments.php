<?php
/**
 * WedgeDesk
 *
 * This file handles the core of WedgeDesk's departmental administration.
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
 *	The start point for all interaction with the WedgeDesk departments
 *
 *	@since 2.0
*/
function shd_admin_departments()
{
	loadPluginTemplate('Arantor:WedgeDesk', 'tpl/WedgeDesk-AdminDepartments');

	$subactions = array(
		'main' => 'shd_admin_dept_list',
		'move' => 'shd_admin_dept_move',
		'createdept' => 'shd_admin_create_dept',
		'editdept' => 'shd_admin_edit_dept',
		'savedept' => 'shd_admin_save_dept',
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subactions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	$subactions[$_REQUEST['sa']]();
}

/**
 *	Display a list of all the departments currently in the system, with appropriate navigation to edit or create more.
 *
 *	@since 2.0
 */
function shd_admin_dept_list()
{
	global $context, $txt;

	$context['page_title'] = $txt['shd_admin_departments_home'];
	wetem::load('shd_departments_home');

	// 1. Get all the departments
	$query = wesql::query('
		SELECT hdd.id_dept, hdd.dept_name, hdd.description, hdd.board_cat, c.name AS cat_name, hdd.before_after
		FROM {db_prefix}helpdesk_depts AS hdd
			LEFT JOIN {db_prefix}categories AS c ON (hdd.board_cat = c.id_cat)
		ORDER BY dept_order'
	);
	while ($row = wesql::fetch_assoc($query))
	{
		$context['shd_departments'][$row['id_dept']] = $row;
		// Also get the first and last for in a minute
		$last = $row['id_dept'];
		if (empty($first))
			$first = $row['id_dept'];
	}
	wesql::free_result($query);

	// 1a. Make sure to log that a given row is first or last for the move wotsits.
	$context['shd_departments'][$first]['is_first'] = true;
	$context['shd_departments'][$last]['is_last'] = true;

	// 2. Just for niceness, get all the helpdesk roles attached to each department.
	$query = wesql::query('
		SELECT hddr.id_dept, hddr.id_role, hdr.template, hdr.role_name
		FROM {db_prefix}helpdesk_dept_roles AS hddr
			INNER JOIN {db_prefix}helpdesk_roles AS hdr ON (hddr.id_role = hdr.id_role)
		ORDER BY hddr.id_dept, hddr.id_role'
	);
	while ($row = wesql::fetch_assoc($query))
		$context['shd_departments'][$row['id_dept']]['roles'][$row['id_role']] = $row;
	wesql::free_result($query);
}

function shd_admin_dept_move()
{
	global $context;

	checkSession('get');

	$_REQUEST['dept'] = isset($_REQUEST['dept']) ? (int) $_REQUEST['dept'] : 0;
	$_REQUEST['direction'] = isset($_REQUEST['direction']) && in_array($_REQUEST['direction'], array('up', 'down')) ? $_REQUEST['direction'] : '';

	$query = wesql::query('
		SELECT id_dept, dept_order
		FROM {db_prefix}helpdesk_depts',
		array()
	);

	if (wesql::num_rows($query) == 0 || empty($_REQUEST['direction']))
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannot_move_dept', false);
	}

	$depts = array();
	while ($row = wesql::fetch_assoc($query))
		$depts[$row['dept_order']] = $row['id_dept'];

	ksort($depts);

	$depts_map = array_flip($depts);
	if (empty($depts_map[$_REQUEST['dept']]))
		fatal_lang_error('shd_admin_cannot_move_dept', false);

	$current_pos = $depts_map[$_REQUEST['dept']];
	$destination = $current_pos + ($_REQUEST['direction'] == 'up' ? -1 : 1);

	if (empty($depts[$destination]))
		fatal_lang_error('shd_admin_cannot_move_dept_' . $_REQUEST['direction'], false);

	$other_dept = $depts[$destination];

	wesql::query('
		UPDATE {db_prefix}helpdesk_depts
		SET dept_order = {int:new_pos}
		WHERE id_dept = {int:dept}',
		array(
			'new_pos' => $destination,
			'dept' => $_REQUEST['dept'],
		)
	);

	wesql::query('
		UPDATE {db_prefix}helpdesk_depts
		SET dept_order = {int:old_pos}
		WHERE id_dept = {int:other_dept}',
		array(
			'old_pos' => $current_pos,
			'other_dept' => $other_dept,
		)
	);

	redirectexit('action=admin;area=helpdesk_depts');
}

function shd_admin_create_dept()
{
	global $context, $txt;

	$context['shd_cat_list'] = array(
		0 => $txt['shd_boardindex_cat_none'],
	);
	$request = wesql::query('
		SELECT id_cat, name
		FROM {db_prefix}categories
		ORDER BY cat_order');
	while ($row = wesql::fetch_assoc($request))
		$context['shd_cat_list'][$row['id_cat']] = $row['name'];
	wesql::free_result($request);

	if (empty($_REQUEST['part']))
	{
		$context['page_title'] = $txt['shd_create_dept'];
		wetem::load('shd_create_dept');
		checkSubmitOnce('register');
	}
	else
	{
		checkSubmitOnce('check');
		checkSession();

		// Boring stuff like session checks done. Were you a naughty admin and didn't set it properly?
		if (!isset($_POST['dept_name']) || westr::htmltrim(westr::htmlspecialchars($_POST['dept_name'])) === '')
			fatal_lang_error('shd_no_dept_name', false);
		else
			$_POST['dept_name'] = strtr(westr::htmlspecialchars($_POST['dept_name']), array("\r" => '', "\n" => '', "\t" => ''));

		// Now to check the category.
		if (!isset($_POST['dept_cat']) || !isset($context['shd_cat_list'][$_POST['dept_cat']]))
			fatal_lang_error('shd_invalid_category', false);
		else
			$_POST['dept_cat'] = (int) $_POST['dept_cat'];

		$_POST['dept_beforeafter'] = empty($_POST['dept_beforeafter']) || empty($_POST['dept_cat']) ? 0 : 1;
		// Change '1 & 2' to '1 &amp; 2', but not '&amp;' to '&amp;amp;'...
		$_POST['dept_desc'] = empty($_POST['dept_desc']) ? '' : preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['dept_desc']);

		// Get the department's order position
		$query = wesql::query('
			SELECT MAX(dept_order)
			FROM {db_prefix}helpdesk_depts');
		list($maxdept) = wesql::fetch_row($query);
		wesql::free_result($query);

		// Create the department
		wesql::insert('insert',
			'{db_prefix}helpdesk_depts',
			array(
				'dept_name' => 'string', 'description' => 'string', 'board_cat' => 'int', 'before_after' => 'int', 'dept_order' => 'int',
			),
			array(
				$_POST['dept_name'], $_POST['dept_desc'], $_POST['dept_cat'], $_POST['dept_beforeafter'], $maxdept + 1,
			),
			array(
				'id_dept',
			)
		);

		$newdept = wesql::insert_id();
		if (empty($newdept))
			fatal_lang_error('shd_could_not_create_dept', false);

		// Take them to the edit screen!
		redirectexit('action=admin;area=helpdesk_depts;sa=editdept;dept=' . $newdept);
	}
}

function shd_admin_edit_dept()
{
	global $context, $txt;

	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskPermissions');

	$_REQUEST['dept'] = isset($_REQUEST['dept']) ? (int) $_REQUEST['dept'] : 0;

	// Get the current department
	$query = wesql::query('
		SELECT id_dept, dept_name, description, board_cat, before_after, dept_theme, autoclose_days
		FROM {db_prefix}helpdesk_depts
		WHERE id_dept = {int:dept}',
		array(
			'dept' => $_REQUEST['dept'],
		)
	);
	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_unknown_dept', false);
	}
	$context['shd_dept'] = wesql::fetch_assoc($query);
	$context['shd_dept']['description'] = htmlspecialchars($context['shd_dept']['description']);
	wesql::free_result($query);

	// Get the category list
	$context['shd_cat_list'] = array(
		0 => $txt['shd_boardindex_cat_none'],
	);
	$request = wesql::query('
		SELECT id_cat, name
		FROM {db_prefix}categories
		ORDER BY cat_order');
	while ($row = wesql::fetch_assoc($request))
		$context['shd_cat_list'][$row['id_cat']] = $row['name'];
	wesql::free_result($request);

	// Now the role list
	$query = wesql::query('
		SELECT id_role, template, role_name
		FROM {db_prefix}helpdesk_roles
		ORDER BY id_role');
	while ($row = wesql::fetch_assoc($query))
		$context['shd_roles'][$row['id_role']] = $row;
	wesql::free_result($query);

	$query = wesql::query('
		SELECT id_role
		FROM {db_prefix}helpdesk_dept_roles
		WHERE id_dept = {int:dept}',
		array(
			'dept' => $_REQUEST['dept'],
		)
	);
	while ($row = wesql::fetch_assoc($query))
		$context['shd_roles'][$row['id_role']]['in_dept'] = true;
	wesql::free_result($query);

	// And the theme list
	shd_get_dept_theme_list();

	$context['page_title'] = $txt['shd_edit_dept'];
	wetem::load('shd_edit_dept');
}

function shd_admin_save_dept()
{
	global $context, $txt;

	// 1. Check they've come from this session
	checkSession();

	// 2. Check it's a valid department.
	$_REQUEST['dept'] = isset($_REQUEST['dept']) ? (int) $_REQUEST['dept'] : 0;
	$query = wesql::query('
		SELECT id_dept, dept_name, description, board_cat, before_after
		FROM {db_prefix}helpdesk_depts
		WHERE id_dept = {int:dept}',
		array(
			'dept' => $_REQUEST['dept'],
		)
	);
	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_unknown_dept', false);
	}
	$context['shd_dept'] = wesql::fetch_assoc($query);
	wesql::free_result($query);

	// 3. We might be deleting. If so, do our business and exit stage left.
	if (isset($_POST['delete']))
	{
		// OK, so how many categories are there? If there's only one, we can't delete it.
		$query = wesql::query('
			SELECT COUNT(*)
			FROM {db_prefix}helpdesk_depts');
		list($count) = wesql::fetch_row($query);
		if ($count == 1)
			fatal_lang_error('shd_must_have_dept', false);

		// What about it having tickets in it?
		$query = wesql::query('
			SELECT COUNT(id_ticket)
			FROM {db_prefix}helpdesk_tickets
			WHERE id_dept = {int:dept}',
			array(
				'dept' => $_REQUEST['dept'],
			)
		);
		list($count) = wesql::fetch_row($query);
		wesql::free_result($query);
		if (!empty($count))
			fatal_lang_error('shd_dept_not_empty', false);

		// Before we kill it, get its order position.
		$query = wesql::query('
			SELECT dept_order
			FROM {db_prefix}helpdesk_depts
			WHERE id_dept = {int:dept}',
			array(
				'dept' => $_REQUEST['dept'],
			)
		);
		if (wesql::num_rows($query) == 0)
		{
			wesql::free_result($query);
			fatal_lang_error(shd_unknown_dept, false);
		}
		list($dept_order) = wesql::fetch_row($query);
		wesql::free_result($query);

		// Oops, bang you're dead.
		wesql::query('
			DELETE FROM {db_prefix}helpdesk_depts
			WHERE id_dept = {int:dept}',
			array(
				'dept' => $_REQUEST['dept'],
			)
		);

		wesql::query('
			DELETE FROM {db_prefix}helpdesk_dept_roles
			WHERE id_dept = {int:dept}',
			array(
				'dept' => $_REQUEST['dept'],
			)
		);

		// Make sure to reset all the department orders from after this one.
		wesql::query('
			UPDATE {db_prefix}helpdesk_depts
			SET dept_order = dept_order - 1
			WHERE dept_order > {int:old_order}',
			array(
				'old_order' => $dept_order,
			)
		);

		// Bat out of hell
		redirectexit('action=admin;area=helpdesk_depts');
	}

	// 4. Get the list of categories, so we can validate that in a moment.
	$context['shd_cat_list'] = array(
		0 => $txt['shd_boardindex_cat_none'],
	);
	$request = wesql::query('
		SELECT id_cat, name
		FROM {db_prefix}categories
		ORDER BY cat_order');
	while ($row = wesql::fetch_assoc($request))
		$context['shd_cat_list'][$row['id_cat']] = $row['name'];
	wesql::free_result($request);

	// 5. Get the stuff in the form.
	// 5a. That there's something in the dept. name
	if (!isset($_POST['dept_name']) || westr::htmltrim(westr::htmlspecialchars($_POST['dept_name'])) === '')
		fatal_lang_error('shd_no_dept_name', false);
	else
		$_POST['dept_name'] = strtr(westr::htmlspecialchars($_POST['dept_name']), array("\r" => '', "\n" => '', "\t" => ''));

	// 5b. Now to check the category exists and where we're putting it in the category.
	if (!isset($_POST['dept_cat']) || !isset($context['shd_cat_list'][$_POST['dept_cat']]))
		fatal_lang_error('shd_invalid_category', false);
	else
		$_POST['dept_cat'] = (int) $_POST['dept_cat'];

	$_POST['dept_beforeafter'] = empty($_POST['dept_beforeafter']) || empty($_POST['dept_cat']) ? 0 : 1;
	// Change '1 & 2' to '1 &amp; 2', but not '&amp;' to '&amp;amp;'...
	$_POST['dept_desc'] = empty($_POST['dept_desc']) ? '' : preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['dept_desc']);

	// 5c. Check the specified theme exists and reset to default if it doesn't.
	shd_get_dept_theme_list();
	$_POST['dept_theme'] = isset($_POST['dept_theme']) && isset($context['dept_theme_list'][$_POST['dept_theme']]) ? (int) $_POST['dept_theme'] : 0;

	$_POST['autoclose_days'] = isset($_POST['autoclose_days']) ? (int) $_POST['autoclose_days'] : 0;
	if ($_POST['autoclose_days'] < 0)
		$_POST['autoclose_days'] = 0;
	if ($_POST['autoclose_days'] > 9999)
		$_POST['autoclose_days'] = 9999;

	// 6. Commit that to DB.
	wesql::query('
		UPDATE {db_prefix}helpdesk_depts
		SET dept_name = {string:dept_name},
			description = {string:description},
			board_cat = {int:board_cat},
			before_after = {int:before_after},
			dept_theme = {int:dept_theme},
			autoclose_days = {int:autoclose_days}
		WHERE id_dept = {int:id_dept}',
		array(
			'id_dept' => $_REQUEST['dept'],
			'dept_name' => $_POST['dept_name'],
			'description' => $_POST['dept_desc'],
			'board_cat' => $_POST['dept_cat'],
			'before_after' => $_POST['dept_beforeafter'],
			'dept_theme' => $_POST['dept_theme'],
			'autoclose_days' => $_POST['autoclose_days'],
		)
	);

	// 7. Now update the list of roles attached to this department.
	$add = array();
	$remove = array();

	// 7a. Get the list of roles and start from there.
	$query = wesql::query('
		SELECT id_role
		FROM {db_prefix}helpdesk_roles');
	while ($row = wesql::fetch_assoc($query))
	{
		if (!empty($_POST['role' . $row['id_role']]))
			$add[] = $row['id_role'];
		else
			$remove[] = $row['id_role'];
	}
	wesql::free_result($query);

	// 7b. Any to remove?
	if (!empty($remove))
	{
		wesql::query('
			DELETE FROM {db_prefix}helpdesk_dept_roles
			WHERE id_role IN ({array_int:role})
				AND id_dept = {int:dept}',
			array(
				'dept' => $_REQUEST['dept'],
				'role' => $remove,
			)
		);
	}

	// 7c. Any to add?
	if (!empty($add))
	{
		$insert = array();
		foreach ($add as $add_role)
			$insert[] = array($add_role, $_REQUEST['dept']);

		wesql::insert('replace',
			'{db_prefix}helpdesk_dept_roles',
			array(
				'id_role' => 'int', 'id_dept' => 'int',
			),
			$insert,
			array(
				'id_role', 'id_dept',
			)
		);
	}

	// 8. Thank you and good night.
	redirectexit('action=admin;area=helpdesk_depts');
}

function shd_get_dept_theme_list()
{
	global $txt, $context;

	$context['dept_theme_list'] = array(
		0 => $txt['shd_dept_theme_use_default'],
	);
	$request = wesql::query('
		SELECT id_theme, value
		FROM {db_prefix}themes
		WHERE id_member = {int:member}
			AND id_theme > {int:theme}
			AND variable = {string:name}',
		array(
			'member' => 0,
			'theme' => 0,
			'name' => 'name',
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$context['dept_theme_list'][$row['id_theme']] = $row['value'];
	wesql::free_result($request);
}

?>