<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*
	Admin area changes for the reCaptcha widget.
*/

function reCaptchaAdmin(&$config_vars, &$return_config)
{
	global $txt;
	loadPluginLanguage('Arantor:reCAPTCHA', 'reCaptcha-Admin');

	// Inject it where I want it, not just at the end of the list, though that is a valid fallback position.
	$old_config_vars = $config_vars;
	$config_vars = array();
	$new_items = array(
		array('title', 'recaptcha', 'force_div_id' => 'recaptcha'),
		array('desc', 'recaptcha_desc'),
		array('select', 'recaptcha_theme',
			array(
				'clean' => $txt['recaptcha_theme_clean'],
				'red' => $txt['recaptcha_theme_red'],
				'white' => $txt['recaptcha_theme_white'],
				'blackglass' => $txt['recaptcha_theme_blackglass'],
			), 'subtext' => $txt['recaptcha_choices_theme']
		),
		array('text', 'recaptcha_public_key'),
		array('text', 'recaptcha_private_key'),
	);

	foreach ($old_config_vars as $k => $v)
	{
		if (is_array($v) && $v[1] == 'setup_verification_questions')
		{
			$added = true;
			$config_vars = array_merge($config_vars, $new_items);
		}
		if (is_numeric($k))
			$config_vars[] = $v;
		else
			$config_vars[$k] = $v;
	}

	// Just in case we didn't add it already, make sure it's on the end of the item.
	if (empty($added))
		$config_vars = array_merge($config_vars, $new_items);
}

?>