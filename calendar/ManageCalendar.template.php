<?php
/**
 * Interface for managing holidays in the calendar.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

function template_holidays()
{
	global $context, $txt;

	echo '
	<form action="<URL>?action=admin;area=managecalendar;sa=holiday" method="post" accept-charset="UTF-8">
		<div class="clear_right">
			<we:title>
				', $txt['predefined_holidays'], '
			</we:title>
		</div>
		<div class="w100 windowbg wrc">';

	foreach ($context['predefined_holidays'] as $holiday => $value)
	{
		echo '
			<div class="chk">
				<label>
					<input type="checkbox" name="preset[', $holiday, ']" value="1"', $value ? ' checked' : '', '> &nbsp;', $txt['cal_hol_' . $holiday], '
				</label>
			</div>';
	}

	echo '
			<br class="clear">
		</div>
		<div class="floatright">
			<div class="additional_row" style="text-align: right;">
				<input type="submit" name="preset_save" value="', $txt['save'], '" class="save">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			</div>
		</div>
	</form>';

	template_show_list('holiday_list');
}

// Editing or adding holidays.
function template_edit_holiday()
{
	global $context, $txt, $settings;

	// This will refill the day list with the proper number of days when switching months or years.
	// Note that February 29 is available if year is set to repeat.
	add_js('
	var monthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	function generateDays()
	{
		var year = $("#year").val(), $day = $("#day"), selected = $day.val();
		monthDays[1] = year % 4 == 0 && (year % 100 != 0 || year % 400 == 0) ? 29 : 28;
		$day.empty();

		for (var i = 1, days = monthDays[$("#month").val() - 1]; i <= days; i++)
			$day.append(new Option(i, i));

		if (selected <= days)
			$day.val(selected);

		$day.sb();
	}');

	// Show a form for all the holiday information.
	echo '
	<div id="admincenter">
		<form action="<URL>?action=admin;area=managecalendar;sa=editholiday" method="post" accept-charset="UTF-8">
			<we:cat>
				', $context['page_title'], '
			</we:cat>
			<div class="windowbg wrc">
				<dl class="settings">
					<dt class="small_caption">
						<strong>', $txt['holidays_title_label'], ':</strong>
					</dt>
					<dd class="small_caption">
						<input type="text" name="title" value="', $context['holiday']['title'], '" size="55" maxlength="60">
					</dd>
					<dt class="small_caption">
						<strong>', $txt['date'], ':</strong>
					</dt>
					<dd class="small_caption">
						', $txt['calendar_year'], '&nbsp;
						<select name="year" id="year" onchange="generateDays();">
							<option value="0000"', $context['holiday']['year'] == '0000' ? ' selected' : '', '>', $txt['every_year'], '</option>';

	// Show a list of all the years we allow...
	for ($year = $settings['cal_minyear']; $year <= $settings['cal_maxyear']; $year++)
		echo '
							<option value="', $year, '"', $year == $context['holiday']['year'] ? ' selected' : '', '>', $year, '</option>';

	echo '
						</select>&nbsp;
						', $txt['calendar_month'], '&nbsp;
						<select name="month" id="month" onchange="generateDays();">';

	// There are 12 months per year - ensure that they all get listed.
	for ($month = 1; $month <= 12; $month++)
		echo '
							<option value="', $month, '"', $month == $context['holiday']['month'] ? ' selected' : '', '>', $txt['months'][$month], '</option>';

	echo '
						</select>&nbsp;
						', $txt['calendar_day'], '&nbsp;
						<select name="day" id="day">';

	// This prints out all the days in the current month - this changes dynamically as we switch months.
	for ($day = 1; $day <= $context['holiday']['last_day']; $day++)
		echo '
							<option value="', $day, '"', $day == $context['holiday']['day'] ? ' selected' : '', '>', $day, '</option>';

	echo '
						</select>
					</dd>
				</dl>';

	if ($context['is_new'])
		echo '
				<input type="submit" value="', $txt['holidays_button_add'], '" class="new">';
	else
		echo '
				<input type="submit" name="edit" value="', $txt['holidays_button_edit'], '" class="save">
				<input type="submit" name="delete" value="', $txt['holidays_button_remove'], '" class="delete">
				<input type="hidden" name="holiday" value="', $context['holiday']['id'], '">';
	echo '
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			</div>
		</form>
	</div>
	<br class="clear">';
}

?>