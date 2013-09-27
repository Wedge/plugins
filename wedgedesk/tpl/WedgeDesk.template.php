<?php
/**
 * WedgeDesk
 *
 * This file handles displaying the blocks of tickets for the front page, as well as the slightly
 * customised views for the recycle bin and the list of resolved tickets.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

/**
 *	Display the main helpdesk view of active tickets.
 *
 *	This function steps through the blocks defined in WedgeDesk.php to display all the blocks that potentially would be visible, noting whether blocks have been collapsed or not, and calling to the sub-subtemplates to output collapsed and noncollapsed blocks.
 *
 *	All the blocks here are defined in {@link shd_main_helpdesk()} (or {@link shd_view_block()} if viewing a single block) and data gathered in {@link shd_helpdesk_listing()}.
 *
 *	@see template_collapsed_ticket_block()
 *	@see template_ticket_block()
 *	@since 1.0
*/
function template_hdmain()
{
	global $context, $txt;

	echo '
		<div class="pagesection">';

	template_button_strip($context['navigation'], 'left');

	echo '
		</div>
		<div id="admin_content">
		<table width="100%" class="shd_main_hd">
			<tr>
				<td valign="top">
					<we:cat>
						', $txt['shd_helpdesk'], !empty($context['shd_dept_name']) && $context['shd_multi_dept'] ? ' - ' . $context['shd_dept_name'] : '', '
					</we:cat>
					<div class="roundframe">';

	template_jumpto_ticket();

	echo '
						<div id="welcome">
							<strong>', sprintf($txt['shd_welcome'], we::$user['name']), '</strong><br>
							', $txt['shd_' . $context['shd_home_view'] . '_greeting'];

	template_block_filter();

	echo '
						</div>
					</div>
					<br>';

	// Start the ticket listing
	$blocks = array_keys($context['ticket_blocks']);
	foreach ($blocks as $block)
	{
		$context['current_block'] = $block;
		if (!empty($context['ticket_blocks'][$block]['count']) && $context['ticket_blocks'][$block]['count'] > 10)
			$context['block_link'] = $_REQUEST['sa'] == 'viewblock' ? '<URL>?' . $context['shd_home'] . $context['shd_dept_link'] : '<URL>?action=helpdesk;sa=viewblock;block=' . $block . $context['shd_dept_link'] . '#shd_block_' . $block;
		else
			$context['block_link'] = '';

		if ($context['ticket_blocks'][$block]['collapsed'])
			template_collapsed_ticket_block();
		else
			template_ticket_block();
	}

	echo '
				</td>
			</tr>
			</table>
		</div>';

	// echo 'I\'m alive!!!!!!!!!!1111oneone';	- I had to save this :P
}

