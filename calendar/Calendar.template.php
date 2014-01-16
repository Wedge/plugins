<?php
/**
 * Displays the main calendar, plus the previous and next month mini-calendars, as well as the event-posting interface.
 *
 * @package wedge
 * @copyright 2010-2011 Wedge Team, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

// The main calendar - January, for example.
function template_main()
{
	global $context, $txt, $settings;

	echo '
		<div id="calendar">';

	if ($context['can_post'])
		echo '
			<div class="right">
				<form action="<URL>?action=calendar;sa=post;month=', $context['current_month'], ';year=' . $context['current_year'], ';', $context['session_query'], '" method="get">
					<input type="submit" class="new" value="', $txt['calendar_post_event'], '">
				</form>
			</div>';

	echo '
			<br class="clear">
			<div id="main_grid">', $context['view_week'] ? template_show_week_grid('main') : template_show_month_grid('main');

	$items = $context['view_week'] ? array('prev', 'current', 'next') : array('prev', 'next');
	$class = count($items) == 3 ? 'inline-block three' : 'two';

	echo '
			<br class="clear">';
	foreach ($items as $cal)
		echo '
			<div class="', $class, '-columns">
				', template_show_month_grid($cal), '
			</div>';
	echo '
			<br class="clear">';

	// Show some controls to allow easy calendar navigation.
	echo '
				<form id="calendar_navigation" action="', SCRIPT, '?action=calendar" method="post" accept-charset="UTF-8">
					<select name="month">';

	// Show a select box with all the months.
	foreach ($txt['months'] as $number => $month)
		echo '
						<option value="', $number, '"', $number == $context['current_month'] ? ' selected' : '', '>', $month, '</option>';
	echo '
					</select>
					<select name="year">';

	// Show a link for every year.....
	for ($year = $settings['cal_minyear']; $year <= $settings['cal_maxyear']; $year++)
		echo '
						<option value="', $year, '"', $year == $context['current_year'] ? ' selected' : '', '>', $year, '</option>';
	echo '
					</select>
					<input type="submit" value="', $txt['go'], '">
				</form>
				<br class="clear">
			</div>
		</div>';
}

// The post form can be used in two ways, either in the standalone case, or in the main post form
// The components are the same, but if it's standalone, we need form stuff - which is these two bad boys.
function template_event_container_before()
{
	global $context, $txt;

	// Start the javascript for drop down boxes...
	add_js('
	function toggleLinked(form)
	{
		form.board.disabled = !form.link_to_board.checked;
	}');

	echo '
		<form action="<URL>?action=calendar;sa=post" method="post" name="postevent" accept-charset="UTF-8" onsubmit="submitonce(this); weSaveEntities(\'postevent\', [\'evtitle\']);" style="margin: 0;" id="postmodify">
			<we:cat>
				', $context['page_title'], '
			</we:cat>
			<div class="roundframe">';

	if (!empty($context['post_error']['messages']))
	{
		echo '
				<div class="errorbox">
					<dl class="event_error">
						<dt>
							', $context['error_type'] == 'serious' ? '<strong>' . $txt['error_while_submitting'] . '</strong>' : '', '
						</dt>
						<dt class="error">
							', implode('<br>', $context['post_error']['messages']), '
						</dt>
					</dl>
				</div>';
	}
}

function template_event_container_after()
{
	global $context, $txt;

	echo '
				<div class="right">
					<input type="submit" value="', empty($context['event']['new']) ? $txt['save'] : $txt['post'], '" class="save">';

	// Delete button?
	if (empty($context['event']['new']))
		echo '
					<input type="submit" name="deleteevent" value="', $txt['event_delete'], '" onclick="return ask(', JavaScriptEscape($txt['calendar_confirm_delete']), ', e);" class="delete">';

	echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<input type="hidden" name="eventid" value="', $context['event']['eventid'], '">
				</div>
			</div>
		</form>';
}

// Display a monthly calendar grid.
function template_show_month_grid($grid_name)
{
	global $context, $txt, $settings;

	if (!isset($context['calendar_grid_' . $grid_name]))
		return false;

	$calendar_data =& $context['calendar_grid_' . $grid_name];
	$colspan = !empty($calendar_data['show_week_links']) ? 8 : 7;

	if (empty($calendar_data['disable_title']))
	{
		echo '
			', $grid_name == 'main' ? '<we:cat>' : '<we:title2>', '
				<div class="center" style="font-size: ', $calendar_data['size'] == 'large' ? 'large' : 'small', ';">';

		if (empty($calendar_data['next_calendar']['disabled']) && $calendar_data['show_next_prev'])
			echo '
					<span class="floatright"><a href="', $calendar_data['next_calendar']['href'], '">', $grid_name == 'main' ? $calendar_data['next_calendar']['title'] . ' &#187;' : '&#187;', '</a></span>';

		if (empty($calendar_data['previous_calendar']['disabled']) && $calendar_data['show_next_prev'])
			echo '
					<span class="floatleft"><a href="', $calendar_data['previous_calendar']['href'], '">', $grid_name == 'main' ? '&#171; ' . $calendar_data['previous_calendar']['title'] : '&#171;', '</a></span>';

		if ($calendar_data['show_next_prev'])
			echo '
					', $txt['months'][$calendar_data['current_month']], ' ', $calendar_data['current_year'];
		else
			echo '
					<a href="', SCRIPT, '?action=calendar;year=', $calendar_data['current_year'], ';month=', $calendar_data['current_month'], '">', $txt['months'][$calendar_data['current_month']], ' ', $calendar_data['current_year'], '</a>';

		echo '
				</div>
			', $grid_name == 'main' ? '</we:cat>' : '</we:title2>';
	}

	echo '
			<table class="calendar_table w100 cs0 center">';

	// Show each day of the week.
	if (empty($calendar_data['disable_day_titles']))
	{
		echo '
				<tr class="titlebg">';

		if (!empty($calendar_data['show_week_links']))
			echo '
					<th>&nbsp;</th>';

		// There are 7 items in this indexed array, 0-6, and we want to know whether we're on the last one - this is the cheapest way to do it.
		foreach ($calendar_data['week_days'] as $day_num => $day)
			echo '
					<th class="days"', $calendar_data['size'] == 'small' ? ' style="font-size: x-small"' : '', '>', !empty($calendar_data['short_day_titles']) ? (westr::substr($txt['days'][$day], 0, 1)) : $txt['days'][$day], '</th>';

		echo '
				</tr>';
	}

	/* Each week in weeks contains the following:
		days (a list of days), number (week # in the year.) */
	foreach ($calendar_data['weeks'] as $week)
	{
		echo '
				<tr>';

		if (!empty($calendar_data['show_week_links']))
			echo '
					<td class="windowbg2 weeks">
						<a href="', SCRIPT, '?action=calendar;viewweek;year=', $calendar_data['current_year'], ';month=', $calendar_data['current_month'], ';day=', $week['days'][0]['day'], '">&#187;</a>
					</td>';

		/* Every day has the following:
			day (# in month), is_today (is this day *today*?), is_first_day (first day of the week?),
			holidays, events. (last two are lists.) */
		foreach ($week['days'] as $day)
		{
			// If this is today, make it a different color and show a border.
			echo '
					<td style="height: ', $calendar_data['size'] == 'small' ? '20' : '100', 'px; padding: 2px;', $calendar_data['size'] == 'small' ? 'font-size: x-small;' : '', '" class="', $day['is_today'] ? 'calendar_today' : 'windowbg', ' days">';

			// Skip it if it should be blank - it's not a day if it has no number.
			if (!empty($day['day']))
			{
				// Should the day number be a link?
				if (!empty($settings['cal_daysaslink']) && $context['can_post'])
					echo '
						<a href="', SCRIPT, '?action=calendar;sa=post;month=', $calendar_data['current_month'], ';year=', $calendar_data['current_year'], ';day=', $day['day'], ';', $context['session_query'], '">', $day['day'], '</a>';
				else
					echo '
						', $day['day'];

				// Is this the first day of the week? (and are we showing week numbers?)
				if ($day['is_first_day'] && $calendar_data['size'] != 'small')
					echo '<span class="smalltext"> - <a href="', SCRIPT, '?action=calendar;viewweek;year=', $calendar_data['current_year'], ';month=', $calendar_data['current_month'], ';day=', $day['day'], '">', $txt['calendar_week'], ' ', $week['number'], '</a></span>';

				if ($grid_name == 'main')
					foreach ($calendar_data['event_types'] as $event)
					{
						$func = 'template_event_' . $event;
						if (!empty($day[$event]) && function_exists($func))
							$func($day, $grid_name);
					}
			}

			echo '
					</td>';
		}

		echo '
				</tr>';
	}

	echo '
			</table>';
}

