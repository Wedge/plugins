<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function contactMenu(&$menu_buttons)
{
	global $context, $txt;
	loadPluginLanguage('Arantor:ContactPage', 'Contact');

	$item = array(
		'contact' => array(
			'title' => $txt['contact_us'],
			'href' => '<URL>?action=contact',
			'show' => allowedTo('view_contact_page'),
		),
	);
	$menu_buttons = array_insert($menu_buttons, 'home', $item, true);

	add_css('
	#m_contact { float: left; width: 16px; height: 16px; padding: 0; background: url("' . $context['plugins_url']['Arantor:ContactPage'] . '/contact_small.png") no-repeat 0 0; margin:4px 4px 0 2px; }');
}

function Contact()
{
	global $settings, $context, $txt, $webmaster_email;

	loadPluginLanguage('Arantor:ContactPage', 'Contact');
	loadPluginTemplate('Arantor:ContactPage', 'Contact');

	isAllowedTo('view_contact_page');
	loadSource('Subs-Editor');

	if (isset($_GET['send']))
	{
		$context['form_errors'] = array();

		$context['contact_name'] = !empty($_POST['contact_name']) ? westr::safe($_POST['contact_name'], ENT_NOQUOTES, false) : '';
		if (empty($_POST['contact_name']) || westr::htmltrim($_POST['contact_name']) === '')
			$context['form_errors']['name'] = $txt['error_no_contact_name'];

		$context['contact_email'] = !empty($_POST['contact_email']) ? trim(westr::safe($_POST['contact_email'])) : '';
		if (empty($_POST['contact_email']) || !is_valid_email($_POST['contact_email']))
			$context['form_errors']['email'] = $txt['error_no_contact_email'];

		$context['contact_subject'] = !empty($_POST['contact_subject']) ? westr::safe($_POST['contact_subject'], ENT_NOQUOTES, false) : '';
		if (empty($_POST['contact_subject']) || westr::htmltrim($_POST['contact_subject']) === '')
			$context['form_errors']['subject'] = $txt['error_no_contact_subject'];

		$context['contact_body'] = !empty($_POST['contact_subject']) ? westr::safe($_POST['contact_body'], ENT_NOQUOTES, false) : '';
		if (empty($_POST['contact_body']) || westr::htmltrim($_POST['contact_body']) === '')
			$context['form_errors']['body'] = $txt['error_no_contact_body'];

		$context['require_verification'] = $settings['contact_verification'] == 'everyone' || ($settings['contact_verification'] == 'guests' && we::$is_guest);
		if ($context['require_verification'])
		{
			$verificationOptions = array(
				'id' => 'contact',
			);
			$context['visual_verification'] = create_control_verification($verificationOptions, true);

			if (is_array($context['visual_verification']))
			{
				loadLanguage('Errors');
				$context['form_errors']['verification'] = $context['visual_verification'];
			}
		}

		// Any errors? If there were errors, simply let it fall back to the page
		if (empty($context['form_errors']))
		{
			loadSource('Subs-Post');
			$message = $txt['contact_message'] . "\n\n" . sprintf($txt['user_name'], $context['contact_name']) . "\n" . sprintf($txt['user_email'], $context['contact_email']) . "\n" . sprintf($txt['user_body'], $context['contact_body']);

			sendmail($webmaster_email, sprintf($txt['user_subject'], $context['contact_subject']), $message, $context['contact_email']);
			$context['page_title'] = $txt['contact_us'];
			wetem::load('contact_sent');
			return;
		}
	}
	else
	{
		$context += array(
			'contact_name' => '',
			'contact_subject' => '',
			'contact_body' => '',
			'contact_email' => '',
			'form_errors' => array(),
		);

		if (empty($settings['contact_verification']))
			$settings['contact_verification'] = 'guests';
	}

	$context['require_verification'] = $settings['contact_verification'] == 'everyone' || ($settings['contact_verification'] == 'guests' && we::$is_guest);
	if ($context['require_verification'])
	{
		$verificationOptions = array(
			'id' => 'contact',
		);
		$context['require_verification'] = create_control_verification($verificationOptions);
		$context['visual_verification_id'] = $verificationOptions['id'];
	}

	$context['page_title'] = $txt['contact_us'];
	wetem::load('contact_form');
}

?>