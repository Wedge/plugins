<?php
/**
 * Wedge: Birthdays
 *
 * This handles the task of any processing that occurs once per day, e.g. sending birthday emails.
 *
 * @package wedge-birthdays
 * @copyright 2010-2011 Wedgeward, wedge.org, 2011 Simple Machines, simplemachines.org
 * @license http://wedge.org/license/
 */

function scheduled_birthdays()
{
	global $settings, $mbname, $txt;

	// Need this in order to load the language files.
	loadEssentialThemeData();

	// Going to need this to send the emails.
	if (!empty($settings['birthday_send_email']))
	{
		loadSource('Subs-Post');

		$greeting = isset($settings['birthday_email']) ? $settings['birthday_email'] : 'happy_birthday';

		// Get the month and day of today.
		$month = date('n'); // Month without leading zeros.
		$day = date('j'); // Day without leading zeros.

		// So who are the lucky ones?  Don't include those who are banned and those who don't want them.
		$result = wesql::query('
			SELECT id_member, real_name, lngfile, email_address
			FROM {db_prefix}members
			WHERE is_activated < 10
				AND MONTH(birthdate) = {int:month}
				AND DAYOFMONTH(birthdate) = {int:day}
				AND notify_announcements = {int:notify_announcements}
				AND YEAR(birthdate) > {int:year}',
			array(
				'notify_announcements' => 1,
				'year' => 1,
				'month' => $month,
				'day' => $day,
			)
		);

		// Group them by languages.
		$birthdays = array();
		while ($row = wesql::fetch_assoc($result))
		{
			if (!isset($birthdays[$row['lngfile']]))
				$birthdays[$row['lngfile']] = array();
			$birthdays[$row['lngfile']][$row['id_member']] = array(
				'name' => $row['real_name'],
				'email' => $row['email_address']
			);
		}
		wesql::free_result($result);

		// Send out the greetings!
		$replacements = array(
			'{FORUMNAME}' => $mbname,
			'{SCRIPTURL}' => SCRIPT,
			'{REGARDS}' => str_replace('{forum_name}', $mbname, $txt['regards_team']),
		);
		foreach ($birthdays as $lang => $recps)
		{
			// We need to do some shuffling to make this work properly.
			loadPluginLanguage('Wedgeward:Birthdays', 'Birthday-Mails', $lang);

			foreach ($recps as $recp)
			{
				$replacements['{REALNAME}'] = $recp['name'];

				$subject = strtr($txt['birthday_template_subject_' . $greeting], $replacements);
				$body = strtr($txt['birthday_template_body_' . $greeting], $replacements);

				sendmail($recp['email'], $subject, $emaildata['body'], null, null, false, 4);

				// Try to stop a timeout, this would be bad...
				@set_time_limit(300);
				if (function_exists('apache_reset_timeout'))
					@apache_reset_timeout();

			}
		}

		// Flush the mail queue, just in case.
		AddMailQueue(true);
	}

	return true;
}

?>