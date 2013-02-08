<?php
/**
 * WedgeDesk
 *
 * This file handles displaying ticket information in the 'unread' and 'unreadreplies' pages.
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
 *	Handles fetching the information for the helpdesk display within the unread and unread replies pages.
 *
 *	The content is 'appended' below the unread posts information by way of a template layer.
 *
 *	@since 2.0
*/
function shd_unread_posts()
{
	global $context, $sourcedir, $txt, $user_profile, $settings;

	// If we're here, it's because we have a $_REQUEST['action'] of 'unread' or 'unreadreplies', both of which
	// are defined in $context['shd_unread_actions'] thanks to shd_init_actions back in Subs-WedgeDesk.php.
	// !!! Fix this $sourcedir for loadSource
	require_once($sourcedir . '/' . $context['shd_unread_actions'][$_REQUEST['action']][0]);
	$context['shd_unread_actions'][$_REQUEST['action']][1]();

	// Are we using Dragooon's very swish mobile theme, or other wireless? If so, disable this.
	// !!! Still relevant?
	if (WIRELESS || (isset($_REQUEST['thememode']) && $_REQUEST['thememode'] == 'mobile') || (isset($_COOKIE['smf4m_cookie']) && $_COOKIE['smf4m_cookie'] == 'mobile'))
		$settings['shd_disable_unread'] = true;

	// We're only displaying this to staff. We didn't do this check on bootstrapping, no sense doing it every page load.
	if (shd_allowed_to('shd_staff', 0) && empty($settings['shd_disable_unread']))
	{
		// Get the data
		$context['shd_preferences'] = shd_load_user_prefs();
		$context['shd_unread_info'] = array();
		if (empty($context['shd_preferences']['display_unread_type']) || $context['shd_preferences']['display_unread_type'] == 'outstanding')
		{
			// Get all the outstanding tickets
			$context['block_title'] = $txt['shd_tickets_open'];
			$request = wesql::query('
				SELECT hdt.id_ticket, hdt.subject, hdt.id_ticket, hdt.num_replies, hdt.last_updated,
					hdtr_first.poster_name, hdt.urgency, hdt.status, hdt.id_member_started, hdt.id_member_assigned, hdt.id_last_msg AS log_read
				FROM {db_prefix}helpdesk_tickets AS hdt
					INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr_first ON (hdt.id_first_msg = hdtr_first.id_msg)
					INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr_last ON (hdt.id_last_msg = hdtr_last.id_msg)
				WHERE {query_see_ticket}
					AND hdt.status IN ({array_int:status})
				ORDER BY hdt.urgency DESC, hdt.last_updated',
				array(
					'status' => array(TICKET_STATUS_NEW, TICKET_STATUS_PENDING_STAFF),
				)
			);
		}
		elseif ($context['shd_preferences']['display_unread_type'] == 'unread')
		{
			// Only unread ones
			$context['block_title'] = $txt['shd_unread_tickets'];
			$request = wesql::query('
				SELECT hdt.id_ticket, hdt.subject, hdt.id_ticket, hdt.num_replies, hdt.last_updated,
					hdtr_first.poster_name, hdt.urgency, hdt.status, hdt.id_member_started, hdt.id_member_assigned, IFNULL(hdlr.id_msg, 0) AS log_read
				FROM {db_prefix}helpdesk_tickets AS hdt
					INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr_first ON (hdt.id_first_msg = hdtr_first.id_msg)
					INNER JOIN {db_prefix}helpdesk_ticket_replies AS hdtr_last ON (hdt.id_last_msg = hdtr_last.id_msg)
					LEFT JOIN {db_prefix}helpdesk_log_read AS hdlr ON (hdt.id_ticket = hdlr.id_ticket AND hdlr.id_member = {int:user})
				WHERE {query_see_ticket}
					AND hdt.status IN ({array_int:status})
					AND (hdlr.id_msg IS NULL OR hdlr.id_msg < hdt.id_last_msg)
				ORDER BY hdt.urgency DESC, hdt.last_updated',
				array(
					'user' => we::$id,
					'status' => array(TICKET_STATUS_NEW, TICKET_STATUS_PENDING_STAFF),
				)
			);
		}

		if (!empty($request))
		{
			$members = array();
			while ($row = wesql::fetch_assoc($request))
			{
				$row['id_ticket_display'] = str_pad($row['id_ticket'], $settings['shd_zerofill'], '0', STR_PAD_LEFT);
				$row['updated'] = timeformat($row['last_updated']);
				$context['shd_unread_info'][] = $row;
				if ($row['id_member_started'] != 0)
					$members[] = $row['id_member_started'];
				if ($row['id_member_assigned'] != 0)
					$members[] = $row['id_member_assigned'];
			}
			loadMemberData(array_unique($members));
			foreach ($context['shd_unread_info'] as $key => $ticket)
			{
				if (!empty($user_profile[$ticket['id_member_started']]))
					$context['shd_unread_info'][$key]['ticket_starter'] = shd_profile_link($user_profile[$ticket['id_member_started']]['member_name'], $ticket['id_member_started']);
				else
					$context['shd_unread_info'][$key]['ticket_starter'] = $ticket['poster_name'];

				if (!empty($user_profile[$ticket['id_member_assigned']]))
					$context['shd_unread_info'][$key]['ticket_assigned'] = shd_profile_link($user_profile[$ticket['id_member_assigned']]['member_name'], $ticket['id_member_assigned']);
				else
					$context['shd_unread_info'][$key]['ticket_assigned'] = '<span class="error">' . $txt['shd_unassigned'] . '</span>';
			}

			// And set up the template too.
			loadPluginTemplate('Arantor:WedgeDesk', 'tpl/WedgeDesk-Unread');
			add_plugin_css_file('Arantor:WedgeDesk', 'css/helpdesk', true);
			wetem::load('shd_unread', 'default', 'add');
		}
	}
}

?>