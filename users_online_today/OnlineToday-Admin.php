<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function uot_admin()
{
	global $admin_areas, $context, $txt;

	loadPluginLanguage('Arantor:UsersOnlineToday', 'OnlineToday-Admin');

	// The name in use here is the plugin's name. It doesn't need to be translatable. Plus the file we need will already be loaded.
	$admin_areas['plugins']['areas']['uot'] = array(
		'label' => $txt['uot'],
		'function' => 'ModifyUOTSettings',
		'icon' => 'scheduled.gif',
		'bigicon' => 'scheduled_tasks.png',
	);
}

function ModifyUOTSettings($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	loadSource('ManageServer');

	$config_vars = array(
		array('select', 'uot_type', array('today' => $txt['uot_today'], '24h' => $txt['uot_24h'], '7d' => $txt['uot_7d'])),
		array('select', 'uot_whoview', array('any' => $txt['uot_whoview_any'], 'members' => $txt['uot_whoview_members'], 'staff' => $txt['uot_whoview_staff'], 'admin' => $txt['uot_whoview_admin'])),
		array('select', 'uot_order', array('name_asc' => $txt['uot_order_name_asc'], 'name_desc' => $txt['uot_order_name_desc'], 'time_asc' => $txt['uot_order_time_asc'], 'name_desc' => $txt['uot_order_time_desc'])),
	);

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=uot');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=uot;save';
	$context['settings_title'] = $context['page_title'] = $txt['uot'];
	wetem::load('show_settings');
	prepareDBSettingContext($config_vars);
}

?>