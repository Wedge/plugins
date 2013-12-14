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
function shd_admin_canned()
{
	loadPluginTemplate('Arantor:WedgeDesk', 'tpl/WedgeDesk-AdminCannedReplies');

	$subactions = array(
		'main' => 'shd_admin_canned_list',
		'createcat' => 'shd_admin_canned_createcat',
		'movecat' => 'shd_admin_canned_movecat',
		'editcat' => 'shd_admin_canned_editcat',
		'savecat' => 'shd_admin_canned_savecat',
		'createreply' => 'shd_admin_canned_createreply',
		'movereply' => 'shd_admin_canned_movereply',
		'movereplycat' => 'shd_admin_canned_movereplycat',
		'editreply' => 'shd_admin_canned_editreply',
		'savereply' => 'shd_admin_canned_savereply',
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subactions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	$subactions[$_REQUEST['sa']]();
}

/**
 *	Set up displaying all of the canned replies in the main listing.
 *
 *	@since 2.0
*/
function shd_admin_canned_list()
{
	global $context, $txt;

	$context['page_title'] = $txt['shd_admin_cannedreplies_home'];
	wetem::load('shd_cannedreplies_home');

	$context['canned_replies'] = array();

	// 1. Get all the item/department joins. This could be complicated, so do it this way.
	$reply_depts = array();
	$query = wesql::query('
		SELECT hdcrd.id_reply, hdd.dept_name
		FROM {db_prefix}helpdesk_cannedreplies_depts AS hdcrd
			INNER JOIN {db_prefix}helpdesk_depts AS hdd ON (hdcrd.id_dept = hdd.id_dept)
		ORDER BY hdcrd.id_reply, hdd.dept_order'
	);
	while ($row = wesql::fetch_assoc($query))
		$reply_depts[$row['id_reply']][] = $row['dept_name'];

	// 2. Get all the actual categories and all their items.
	$query = wesql::query('
		SELECT hdcr.id_reply, hdcr.title, hdcrc.id_cat, hdcrc.cat_name, hdcr.active, hdcr.vis_user, hdcr.vis_staff
		FROM {db_prefix}helpdesk_cannedreplies_cats AS hdcrc
			LEFT JOIN {db_prefix}helpdesk_cannedreplies AS hdcr ON (hdcr.id_cat = hdcrc.id_cat)
		ORDER BY hdcrc.cat_order, hdcr.reply_order'
	);

	while ($row = wesql::fetch_assoc($query))
	{
		if (!isset($context['canned_replies'][$row['id_cat']]))
			$context['canned_replies'][$row['id_cat']] = array(
				'name' => $row['cat_name'],
				'replies' => array(),
				'move_up' => true,
				'move_down' => true,
			);

		$row['active_string'] = empty($row['active']) ? 'inactive' : 'active';
		$row['move_up'] = true;
		$row['move_down'] = true;
		$row['depts'] = !empty($reply_depts[$row['id_reply']]) ? implode(', ', $reply_depts[$row['id_reply']]) : $txt['shd_none'];

		if (!empty($row['title']))
			$context['canned_replies'][$row['id_cat']]['replies'][] = $row;
	}

	$context['move_between_cats'] = count($context['canned_replies']) > 1;
	foreach ($context['canned_replies'] as $cat_id => $cat)
	{
		if (!empty($cat['replies']))
		{
			$context['canned_replies'][$cat_id]['replies'][0]['move_up'] = false;
			$context['canned_replies'][$cat_id]['replies'][count($cat['replies'])-1]['move_down'] = false;
		}
		$context['canned_replies'][$cat_id]['move_up'] &= $context['move_between_cats'];
		$context['canned_replies'][$cat_id]['move_down'] &= $context['move_between_cats'];

		if (!isset($first))
			$first = $cat_id;
		$last = $cat_id;
	}
	if (isset($first))
	{
		$context['canned_replies'][$first]['move_up'] = false;
		$context['canned_replies'][$last]['move_down'] = false;
	}
}

/**
 *	Handle moving a category of canned replied up and down.
 *
 *	@since 2.0
*/
function shd_admin_canned_movecat()
{
	checkSession('get');

	$_REQUEST['cat'] = isset($_REQUEST['cat']) ? (int) $_REQUEST['cat'] : 0;
	$_REQUEST['direction'] = isset($_REQUEST['direction']) && in_array($_REQUEST['direction'], array('up', 'down')) ? $_REQUEST['direction'] : '';

	$query = wesql::query('
		SELECT id_cat, cat_order
		FROM {db_prefix}helpdesk_cannedreplies_cats',
		array()
	);

	if (wesql::num_rows($query) == 0 || empty($_REQUEST['direction']))
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannedreplies_cannot_move_cat', false);
	}

	$cats = array();
	while ($row = wesql::fetch_assoc($query))
		$cats[$row['cat_order']] = $row['id_cat'];

	ksort($cats);

	$cats_map = array_flip($cats);
	if (empty($cats_map[$_REQUEST['cat']]))
		fatal_lang_error('shd_admin_cannedreplies_cannot_move_cat', false);

	$current_pos = $cats_map[$_REQUEST['cat']];
	$destination = $current_pos + ($_REQUEST['direction'] == 'up' ? -1 : 1);

	if (empty($cats[$destination]))
		fatal_lang_error('shd_admin_cannedreplies_cannot_move_cat_' . $_REQUEST['direction'], false);

	$other_cat = $cats[$destination];

	wesql::query('
		UPDATE {db_prefix}helpdesk_cannedreplies_cats
		SET cat_order = {int:new_pos}
		WHERE id_cat = {int:cat}',
		array(
			'new_pos' => $destination,
			'cat' => $_REQUEST['cat'],
		)
	);

	wesql::query('
		UPDATE {db_prefix}helpdesk_cannedreplies_cats
		SET cat_order = {int:old_pos}
		WHERE id_cat = {int:other_cat}',
		array(
			'old_pos' => $current_pos,
			'other_cat' => $other_cat,
		)
	);

	redirectexit('action=admin;area=helpdesk_cannedreplies');
}

