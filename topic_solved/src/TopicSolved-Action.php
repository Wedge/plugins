<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSolvedAction()
{
	global $context, $txt, $board, $topic, $settings, $user_info;

	if (empty($topic) || empty($board))
		redirectexit();

	$board_list = !empty($settings['topicsolved_boards']) ? unserialize($settings['topicsolved_boards']) : array();
	if (!in_array($board, $board_list))
		redirectexit();

	// So, we need to know whether it is solved. Load.php will already have identified whether we can see the topic.
	$request = wesql::query('
		SELECT t.id_member_started, ts.solved
		FROM {db_prefix}topics AS t
			LEFT JOIN {db_prefix}topicsolved AS ts ON (t.id_topic = ts.id_topic)
		WHERE t.id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	list ($topic_starter, $solved) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Can we mark this solved?
	// !!! Nicer error
	if (!allowedTo('topicsolved_any') && ($topic_starter != $user_info['id'] || !allowedTo('topicsolved_own')))
		fatal_lang_error('no_access');

	if (empty($solved))
	{
		wesql::insert('replace',
			'{db_prefix}topicsolved',
			array(
				'id_topic' => 'int', 'solved' => 'int', 'id_member' => 'int',
			),
			array(
				$topic, time(), $user_info['id'],
			),
			array('id_topic')
		);
		logAction('solve', array('topic' => $topic), 'moderate');
		redirectexit('topic=' . $topic . '.0');
	}
	else
	{
		wesql::query('
			DELETE FROM {db_prefix}topicsolved
			WHERE id_topic = {int:topic}',
			array(
				'topic' => $topic,
			)
		);
		logAction('unsolve', array('topic' => $topic), 'moderate');
		redirectexit('topic=' . $topic . '.0');
	}
}

?>