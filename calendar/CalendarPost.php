<?php
/**
 * Wedge
 *
 * Provides functionality for showing the calendar items in the topic posting view, including creating events.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function calendar_post_form_pre()
{
	global $context;

	// Posting an event?
	$context['make_event'] = isset($_REQUEST['calendar']);
	if ($context['make_event'])
		$context['allow_no_board'] = true;
}
function calendar_post_form()
{
	global $context, $txt, $settings;

	// Guess not posting an event after all?
	if (!$context['make_event'])
		return;

	// Just in case for whatever reason we don't have this.
	loadPluginLanguage('Wedgeward:Calendar', 'lang/Calendar');
	loadPluginTemplate('Wedgeward:Calendar', 'CalendarIntegration');

	// They might want to pick a board.
	if (!isset($context['current_board']))
		$context['current_board'] = 0;

	// Start loading up the event info.
	$context['event'] = array();
	$context['event']['title'] = isset($_REQUEST['evtitle']) ? htmlspecialchars(stripslashes($_REQUEST['evtitle'])) : '';

	$context['event']['id'] = isset($_REQUEST['eventid']) ? (int) $_REQUEST['eventid'] : -1;
	$context['event']['new'] = $context['event']['id'] == -1;

	// Permissions check!
	isAllowedTo('calendar_post');

	if (!isset($settings['cal_defaultboard']))
		$settings['cal_defaultboard'] = 0;

	// Editing an event?  (but NOT previewing!?)
	if (!$context['event']['new'] && !isset($_REQUEST['subject']))
	{
		// If the user doesn't have permission to edit the post in this topic, redirect them.
		if ((empty($id_member_poster) || $id_member_poster != $user_info['id'] || !allowedTo('modify_own')) && !allowedTo('modify_any'))
		{
			loadPluginSource('Wedgeward:Calendar', 'Calendar');
			return CalendarPost();
		}

		// Get the current event information.
		$request = wesql::query('
			SELECT
				id_member, title, MONTH(start_date) AS month, DAYOFMONTH(start_date) AS day,
				YEAR(start_date) AS year, (TO_DAYS(end_date) - TO_DAYS(start_date)) AS span
			FROM {db_prefix}calendar
			WHERE id_event = {int:id_event}
			LIMIT 1',
			array(
				'id_event' => $context['event']['id'],
			)
		);
		$row = wesql::fetch_assoc($request);
		wesql::free_result($request);

		// Make sure the user is allowed to edit this event.
		if ($row['id_member'] != $user_info['id'])
			isAllowedTo('calendar_edit_any');
		elseif (!allowedTo('calendar_edit_any'))
			isAllowedTo('calendar_edit_own');

		$context['event']['month'] = $row['month'];
		$context['event']['day'] = $row['day'];
		$context['event']['year'] = $row['year'];
		$context['event']['title'] = $row['title'];
		$context['event']['span'] = $row['span'] + 1;
	}
	else
	{
		$today = getdate();

		// You must have a month and year specified!
		if (!isset($_REQUEST['month']))
			$_REQUEST['month'] = $today['mon'];
		if (!isset($_REQUEST['year']))
			$_REQUEST['year'] = $today['year'];

		$context['event']['month'] = (int) $_REQUEST['month'];
		$context['event']['year'] = (int) $_REQUEST['year'];
		$context['event']['day'] = isset($_REQUEST['day']) ? $_REQUEST['day'] : ($_REQUEST['month'] == $today['mon'] ? $today['mday'] : 0);
		$context['event']['span'] = isset($_REQUEST['span']) ? $_REQUEST['span'] : 1;

		// Make sure the year and month are in the valid range.
		if ($context['event']['month'] < 1 || $context['event']['month'] > 12)
			fatal_lang_error('invalid_month', false);
		if ($context['event']['year'] < $settings['cal_minyear'] || $context['event']['year'] > $settings['cal_maxyear'])
			fatal_lang_error('invalid_year', false);

		// Get a list of boards they can post in.
		$boards = boardsAllowedTo('post_new');
		if (empty($boards))
			fatal_lang_error('cannot_post_new', 'user');

		// Load a list of boards for this event in the context.
		loadSource('Subs-MessageIndex');
		$boardListOptions = array(
			'included_boards' => in_array(0, $boards) ? null : $boards,
			'not_redirection' => true,
			'use_permissions' => true,
			'selected_board' => empty($context['current_board']) ? $settings['cal_defaultboard'] : $context['current_board'],
		);
		$context['event']['categories'] = getBoardList($boardListOptions);
	}

	// Find the last day of the month.
	$context['event']['last_day'] = (int) strftime('%d', mktime(0, 0, 0, $context['event']['month'] == 12 ? 1 : $context['event']['month'] + 1, 0, $context['event']['month'] == 12 ? $context['event']['year'] + 1 : $context['event']['year']));

	$context['event']['board'] = !empty($board) ? $board : $settings['cal_defaultboard'];

	// Also, add the delete-event button to the button list.
	if (!$context['event']['new'])
		$context['postbox']->addButton(
			'deleteevent',
			$txt['event_delete'],
			'return confirm(' . JavaScriptEscape($txt['event_delete_confirm']) . ');'
		);

	// Add the relevant template
	wetem::before('postbox', 'form_event_details');
	if ($context['event']['new'] && $context['is_new_post'])
		wetem::before('postbox', 'form_link_calendar');

	$context['postbox']->addEntityField('evtitle');

	// Add the date input magic
	add_plugin_css_file('Wedgeward:Calendar', 'css/dateinput', true);
	add_plugin_js_file('Wedgeward:Calendar', 'js/dateinput.js');
	add_js('
    var
        days = ' . json_encode(array_values($txt['days'])) . ',
        daysShort = ' . json_encode(array_values($txt['days_short'])) . ',
        months = ' . json_encode(array_values($txt['months'])) . ',
        monthsShort = ' . json_encode(array_values($txt['months_short'])) . ';
	$("#date").dateinput();');

	// Reset the page title.
	$context['page_title'] = $context['event']['id'] == -1 ? $txt['calendar_post_event'] : $txt['calendar_edit'];

	// Add items to the form so we know to expect them.
	$context['form_fields']['numeric'] += array('eventid', 'calendar', 'year', 'month', 'day');
}

function validateCalendarEvent(&$post_errors, &$posterIsGuest)
{
	if (isset($_POST['calendar']) && !isset($_REQUEST['deleteevent']) && (empty($_POST['evtitle']) || westr::htmltrim($_POST['evtitle']) === ''))
	{
		// Just in case for whatever reason we don't have this.
		loadPluginLanguage('Wedgeward:Calendar', 'lang/Calendar');
		$post_errors[] = 'no_event';
	}
}

// !!! This probably should be returning back from the validateCalendarEvent setup and reflowing the form if necessary.
function postCalendarEvent(&$msgOptions, &$topicOptions, &$posterOptions)
{
	// Editing or posting an event?
	if (isset($_POST['calendar']) && (!isset($_REQUEST['eventid']) || $_REQUEST['eventid'] == -1))
	{
		loadPluginSource('Wedgeward:Calendar', 'Subs-Calendar');

		// Make sure they can link an event to this post.
		canLinkEvent();

		// Insert the event.
		$eventOptions = array(
			'board' => $board,
			'topic' => $topic,
			'title' => $_POST['evtitle'],
			'member' => $user_info['id'],
			'start_date' => sprintf('%04d-%02d-%02d', $_POST['year'], $_POST['month'], $_POST['day']),
			'span' => isset($_POST['span']) && $_POST['span'] > 0 ? min((int) $settings['cal_maxspan'], (int) $_POST['span'] - 1) : 0,
		);
		insertEvent($eventOptions);
	}
	elseif (isset($_POST['calendar']))
	{
		$_REQUEST['eventid'] = (int) $_REQUEST['eventid'];

		// Validate the post...
		loadPluginSource('Wedgeward:Calendar', 'Subs-Calendar');
		validateEventPost();

		// If you're not allowed to edit any events, you have to be the poster.
		if (!allowedTo('calendar_edit_any'))
		{
			// Get the event's poster.
			$request = wesql::query('
				SELECT id_member
				FROM {db_prefix}calendar
				WHERE id_event = {int:id_event}',
				array(
					'id_event' => $_REQUEST['eventid'],
				)
			);
			$row2 = wesql::fetch_assoc($request);
			wesql::free_result($request);

			// Silly hacker, Trix are for kids. ...probably trademarked somewhere, this is FAIR USE! (parody...)
			isAllowedTo('calendar_edit_' . ($row2['id_member'] == $user_info['id'] ? 'own' : 'any'));
		}

		// Delete it?
		if (isset($_REQUEST['deleteevent']))
			wesql::query('
				DELETE FROM {db_prefix}calendar
				WHERE id_event = {int:id_event}',
				array(
					'id_event' => $_REQUEST['eventid'],
				)
			);
		// ... or just update it?
		else
		{
			$span = !empty($settings['cal_allowspan']) && !empty($_REQUEST['span']) ? min((int) $settings['cal_maxspan'], (int) $_REQUEST['span'] - 1) : 0;
			$start_time = mktime(0, 0, 0, (int) $_REQUEST['month'], (int) $_REQUEST['day'], (int) $_REQUEST['year']);

			wesql::query('
				UPDATE {db_prefix}calendar
				SET end_date = {date:end_date},
					start_date = {date:start_date},
					title = {string:title}
				WHERE id_event = {int:id_event}',
				array(
					'end_date' => strftime('%Y-%m-%d', $start_time + $span * 86400),
					'start_date' => strftime('%Y-%m-%d', $start_time),
					'id_event' => $_REQUEST['eventid'],
					'title' => westr::htmlspecialchars($_REQUEST['evtitle'], ENT_QUOTES),
				)
			);
		}
		updateSettings(array(
			'calendar_updated' => time(),
		));
	}
}
?>