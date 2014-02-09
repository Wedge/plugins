<?php
/**
 * Provides functionality for showing the calendar contents in the info center.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function info_center_calendar()
{
	global $context, $settings;
	// Load the calendar?
	if (allowedTo('calendar_view'))
	{
		loadPluginTemplate('Wedge:Calendar', 'CalendarIntegration');
		// Retrieve the calendar data (events, holidays).
		$eventOptions = array(
			'include_holidays' => $settings['cal_showholidays'] > 1,
			'include_events' => $settings['cal_showevents'] > 1,
			'num_days_shown' => empty($settings['cal_days_for_index']) || $settings['cal_days_for_index'] < 1 ? 1 : $settings['cal_days_for_index'],
		);
		$context += cache_quick_get('calendar_index_offset_' . (we::$user['time_offset'] + $settings['time_offset']), array('Wedge:Calendar', 'Subs-Calendar'), 'cache_getRecentEvents', array($eventOptions));

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