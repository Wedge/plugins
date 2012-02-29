<?php
/**
 * Wedge
 *
 * Displays the calendar integration into the info center and the topic display.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_linked_calendar()
{
	global $context, $theme, $txt;

	// Does this topic have some events linked to it?
	if (empty($context['linked_calendar_events']))
		return;

	echo '
			<div class="linked_events">
				<we:title>
					', $txt['calendar_linked_events'], '
				</we:title>
				<div class="windowbg wrc">
					<ul class="reset">';

	foreach ($context['linked_calendar_events'] as $event)
		echo '
						<li>
							', $event['can_edit'] ? '<a href="' . $event['modify_href'] . '"> <img src="' . $theme['images_url'] . '/icons/modify_small.gif" title="' . $txt['modify'] . '" class="edit_event"></a> ' : '',
							'<strong>', $event['title'], '</strong>: ', $event['start_date'], ($event['start_date'] != $event['end_date'] ? ' - ' . $event['end_date'] : ''), '
						</li>';

	echo '
					</ul>
				</div>
			</div>';
}

function template_info_center_calendar()
{
	global $context, $theme, $options, $txt, $scripturl, $settings;

	if (!$context['show_calendar'])
		return;

	echo '
		<section class="ic">
			<we:title>
				<a href="', $scripturl, '?action=calendar"><img src="', $theme['images_url'], '/icons/calendar.gif', '" alt="', $context['calendar_only_today'] ? $txt['calendar_today'] : $txt['calendar_upcoming'], '"></a>
				', $context['calendar_only_today'] ? $txt['calendar_today'] : $txt['calendar_upcoming'], '
			</we:title>
			<p class="smalltext">';

	// Holidays like "Christmas", "Chanukah", and "We Love [Unknown] Day" :P.
	if (!empty($context['calendar_holidays']))
		echo '
				<span class="holiday">', $txt['calendar_prompt'], ' ', implode(', ', $context['calendar_holidays']), '</span><br>';

	// Events like community get-togethers.
	if (!empty($context['calendar_events']))
	{
		echo '
				<span class="event">', $context['calendar_only_today'] ? $txt['events'] : $txt['events_upcoming'], '</span>';

		/* Each event in calendar_events should have:
			title, href, is_last, can_edit (are they allowed?), modify_href, and is_today. */

		foreach ($context['calendar_events'] as $event)
			echo $event['can_edit'] ? '
				<a href="' . $event['modify_href'] . '" title="' . $txt['calendar_edit'] . '"><img src="' . $theme['images_url'] . '/icons/modify_small.gif"></a>' : '', '
				', $event['href'] == '' ? '' : '<a href="' . $event['href'] . '">', $event['is_today'] ? '<strong>' . $event['title'] . '</strong>' : $event['title'], $event['href'] == '' ? '' : '</a>', $event['is_last'] ? '<br>' : ', ';
	}

	echo '
			</p>
		</section>';
}

function template_make_event()
{
	global $context, $theme, $options, $txt, $scripturl, $settings;

	// We want to ensure we show the current days in a month etc... This is done here.
	add_js('
	var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

	function generateDays()
	{
		var dayElement = $("#day")[0], year = $("#year").val(), monthElement = ("#month")[0];
		var days, selected = dayElement.selectedIndex;

		monthLength[1] = (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0)) ? 29 : 28;

		days = monthLength[monthElement.value - 1];
		while (dayElement.options.length)
			dayElement.options[0] = null;

		for (i = 1; i <= days; i++)
			dayElement.options.push(new Option(i, i));

		if (selected < days)
			dayElement.selectedIndex = selected;
	}');

	echo '
				<div id="post_event">
					<fieldset id="event_main">
						<legend><span', isset($context['post_error']['no_event']) ? ' class="error"' : '', ' id="caption_evtitle">', $txt['calendar_event_title'], '</span></legend>
						<input type="text" name="evtitle" maxlength="80" value="', $context['event']['title'], '" tabindex="', $context['tabindex']++, '" class="w75">
						<div class="smalltext nowrap">
							<input type="hidden" name="calendar" value="1">', $txt['calendar_year'], '
							<select name="year" id="year" tabindex="', $context['tabindex']++, '" onchange="generateDays();">';

	// Show a list of all the years we allow...
	for ($year = $settings['cal_minyear']; $year <= $settings['cal_maxyear']; $year++)
		echo '
								<option value="', $year, '"', $year == $context['event']['year'] ? ' selected' : '', '>', $year, '&nbsp;</option>';

	echo '
							</select>
							', $txt['calendar_month'], '
							<select name="month" id="month" onchange="generateDays();">';

	// There are 12 months per year - ensure that they all get listed.
	for ($month = 1; $month <= 12; $month++)
		echo '
								<option value="', $month, '"', $month == $context['event']['month'] ? ' selected' : '', '>', $txt['months'][$month], '&nbsp;</option>';

	echo '
							</select>
							', $txt['calendar_day'], '
							<select name="day" id="day">';

	// This prints out all the days in the current month - this changes dynamically as we switch months.
	for ($day = 1; $day <= $context['event']['last_day']; $day++)
		echo '
								<option value="', $day, '"', $day == $context['event']['day'] ? ' selected' : '', '>', $day, '&nbsp;</option>';

	echo '
							</select>
						</div>
					</fieldset>';

	if (!empty($settings['cal_allowspan']) || ($context['event']['new'] && $context['is_new_post']))
	{
		echo '
					<fieldset id="event_options">
						<legend>', $txt['calendar_event_options'], '</legend>
						<div class="event_options smalltext">
							<ul class="event_options">';

		// If events can span more than one day then allow the user to select how long it should last.
		if (!empty($settings['cal_allowspan']))
		{
			echo '
								<li>
									', $txt['calendar_numb_days'], '
									<select name="span">';

			for ($days = 1; $days <= $settings['cal_maxspan']; $days++)
				echo '
										<option value="', $days, '"', $days == $context['event']['span'] ? ' selected' : '', '>', $days, '&nbsp;</option>';

			echo '
									</select>
								</li>';
		}

		// If this is a new event let the user specify which board they want the linked post to be put into.
		if ($context['event']['new'] && $context['is_new_post'])
		{
			echo '
								<li>
									', $txt['calendar_post_in'], '
									<select name="board">';
			foreach ($context['event']['categories'] as $category)
			{
				echo '
										<optgroup label="', $category['name'], '">';
				foreach ($category['boards'] as $board)
					echo '
											<option value="', $board['id'], '"', $board['selected'] ? ' selected' : '', '>', $board['child_level'] > 0 ? str_repeat('==', $board['child_level'] - 1) . '=&gt;' : '', ' ', $board['name'], '&nbsp;</option>';
				echo '
										</optgroup>';
			}
			echo '
									</select>
								</li>';
		}

		echo '
							</ul>
						</div>
					</fieldset>';
	}

	echo '
				</div>';

	if (!$context['event']['new'] || !empty($context['current_board']))
		echo '
			<input type="hidden" name="eventid" value="', $context['event']['id'], '">';
}

?>