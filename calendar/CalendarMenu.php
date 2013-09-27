<?php
/**
 * Per-page processing, e.g. adding the menu item and potentially banned permissions too.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function calendarMenu(&$items)
{
	global $context, $txt, $scripturl;
	loadPluginLanguage('Wedgeward:Calendar', 'lang/Calendar');

	$context['allow_calendar'] = allowedTo('calendar_view');

	$menu_item = array(
		'calendar' => array(
			'title' => $txt['calendar'],
			'href' => $scripturl . '?action=calendar',
			'show' => $context['allow_calendar'],
			'sub_items' => array(
				'view' => array(
					'title' => $txt['calendar_menu'],
					'href' => $scripturl . '?action=calendar',
					'show' => allowedTo('calendar_post'),
				),
				'post' => array(
					'title' => $txt['calendar_post_event'],
					'href' => $scripturl . '?action=calendar;sa=post',
					'show' => allowedTo('calendar_post'),
				),
			),
		),
	);

	$items = array_insert($items, 'media', $menu_item, false);
}
// Remove these permissions if post-banned.
function bannedCalendar(&$denied_permissions)
{
	$denied_permissions += array('calendar_post', 'calendar_edit_own', 'calendar_edit_any');
}
?>