function template_shd_depts()
{
	global $context, $txt, $theme;

	echo '
		<div class="pagesection">';

	template_button_strip($context['navigation'], 'left');

	echo '
		</div>
		<div id="admin_content">
		<table width="100%" class="shd_main_hd">
			<tr>
				<td valign="top">
					<we:cat>
						', $txt['shd_helpdesk'], '
					</we:cat>
					<div class="roundframe">
						<div class="shd_gototicket smalltext">
							<form action="<URL>?action=helpdesk;sa=ticket" method="get">
								', $txt['shd_go_to_ticket'], ':
								<input type="hidden" name="action" value="helpdesk">
								<input type="hidden" name="sa" value="ticket">
								<input type="text" class="input_text" id="ticketJump" name="ticket" size="4">
								<input type="submit" class="button_submit" value="', $txt['shd_go'], '" onclick="shd_quickTicketJump(this.parentNode.ticketJump.value);">
							</form>
						</div>
						<div id="welcome">
							<strong>', sprintf($txt['shd_welcome'], we::$user['name']), '</strong><br>
							', $txt['shd_' . $context['shd_home_view'] . '_greeting'], '
						</div>
					</div>
					<br>
					<we:cat>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/departments.png">
						', $txt['shd_departments'], '
					</we:cat>
					<table class="shd_ticketlist table_list" cellspacing="0" width="100%" id="board_list">
						<tbody class="content">';

	foreach ($context['dept_list'] as $dept)
	{
		$state = $context['dept_list'][$dept['id_dept']]['new'] ? 'on' : 'off';
		if (file_exists($theme['theme_dir'] . '/icons/shd' . $dept['id_dept'] . '/on.png'))
			$icon = $theme['theme_url'] . '/icons/shd' . $dept['id_dept'] . '/' . $state . '.png';
		else
			$icon = $context['plugins_url']['Arantor:WedgeDesk'] . '/images/helpdesk_' . $state . '.png';

		echo '
							<tr class="windowbg2">
								<td class="icon windowbg"><img src="', $icon, '"></td>
								<td class="info"><a href="<URL>?', $context['shd_home'], ';dept=', $dept['id_dept'], '">', $dept['dept_name'], '</a></td>
								<td class="stats windowbg">', $dept['tickets']['open'], ' open<br>', $dept['tickets']['closed'], ' closed</td>
								<td class="lastpost"></td>
							</tr>';
	}

	echo '
						</tbody>
					</table>
				</td>
			</tr>
			</table>
		</div>';
}

/**
 *	Display the helpdesk view of resolved tickets.
 *
 *	This function steps through the blocks defined in WedgeDesk.php to display all the block of closed items.
 *
 *	All the blocks here are defined in {@link shd_closed_tickets()} and data gathered in {@link shd_helpdesk_listing()}.
 *
 *	@see template_ticket_block()
 *	@since 1.0
*/
function template_closedtickets()
{
	global $context, $txt;

	echo '
		<div class="pagesection">';

	template_button_strip($context['navigation'], 'left');

	echo '
		</div>
		<div id="admin_content">
		<table width="100%" class="shd_main_hd">
			<tr>
				<td valign="top">
					<we:cat>
						', $txt['shd_helpdesk'], '
					</we:cat>
					<div class="roundframe">';

	template_jumpto_ticket();

	echo '
						<div id="welcome">
							<strong>', sprintf($txt['shd_welcome'], we::$user['name']), '</strong><br>
							', $txt['shd_closed_' . $context['shd_home_view'] . '_greeting'];

	template_block_filter();

	echo '
						</div>
					</div>
					<br>';

	// Start the ticket listing
	$blocks = array_keys($context['ticket_blocks']);
	foreach ($blocks as $block)
	{
		$context['current_block'] = $block;
		template_ticket_block();
	}

	echo '
				</td>
			</tr>
			</table>
		</div>';
}

/**
 *	Display the helpdesk view of recycled and partly recycled tickets.
 *
 *	This function steps through the blocks defined in WedgeDesk.php to display all the blocks that would be related; the list of deleted tickets, and the list of tickets with deleted replies in.
 *
 *	All the blocks here are defined in {@link shd_recycle_bin()} and data gathered in {@link shd_helpdesk_listing()}.
 *
 *	@see template_ticket_block()
 *	@since 1.0
*/
function template_recyclebin()
{
	global $context, $txt;

	echo '
		<div class="pagesection">';

	template_button_strip($context['navigation'], 'left');

	echo '
		</div>
		<div id="admin_content">
		<table width="100%" class="shd_main_hd">
			<tr>';

	echo '
				<td valign="top">
					<we:cat>
						', $txt['shd_helpdesk'], '
					</we:cat>
					<div class="roundframe">';

	template_jumpto_ticket();

	echo '
						<div id="welcome">
							<strong>', sprintf($txt['shd_welcome'], we::$user['name']), '</strong><br>
							', $txt['shd_recycle_greeting'];

	template_block_filter();

	echo '
						</div>
					</div>
					<br>';

	// Loop through the crap... Uh, I mean the tickets! :)
	$blocks = array_keys($context['ticket_blocks']);
	foreach ($blocks as $block)
	{
		$context['current_block'] = $block;
		template_ticket_block();
	}

	echo '
				</td>
			</tr>
			</table>
		</div>';
}

