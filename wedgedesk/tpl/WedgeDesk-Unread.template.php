<?php
/**
 * WedgeDesk
 *
 * Displays the ticket information in the unread posts page.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

/**
 *	Displays the list of possible users a ticket can have assigned.
 *
 *	Will have been populated by shd_unread_posts() in WedgeDesk-Unread.php, adding into $context['shd_unread_info'].
 *
 *	@see shd_unread_posts()
 *	@since 2.0
*/
function template_shd_unread()
{
	global $context, $txt;

	echo '
				<br>
				<we:cat>
					<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/ticket.png">
					', $context['block_title'], '
					<span class="smalltext">(', count($context['shd_unread_info']) == 1 ? '1 ' . $txt['shd_count_ticket_1'] : count($context['shd_unread_info']) . ' ' . $txt['shd_count_tickets'], ')</span>
				</we:cat>

				<table class="shd_ticketlist" cellspacing="0" width="100%">
					<tr class="titlebg">
						<td width="8%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/ticket.png" class="shd_smallicon"> ', $txt['shd_ticket'], '</td>
						<td width="15%" class="shd_nowrap">', $txt['shd_ticket_name'], '</td>
						<td width="12%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/user.png" class="shd_smallicon"> ', $txt['shd_ticket_started_by'], '</td>
						<td width="7%" class="shd_nowrap">', $txt['shd_ticket_replies'], '</td>
						<td width="17%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/status.png" class="shd_smallicon"> ', $txt['shd_ticket_status'], '</td>
						<td width="8%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/urgency.png" class="shd_smallicon"> ', $txt['shd_ticket_urgency'], '</td>
						<td width="22%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/time.png" class="shd_smallicon"> ', $txt['shd_ticket_updated'], '</td>
					</tr>';

	if (empty($context['shd_unread_info']))
	{
		echo '
					<tr class="windowbg2">
						<td colspan="7">', $txt['shd_error_no_tickets'], '</td>
					</tr>';
	}
	else
	{
		$use_bg2 = true;
		foreach ($context['shd_unread_info'] as $ticket)
		{
			echo '
					<tr class="windowbg', $use_bg2 ? '2' : '', '">
						<td width="4%" class="smalltext">', $ticket['id_ticket_display'], '</td>
						<td class="smalltext"><a href="<URL>?action=helpdesk;sa=ticket;ticket=', $ticket['id_ticket'], '">', $ticket['subject'], '</a></td>
						<td class="smalltext">', $ticket['ticket_starter'], '</td>
						<td class="smalltext">', $ticket['num_replies'], '</td>
						<td class="smalltext">', $txt['shd_status_' . $ticket['status']], '</td>
						<td class="smalltext">', $txt['shd_urgency_' . $ticket['urgency']], '</td>
						<td class="smalltext">', $ticket['updated'], '</td>
					</tr>';

			$use_bg2 = !$use_bg2;
		}
	}

	echo '
				</table>';
}

?>