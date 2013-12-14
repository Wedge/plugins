<?php
/**
 * WedgeDesk
 *
 * This file deals with changes for the board index for board integration.
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

function shd_add_to_boardindex(&$boardIndexOptions, &$categories)
{
	global $context, $settings, $board, $txt;

	// Does the category exist? If it has no boards, it actually might not exist, daft as it sounds.
	// But it's more tricky than that, too! We need to be at the board index, not in a child board.
	if (!empty($board) || empty($settings['helpdesk_active']))
		return;

	// OK, so what helpdesks are we displaying?
	$depts = shd_allowed_to('access_helpdesk', false);
	if (empty($depts))
		return;

	$cat_list = array();
	$query = wesql::query('
		SELECT id_dept, dept_name, description, board_cat, before_after
		FROM {db_prefix}helpdesk_depts
		WHERE id_dept IN ({array_int:depts})
		ORDER BY before_after DESC, dept_order',
		array(
			'depts' => $depts,
		)
	);
	$depts = array_flip($depts);
	while ($row = wesql::fetch_assoc($query))
	{
		if ($row['board_cat'] == 0)
		{
			unset($depts[$row['id_dept']]);
			continue;
		}

		$depts[$row['id_dept']] = $row;
		$cat_list[] = $row['board_cat'];
		$context['dept_list'][$row['id_dept']] = array(
			'id_dept' => $row['id_dept'],
			'dept_name' => $row['dept_name'],
			'dept_desc' => $row['description'],
			'tickets' => array(
				'open' => 0,
				'closed' => 0,
				'assigned' => 0,
			),
			'new' => false,
		);
	}
	if (empty($context['dept_list']))
		return;

	$cat_list = array_unique($cat_list);

	// Do we have all these categories?
	foreach ($cat_list as $k => $v)
		if (!empty($categories[$v]))
			unset($cat_list[$k]);

	if (!empty($cat_list))
	{
		// Uh oh, we have to load a category or two.
		$new_cats = array();
		$request = wesql::query('
			SELECT c.id_cat, c.name, c.can_collapse, IFNULL(cc.id_member, 0) AS is_collapsed
			FROM {db_prefix}categories AS c
				LEFT JOIN {db_prefix}collapsed_categories AS cc ON (cc.id_cat = c.id_cat AND cc.id_member = {int:current_member})
			WHERE c.id_cat IN ({array_int:cat})',
			array(
				'cat' => $cat_list,
				'current_member' => MID,
			)
		);
		while ($this_cat = wesql::fetch_assoc($request))
		{
			$new_cats[$this_cat['id_cat']] = array(
				'id' => $this_cat['id_cat'],
				'name' => $this_cat['name'],
				'is_collapsed' => isset($this_cat['can_collapse']) && $this_cat['can_collapse'] == 1 && $this_cat['is_collapsed'] > 0,
				'can_collapse' => isset($this_cat['can_collapse']) && $this_cat['can_collapse'] == 1,
				'collapse_href' => isset($this_cat['can_collapse']) ? '<URL>?action=collapse;c=' . $this_cat['id_cat'] . ';sa=' . ($this_cat['is_collapsed'] > 0 ? 'expand;' : 'collapse;') . $context['session_var'] . '=' . $context['session_id'] . '#c' . $this_cat['id_cat'] : '',
				'collapse_image' => isset($this_cat['can_collapse']) ? '<img src="' . ASSETS . '/' . ($this_cat['is_collapsed'] > 0 ? 'expand.gif" alt="+"' : 'collapse.gif" alt="-"') . '>' : '',
				'href' => '<URL>?category=' . $this_cat['id_cat'],
				'boards' => array(),
				'new' => false,
			);
			$new_cats[$this_cat['id_cat']]['link'] = '<a id="c' . $this_cat['id_cat'] . '" href="' . (isset($this_cat['can_collapse']) ? $new_cats[$this_cat['id_cat']]['collapse_href'] : $new_cats[$this_cat['id_cat']]['href']) . '">' . $this_cat['name'] . '</a>';
		}
		wesql::free_result($request);

		// So, did we add any new categories? If we didn't, something's wrong - abort safely NOW.
		if (empty($new_cats))
			return;

		// OK, so we have some categories to integrate.
		$old_cats = $categories;
		$categories = array();

		$request = wesql::query('
			SELECT id_cat
			FROM {db_prefix}categories
			ORDER BY cat_order');
		while ($row = wesql::fetch_assoc($request))
		{
			if (isset($old_cats[$row['id_cat']]))
				$categories[$row['id_cat']] = $old_cats[$row['id_cat']];
			elseif (isset($new_cats[$row['id_cat']]))
				$categories[$row['id_cat']] = $new_cats[$row['id_cat']];
		}
		wesql::free_result($request);
	}

	// Last but not least, fix up the replacements and some figuring out.
	shd_get_ticket_counts();
	shd_get_unread_departments();

	// So, OK, the categories exist. Now we need to create our magic boards, and integrate them.
	// First we do the after's, in order.
	foreach ($depts as $dept)
	{
		if (empty($dept['before_after']))
			continue;
		$dept['link'] = count($depts) != 0 ? ';dept=' . $dept['id_dept'] : '';
		$new_board = shd_dept_board($dept);

		$categories[$dept['board_cat']]['boards'][$new_board['id']] = $new_board;
	}

	// OK, now for the before's. Because we're merging, that means we're doing them last-first.
	$depts = array_reverse($depts);
	foreach ($depts as $dept)
	{
		if (!empty($dept['before_after']))
			continue;
		$dept['link'] = count($depts) != 0 ? ';dept=' . $dept['id_dept'] : '';
		$new_board = shd_dept_board($dept);

		$categories[$dept['board_cat']]['boards'] = array_merge(
			array($new_board['id'] => $new_board),
			$categories[$dept['board_cat']]['boards']
		);
	}

	if (empty($context['shd_buffer_preg_replacements']))
		$context['shd_buffer_preg_replacements'] = array();

	foreach ($context['dept_list'] as $dept => $dept_details)
	{
		// Inject the count of tickets.
		$dept_id = '~' . preg_quote(number_context('redirects', -$dept), '~') . '~';
		$context['shd_buffer_preg_replacements'][$dept_id] = $dept_details['tickets']['open'] . ' ' . ($dept_details['tickets']['open'] == 1 ? $txt['shd_open_ticket'] : $txt['shd_open_tickets']);
	}

	add_css('.boardstatus.helpdesk_on { background:url("' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/helpdesk_on.png") 0 0 no-repeat } .boardstatus.helpdesk_off { background:url("' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/helpdesk_off.png") 0 0 no-repeat }');
}

function shd_dept_board($dept)
{
	global $txt, $context;

	return array(
		'id' => 'shd' . $dept['id_dept'],
		'shd' => true,
		'name' => $dept['dept_name'],
		'description' => $dept['description'],
		'new' => !empty($context['dept_list'][$dept['id_dept']]['new']),
		'custom_class' => empty($context['dept_list'][$dept['id_dept']]['new']) ? 'helpdesk_off' : 'helpdesk_on',
		'children_new' => false,
		'topics' => 0,
		'posts' => -$dept['id_dept'],
		'is_redirect' => true,
		'redirect_newtab' => false,
		'unapproved_topics' => 0,
		'unapproved_posts' => 0,
		'can_approve_posts' => false,
		'href' => '<URL>?' . $context['shd_home'] . $dept['link'],
		'link' => '<a href="<URL>?' . $context['shd_home'] . $dept['link'] . '">' . $dept['dept_name'] . '</a>',
		'last_post' => array(
			'id' => 0,
			'time' => $txt['not_applicable'],
			'timestamp' => forum_time(true, 0),
			'subject' => '',
			'member' => array(
				'id' => 0,
				'username' => $txt['not_applicable'],
				'name' => '',
				'href' => '',
				'link' => $txt['not_applicable'],
			),
			'start' => 'msg0',
			'topic' => 0,
			'href' => '',
			'link' => $txt['not_applicable'],
		),
	);
}

function shd_get_ticket_counts()
{
	global $context;

	if (empty($context['dept_list']))
		return;

	$query = wesql::query('
		SELECT id_dept, status, COUNT(status) AS tickets
		FROM {db_prefix}helpdesk_tickets AS hdt
		WHERE {query_see_ticket}
			AND id_dept IN ({array_int:depts})
			AND status != {int:deleted}
		GROUP BY id_dept, status
		ORDER BY null',
		array(
			'depts' => array_keys($context['dept_list']),
			'deleted' => TICKET_STATUS_DELETED,
		)
	);
	while ($row = wesql::fetch_assoc($query))
		$context['dept_list'][$row['id_dept']]['tickets'][$row['status'] == TICKET_STATUS_CLOSED ? 'closed' : 'open'] += $row['tickets'];
}

function shd_get_unread_departments()
{
	global $context;

	$query = wesql::query('
		SELECT hdd.id_dept, MAX(hdt.id_last_msg) AS last_msg, MAX(hdlr.id_msg) AS last_read
		FROM {db_prefix}helpdesk_depts AS hdd
			INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdd.id_dept = hdt.id_dept)
			LEFT JOIN {db_prefix}helpdesk_log_read AS hdlr ON (hdt.id_ticket = hdlr.id_ticket AND hdlr.id_member = {int:user_id})
		WHERE hdd.id_dept IN ({array_int:dept_list})
			AND {query_see_ticket}
			AND hdt.last_updated > {int:the_last_week}
		GROUP BY hdd.id_dept',
		array(
			'dept_list' => array_keys($context['dept_list']),
			'the_last_week' => time() - (86400 * 7),
			'user_id' => MID,
		)
	);
	while ($row = wesql::fetch_assoc($query))
	{
		$row['last_read'] = (int) $row['last_read'];
		if ($row['last_msg'] > $row['last_read'])
			$context['dept_list'][$row['id_dept']]['new'] = true;
	}
}

?>