<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*
	This is the 'Users Online Today' plugin for Wedge.
	Note that the structure of this file is not typical: the source and template are in the same file.
	The contents are still structurally separate but the two are in the same file for efficiency.
	-- Arantor
*/

function getOnlineToday()
{
	global $txt, $user_info, $language, $modSettings, $context, $scripturl;

	$context['users_online_today'] = array();
	$request = wesql::query('
		SELECT mem.id_member, mem.last_login, mem.real_name, mem.show_online, mg.online_color, mg.group_name
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = CASE WHEN mem.id_group = {int:reg_mem_group} THEN mem.id_post_group ELSE mem.id_group END)
		WHERE mem.last_login >= {int:midnight}
		ORDER BY mem.real_name',
		array(
			'reg_mem_group' => 0,
			'midnight' => strtotime('today') - $modSettings['time_offset'] * 3600,
		)
	);
	$hidden = 0;
	$mod_forum = allowedTo('moderate_forum');
	while ($row = wesql::fetch_assoc($request))
	{
		$link = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '"' . (!empty($row['online_color']) ? ' style="color: ' . $row['online_color'] . '"' : '') . '>' . $row['real_name'] . '</a>';
		if (empty($row['show_online']))
		{
			$hidden++;
			if (!$mod_forum)
				continue;
			$link = '<i>' . $link . '</i>';
		}

		$context['users_online_today'][$row['id_member']] = $link;
	}

	// To avoid having to load language files just for a few strings, we embed it here, too.
	$lang = isset($user_info['language']) ? $user_info['language'] : $language;
	switch ($lang)
	{
		case 'english':
		default:
			$txt['users_online_today'] = 'Visitors Today (%1$s%2$s)';
			$txt['users_online_today_users_1'] = '%s user';
			$txt['users_online_today_users_n'] = '%s users';
			$txt['users_online_today_hidden_n'] = ' plus %s hidden'; // doesn't matter how many are hidden, the same applies in English: 1 hidden, 10 hidden etc.
			$txt['users_online_today_none'] = 'No users have been online today.';
			break;
	}
	$txt['users_online_today'] = sprintf($txt['users_online_today'], number_context('users_online_today_users', count($context['users_online_today'])), !empty($hidden) ? number_context('users_online_today_hidden', $hidden) : '');

	wetem::load('info_center_online_today', 'info_center', 'add');
}

function template_info_center_online_today()
{
	global $context, $settings, $txt;

	echo '
			<we:title2>
				<img src="', $settings['images_url'], '/icons/online.gif', '" alt="', $txt['online_users'], '">
				', $txt['users_online_today'], '
			</we:title2>
			<p class="inline smalltext">', empty($context['users_online_today']) ? $txt['users_online_today_none'] : implode(', ', $context['users_online_today']), '</p>';
}

?>