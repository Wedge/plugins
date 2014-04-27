<?php
/**
 * Displays the calendar integration into the info center and the topic display.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

function template_linked_calendar()
{
	global $context, $txt;

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
							', $event['can_edit'] ? '<a href="' . $event['modify_href'] . '"> <img src="' . ASSETS . '/icons/modify_small.gif" title="' . $txt['modify'] . '" class="edit_event"></a> ' : '',
							'<strong>', $event['title'], '</strong>: ', $event['start_date'], ($event['start_date'] != $event['end_date'] ? ' - ' . $event['end_date'] : ''), '
						</li>';

	echo '
					</ul>
				</div>
			</div>';
}

function template_info_center_calendar()
{
	global $context, $txt;

	if (!$context['show_calendar'])
		return;

	echo '
		<section class="ic">
			<we:title>
				<a href="', SCRIPT, '?action=calendar"><img src="', $context['plugins_url']['Wedge:Calendar'] . '/img/cal.gif" alt="', $context['calendar_only_today'] ? $txt['calendar_today'] : $txt['calendar_upcoming'], '"></a>
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
				<a href="' . $event['modify_href'] . '" title="' . $txt['calendar_edit'] . '"><img src="' . ASSETS . '/icons/modify_small.gif"></a>' : '', '
				', $event['href'] == '' ? '' : '<a href="' . $event['href'] . '">', $event['is_today'] ? '<strong>' . $event['title'] . '</strong>' : $event['title'], $event['href'] == '' ? '' : '</a>', $event['is_last'] ? '<br>' : ', ';
	}

	echo '
			</p>
		</section>';
}

function template_form_event_details()
{
	global $context, $txt, $settings;

	if (!$context['event']['new'] || !empty($context['current_board']))
		echo '
			<input type="hidden" name="eventid" value="', !empty($context['event']['eventid']) ? $context['event']['eventid'] : $context['event']['id'], '">';

	echo '
					<fieldset id="event_main">
						<legend><span', isset($context['post_error']['no_event']) ? ' class="error"' : '', ' id="caption_evtitle">', $txt['calendar_event_details'], '</span></legend>
						<input type="text" name="evtitle" maxlength="80" value="', $context['event']['title'], '" tabindex="', $context['tabindex']++, '" class="w75">
						<div class="smalltext nowrap">
							<input type="hidden" name="calendar" value="1">
							<input type="text" name="date" id="date" size="10">
						</div>';

		// If events can span more than one day then allow the user to select how long it should last.
		if (!empty($settings['cal_allowspan']))
		{
			echo '
						<span>
							', $txt['calendar_numb_days'], '
							<select name="span">';

			for ($days = 1; $days <= $settings['cal_maxspan']; $days++)
				echo '
								<option value="', $days, '"', $days == $context['event']['span'] ? ' selected' : '', '>', $days, '&nbsp;</option>';

			echo '
							</select>
						</span>';
		}

	echo '
					</fieldset>';
}

function template_form_link_calendar()
{
	global $context, $txt;

	echo '
					<fieldset id="event_options">
						<legend>', $txt['calendar_event_options'], '</legend>
						<div class="event_options smalltext">
							<ul class="event_options">
								<li>
									', $txt['calendar_post_in'], '
									<select name="board">';

	foreach ($context['event']['categories'] as $category)
	{
		echo '
										<optgroup label="', $category['name'], '">';

		foreach ($category['boards'] as $bdata)
			echo '
											<option value="', $bdata['id'], '"', $bdata['selected'] ? ' selected' : '', '>',
												$bdata['child_level'] > 0 ? str_repeat('==', $bdata['child_level'] - 1) . '=&gt;' : '', ' ', $bdata['name'],
											'&nbsp;</option>';

		echo '
										</optgroup>';
	}

	echo '
									</select>
								</li>
							</ul>
						</div>
					</fieldset>';
}

?>