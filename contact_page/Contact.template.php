<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function template_contact_form()
{
	global $txt, $context;

	echo '
	<we:cat>', $txt['contact_us'], '</we:cat>
	<div class="windowbg2 wrc">
		<form action="<URL>?action=contact;send" method="post">
			<dl class="settings">
				<dt', isset($context['form_errors']['name']) ? ' class="error"' : '', '>
					<label for="contact_name">', $txt['contact_name'], '</label>
				</dt>
				<dd>
					<input type="text" name="contact_name" id="contact_name" size="30" value="', $context['contact_name'], '">
				</dd>
				<dt', isset($context['form_errors']['email']) ? ' class="error"' : '', '>
					<label for="contact_email">', $txt['contact_email'], '</label>
				</dt>
				<dd>
					<input type="text" name="contact_email" id="contact_email" size="30" value="', $context['contact_email'], '">
				</dd>
				<dt', isset($context['form_errors']['subject']) ? ' class="error"' : '', '>
					<label for="contact_subject">', $txt['contact_subject'], '</label>
				</dt>
				<dd>
					<input type="text" name="contact_subject" id="contact_subject" size="30" value="', $context['contact_subject'], '">
				</dd>
				<dt', isset($context['form_errors']['body']) ? ' class="error"' : '', '>
					<label for="contact_body">', $txt['contact_body'], '</label>
				</dt>
				<dd>
					<textarea rows="6" name="contact_body" cols="54">', $context['contact_body'], '</textarea>
				</dd>';

	if ($context['require_verification'])
		echo '
				<dt', isset($context['form_errors']['verification']) ? ' class="error"' : '', '>', $txt['verification'], '</dt>
				<dd>', template_control_verification($context['visual_verification_id'], 'all'), '</dd>';

	if (!empty($context['form_errors']))
	{
		if (isset($context['form_errors']['verification']) && is_array($context['form_errors']['verification']))
		{
			$array = array();
			foreach ($context['form_errors']['verification'] as $error)
				$array[] = $txt['error_' . $error];
			$context['form_errors']['verification'] = implode('<br>', $array);
		}

		echo '
				<dt>', $txt['contact_errors'], '</dt>
				<dd>', implode('<br>', $context['form_errors']), '</dd>';
	}

	echo '
			</dl>
			<hr>
			<div class="right">
				<input type="submit" value="', $txt['send_message'], '" class="submit">
			</div>
		</form>
	</div>';

}

function template_contact_sent()
{
	global $txt, $context;

	echo '
	<we:cat>', $txt['contact_us'], '</we:cat>
	<div class="windowbg2 wrc">
		<div class="padding">', $txt['message_sent'], '</div>
	</div>';
}

?>