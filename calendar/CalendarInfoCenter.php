<?php
/**
 * Wedge
 *
 * Provides functionality for showing the calendar contents in the info center.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function info_center_calendar()
{
	global $context, $txt, $settings, $user_info;
	// Load the calendar?
	if (allowedTo('calendar_view'))
	{
		loadPluginTemplate('Wedgeward:Calendar', 'CalendarIntegration');
		// Retrieve the calendar data (events, holidays).
		$eventOptions = array(
			'include_holidays' => $settings['cal_showholidays'] > 1,
			'include_events' => $settings['cal_showevents'] > 1,
			'num_days_shown' => empty($settings['cal_days_for_index']) || $settings['cal_days_for_index'] < 1 ? 1 : $settings['cal_days_for_index'],
		);
		$context += cache_quick_get('calendar_index_offset_' . ($user_info['time_offset'] + $settings['time_offset']), array('Wedgeward:Calendar', 'Subs-Calendar'), 'cache_getRecentEvents', array($eventOptions));

		// Whether one or multiple days are shown on the board index.
		$context['calendar_only_today'] = $settings['cal_days_for_index'] == 1;

		// This is used to show the "how-do-I-edit" help.
		$context['calendar_can_edit'] = allowedTo('calendar_edit_any');

		wetem::before('info_center_statistics', 'info_center_calendar');
	}
	else
		$context['show_calendar'] = false;
}

?>