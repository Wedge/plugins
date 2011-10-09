<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function flitter_main()
{
	global $modSettings, $user_info, $language, $txt;

	if (empty($modSettings['flitter_showfb']) && empty($modSettings['flitter_showtwitter']) && empty($modSettings['flitter_showgoogle']))
		return;

	loadPluginTemplate('Arantor:Flitter', 'Flitter-Main');

	$lang = isset($user_info['language']) ? $user_info['language'] : $language;
	switch ($lang)
	{
		case 'english':
		default:
			$txt['flitter_share'] = 'Share Topic';
			break;
	}

	if (!empty($modSettings['flitter_position']) && $modSettings['flitter_position'] == 'sidebar')
	{
		loadLayer('flitter_sidebar', 'sidebar', 'lastchild');
		$dest = 'flitter_sidebar';
	}
	else
	{
		loadLayer('flitter_topic', 'default', 'firstchild');
		$dest = 'flitter_topic';
	}

	$services = array('fb', 'twitter', 'google');
	foreach ($services as $service)
		if (!empty($modSettings['flitter_show' . $service]))
			loadBlock('flitter_' . $service, $dest, 'add');
}

?>