/**
 *	Handle moving a reply up and down within its category.
 *
 *	@since 2.0
*/
function shd_admin_canned_movereply()
{
	checkSession('get');

	$_REQUEST['reply'] = isset($_REQUEST['reply']) ? (int) $_REQUEST['reply'] : 0;
	$_REQUEST['direction'] = isset($_REQUEST['direction']) && in_array($_REQUEST['direction'], array('up', 'down')) ? $_REQUEST['direction'] : '';

	$query = wesql::query('
		SELECT id_reply, reply_order
		FROM {db_prefix}helpdesk_cannedreplies',
		array()
	);

	if (wesql::num_rows($query) == 0 || empty($_REQUEST['direction']))
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannedreplies_cannot_move_reply', false);
	}

	$replies = array();
	while ($row = wesql::fetch_assoc($query))
		$replies[$row['reply_order']] = $row['id_reply'];

	ksort($replies);

	$replies_map = array_flip($replies);
	if (empty($replies_map[$_REQUEST['reply']]))
		fatal_lang_error('shd_admin_cannedreplies_cannot_move_reply', false);

	$current_pos = $replies_map[$_REQUEST['reply']];
	$destination = $current_pos + ($_REQUEST['direction'] == 'up' ? -1 : 1);

	if (empty($replies[$destination]))
		fatal_lang_error('shd_admin_cannedreplies_cannot_move_reply_' . $_REQUEST['direction'], false);

	$other_reply = $replies[$destination];

	wesql::query('
		UPDATE {db_prefix}helpdesk_cannedreplies
		SET reply_order = {int:new_pos}
		WHERE id_reply = {int:reply}',
		array(
			'new_pos' => $destination,
			'reply' => $_REQUEST['reply'],
		)
	);

	wesql::query('
		UPDATE {db_prefix}helpdesk_cannedreplies
		SET reply_order = {int:old_pos}
		WHERE id_reply = {int:other_reply}',
		array(
			'old_pos' => $current_pos,
			'other_reply' => $other_reply,
		)
	);

	redirectexit('action=admin;area=helpdesk_cannedreplies');
}

/**
 *	Display the UI for creating a category of canned replies.
 *
 *	@since 2.0
*/
function shd_admin_canned_createcat()
{
	global $context, $txt;

	$context['page_title'] = $txt['shd_admin_cannedreplies_createcat'];
	wetem::load('shd_edit_canned_category');

	// Setting up for the form. One form, two uses, sneaky, huh.
	$_REQUEST['cat'] = 'new';
	$context['category_name'] = '';
	checkSubmitOnce('register');
}

