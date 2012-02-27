<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function contact_admin()
{
	global $admin_areas, $context;

	// The name in use here is the plugin's name. It doesn't need to be translatable. Plus the file we need will already be loaded.
	$admin_areas['plugins']['areas']['contact'] = array(
		'label' => 'Contact Page',
		'function' => 'ModifyContactPageSettings',
		'icon' => $context['plugins_url']['Arantor:ContactPage'] . '/contact_small.png',
		'bigicon' => $context['plugins_url']['Arantor:ContactPage'] . '/contact_large.png',
	);
}

function ModifyContactPageSettings($return_config = false)
{
	global $txt, $scripturl, $context, $settings;

	loadSource('ManageServer');
	loadPluginLanguage('Arantor:ContactPage', 'Contact-Admin');

	$config_vars = array(
		array('permissions', 'view_contact_page'),
		array('select', 'contact_verification', array('none' => $txt['contact_verification_none'], 'guests' => $txt['contact_verification_guests'], 'everyone' => $txt['contact_verification_everyone'])),
	);

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=contact');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=contact;save';
	$context['settings_title'] = $context['page_title'] = 'Contact Page';
	wetem::load('show_settings');
	prepareDBSettingContext($config_vars);
}

?>