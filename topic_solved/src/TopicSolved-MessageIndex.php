<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSolvedMessageIndex()
{
	global $context, $txt, $board, $board_info, $settings;

	loadPluginLanguage('Arantor:TopicSolved', 'lang/TopicSolved-MessageIndex');

	// Check if the current board is in the list of boards practising Topic Solved, leave if not.
	$board_list = !empty($settings['topicsolved_boards']) ? unserialize($settings['topicsolved_boards']) : array();
	if (!in_array($board_info['id'], $board_list))
		return;

	if (empty($context['topics']))
		return;

	$topic_ids = array_keys($context['topics']);
	$request = wesql::query('
		SELECT id_topic
		FROM {db_prefix}topicsolved
		WHERE id_topic IN ({array_int:topics})',
		array(
			'topics' => $topic_ids,
		)
	);
	while (list ($id) = wesql::fetch_row($request))
	{
		$context['topics'][$id]['style'] .= ' solved';
		$context['topics'][$id]['first_post']['icon_url'] = $context['plugins_url']['Arantor:TopicSolved'] . '/img/tick.png';
	}

	if (wesql::num_rows($request) > 0 && !empty($settings['topicsolved_bg1']) && !empty($settings['topicsolved_bg2']) && !empty($settings['topicsolved_fg']))
		add_css('
	.solved { color: ' . $settings['topicsolved_fg'] . ' } .windowbg.solved { background-color: ' . $settings['topicsolved_bg1'] . ' } .windowbg2.solved { background-color: ' . $settings['topicsolved_bg2'] . ' }');
}

// Since the usual case for this function is message index, save something by putting this here.
function topicSolvedQuickModeration(&$quickmod)
{
	global $context, $txt, $board, $board_info, $settings;

	loadPluginLanguage('Arantor:TopicSolved', 'lang/TopicSolved-MessageIndex');

	// Do permission test for 'any' in this board (or for multiple boards if it is search)
	if (!empty($board))
	{
		$board_list = !empty($settings['topicsolved_boards']) ? unserialize($settings['topicsolved_boards']) : array();
		if (!in_array($board_info['id'], $board_list))
			return;
	}
	$quickmod['marksolved'] = $txt['quick_mod_marksolved'];
}

?>