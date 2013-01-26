<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function template_historylist()
{
	global $context, $txt;

	// Since this is a popup of its own we need to start the html, unless we're coming from jQuery.
	if ($context['is_ajax'])
	{
		echo '
<header>
	', $txt['edit_history'], '
</header>
<section class="nodrag">
	', template_historylist_table(), '
</section>
<footer>
	<input type="button" class="delete" onclick="$(\'#popup\').fadeOut(function () { $(this).remove(); });" value="', $txt['close_window'], '" />
</footer>';
	}
	else
	{
		echo '<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', '>
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<title>', $context['page_title'], '</title>',
	theme_base_css(), '
</head>
<body id="help_page">
	<div class="description wrc">', template_historylist_table(), '
		<br><br>
		<a href="#" onclick="history.back(); return false;">', $txt['back'], '</a>
	</div>
</body>
</html>';
	}
}

function template_historylist_table()
{
	global $context, $txt;

	echo '
		<form action="<URL>?action=edithistory;sa=compare;topic=', $_GET['topic'], '.0;msg=', $_GET['msg'], '" method="post">
			<table class="table_grid cs0" style="width: 100%">
				<thead>
					<tr class="catbg">
						<th scope="col" class="first_th left">', $txt['edited_by'], '</th>
						<th scope="col" class="last_th left" colspan="4">', $txt['edited_on'], '</th>
					</tr>
				</thead>
				<tbody>';
	$use_bg2 = true;
	$last_row = false;
	foreach ($context['versions'] as $id_edit => $data)
	{
		echo '
					<tr class="windowbg', $use_bg2 ? '2' : '', '">
						<td>', !empty($data['id_member']) ? '<a href="<URL>?action=profile;u=' . $data['id_member'] . '">' . $data['name'] . '</a>' : $data['name'], '</td>
						<td>', $data['time_format'], $id_edit == 'current' ? ' ' . $txt['current'] : '', '</td>
						<td>', $id_edit != 'current' ? '<input type="radio" name="prev" value="' . $id_edit . '"' . ($last_row == 'current' ? ' checked' : '') . '>' : '', '</td>
						<td><input type="radio" name="cur" value="', $id_edit, '"', $id_edit == 'current' ? ' checked' : '', '></td>
						<td><a href="<URL>?action=edithistory;sa=view;topic=', $_GET['topic'], '.0;msg=', $_GET['msg'], ';edit=', $id_edit, '">', $txt['view_post'], '</a></td>
					</tr>';
		$use_bg2 = !$use_bg2;
		$last_row = $id_edit;
	}

	echo '
				</tbody>
			</table>
		</form>';
}

function template_view_post()
{
	global $context, $txt;

	echo '
		<we:cat>', $context['page_title'], '</we:cat>
		<p class="description">', sprintf($txt['this_edit_by_on'], $context['post_details']['name'], on_timeformat($context['post_details']['time'])), '</p>
		<div class="windowbg2 wrc">', $context['post_details']['body'], '</div>
		<br class="clear">';
}

?>