function template_jumpto_ticket()
{
	global $txt;

	echo '
						<div class="shd_gototicket smalltext">
							<form action="<URL>?action=helpdesk" method="post">
								', $txt['shd_go_to_ticket'], ':
								<input type="text" name="ticket" size="4">
								<input type="submit" value="', $txt['shd_go'], '">
								<input type="hidden" name="sa" value="ticket">
							</form>
						</div>';
}

function template_block_filter()
{
	global $context, $txt;
	if (!empty($context['shd_filter_fields']))
	{
		echo '
			<br><br><strong>', $txt['shd_category_filter'], ':</strong>';
		foreach ($context['shd_filter_fields'] as $id_field => $field)
		{
			echo '
			<br>', $field['name'], ':';
			foreach ($field['options'] as $key => $opt)
			{
				if (!empty($context['filter_fragment']) && $_REQUEST['field'] == $id_field && $_REQUEST['filter'] == $key)
					echo ' [', $opt, ']';
				else
					echo ' [<a href="' . $context['filterbase'] . $context['shd_dept_link'] . ';field=' . $id_field . ';filter=' . $key . '">' . $opt . '</a>]';
			}
		}
	}
}

/**
 *	Display a collapsed block.
 *
 *	In the front page, you are able to force a given block to be displayed in its entirety. When that happens, the other blocks are collapsed, so that they are present but their principle content is not.
 *
 *	@see template_ticket_block()
 *	@since 1.0
*/
function template_collapsed_ticket_block()
{
	global $context, $txt;

	echo '
					<we:cat>
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $context['ticket_blocks'][$context['current_block']]['block_icon'], '">
						', (empty($context['block_link']) ? '' : '<a href="' . $context['block_link'] . '">'), $context['ticket_blocks'][$context['current_block']]['title'], '
						<span class="smalltext">(', $context['ticket_blocks'][$context['current_block']]['count'], ' ', ($context['ticket_blocks'][$context['current_block']]['count'] == 1 ? $txt['shd_count_ticket_1'] : $txt['shd_count_tickets']), ')</span>', (empty($context['block_link']) ? '' : '</a>'), '
					</we:cat>
					<br>
					<br>';
}

