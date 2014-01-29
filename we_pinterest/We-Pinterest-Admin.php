<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function we_pinterest_admin()
{
	global $admin_areas, $context;

	$admin_areas['plugins']['areas']['we_pinterest'] = array(
		'label' => 'We-Pinterest',
		'function' => 'ModifyPinterestSettings',
		'icon' => 'pinterest.png',
		'bigicon' => $context['plugins_url']['Pandos:We-Pinterest'] . '/pinterest.png',
	);
}

function ModifyPinterestSettings($return_config = false)
{
	global $txt, $context, $settings;

	loadSource('ManageServer');
	loadPluginLanguage('Pandos:We-Pinterest', 'We-Pinterest-Admin');

	if (empty($settings['allow_guestAccess']))
		$config_vars = array(
			array('desc', 'we_pinterest_not_available'),
		);
	else
		$config_vars = array(
			array('desc', 'we_pinterest_desc'),
			array('title', 'we_pinterest'),
			array('check', 'we_pinterest_on'),
		);

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=we_pinterest');
	}

	$context['post_url'] = '<URL>?action=admin;area=we_pinterest;save';
	$context['settings_title'] = $context['page_title'] = $txt['we_pinterest'];
	wetem::load('show_settings');
	prepareDBSettingContext($config_vars);
}

?>