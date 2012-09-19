<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSolvedApplyQuickMod(&$quickMod)
{
	global $board, $settings;

	// It must be active in at least one board.
	if (empty($settings['topicsolved_boards']))
		return;

	// Are we in a board?
	if (!empty($board))
	{
		// If so, make sure we're actually in a board that practices topic solved.
		$board_list = unserialize($settings['topicsolved_boards']);
		if (!in_array($board, $board_list))
			return;
	}

	// !!! No permission at present, but it will be an own/any deal
	$quickMod['marksolved'] => array(true, 'marksolved', 'quickMod_marksolved');
}

function quickMod_marksolved($topic_data, $boards_can)
{
	global $settings, $board, $user_info;

	$board_list = unserialize($settings['topicsolved_boards']);

	if (!in_array(0, $boards_can['marksolved_any']))
	{
		foreach ($topic_data as $topic => $this_topic)
		{
			if (!in_array($this_topic['id_board'], $boards_can['marksolvee_any']))
			{
				// So they can't just (un)solve *any* topic. That makes things more complicated. It needs to be their topic and they have to have permission
				if ($this_topic['id_member_started'] != $user_info['id'] || !in_array($this_topic['id_board'], $boards_can['marksolved_own']))
					unset($topic_data[$topic]);
			}
		}
	}

	// Check that all topics are in boards that topic solved is active in.
	foreach ($topic_data as $topic => $this_topic)
		if (!in_array($this_topic['id_board'], $board_list))
			unset($topic_data[$topic]);

	if (empty($topic_data))
		return;

	// Firstly, find all the ones that are currently marked solved - so they can be unmarked.
	$request = wesql::query('
		SELECT id_topic
		FROM {db_prefix}topicsolved
		WHERE id_topic IN ({array_int:topics})',
		array(
			'topics' => array_keys($topic_data),
		)
	);
	$purge_rows = array();
	while ($row = wesql::fetch_row($request))
	{
		$purge_rows[] = $row[0];
		unset($topic_data[$topic]);
	}
	wesql::free_result($request);

	// Purge them.
	if (!empty($purge_rows))
	{
		wesql::query('
			DELETE FROM {db_prefix}topicsolved
			WHERE id_topic IN ({array_int:topics})',
			array(
				'topics' => $purge_rows,
			)
		);

		// Log them in the moderation log
		foreach ($purge_rows as $topic)
			logAction('unsolve', array('topic' => $topic), 'moderate');
	}

	// Anything else left to mark solved?
	if (!empty($topic_data))
	{
		$time = time();
		$insert = array();
		foreach ($topic_data as $topic => $this_topic)
		{
			$insert[] = array($topic, $time, $user_info['id']);
			logAction('solve', array('topic' => $topic), 'moderate');
		}

		wesql::insert('replace',
			'{db_prefix}topicsolved',
			array(
				'id_topic' => 'int', 'solved' => 'int', 'id_member' => 'int',
			),
			$insert,
			array('id_topic')
		);
	}
}

?>