/**
 *	Display an individual, non-collapsed block.
 *
 *	Each front-page template uses this function to display a given block of tickets. It handles displaying the menu header, including ticket count, followed by all the different column types as listed in {@link shd_main_helpdesk()}, then to iterate through the ticket details to display each row (provided by {@link shd_helpdesk_listing()}.
 *
 *	@see shd_main_helpdesk()
 *	@see shd_helpdesk_listing()
 *	@since 1.0
*/
function template_ticket_block()
{
	global $context, $txt;
	// $context['ticket_blocks'] = array of arrays of ticket data
	// $context['current_block'] = the block to display now

	echo '
					<we:cat>
						', !empty($context['ticket_blocks'][$context['current_block']]['page_index']) ? '<span class="floatright smalltext">'. $txt['pages'] . ': ' . $context['ticket_blocks'][$context['current_block']]['page_index'] . '</span>' : '', '
						<img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/', $context['ticket_blocks'][$context['current_block']]['block_icon'], '">
						', (empty($context['block_link']) ? '' : '<a href="' . $context['block_link'] . '">'), $context['ticket_blocks'][$context['current_block']]['title'], '
						<span class="smalltext">(', $context['ticket_blocks'][$context['current_block']]['count'], ' ', ($context['ticket_blocks'][$context['current_block']]['count'] == 1 ? $txt['shd_count_ticket_1'] : $txt['shd_count_tickets']), ')</span>', (empty($context['block_link']) ? '' : '</a>'), '
					</we:cat>

					<table class="shd_ticketlist table_grid w100 cs0">
						<tr class="catbg">';

	$block_width = 0;
	$max = count($context['ticket_blocks'][$context['current_block']]['columns']) - 1;
	foreach ($context['ticket_blocks'][$context['current_block']]['columns'] as $column)
	{
		$block_width++;
		switch ($column)
		{
			case 'ticket_id':
				echo '
							<th width="3%" class="shd_nowrap">', template_shd_menu_header('ticketid', $txt['shd_ticket']), '</th>';
				break;
			case 'ticket_name':
				echo '
							<th width="15%" class="shd_nowrap">', template_shd_menu_header('ticketname', $txt['shd_ticket_name']), '</th>';
				break;
			case 'starting_user':
				echo '
							<th width="12%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/user.png" class="shd_smallicon"> ', template_shd_menu_header('starter', $txt['shd_ticket_started_by']), '</th>';
				break;
			case 'last_reply':
				echo '
							<th width="12%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/staff.png" class="shd_smallicon"> ', template_shd_menu_header('lastreply', $txt['shd_ticket_updated_by']), '</th>';
				break;
			case 'assigned':
				echo '
							<th width="12%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/staff.png" class="shd_smallicon"> ', template_shd_menu_header('assigned', $txt['shd_ticket_assigned']), '</th>';
				break;
			case 'status':
				echo '
							<th width="15%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/status.png" class="shd_smallicon"> ', template_shd_menu_header('status', $txt['shd_ticket_status']), '</th>';
				break;
			case 'urgency':
				echo '
							<th width="8%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/urgency.png" class="shd_smallicon"> ', template_shd_menu_header('urgency', $txt['shd_ticket_urgency']), '</th>';
				break;
			case 'updated':
				echo '
							<th width="17%" class="shd_nowrap"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/time.png" class="shd_smallicon"> ', template_shd_menu_header('updated', $txt['shd_ticket_updated']), '</th>';
				break;
			case 'replies':
				echo '
							<th width="7%" class="shd_nowrap">', template_shd_menu_header('replies', $txt['shd_ticket_num_replies']), '</th>';
				break;
			case 'allreplies':
				echo '
							<th width="7%" class="shd_nowrap">', template_shd_menu_header('allreplies', $txt['shd_ticket_num_replies']), '</th>';
				break;
			case 'actions':
				echo '
							<th width="5%" class="shd_nowrap">', $txt['shd_actions'] , '</th>';
				break;
			default:
				echo '
							<td><td>';
				break;
		}
	}

	echo '
						</tr>';

	if (empty($context['ticket_blocks'][$context['current_block']]['tickets']))
	{
		echo '
						<tr class="windowbg2">
							<td colspan="', $block_width, '" class="shd_noticket">', $txt['shd_error_no_tickets'], '</td>
						</tr>';
	}
	else
	{
		$use_bg2 = true; // start with windowbg2 to differentiate between that and titlebg
		foreach ($context['ticket_blocks'][$context['current_block']]['tickets'] as $ticket)
		{
			echo '
						<tr class="', ($use_bg2 ? 'windowbg2' : 'windowbg'), '">';

			foreach ($context['ticket_blocks'][$context['current_block']]['columns'] as $column)
			{
				switch ($column)
				{
					case 'ticket_id':
						echo '
							<td width="3%" class="smalltext">', $ticket['display_id'], '</td>';
						break;
					case 'ticket_name':
						echo '
							<td class="smalltext">', $ticket['dept_link'], $ticket['link'];

						if ($ticket['private'])
							echo ' <img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/private.png" alt="', $txt['shd_ticket_private'], '" title="', $txt['shd_ticket_private'], '">';

						if ($ticket['is_unread'] && !empty($ticket['new_href']))
							echo ' <a href="', $ticket['new_href'], '"><div class="new_icon" title="', $txt['new'], '"></div></a>';

						echo '</td>';
						break;
					case 'starting_user':
						echo '
							<td class="smalltext">', $ticket['starter']['link'], '</td>';
						break;
					case 'last_reply':
						echo '
							<td class="smalltext">', $ticket['respondent']['link'], '</td>';
						break;
					case 'assigned':
						echo '
							<td class="smalltext">' . $ticket['assigned']['link'] . '</td>';
						break;
					case 'status':
						echo '
							<td class="smalltext">', $ticket['status']['label'], '</td>';
						break;
					case 'urgency':
						echo '
							<td class="smalltext">' . $ticket['urgency']['label'] . '</td>';
						break;
					case 'updated':
						echo '
							<td class="smalltext">', $ticket['last_update'], '</td>';
						break;
					case 'replies':
						echo '
							<td class="smalltext"><a href="', $ticket['replies_href'], '">', $ticket['num_replies'], '</a></td>';
						break;
					case 'allreplies':
						echo '
							<td class="smalltext"><a href="', $ticket['replies_href'], '">', $ticket['all_replies'], '</a></td>';
						break;
					case 'actions':
						echo '
							<td class="shd_nowrap">';

						foreach ($ticket['actions'] as $action)
							echo '
								', $action;

						echo '
							</td>';

						break;
					default:
						echo '
							<td><td>';
						break;
				}
			}

			echo '
						</tr>';

			$use_bg2 = !$use_bg2;
		}
	}

	if (!empty($context['ticket_blocks'][$context['current_block']]['page_index']))
		echo '
						<tr class="titlebg">
							<td colspan="', $block_width, '"><span class="floatright smalltext">', $txt['pages'], ': ', $context['ticket_blocks'][$context['current_block']]['page_index'], '</span></td>
						</tr>';

	echo '
					</table>
					<br>';
}

