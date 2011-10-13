<?php
/**
 * Wedge: Birthdays
 *
 * This forms the SSI connectivity.
 *
 * @package wedge-birthdays
 * @copyright 2010-2011 Wedgeward, wedge.org, 2011 Simple Machines, simplemachines.org
 * @license http://wedge.org/license/
 */

function birthdaySSI()
{
	// Dummy function. The function hooks must have a function to call.
}

// Show today's birthdays.
function ssi_todaysBirthdays($output_method = 'echo')
{
	global $scripturl, $modSettings, $user_info;

	if (empty($modSettings['allow_guestAccess']) && $user_info['is_guest'])
		return array();

	$eventOptions = array(
		'include_birthdays' => true,
		'num_days_shown' => empty($modSettings['cal_days_for_index']) || $modSettings['cal_days_for_index'] < 1 ? 1 : $modSettings['cal_days_for_index'],
	);
	$return = cache_quick_get('calendar_index_offset_' . ($user_info['time_offset'] + $modSettings['time_offset']), 'Subs-Calendar.php', 'cache_getRecentEvents', array($eventOptions));

	if ($output_method != 'echo')
		return $return['calendar_birthdays'];

	foreach ($return['calendar_birthdays'] as $member)
		echo '
			<a href="', $scripturl, '?action=profile;u=', $member['id'], '">' . $member['name'] . (isset($member['age']) ? ' (' . $member['age'] . ')' : '') . '</a>' . (!$member['is_last'] ? ', ' : '');
}

?>