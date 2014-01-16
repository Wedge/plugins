<?php
/**
 * Wedge: Birthdays
 *
 * This forms the SSI connectivity.
 *
 * @package wedge-birthdays
 * @copyright 2010-2011 Wedge Team, wedge.org, 2011 Simple Machines, simplemachines.org
 * @license http://wedge.org/license/
 */

function birthdaySSI()
{
	// Dummy function. The function hooks must have a function to call.
}

// Show today's birthdays.
function ssi_todaysBirthdays($output_method = 'echo')
{
	global $settings;

	if (empty($settings['allow_guestAccess']) && we::$is_guest)
		return array();

	$eventOptions = array(
		'include_birthdays' => true,
		'num_days_shown' => empty($settings['cal_days_for_index']) || $settings['cal_days_for_index'] < 1 ? 1 : $settings['cal_days_for_index'],
	);
	$return = cache_quick_get('calendar_index_offset_' . (we::$user['time_offset'] + $settings['time_offset']), 'Subs-Calendar.php', 'cache_getRecentEvents', array($eventOptions));

	if ($output_method != 'echo')
		return $return['calendar_birthdays'];

	foreach ($return['calendar_birthdays'] as $member)
		echo '
			<a href="<URL>?action=profile;u=', $member['id'], '">' . $member['name'] . (isset($member['age']) ? ' (' . $member['age'] . ')' : '') . '</a>' . (!$member['is_last'] ? ', ' : '');
}

?>