/**
 *	Makes a menu header clickable/sortable.
 *
 *	Within the ticket blocks, it is possible to sort the blocks by column, and do so in a way that is retained as you manipulate individual blocks. Since this is transient (not pushed to the database) it needs to be recorded in the URL over time.
 *
 *	@param string $header The identifier of the header to use here; related to {@link shd_helpdesk_listing()}
 *	@param string $string The text string to use as the header text
 *
 *	@return string The fully formed HTML fragment for the link, text and hint image
 *	@see template_ticket_block()
 *	@since 1.0
*/
function template_shd_menu_header($header, $string)
{
	global $context, $theme;

	if (empty($context['ticket_blocks'][$context['current_block']]['tickets']))
		return $string; // no sense doing any work if it's an empty block and thus not sortable!

	$link = '';
	// Get the pages of existing items first
	foreach ($context['ticket_blocks'] as $block_key => $block)
	{
		if (isset($_REQUEST['st_' . $block_key]))
			$link .= ';st_' . $block_key . '=' . $block['start'];
	}

	$direction = 'down';
	// Now for sorting direction per block
	foreach ($context['ticket_blocks'] as $block_key => $block)
	{
		if (!$block['sort']['add_link'] && $block_key != $context['current_block'])
			continue;

		$link_direction = ($block_key == $context['current_block']) ? ($block['sort']['direction'] == 'asc' ? 'desc' : 'asc') : $block['sort']['direction'];

		$link .= ';so_' . $block_key . '=' . ($block_key != $context['current_block'] ? $block['sort']['item'] : $header) . '_' . $link_direction;
	}

	$html = '<a href="<URL>?action=helpdesk;sa=' . $_REQUEST['sa'] . ($_REQUEST['sa'] == 'viewblock' ? ';block=' . $_REQUEST['block'] : '') . $link . $context['shd_dept_link'] . '">' . $string . '</a> ';

	if ($context['ticket_blocks'][$context['current_block']]['sort']['item'] == $header)
	{
		$html .= '<img src="' . $theme['images_url'] . '/sort_' . ($context['ticket_blocks'][$context['current_block']]['sort']['direction'] == 'asc' ? 'down' : 'up') . '.gif">';
	}

	return $html;
}

