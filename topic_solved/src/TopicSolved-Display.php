<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSolvedDisplay()
{
	global $context, $txt, $board, $topic, $topicinfo, $settings;

	loadPluginLanguage('Arantor:TopicSolved', 'lang/TopicSolved-Display');

	// Check if the current board is in the list of boards practising Topic Solved, leave if not.
	$board_list = !empty($settings['topicsolved_boards']) ? unserialize($settings['topicsolved_boards']) : array();
	if (!in_array($board, $board_list))
		return;

	$request = wesql::query('
		SELECT id_topic, solved, ts.id_member, mem.real_name
		FROM {db_prefix}topicsolved AS ts
			LEFT JOIN {db_prefix}members AS mem ON (ts.id_member = mem.id_member)
		WHERE id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	if (wesql::num_rows($request) != 0)
	{
		$context['topic_solved'] = wesql::fetch_assoc($request);
		// Generate the right message
		if (empty($context['topic_solved']['id_member']))
			$context['topic_solved']['message'] = sprintf($txt['topic_was_solved_missing_author'], on_timeformat($context['topic_solved']['solved']));
		elseif ($topicinfo['id_member_started'] == $context['topic_solved']['id_member'])
			$context['topic_solved']['message'] = sprintf($txt['topic_was_solved_author'], on_timeformat($context['topic_solved']['solved']));
		else
			$context['topic_solved']['message'] = sprintf($txt['topic_was_solved_non_author'], '<a href="<URL>?action=profile;u=' . $context['topic_solved']['id_member'] . '">' . $context['topic_solved']['real_name'] . '</a>', on_timeformat($context['topic_solved']['solved']));

		wetem::before('report_success', 'topic_solved_warning');

		add_css('
	.solved { color: ' . $settings['topicsolved_fg'] . '; background-color: ' . $settings['topicsolved_bg1'] . ' }');
	}

	if (allowedTo('topicsolved_any') || (allowedTo('topicsolved_own') && $topicinfo['id_member_started'] == MID))
	{
		$context['can_solve'] = true;
		$nav = array(
			'marksolved' => array(
				'test' => 'can_solve',
				'text' => !empty($context['topic_solved']) ? 'topic_mark_unsolved' : 'topic_mark_solved',
				'url' => '<URL>?action=marksolved;topic=' . $context['current_topic'],
			),
		);
		add_css('
	#modbuttons a.marksolved { background-image: url(' . $context['plugins_url']['Arantor:TopicSolved'] . '/img/' . (!empty($context['topic_solved']) ? 'un' : '') . 'solved.gif) }');

		$context['nav_buttons']['mod'] = array_insert($context['nav_buttons']['mod'], 'move', $nav, false);
	}
}

// Yes, yes, I know this is naughty. But loading an extra file for this silly little function? REALLY?
function template_topic_solved_warning()
{
	global $context;

	echo '
		<div class="description solved">
			<img src="', $context['plugins_url']['Arantor:TopicSolved'], '/img/tick.png"> ', $context['topic_solved']['message'], '
		</div>';
}

?>