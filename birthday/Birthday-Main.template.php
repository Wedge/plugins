<?php
/**
 * Wedge: Birthdays
 *
 * This forms the info center handling templates.
 *
 * @package wedge-birthdays
 * @copyright 2010-2011 Wedge Team, wedge.org, 2011 Simple Machines, simplemachines.org
 * @license http://wedge.org/license/
 */

function template_birthdays_info_center()
{
	global $context, $settings;

	echo '
			<we:title2>
				<img src="', ASSETS, '/icons/online.gif">', '
				', number_context('birthdays_upcoming', $settings['birthday_info_center_days']), '
			</we:title2>
			<p class="inline smalltext">';

	/* Each member in calendar_birthdays has:
		id, name (person), age (if they have one set?), is_last. (last in list?), and is_today (birthday is today?) */

	foreach ($context['birthdays_to_display'] as $member)
		echo '
				<a href="<URL>?action=profile;u=', $member['id'], '">', $member['is_today'] ? '<strong>' : '', $member['name'], $member['is_today'] ? '</strong>' : '', isset($member['age']) ? ' (' . $member['age'] . ')' : '', '</a>', $member['is_last'] ? '<br>' : ', ';

	echo '
			</p>';
}

?>