/**
 *	Displays a header to admins while the helpdesk is in maintenance mode.
 *
 *	The helpdesk is disabled to non admins while in maintenance mode, but this template is added to the template layers if the user is an admin and it's in maintenance mode.
 *	@since 2.0
*/
function template_shd_maintenance()
{
	global $txt, $context;
	echo '<div class="errorbox"><img src="', $context['plugins_url']['Arantor:WedgeDesk'], '/images/update.png" class="shd_icon_minihead"> &nbsp;', $txt['shd_helpdesk_maintenance'], '</div>';
}

function template_tracker_view()
{
	global $context, $txt;

	echo '
		<div class="pagesection">';

	template_button_strip($context['navigation'], 'left');

	echo '
		</div>
		<we:cat>
			', $txt['shd_helpdesk'], !empty($context['shd_dept_name']) && $context['shd_multi_dept'] ? ' - ' . $context['shd_dept_name'] : '', '
		</we:cat>
		<div class="roundframe">';

	template_jumpto_ticket();

	echo '
			<div id="welcome">
				<strong>', sprintf($txt['shd_welcome'], we::$user['name']), '</strong><br>
				', $txt['shd_' . $context['shd_home_view'] . '_greeting'];

	template_block_filter();

	echo '
			</div>
		</div>
		<br>';

	$done = 0;
	foreach ($context['tracker_blocks'] as $block)
	{
		template_tracker_block($block);
		$done++;
		if ($done % 2 == 0)
			echo '
		<div class="clear"></div><br>';
	}
}

function template_tracker_block($block)
{
	global $context, $txt, $settings;

	echo '
			<div style="width: 49%; float: left; margin: 0 .5%">
				<we:cat>
					', $block['title'], '
				</we:cat>';

	if (!empty($block['tickets']))
	{
		echo '
					<table class="shd_tracker_block">
						<tbody>';
		foreach ($block['tickets'] as $id_ticket => $ticket)
			echo '
							<tr', !empty($ticket['class']) ? ' class="' . $ticket['class'] . '_tickets"' : '', '>
								<td class="tid">
									<a href="<URL>?action=helpdesk;sa=ticket;ticket=', $id_ticket, '">', str_pad($id_ticket, $settings['shd_zerofill'], '0', STR_PAD_LEFT), '</a><br>
									', $ticket['private'] ? ' <img src="' . $context['plugins_url']['Arantor:WedgeDesk'] . '/images/private.png" alt="' . $txt['shd_ticket_private'] . '" title="' . $txt['shd_ticket_private'] . '">' : '', '
								</td>
								<td class="tinfo">
									', $context['shd_multi_dept'] ? '[<a href="<URL>?action=helpdesk;sa=main;dept=' . $ticket['id_dept'] . '">' . $ticket['dept_name'] . '</a>] ' : '', '<a href="<URL>?action=helpdesk;sa=ticket;ticket=', $id_ticket, '">', $ticket['subject'], '</a><br>
									', timeformat($ticket['last_updated']), '
								</td>
							</tr>';

		echo '
						</tbody>
					</table>';
	}

	echo '
			</div>';
}

function template_tracker_legend()
{
	global $txt;

	echo '
		<we:title>', $txt['shd_tickets_legend'], '</we:title>
		<dl id="tracker_legend">
			<dt class="new_tickets">', $txt['shd_status_0_heading'], '</dt>
			<dt class="feedback_tickets">', $txt['shd_tickets_feedback'], '</dt>
			<dt class="assigned_tickets">', $txt['shd_tickets_assigned'], '</dt>
			<dt class="resolved_tickets">', $txt['shd_tickets_closed'], '</dt>
		</dl>';
}


// Provide a placeholder in the event template_button_strip isn't defined (like in the mobile templates)
if (!function_exists('template_button_strip'))
{
	function template_button_strip($navigation, $direction)
	{
	}
}
?>