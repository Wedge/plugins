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
	global $txt, $settings, $context;

	loadPluginLanguage('Arantor:UsersOnlineToday', 'OnlineToday');

	if (empty($settings['uot_whoview']))
		$settings['uot_whoview'] = 'members';

	switch ($settings['uot_whoview'])
	{
		case 'any':
			$can_view = true;
			break;
		case 'members':
		default:
			$can_view = we::$is_member;
			break;
		case 'staff':
			$can_view = allowedTo(array('moderate_forum', 'admin_forum'));
			break;
		case 'admin':
			$can_view = we::$is_admin;
			break;
	}

	if (!$can_view)
		return;

	if (empty($settings['uot_type']))
		$settings['uot_type'] = 'today';
	switch ($settings['uot_type'])
	{
		default:
			$settings['uot_type'] = 'today'; // This is deliberate, falling through to 'today' because that's what to use in the event of an invalid type being used.
		case 'today':
			$earliest_time = strtotime('today') - $settings['time_offset'] * 3600;
			break;
		case '24h':
			$earliest_time = time() - 86400;
			break;
		case '7d':
			$earliest_type = time() - 604800;
			break;
	}

	if (empty($settings['uot_order']) || strpos($settings['uot_order'], '_') === false)
		$settings['uot_order'] = 'name_asc';

	list ($sort, $order) = explode('_', $settings['uot_order']);
	if ($sort !== 'name' && $sort !== 'time')
		$sort = 'name';
	if ($order !== 'asc' && $order !== 'desc')
		$order = 'asc';

	$sort_criteria = $sort == 'name' ? 'mem.real_name' : 'mem.last_login';

	$context['users_online_today'] = array();
	$request = wesql::query('
		SELECT mem.id_member, mem.last_login, mem.real_name, mem.show_online
		FROM {db_prefix}members AS mem
		WHERE mem.last_login >= {int:earliest_time}
		ORDER BY {raw:sort_criteria} {raw:sort_order}',
		array(
			'earliest_time' => $earliest_time,
			'sort_criteria' => $sort_criteria,
			'sort_order' => $order,
		)
	);
	$actual = $hidden = 0;
	$mod_forum = allowedTo('moderate_forum');
	while ($row = wesql::fetch_assoc($request))
	{
		$actual++;
		$link = '<a href="<URL>?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';
		if (empty($row['show_online']))
		{
			$hidden++;
			if (!$mod_forum)
				continue;
			$link = '<em>' . $link . '</em>';
		}

		$context['users_online_today'][$row['id_member']] = $link;
	}

	if ($actual)
		$context['uot_users'] = sprintf($txt['users_online_today_userhidden'], number_context('users_online_today_users', $actual), !empty($hidden) ? number_context('users_online_today_hidden', $hidden) : '');

	wetem::after('info_center_usersonline', 'info_center_online_today');
}

function template_info_center_online_today()
{
	global $context, $theme, $txt, $settings;

	echo '
	<section class="ic">
		<we:title>
			<img src="', $theme['images_url'], '/icons/online.gif', '" alt="', $txt['online_users'], '">
			', $txt['users_online_' . $settings['uot_type']], '
		</we:title>';

	if (empty($context['users_online_today']))
		echo '
		<p class="inline smalltext">', $txt['users_online_today_none'], '</p>';
	else
		echo '
		<p class="inline stats">', $context['uot_users'], '</p>
		<p class="inline smalltext">', implode(', ', $context['users_online_today']), '</p>';

	echo '
	</section>';
}
