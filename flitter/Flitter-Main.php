<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function flitter_main()
{
	global $settings, $language, $txt;

	if (empty($settings['flitter_showfb']) && empty($settings['flitter_showtwitter']) && empty($settings['flitter_showgoogle']))
		return;

	loadPluginTemplate('Wedge:Flitter', 'Flitter-Main');

	$lang = isset(we::$user['language']) ? we::$user['language'] : $language;
	switch ($lang)
	{
		case 'french':
			$txt['flitter_share'] = 'Partager';
			break;
		case 'german':
			$txt['flitter_share'] = 'Share';
			break;
		case 'english':
		default:
			$txt['flitter_share'] = 'Share Topic';
			break;
	}

	if (!empty($settings['flitter_position']) && $settings['flitter_position'] == 'sidebar')
	{
		wetem::add('sidebar', array('flitter_sidebar' => array()));
		foreach (array('fb', 'twitter', 'google') as $service)
			if (!empty($settings['flitter_show' . $service]))
				wetem::add('flitter_sidebar', 'flitter_' . $service);
	}
	else
	{
		wetem::add_hook('first_post_done', array('flitter_topic' => array()));
		foreach (array('fb', 'twitter', 'google') as $service)
			if (!empty($settings['flitter_show' . $service]))
				wetem::$hooks->add('flitter_topic', 'flitter_' . $service);
	}
}
