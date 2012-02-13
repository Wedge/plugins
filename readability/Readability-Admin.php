<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function readability_admin()
{
	global $admin_areas, $context;

	// The name in use here is the plugin's name. It doesn't need to be translatable. Plus the file we need will already be loaded.
	$admin_areas['plugins']['areas']['readability'] = array(
		'label' => 'Readability',
		'function' => 'ModifyReadabilitySettings',
		'icon' => 'posts.gif',
		'bigicon' => $context['plugins_url']['Arantor:Readability'] . '/readability.png',
	);
}

function ModifyReadabilitySettings($return_config = false)
{
	global $txt, $scripturl, $context, $settings;

	loadSource('ManageServer');
	loadPluginLanguage('Arantor:Readability', 'Readability-Admin');

	if (empty($settings['allow_guestAccess']))
		$config_vars = array(
			array('desc', 'readability_not_available'),
		);
	else
		$config_vars = array(
			array('desc', 'readability_desc'),
			array('check', 'rdb_nowlater'),
			array('check', 'rdb_print'),
			array('check', 'rdb_email'),
			array('check', 'rdb_kindle'),
			'',
			array('text', 'rdb_text_fg'),
			array('text', 'rdb_text_bg'),
			array('select', 'rdb_position', array('abovetopic' => $txt['rdb_position_abovetopic'], 'sidebar' => $txt['rdb_position_sidebar'])),
		);

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=readability');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=readability;save';
	$context['settings_title'] = $context['page_title'] = $txt['readability'];
	wetem::load('show_settings');
	prepareDBSettingContext($config_vars);
}

?>