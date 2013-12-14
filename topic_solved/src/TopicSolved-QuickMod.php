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

	$quickMod['marksolved'] = array(true, 'topicsolved', 'quickMod_marksolved');
}

function quickMod_marksolved($topic_data, $boards_can)
{
	global $settings;

	$board_list = unserialize($settings['topicsolved_boards']);

	if (!in_array(0, $boards_can['topicsolved_any']))
	{
		foreach ($topic_data as $id_topic => $this_topic)
		{
			if (!in_array($this_topic['id_board'], $boards_can['topicsolved_any']))
			{
				// So they can't just (un)solve *any* topic. That makes things more complicated. It needs to be their topic and they have to have permission
				if ($this_topic['id_member_started'] != MID || !in_array($this_topic['id_board'], $boards_can['topicsolved_own']))
					unset($topic_data[$id_topic]);
			}
		}
	}

	// Check that all topics are in boards that topic solved is active in.
	foreach ($topic_data as $id_topic => $this_topic)
		if (!in_array($this_topic['id_board'], $board_list))
			unset($topic_data[$id_topic]);

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
		unset($topic_data[$row[0]]);
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
		foreach ($purge_rows as $id_topic)
			logAction('unsolve', array('topic' => $id_topic), 'moderate');
	}

	// Anything else left to mark solved?
	if (!empty($topic_data))
	{
		$time = time();
		$insert = array();
		foreach ($topic_data as $id_topic => $this_topic)
		{
			$insert[] = array($id_topic, $time, MID);
			logAction('solve', array('topic' => $id_topic), 'moderate');
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