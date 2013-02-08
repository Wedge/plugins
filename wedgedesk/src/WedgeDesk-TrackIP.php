<?php
/**
 * WedgeDesk
 *
 * This file adds support to the profile area for tracking user IP addresses.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/**
 *	Base code for adding to the profile view/track IP screen.
 *
 *	@since 2.0
*/
function shd_trackip(&$ip_string, &$ip_var)
{
	global $context, $settings, $txt;

	$listOptions = array(
		'id' => 'track_helpdesk_list',
		'title' => $txt['shd_replies_from_ip'] . ' ' . $context['ip'],
		'start_var_name' => 'helpdeskStart',
		'items_per_page' => $settings['defaultMaxMessages'],
		'no_items_label' => $txt['shd_replies_from_ip'],
		'base_href' => $context['base_url'] . ';searchip=' . $context['ip'],
		'default_sort_col' => 'date2',
		'get_items' => array(
			'function' => 'shd_list_get_ip_messages',
			'params' => array(
				'hdtr.poster_ip ' . $ip_string,
				array('ip_address' => $ip_var),
			),
		),
		'get_count' => array(
			'function' => 'shd_list_get_ip_message_count',
			'params' => array(
				'hdtr.poster_ip ' . $ip_string,
				array('ip_address' => $ip_var),
			),
		),
		'columns' => array(
			'ip_address2' => array(
				'header' => array(
					'value' => $txt['ip_address'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $context['base_url'] . ';searchip=%1$s">%1$s</a>',
						'params' => array(
							'ip' => false,
						),
					),
				),
				'sort' => array(
					'default' => 'INET_ATON(hdtr.poster_ip)',
					'reverse' => 'INET_ATON(hdtr.poster_ip) DESC',
				),
			),
			'display_name' => array(
				'header' => array(
					'value' => $txt['display_name'],
				),
				'data' => array(
					'db' => 'member_link',
				),
			),
			'subject' => array(
				'header' => array(
					'value' => $txt['subject'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="<URL>?action=helpdesk;sa=ticket;ticket=%1$s.msg%2$s#msg%2$s" rel="nofollow">%3$s</a>%4$s',
						'params' => array(
							'ticket' => false,
							'id' => false,
							'subject' => false,
							'additional' => false,
						),
					),
				),
			),
			'date2' => array(
				'header' => array(
					'value' => $txt['date'],
				),
				'data' => array(
					'db' => 'time',
				),
				'sort' => array(
					'default' => 'hdtr.id_msg DESC',
					'reverse' => 'hdtr.id_msg',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'after_title',
				'value' => $txt['shd_replies_from_ip_desc'],
				'class' => 'smalltext',
				'style' => 'padding: 2ex;',
			),
		),
	);

	// Create the helpdesk replies list.
	createList($listOptions);

	// !!! This might not be true any more.
	loadPluginTemplate('Arantor:WedgeDesk', '$plugindir/tpl/WedgeDesk-Profile');
	wetem::load('shd_trackip', 'default', 'add');
}

function shd_list_get_ip_messages($start, $items_per_page, $sort, $where, $where_vars = array())
{
	global $txt;
	$query = wesql::query('
		SELECT
			hdtr.id_msg, hdtr.poster_ip, IFNULL(mem.real_name, hdtr.poster_name) AS display_name, mem.id_member,
			hdt.subject, hdtr.poster_time, hdt.id_ticket, hdt.id_first_msg
		FROM {db_prefix}helpdesk_ticket_replies AS hdtr
			INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdtr.id_ticket = hdt.id_ticket)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = hdtr.id_member)
		WHERE {query_see_ticket} AND ' . $where . '
		ORDER BY ' . $sort . '
		LIMIT ' . $start . ', ' . $items_per_page,
		array_merge($where_vars, array(
		))
	);
	$messages = array();
	while ($row = wesql::fetch_assoc($query))
		$messages[] = array(
			'ip' => $row['poster_ip'],
			'member_link' => shd_profile_link($row['display_name'], $row['id_member']),
			'ticket' => $row['id_ticket'],
			'id' => $row['id_msg'],
			'subject' => $row['subject'],
			'time' => timeformat($row['poster_time']),
			'timestamp' => forum_time(true, $row['poster_time']),
			'additional' => $row['id_first_msg'] == $row['id_msg'] ? $txt['shd_is_ticket_opener'] : '',
		);
	wesql::free_result($query);

	return $messages;
}

function shd_list_get_ip_message_count($where, $where_vars = array())
{
	$request = wesql::query('
		SELECT COUNT(id_msg) AS message_count
		FROM {db_prefix}helpdesk_ticket_replies AS hdtr
			INNER JOIN {db_prefix}helpdesk_tickets AS hdt ON (hdtr.id_ticket = hdt.id_ticket)
		WHERE {query_see_ticket} AND ' . $where,
		$where_vars
	);
	list ($count) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $count;
}