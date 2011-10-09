<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function flitter_admin()
{
	global $admin_areas, $context;

	// The name in use here is the plugin's name. It doesn't need to be translatable. Plus the file we need will already be loaded.
	$admin_areas['plugins']['areas']['flitter'] = array(
		'label' => 'Flitter',
		'function' => 'ModifyFlitterSettings',
		'icon' => 'mgallery.png',
		'bigicon' => $context['plugins_url']['Arantor:Flitter'] . '/flitter.png',
	);
}

function ModifyFlitterSettings($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	loadSource('ManageServer');
	loadPluginLanguage('Arantor:Flitter', 'Flitter-Admin');

	if (empty($modSettings['allow_guestAccess']))
		$config_vars = array(
			array('desc', 'flitter_not_available'),
		);
	else
		$config_vars = array(
			array('desc', 'flitter_desc'),
			array('select', 'flitter_position', array('topic' => $txt['flitter_position_topic'], 'sidebar' => $txt['flitter_position_sidebar'])),
			array('title', 'flitter_fb'),
			array('check', 'flitter_showfb'),

			array('title', 'flitter_twitter'),
			array('check', 'flitter_showtwitter'),
			$txt['flitter_twitter_via_desc'],
			array('text', 'flitter_twitter_via'),
			array('text', 'flitter_twitter_related'),
			array('text', 'flitter_twitter_related_desc'),

			array('title', 'flitter_google'),
			array('check', 'flitter_showgoogle'),
		);

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=flitter');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=flitter;save';
	$context['settings_title'] = $context['page_title'] = $txt['flitter'];
	loadBlock('show_settings');
	prepareDBSettingContext($config_vars);
}

?>