// Or show a weekly one?
function template_show_week_grid($grid_name)
{
	global $context, $txt, $settings;

	if (!isset($context['calendar_grid_' . $grid_name]))
		return false;

	$calendar_data =& $context['calendar_grid_' . $grid_name];

	// Loop through each month (At least one) and print out each day.
	foreach ($calendar_data['months'] as $month_data)
	{
		echo '
			<we:cat>
				<div class="weekly">';

		if (empty($calendar_data['previous_calendar']['disabled']) && $calendar_data['show_next_prev'] && empty($done_title))
			echo '
					<span class="floatleft"><a href="', $calendar_data['previous_week']['href'], '">&#171;</a></span>';

		if (empty($calendar_data['next_calendar']['disabled']) && $calendar_data['show_next_prev'] && empty($done_title))
			echo '
					<span class="floatright"><a href="', $calendar_data['next_week']['href'], '">&#187;</a></span>';

		echo '
					<a href="', SCRIPT, '?action=calendar;month=', $month_data['current_month'], ';year=', $month_data['current_year'], '">', $txt['months'][$month_data['current_month']], ' ', $month_data['current_year'], '</a>', empty($done_title) && !empty($calendar_data['week_number']) ? (' - ' . $txt['calendar_week'] . ' ' . $calendar_data['week_number']) : '', '
				</div>
			</we:cat>';

		$done_title = true;

		echo '
			<table class="calendar_table weeklist w100 cs1 cp0">';

		foreach ($month_data['days'] as $day)
		{
			echo '
				<tr>
					<td colspan="2">
						<we:title2>
							', $txt['days'][$day['day_of_week']], '
						</we:title2>
					</td>
				</tr>
				<tr>
					<td class="windowbg">';

			// Should the day number be a link?
			if (!empty($settings['cal_daysaslink']) && $context['can_post'])
				echo '
						<a href="', SCRIPT, '?action=calendar;sa=post;month=', $month_data['current_month'], ';year=', $month_data['current_year'], ';day=', $day['day'], ';', $context['session_query'], '">', $day['day'], '</a>';
			else
				echo '
						', $day['day'];

			echo '
					</td>
					<td class="', $day['is_today'] ? 'calendar_today' : 'windowbg2', ' weekdays">';

			foreach ($calendar_data['event_types'] as $event)
			{
				$func = 'template_event_' . $event;
				if (!empty($day[$event]) && function_exists($func))
					$func($day, $grid_name);
			}

			echo '
					</td>
				</tr>';
		}

		echo '
			</table>';
	}
}

function template_event_holidays($day, $grid_name)
{
	global $txt;

	echo '
						<div class="smalltext holiday">', $txt['calendar_prompt'], ' ', implode(', ', $day['holidays']), '</div>';

}

function template_event_events($day, $grid_name)
{
	global $txt;

	echo '
						<div class="smalltext">
							<span class="event">', $txt['events'], '</span>';

	/* The events are made up of:
		title, href, is_last, can_edit (are they allowed to?), and modify_href. */
	foreach ($day['events'] as $event)
	{
		// If they can edit the event, show a star they can click on....
		if ($event['can_edit'])
			echo '
							<a class="modify_event" href="', $event['modify_href'], '"><img src="' . ASSETS . '/icons/modify_small.gif"></a>';

		echo '
							', $event['link'], $event['is_last'] ? '' : ', ';
	}

	echo '
						</div>';
}

?>