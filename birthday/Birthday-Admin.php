<?php
/**
 * Wedge: Birthdays
 *
 * This handles the task of providing configuration items.
 *
 * @package wedge-birthdays
 * @copyright 2010-2011 Wedge Team, wedge.org, 2011 Simple Machines, simplemachines.org
 * @license http://wedge.org/license/
 */

function birthdayAdmin()
{
	global $admin_areas, $txt;

	loadPluginLanguage('Wedge:Birthdays', 'Birthday-Admin');

	// The name in use here is the plugin's name. It doesn't need to be translatable. Plus the file we need will already be loaded.
	$admin_areas['plugins']['areas']['birthdays'] = array(
		'label' => $txt['birthdays'],
		'function' => 'ModifyBirthdaySettings',
	);
}

function birthdayAdminSearch(&$settings_search)
{
	$settings_search[] = array('ModifyBirthdaySettings', 'area=birthdays');
}

function ModifyBirthdaySettings($return_config = false)
{
	global $txt, $settings, $context;

	loadSource('ManageServer');
	loadPluginLanguage('Wedge:Birthdays', 'Birthday-Mails');

	if (empty($settings['birthday_email']))
		$settings['birthday_email'] = 'happy_birthday';

	$subject = $txt['birthday_template_subject_' . $settings['birthday_email']];
	$body = $txt['birthday_template_body_' . $settings['birthday_email']];

	// !!! Yeah, we'll improve this at some point!
	$types = array('happy_birthday', 'karlbenson1', 'nite0859', 'zwaldowski', 'geezmo', 'karlbenson2');
	$types = array_flip($types);
	foreach ($types as $k => $v)
		$types[$k] = $k;

	$config_vars = array(
		array('check', 'birthday_info_center'),
		array('int', 'birthday_info_center_days'),
		array('check', 'birthday_show_ages'),
		'',
		array('check', 'birthday_send_email'),
		array('select', 'birthday_email', $types, 'javascript' => 'onchange="fetch_birthday_preview();"'),
		'birthday_subject' => array('var_message', 'birthday_subject', 'var_message' => $subject, 'disabled' => true, 'size' => strlen($subject) + 3),
		'birthday_body' => array('var_message', 'birthday_body', 'var_message' => westr::nl2br($body), 'disabled' => true, 'size' => ceil(strlen($body) / 25)),
	);

	$context['post_url'] = '<URL>?action=admin;area=birthdays;save';
	$context['settings_title'] = $txt['birthdays'];

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// We don't want to save the subject and body previews.
		unset($config_vars['birthday_subject'], $config_vars['birthday_body']);

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=birthdays');
	}

	wetem::load('show_settings');

	prepareDBSettingContext($config_vars);

	add_js('
	var bDay = {');

	$i = 0;
	$c = count($types);
	foreach ($types as $email)
	{
		$is_last = ++$i == $c;
		add_js('
		', $email, ': {
			subject: ', JavaScriptEscape($txt['birthday_template_subject_' . $email]), ',
			body: ', JavaScriptEscape(westr::nl2br($txt['birthday_template_body_' . $email])), '
		}', !$is_last ? ',' : '');
	}

	add_js('
	};
	function fetch_birthday_preview()
	{
		var index = $(\'#birthday_email\').val();
		$(\'#birthday_subject\').html(bDay[index].subject);
		$(\'#birthday_body\').html(bDay[index].body);
	}');
}

?>