<?php
/**
 * Provides functionality for showing the calendar items externally to the forum through SSI.
 *
 * @package wedge
 * @copyright 2010-2011 Wedge Team, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function ssi_calendar_init()
{
	// It might already be loaded, it might not.
	loadPluginLanguage('Wedge:Calendar', 'lang/Calendar');
}

// Show today's holidays.
function ssi_todaysHolidays($output_method = 'echo')
{
	global $settings;

	if (empty($settings['allow_guestAccess']) && we::$is_guest)
		return array();

	$eventOptions = array(
		'include_holidays' => true,
		'num_days_shown' => empty($settings['cal_days_for_index']) || $settings['cal_days_for_index'] < 1 ? 1 : $settings['cal_days_for_index'],
	);
	$return = cache_quick_get('calendar_index_offset_' . (we::$user['time_offset'] + $settings['time_offset']), array('Wedge:Calendar', 'Subs-Calendar'), 'cache_getRecentEvents', array($eventOptions));

	if ($output_method != 'echo')
		return $return['calendar_holidays'];

	echo '
		', implode(', ', $return['calendar_holidays']);
}

// Show today's events.
function ssi_todaysEvents($output_method = 'echo')
{
	global $settings;

	if (empty($settings['allow_guestAccess']) && we::$is_guest)
		return array();

	$eventOptions = array(
		'include_events' => true,
		'num_days_shown' => empty($settings['cal_days_for_index']) || $settings['cal_days_for_index'] < 1 ? 1 : $settings['cal_days_for_index'],
	);
	$return = cache_quick_get('calendar_index_offset_' . (we::$user['time_offset'] + $settings['time_offset']), array('Wedge:Calendar', 'Subs-Calendar'), 'cache_getRecentEvents', array($eventOptions));

	if ($output_method != 'echo')
		return $return['calendar_events'];

	foreach ($return['calendar_events'] as $event)
	{
		if ($event['can_edit'])
			echo '
	<a href="' . $event['modify_href'] . '" style="color: #ff0000">*</a> ';
		echo '
	' . $event['link'] . (!$event['is_last'] ? ', ' : '');
	}
}

// Show all calendar entries for today. (holidays, and events.)
function ssi_todaysCalendar($output_method = 'echo')
{
	global $settings, $txt;

	if (empty($settings['allow_guestAccess']) && we::$is_guest)
		return array();

	$eventOptions = array(
		'include_holidays' => true,
		'include_events' => true,
		'num_days_shown' => empty($settings['cal_days_for_index']) || $settings['cal_days_for_index'] < 1 ? 1 : $settings['cal_days_for_index'],
	);
	$return = cache_quick_get('calendar_index_offset_' . (we::$user['time_offset'] + $settings['time_offset']), array('Wedge:Calendar', 'Subs-Calendar'), 'cache_getRecentEvents', array($eventOptions));

	if ($output_method != 'echo')
		return $return;

	if (!empty($return['calendar_holidays']))
		echo '
			<span class="holiday">' . $txt['calendar_prompt'] . ' ' . implode(', ', $return['calendar_holidays']) . '<br /></span>';
	if (!empty($return['calendar_events']))
	{
		echo '
			<span class="event">' . $txt['events_upcoming'] . '</span> ';
		foreach ($return['calendar_events'] as $event)
		{
			if ($event['can_edit'])
				echo '
			<a href="' . $event['modify_href'] . '" style="color: #ff0000">*</a> ';
			echo '
			' . $event['link'] . (!$event['is_last'] ? ', ' : '');
		}
	}
}

// Show the most recent events.
function ssi_recentEvents($max_events = 7, $output_method = 'echo')
{
	global $settings, $txt, $context;

	if (empty($settings['allow_guestAccess']))
		return array();

	// Find all events which are happening in the near future that the member can see.
	$request = wesql::query('
		SELECT
			cal.id_event, cal.start_date, cal.end_date, cal.title, cal.id_member, cal.id_topic,
			cal.id_board, t.id_first_msg, t.approved
		FROM {db_prefix}calendar AS cal
			LEFT JOIN {db_prefix}boards AS b ON (b.id_board = cal.id_board)
			LEFT JOIN {db_prefix}topics AS t ON (t.id_topic = cal.id_topic)
		WHERE cal.start_date <= {date:current_date}
			AND cal.end_date >= {date:current_date}
			AND (cal.id_board = {int:no_board} OR {query_wanna_see_board})
		ORDER BY cal.start_date DESC
		LIMIT ' . $max_events,
		array(
			'current_date' => strftime('%Y-%m-%d', forum_time(false)),
			'no_board' => 0,
		)
	);
	$return = array();
	$duplicates = array();
	while ($row = wesql::fetch_assoc($request))
	{
		// Check if we've already come by an event linked to this same topic with the same title... and don't display it if we have.
		if (!empty($duplicates[$row['title'] . $row['id_topic']]))
			continue;

		// Censor the title.
		censorText($row['title']);

		if ($row['start_date'] < strftime('%Y-%m-%d', forum_time(false)))
			$date = strftime('%Y-%m-%d', forum_time(false));
		else
			$date = $row['start_date'];

		// If the topic it is attached to is not approved then don't link it.
		if (!empty($row['id_first_msg']) && !$row['approved'])
			$row['id_board'] = $row['id_topic'] = $row['id_first_msg'] = 0;

		$return[$date][] = array(
			'id' => $row['id_event'],
			'title' => $row['title'],
			'can_edit' => allowedTo('calendar_edit_any') || ($row['id_member'] == MID && allowedTo('calendar_edit_own')),
			'modify_href' => SCRIPT . '?action=' . ($row['id_board'] == 0 ? 'calendar;sa=post;' : 'post;msg=' . $row['id_first_msg'] . ';topic=' . $row['id_topic'] . '.0;calendar;') . 'eventid=' . $row['id_event'] . ';' . $context['session_query'],
			'href' => $row['id_board'] == 0 ? '' : SCRIPT . '?topic=' . $row['id_topic'] . '.0',
			'link' => $row['id_board'] == 0 ? $row['title'] : '<a href="' . SCRIPT . '?topic=' . $row['id_topic'] . '.0">' . $row['title'] . '</a>',
			'start_date' => $row['start_date'],
			'end_date' => $row['end_date'],
			'is_last' => false
		);

		// Let's not show this one again, huh?
		$duplicates[$row['title'] . $row['id_topic']] = true;
	}
	wesql::free_result($request);

	foreach ($return as $mday => $array)
		$return[$mday][count($array) - 1]['is_last'] = true;

	if ($output_method != 'echo' || empty($return))
		return $return;

	// Well the output method is echo.
	echo '
			<span class="event">' . $txt['events'] . '</span> ';
	foreach ($return as $mday => $array)
		foreach ($array as $event)
		{
			if ($event['can_edit'])
				echo '
				<a href="' . $event['modify_href'] . '" style="color: #ff0000">*</a> ';

			echo '
				' . $event['link'] . (!$event['is_last'] ? ', ' : '');
		}
}

?>