<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function EditHistory()
{
	// Load our resources
	loadPluginTemplate('Arantor:EditHistory', 'EditHistory');
	loadPluginLanguage('Arantor:EditHistory', 'EditHistory');

	$subactions = array(
		'popup' => 'HistoryPopup',
		'view' => 'HistoryView',
		'compare' => 'HistoryCompare',
	);
	$sa = !empty($_GET['sa']) && !empty($subactions[$_GET['sa']]) ? $_GET['sa'] : 'popup';

	$subactions[$sa]();
}

function HistoryPopup()
{
	global $context, $txt, $user_profile;

	// Load other stuff we need
	loadLanguage('Help');
	wetem::hide();

	// Go get the topic title, and validate that the information provided is valid.
	$context['versions'] = array();
	if (!empty($_GET['topic']) && !empty($_GET['msg']))
	{
		$_GET['topic'] = (int) $_GET['topic'];
		$_GET['msg'] = (int) $_GET['msg'];
		$query = wesql::query('SELECT m.id_topic, m.subject, m.modified_member, m.modified_name, m.modified_time,
				m.id_member, m.poster_name, m.poster_time
			FROM {db_prefix}messages AS m
			INNER JOIN {db_prefix}topics AS t ON (m.id_topic = t.id_topic)
			WHERE m.id_msg = {int:msg}
				AND t.id_topic = {int:topic}',
			array(
				'msg' => $_GET['msg'],
				'topic' => $_GET['topic'],
			)
		);
		if (wesql::num_rows($query) != 0)
		{
			$row = wesql::fetch_assoc($query);
			$context['versions']['current'] = array(
				'time' => !empty($row['modified_time']) ? $row['modified_time'] : $row['poster_time'],
				'id_member' => !empty($row['modified_member']) ? $row['modified_member'] : $row['id_member'],
				'name' => !empty($row['modified_name']) ? $row['modified_name'] : $row['modified_name'],
			);
			$context['page_title'] = $txt['edit_history'] . ' - ' . $row['subject'];
		}
		wesql::free_result($query);

		if (!empty($context['versions']))
		{
			// OK, so let's get all the historical versions for a nice list.
			$query = wesql::query('
				SELECT id_edit, modified_member, modified_name, modified_time
				FROM {db_prefix}edit_history
				WHERE id_msg = {int:msg}
				ORDER BY modified_time DESC',
				array(
					'msg' => $_GET['msg'],
				)
			);

			while ($row = wesql::fetch_assoc($query))
				$context['versions'][$row['id_edit']] = array(
					'time' => $row['modified_time'],
					'id_member' => $row['modified_member'],
					'name' => $row['modified_name'],
				);
		}
	}

	if (empty($context['versions']))
	{
		$context['page_title'] = $txt['edit_history'];
		$context['popup_contents'] = $txt['edit_history_invalid'];
		loadTemplate('GenericPopup');
		wetem::load('popup');
	}
	else
	{
		$members = array();
		foreach ($context['versions'] as $id => $data)
			if ($data['id_member'] != 0)
				$members[] = $data['id_member'];

		if (!empty($members))
		{
			$members = loadMemberData(array_flip(array_flip($members)), false, 'minimal');
			foreach ($context['versions'] as $id => $data)
			{
				if (isset($user_profile[$data['id_member']]))
					$context['versions'][$id]['name'] = $user_profile[$data['id_member']]['member_name'];
				else
					unset ($context['versions'][$id]['id_member']);
				$context['versions'][$id]['time_format'] = timeformat($data['time']);
			}
		}
		wetem::load('historylist');
	}
}

function HistoryView()
{
	global $context, $txt, $user_profile;

	// Go get the topic title, and validate that the information provided is valid.
	$context['versions'] = array();
	if (!empty($_GET['topic']) && !empty($_GET['msg']) && !empty($_GET['edit']))
	{
		$_GET['topic'] = (int) $_GET['topic'];
		$_GET['msg'] = (int) $_GET['msg'];

		if ($_GET['edit'] == 'current')
		{
			$query = wesql::query('SELECT m.id_topic, m.subject, m.modified_member, m.modified_name, m.modified_time,
					m.id_member, m.poster_name, m.poster_time, m.smileys_enabled, m.body
				FROM {db_prefix}messages AS m
				INNER JOIN {db_prefix}topics AS t ON (m.id_topic = t.id_topic)
				WHERE m.id_msg = {int:msg}
					AND t.id_topic = {int:topic}',
				array(
					'msg' => $_GET['msg'],
					'topic' => $_GET['topic'],
				)
			);
		}
		else
		{
			$_GET['edit'] = (int) $_GET['edit'];
			$query = wesql::query('SELECT m.id_topic, m.subject, eh.modified_member, eh.modified_name, eh.modified_time,
					eh.modified_member AS id_member, eh.modified_name AS poster_name, eh.modified_time AS poster_time, m.smileys_enabled, eh.body
				FROM {db_prefix}messages AS m
				INNER JOIN {db_prefix}topics AS t ON (m.id_topic = t.id_topic)
				INNER JOIN {db_prefix}edit_history AS eh ON (m.id_msg = eh.id_msg AND eh.id_edit = {int:edit})
				WHERE m.id_msg = {int:msg}
					AND t.id_topic = {int:topic}',
				array(
					'msg' => $_GET['msg'],
					'topic' => $_GET['topic'],
					'edit' => $_GET['edit'],
				)
			);
		}

		if (!empty($query) && wesql::num_rows($query) != 0)
		{
			$row = wesql::fetch_assoc($query);
			$context['post_details'] = array(
				'time' => !empty($row['modified_time']) ? $row['modified_time'] : $row['poster_time'],
				'id_member' => !empty($row['modified_member']) ? $row['modified_member'] : $row['id_member'],
				'name' => !empty($row['modified_name']) ? $row['modified_name'] : $row['modified_name'],
				'body' => parse_bbc($row['body'], 'history', array('smileys' => !empty($row['smileys_enabled']))),
				'current' => $_GET['edit'] == 'current',
			);
			if (!empty($context['post_details']['id_member']))
			{
				$member = loadMemberData($context['post_details']['id_member'], false, 'minimal');
				if (!empty($member))
					$context['post_details']['name'] = '<a href="<URL>?action=profile;u=' . $context['post_details']['id_member'] . '">' . $user_profile[$context['post_details']['id_member']]['member_name'] . '</a>';
			}
			$context['page_title'] = $txt['edit_history'] . ' - ' . $row['subject'];
			wetem::load('view_post');

			$context['linktree'][] = array(
				'name' => $row['subject'],
				'url' => '<URL>?topic=' . $_GET['topic'] . '.msg' . $_GET['msg'] . '#msg' . $_GET['msg'],
			);
			$context['linktree'][] = array(
				'name' => $txt['edit_history'],
				'url' => '<URL>?action=edithistory;sa=view;topic=' . $_GET['topic'] . '.0;msg=' . $_GET['msg'] . ';edit=' . $_GET['edit'],
			);
		}
		wesql::free_result($query);
	}

	if (empty($context['post_details']))
		fatal_lang_error('edit_history_invalid', false);
}

function HistoryCompare()
{

}
