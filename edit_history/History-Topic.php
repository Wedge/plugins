<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function loadHistory(&$messages, &$times, &$all_posters)
{
	global $context;

	$context['post_histories'] = array();
	$query = wesql::query('SELECT id_msg, COUNT(id_edit) AS count
		FROM {db_prefix}edit_history
		WHERE id_msg IN ({array_int:msgs})
		GROUP BY id_msg',
		array(
			'msgs' => $messages,
		)
	);
	while ($row = wesql::fetch_assoc($query))
		if (!empty($row['count']))
			$context['post_histories'][$row['id_msg']] = $row['count'];
	wesql::free_result($query);
}

function historyMenu()
{
	global $context, $txt;

	if (empty($context['post_histories']))
		return;

	loadPluginLanguage('Arantor:EditHistory', 'EditHistory');

	// We don't care about the msgs at this point, just the counts.
	$context['post_histories_str'] = array_flip($context['post_histories']);
	foreach ($context['post_histories_str'] as $k => $v)
	{
		$txt['dynamic_history_' . $k] = number_context('view_history', $k);
		$context['mini_menu_items']['action']['hi'] = array(
			'caption' => 'dynamic_history_' . $k,
			'action' => '<URL>?action=edithistory;sa=popup;topic=' . $context['current_topic'] . '.' . $context['start'] . ';msg=%1%',
			'class' => 'edit_button',
			'click' => 'return reqWin(this);',
		);
		$context['mini_menu_items_show']['action']['hi'] = true;
	}
}

function historyLink(&$counter, &$output)
{
	global $context;

	if (!empty($context['post_histories'][$output['id']]))
		$context['mini_menu']['action'][$output['id']][] = 'hi/' . $context['post_histories'][$output['id']];
}
