<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function recentitems_admin()
{
	global $admin_areas, $context, $txt;

	loadPluginLanguage('Arantor:RecentItems', 'Recent-Admin');

	// The name in use here is the plugin's name. It doesn't need to be translatable. Plus the file we need will already be loaded.
	$admin_areas['plugins']['areas']['recent'] = array(
		'label' => $txt['recent_items'],
		'function' => 'ModifyRecentItemsSettings',
		'icon' => 'scheduled.gif',
		'bigicon' => 'scheduled_tasks.png',
	);
}

function ModifyRecentItemsSettings($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	loadSource('ManageServer');

	$config_vars = array(
		array('int', 'recentitems_show'),
		array('select', 'recentitems_posttopic', array('post' => $txt['recentitems_as_post'], 'topic' => $txt['recentitems_as_topic'])),
		'',
		array('check', 'recentitems_sidebar_infocenter'),
	);

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=recent');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=recent;save';
	$context['settings_title'] = $context['page_title'] = $txt['recent_items'];
	wetem::load('show_settings');
	prepareDBSettingContext($config_vars);
}

?>