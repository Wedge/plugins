<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function flitter_main()
{
	global $settings, $user_info, $language, $txt;

	if (empty($settings['flitter_showfb']) && empty($settings['flitter_showtwitter']) && empty($settings['flitter_showgoogle']))
		return;

	loadPluginTemplate('Arantor:Flitter', 'Flitter-Main');

	$lang = isset($user_info['language']) ? $user_info['language'] : $language;
	switch ($lang)
	{
		case 'french':
			$txt['flitter_share'] = 'Réseaux sociaux';
			break;
		case 'english':
		default:
			$txt['flitter_share'] = 'Share Topic';
			break;
	}

	if (!empty($settings['flitter_position']) && $settings['flitter_position'] == 'sidebar')
		wetem::add('sidebar', array('flitter' => array()));
	else
		wetem::first('default', array('flitter' => array()));

	foreach (array('fb', 'twitter', 'google') as $service)
		if (!empty($settings['flitter_show' . $service]))
			wetem::add('flitter', 'flitter_' . $service);
}

?>