function shd_admin_canned_editcat()
{
	global $context, $txt;

	$context['page_title'] = $txt['shd_admin_cannedreplies_editcat'];
	wetem::load('shd_edit_canned_category');

	$_REQUEST['cat'] = isset($_REQUEST['cat']) ? (int) $_REQUEST['cat'] : 0;
	$query = wesql::query('
		SELECT cat_name
		FROM {db_prefix}helpdesk_cannedreplies_cats
		WHERE id_cat = {int:cat}',
		array(
			'cat' => $_REQUEST['cat'],
		)
	);
	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannedreplies_thecatisalie', false);
	}

	list($context['category_name']) = wesql::fetch_row($query);
	wesql::free_result($query);
	checkSubmitOnce('register');
}

function shd_admin_canned_savecat()
{
	checkSession('request');

	// If we're deleting a category, do it first, get it out the way.
	if (!empty($_POST['delete']))
	{
		$_REQUEST['cat'] = isset($_REQUEST['cat']) ? (int) $_REQUEST['cat'] : 0;
		if ($_REQUEST['cat'] > 0)
		{
			// 1. Get the category's position.
			$query = wesql::query('
				SELECT cat_order
				FROM {db_prefix}helpdesk_cannedreplies_cats
				WHERE id_cat = {int:cat}',
				array(
					'cat' => $_REQUEST['cat'],
				)
			);
			if (wesql::num_rows($query) == 0)
				redirectexit('action=admin;area=helpdesk_cannedreplies');

			list($old_pos) = wesql::fetch_row($query);

			// 2. Delete the old category.
			wesql::query('
				DELETE FROM {db_prefix}helpdesk_cannedreplies_cats
				WHERE id_cat = {int:cat}',
				array(
					'cat' => $_REQUEST['cat'],
				)
			);
			// 3. Bump everything else up one.
			wesql::query('
				UPDATE {db_prefix}helpdesk_cannedreplies_cats
				SET cat_order = cat_order - 1
				WHERE cat_order > {int:old_pos}',
				array(
					'old_pos' => $old_pos,
				)
			);
			// 4. Get all the replies in this category, so we can purge related records.
			$query = wesql::query('
				SELECT id_reply
				FROM {db_prefix}helpdesk_cannedreplies
				WHERE id_cat = {int:cat}',
				array(
					'cat' => (int) $_REQUEST['cat'],
				)
			);
			$replies = array();
			while ($row = wesql::fetch_row($query))
				$replies[] = $row[0];
			wesql::free_result($query);

			if (!empty($replies))
			{
				// 5. Remove the dept/reply relationships. (If we have any.)
				wesql::query('
					DELETE FROM {db_prefix}helpdesk_cannedreplies_depts
					WHERE id_reply IN ({array_int:replies})',
					array(
						'replies' => $replies,
					)
				);
				// 6. Remove the replies themselves.
				wesql::query('
					DELETE FROM {db_prefix}helpdesk_cannedreplies
					WHERE id_reply IN ({array_int:replies})',
					array(
						'replies' => $replies,
					)
				);
			}
		}

		redirectexit('action=admin;area=helpdesk_cannedreplies');
	}

	$_POST['catname'] = isset($_POST['catname']) ? strtr(westr::htmlspecialchars($_POST['catname']), array("\r" => '', "\n" => '', "\t" => '')) : '';

	// There are two things we could be doing here. $_POST['cat'] should be set, and it should be set to 'new' for new categories, or a number of an existing category otherwise.
	if (empty($_POST['cat']))
		fatal_lang_error('shd_admin_cannedreplies_thecatisalie', false);
	elseif (empty($_POST['catname']))
		fatal_lang_error('shd_admin_cannedreplies_nocatname', false);

	checkSubmitOnce('check');

	// Otherwise we're doing something with a category.
	if ($_POST['cat'] == 'new')
	{
		$query = wesql::query('
			SELECT MAX(cat_order)
			FROM {db_prefix}helpdesk_cannedreplies_cats');
		list($current_max) = wesql::fetch_row($query);
		wesql::free_result($query);

		// Insert the category.
		wesql::insert('insert',
			'{db_prefix}helpdesk_cannedreplies_cats',
			array(
				'cat_name' => 'string', 'cat_order' => 'int',
			),
			array(
				$_POST['catname'], (int) $current_max + 1,
			),
			array(
				'id_cat',
			)
		);
	}
	else
	{
		// We're updating, apparently.
		wesql::query('
			UPDATE {db_prefix}helpdesk_cannedreplies_cats
			SET cat_name = {string:name}
			WHERE id_cat = {int:cat}',
			array(
				'name' => $_POST['catname'],
				'cat' => (int) $_POST['cat'],
			)
		);
		if (wesql::affected_rows() == 0)
			fatal_lang_error('shd_admin_cannedreplies_thecatisalie', false);
	}

	redirectexit('action=admin;area=helpdesk_cannedreplies');
}

/**
 *	Display the UI for creating a new reply.
 *
 *	@since 2.0
*/
function shd_admin_canned_createreply()
{
	global $context, $txt;

	$context['page_title'] = $txt['shd_admin_cannedreplies_addreply'];
	wetem::load('shd_edit_canned_reply');

	$_REQUEST['cat'] = isset($_REQUEST['cat']) ? (int) $_REQUEST['cat'] : 0;
	$query = wesql::query('
		SELECT cat_name
		FROM {db_prefix}helpdesk_cannedreplies_cats
		WHERE id_cat = {int:cat}',
		array(
			'cat' => $_REQUEST['cat'],
		)
	);

	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannedreplies_thecatisalie', false);
	}

	wesql::free_result($query);

	$context['canned_reply'] = array(
		'id' => 'new',
		'title' => '',
		'body' => '',
		'active' => 1,
		'vis_user' => '',
		'vis_staff' => '',
		'cat' => $_REQUEST['cat'],
		'depts_selected' => array(),
		'depts_available' => array(),
	);
	// Now we need to get the possible departments.
	$query = wesql::query('
		SELECT id_dept, dept_name
		FROM {db_prefix}helpdesk_depts
		ORDER BY dept_order');
	while ($row = wesql::fetch_assoc($query))
		$context['canned_reply']['depts_available'][$row['id_dept']] = $row['dept_name'];
	wesql::free_result($query);

	checkSubmitOnce('register');

	loadSource('Class-Editor');

	$context['postbox'] = new wedit(
		array(
			'id' => 'shd_canned_reply',
			'value' => $context['canned_reply']['body'],
			'buttons' => array(
				array(
					'name' => 'post_button',
					'button_text' => $txt['shd_admin_cannedreplies_addreply'],
					'onclick' => 'return submitThisOnce(this);',
					'accesskey' => 's',
				),
			),
			'preview_type' => 0,
			'width' => '70%',
			'disable_smiley_box' => false,
			'form' => 'cannedreply',
			'drafts' => 'none',
		)
	);
}

function shd_admin_canned_editreply()
{
	global $context, $txt;

	loadSource('Class-Editor');

	$context['page_title'] = $txt['shd_admin_cannedreplies_editreply'];
	wetem::load('shd_edit_canned_reply');

	$_REQUEST['reply'] = isset($_REQUEST['reply']) ? (int) $_REQUEST['reply'] : 0;
	$query = wesql::query('
		SELECT hdcr.title, hdcr.body, hdcr.vis_user, hdcr.vis_staff, hdcr.active, hdcr.id_cat
		FROM {db_prefix}helpdesk_cannedreplies AS hdcr
		WHERE id_reply = {int:reply}',
		array(
			'reply' => $_REQUEST['reply'],
		)
	);

	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannedreplies_thereplyisalie', false);
	}

	$row = wesql::fetch_assoc($query);
	wesql::free_result($query);

	$context['canned_reply'] = array(
		'id' => $_REQUEST['reply'],
		'title' => $row['title'],
		'body' => wedit::un_preparsecode($row['body']),
		'active' => !empty($row['active']),
		'vis_user' => !empty($row['vis_user']),
		'vis_staff' => !empty($row['vis_staff']),
		'cat' => $row['id_cat'],
		'depts_selected' => array(),
		'depts_available' => array(),
	);

	// Now we need to get the possible departments.
	$query = wesql::query('
		SELECT id_dept, dept_name
		FROM {db_prefix}helpdesk_depts
		ORDER BY dept_order');
	while ($row = wesql::fetch_assoc($query))
		$context['canned_reply']['depts_available'][$row['id_dept']] = $row['dept_name'];
	wesql::free_result($query);

	// Now any departments this reply is attached to.
	$query = wesql::query('
		SELECT hdcrd.id_dept
		FROM {db_prefix}helpdesk_cannedreplies_depts AS hdcrd
		WHERE hdcrd.id_reply = {int:reply}',
		array(
			'reply' => $_REQUEST['reply'],
		)
	);
	while ($row = wesql::fetch_assoc($query))
		$context['canned_reply']['depts_selected'][] = $row['id_dept'];
	wesql::free_result($query);

	checkSubmitOnce('register');

	$context['postbox'] = new wedit(
		array(
			'id' => 'shd_canned_reply',
			'value' => $context['canned_reply']['body'],
			'buttons' => array(
				array(
					'name' => 'post_button',
					'button_text' => $txt['shd_admin_cannedreplies_editreply'],
					'onclick' => 'return submitThisOnce(this);',
					'accesskey' => 's',
				),
				array(
					'name' => 'delete',
					'button_text' => $txt['shd_admin_cannedreplies_deletereply'],
					'onclick' => 'return confirm(' . JavaScriptEscape($txt['shd_admin_cannedreplies_deletereply_confirm']) . ') && submitThisOnce(this);',
					'class' => 'delete',
				),
			),
			'preview_type' => 0,
			'width' => '70%',
			'disable_smiley_box' => false,
			'form' => 'cannedreply',
			'drafts' => 'none',
		)
	);
}

function shd_admin_canned_savereply()
{
	checkSession('request');

	loadSource('Class-Editor');

	// If we're deleting this reply, do it first and get it out the way.
	if (!empty($_REQUEST['delete']))
	{
		$_REQUEST['reply'] = isset($_REQUEST['reply']) ? (int) $_REQUEST['reply'] : 0;
		if ($_REQUEST['reply'] > 0)
		{
			// 1. Get the current position.
			$query = wesql::query('
				SELECT reply_order
				FROM {db_prefix}helpdesk_cannedreplies
				WHERE id_reply = {int:reply}',
				array(
					'reply' => $_REQUEST['reply'],
				)
			);
			if (wesql::num_rows($query) == 0)
				redirectexit('action=admin;area=helpdesk_cannedreplies');
			list($old_pos) = wesql::fetch_row($query);
			wesql::free_result($query);
			// 2. Delete the reply itself.
			wesql::query('
				DELETE FROM {db_prefix}helpdesk_cannedreplies
				WHERE id_reply = {int:reply}',
				array(
					'reply' => $_REQUEST['reply'],
				)
			);
			// 3. Shunt the rest up one.
			wesql::query('
				UPDATE {db_prefix}helpdesk_cannedreplies
				SET reply_order = reply_order - 1
				WHERE reply_order > {int:old_pos}',
				array(
					'old_pos' => $old_pos,
				)
			);
			// 4. Delete any attached departments.
			wesql::query('
				DELETE FROM {db_prefix}helpdesk_cannedreplies_depts
				WHERE id_reply = {int:reply}',
				array(
					'reply' => $_REQUEST['reply'],
				)
			);
		}
		redirectexit('action=admin;area=helpdesk_cannedreplies');
	}

	if (empty($_REQUEST['reply']))
		fatal_lang_error('shd_admin_cannedreplies_thereplyisalie', false);

	$_POST['title'] = isset($_POST['title']) ? strtr(westr::htmlspecialchars($_POST['title']), array("\r" => '', "\n" => '', "\t" => '')) : '';
	if (empty($_POST['title']))
		fatal_lang_error('shd_admin_cannedreplies_notitle', false);

	$_REQUEST['shd_canned_reply'] = isset($_POST['shd_canned_reply']) ? $_POST['shd_canned_reply'] : '';

	// If we came from WYSIWYG, we need to convert from HTML to bbc, then unhtml it.
	if (!empty($_REQUEST['shd_canned_reply_mode']))
		wedit::preparseWYSIWYG('shd_canned_reply_mode');

	if (westr::htmltrim(westr::htmlspecialchars($_POST['shd_canned_reply']), ENT_QUOTES) === '')
		fatal_lang_error('shd_admin_cannedreplies_nobody', false);

	$_POST['shd_canned_reply'] = westr::htmlspecialchars($_POST['shd_canned_reply'], ENT_QUOTES);
	wedit::preparsecode($_POST['shd_canned_reply']);

	// Now clean up the rest of the stuff.
	$_POST['vis_user'] = !empty($_POST['vis_user']) ? 1 : 0;
	$_POST['vis_staff'] = !empty($_POST['vis_staff']) ? 1 : 0;
	$_POST['active'] = !empty($_POST['active']) ? 1 : 0;

	// Verify the destination category exists.
	if (!isset($_POST['cat']))
		fatal_lang_error('shd_admin_cannedreplies_thecatisalie', false);

	$query = wesql::query('
		SELECT cat_name
		FROM {db_prefix}helpdesk_cannedreplies_cats
		WHERE id_cat = {int:cat}',
		array(
			'cat' => (int) $_REQUEST['cat'],
		)
	);
	if (wesql::num_rows($query) == 0)
	{
		wesql::free_result($query);
		fatal_lang_error('shd_admin_cannedreplies_thecatisalie', false);
	}
	wesql::free_result($query);

	// Lastly, figure out what departments we're doing.
	$depts_insert = array();
	$query = wesql::query('
		SELECT id_dept
		FROM {db_prefix}helpdesk_depts
		ORDER BY dept_order');
	while ($row = wesql::fetch_assoc($query))
		if (!empty($_POST['dept_' . $row['id_dept']]))
			$depts_insert[] = $row['id_dept'];
	wesql::free_result($query);

	checkSubmitOnce('check');

	if ($_REQUEST['reply'] == 'new')
	{
		// 1. Get the next reply order
		$query = wesql::query('
			SELECT MAX(reply_order)
			FROM {db_prefix}helpdesk_cannedreplies');
		list($current_max) = wesql::fetch_row($query);
		wesql::free_result($query);

		// 2. Insert the row.
		wesql::insert('insert',
			'{db_prefix}helpdesk_cannedreplies',
			array(
				'id_cat' => 'int', 'title' => 'string', 'body' => 'string',
				'vis_user' => 'int', 'vis_staff' => 'int', 'reply_order' => 'int', 'active' => 'int',
			),
			array(
				$_POST['cat'], $_POST['title'], $_POST['shd_canned_reply'],
				$_POST['vis_user'], $_POST['vis_staff'], (int) $current_max + 1, $_POST['active'],
			),
			array(
				'id_cat',
			)
		);

		// 3. Insert the departments.
		$reply_id = wesql::insert_id();
		if (empty($reply_id))
			fatal_lang_error('shd_admin_cannedreplies_notcreated', false);

		$insert = array();
		foreach ($depts_insert as $dept)
			$insert[] = array($dept, $reply_id);

		wesql::insert('insert',
			'{db_prefix}helpdesk_cannedreplies_depts',
			array(
				'id_dept' => 'int', 'id_reply' => 'int',
			),
			$insert,
			array(
				'id_dept', 'id_reply',
			)
		);
	}
	else
	{
		// Verify it exists.
		$query = wesql::query('
			SELECT id_reply
			FROM {db_prefix}helpdesk_cannedreplies
			WHERE id_reply = {int:reply}',
			array(
				'reply' => $_REQUEST['reply'],
			)
		);
		if (wesql::num_rows($query) == 0)
			fatal_lang_error('shd_admin_cannedreplies_thereplyisalie', false);
		wesql::free_result($query);

		// We're updating, apparently.
		wesql::query('
			UPDATE {db_prefix}helpdesk_cannedreplies
			SET title = {string:title},
				body = {string:body},
				vis_user = {int:vis_user},
				vis_staff = {int:vis_staff},
				active = {int:active}
			WHERE id_reply = {int:reply}',
			array(
				'reply' => $_REQUEST['reply'],
				'title' => $_POST['title'],
				'body' => $_POST['shd_canned_reply'],
				'vis_user' => $_POST['vis_user'],
				'vis_staff' => $_POST['vis_staff'],
				'active' => $_POST['active'],
			)
		);

		// Sort out departments. Nuke the existing ones first.
		wesql::query('
			DELETE FROM {db_prefix}helpdesk_cannedreplies_depts
			WHERE id_reply = {int:reply}',
			array(
				'reply' => $_REQUEST['reply'],
			)
		);

		$insert = array();
		foreach ($depts_insert as $dept)
			$insert[] = array($dept, $_REQUEST['reply']);

		wesql::insert('insert',
			'{db_prefix}helpdesk_cannedreplies_depts',
			array(
				'id_dept' => 'int', 'id_reply' => 'int',
			),
			$insert,
			array(
				'id_dept', 'id_reply',
			)
		);
	}

	redirectexit('action=admin;area=helpdesk_cannedreplies');
}

function shd_admin_canned_movereplycat()
{
	global $context, $txt;

	// Before we go any further, establish that the user specified a reply to move and that there is at least one category not including the one the reply is in.
	$_REQUEST['reply'] = isset($_REQUEST['reply']) ? (int) $_REQUEST['reply'] : 0;
	if (empty($_REQUEST['reply']) || $_REQUEST['reply'] < 0)
		fatal_lang_error('shd_admin_cannedreplies_thereplyisalie', false);

	$query = wesql::query('
		SELECT id_cat, reply_order
		FROM {db_prefix}helpdesk_cannedreplies
		WHERE id_reply = {int:reply}',
		array(
			'reply' => $_REQUEST['reply'],
		)
	);
	if (wesql::num_rows($query) == 0)
		fatal_lang_error('shd_admin_cannedreplies_thereplyisalie', false);

	list($current_cat, $current_reply_pos) = wesql::fetch_row($query);
	wesql::free_result($query);

	// So, the reply exists. Now to check categories. We need to verify it regardless of calling context here, so might as well get the entire table.
	$context['cannedreply_cats'] = array();
	$query = wesql::query('
		SELECT id_cat, cat_name
		FROM {db_prefix}helpdesk_cannedreplies_cats
		WHERE id_cat != {int:current_cat}
		ORDER BY cat_order',
		array(
			'current_cat' => $current_cat,
		)
	);
	if (wesql::num_rows($query) == 0)
		fatal_lang_error('shd_admin_cannedreplies_onlyonecat', false);
	while ($row = wesql::fetch_assoc($query))
		$context['cannedreply_cats'][$row['id_cat']] = $row['cat_name'];
	wesql::free_result($query);

	// So, either we're moving, or we're displaying the form. Either way, it's time to make that decision.
	if (empty($_GET['part']) || $_GET['part'] != '2')
	{
		$context['page_title'] = $txt['shd_admin_cannedreplies_move_between_cat'];
		wetem::load('shd_move_reply_cat');

		checkSubmitOnce('register');
	}
	else
	{
		// OK, so they're moving. We know the reply exists, we know the possible list of departments they can move to.
		// 1. Is the new department valid?
		$_REQUEST['newcat'] = isset($_REQUEST['newcat']) ? (int) $_REQUEST['newcat'] : 0;
		if (!isset($context['cannedreply_cats'][$_REQUEST['newcat']]))
			fatal_lang_error('shd_admin_cannedreplies_destnoexist', false);

		// 1a. Everything is valid, just double check it's not a random double submission.
		checkSubmitOnce('check');

		// 2. Everything's OK. Figure out where the reply will move to in the new category.
		$query = wesql::query('
			SELECT MAX(reply_order)
			FROM {db_prefix}helpdesk_cannedreplies
			WHERE id_cat = {int:newcat}',
			array(
				'newcat' => $_REQUEST['newcat'],
			)
		);
		list($newpos) = wesql::fetch_row($query);
		wesql::free_result($query);

		// 3. Move the reply.
		wesql::query('
			UPDATE {db_prefix}helpdesk_cannedreplies
			SET id_cat = {int:newcat},
				reply_order = {int:newpos}
			WHERE id_reply = {int:reply}',
			array(
				'newcat' => $_REQUEST['newcat'],
				'newpos' => (int) $newpos + 1,
				'reply' => $_REQUEST['reply'],
			)
		);

		// 4. Shunt the rest back down.
		wesql::query('
			UPDATE {db_prefix}helpdesk_cannedreplies
			SET reply_order = reply_order - 1
			WHERE id_cat = {int:current_cat}
				AND reply_order > {int:current_pos}',
			array(
				'current_cat' => $current_cat,
				'current_pos' => $current_reply_pos,
			)
		);

		// 5. Scram.
		redirectexit('action=admin;area=helpdesk_cannedreplies');
	}
}

?>