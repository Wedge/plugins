<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*
	Core code for the reCAPTCHA widget. As usual, the template and logic are in the same file, but the logic and presentation are still separate.
	This is primarily for performance, no point loading two small files when you might as well just load one slightly bigger one.
*/

function setup_recaptcha($verify_id)
{
	// This is really a placeholder to inform the system that something is going to be using this. Just make sure to return something that resolves to boolean true.
	global $modSettings;
	if (!empty($modSettings['recaptcha_public_key']) && !empty($modSettings['recaptcha_private_key']))
	{
		loadPluginLanguage('Arantor:reCAPTCHA', 'reCaptcha-Main');
		return true;
	}
	return false;
}

function validate_recaptcha(&$verify_id, &$verification_errors)
{
	global $modSettings;
	if (empty($modSettings['recaptcha_public_key']) || empty($modSettings['recaptcha_private_key']))
		return;

	// Start by checking they actually filled something in at all.
	if (empty($_POST['recaptcha_response_field']) || empty($_POST['recaptcha_challenge_field']))
		fatal_lang_error('wrong_verification_code', false);

	loadPluginSource('Arantor:reCAPTCHA', 'recaptchalib');

	$resp = recaptcha_check_answer($modSettings['recaptcha_private_key'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
	if (!$resp->is_valid)
		fatal_lang_error('error_wrong_verification_code', false);
}

function template_recaptcha(&$verify_id)
{
	global $modSettings, $txt, $context;

	if (empty($modSettings['recaptcha_public_key']) || empty($modSettings['recaptcha_private_key']))
		return;

	// Unfortunately we have to bend some rules here. We need all of this stuff to execute prior to the footer, because of the way reCAPTCHA works.
	echo '
	<script><!-- // --><![CDATA[
		var RecaptchaOptions = {
			custom_translations : {';

	$vars = array('visual_challenge', 'audio_challenge', 'refresh_btn', 'instructions_visual', 'instructions_context', 'instructions_audio', 'help_btn', 'play_again', 'cant_hear_this', 'incorrect_try_again');
	foreach ($vars as $k => $v)
		$vars[$k] = $v . ':' . JavaScriptEscape($txt['recaptcha_' . $v]);

	echo implode(',
			', $vars), '
			},
			theme: \'', (empty($modSettings['recaptcha_theme']) ? 'clean' : $modSettings['recaptcha_theme']), '\',
			tabindex: ', ($context['tabindex']++), '
		};
	// ]]></script>
	<script src="http://api.recaptcha.net/challenge?k=', $modSettings['recaptcha_public_key'], '"></script>
	<noscript>
		<iframe src="http://api.recaptcha.net/noscript?k=', $modSettings['recaptcha_public_key'], '" height="300" width="500" frameborder="0"></iframe><br />
		<textarea name="recaptcha_challenge_field" rows="3" cols="40"> </textarea>
		<input type="hidden" name="recaptcha_response_field" value="manual_challenge" />
	</noscript>';
}

?>