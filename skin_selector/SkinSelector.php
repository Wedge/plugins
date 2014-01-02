<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/* This is the 'Skin Selector' plugin for Wedge. */

function skinSelector()
{
	global $context, $board_info;

	if (!empty(we::$user['possibly_robot']) || (isset($board_info) && !empty($board_info['skin']) && $board_info['override_theme']))
		return;

	// Will need this.
	loadSource('Themes');

	// Get a list of all the skins.
	$context['skin_selector'] = wedge_get_skin_list(SKINS_DIR);

	wetem::add('sidebar', 'sidebar_skin_selector');
}

function template_sidebar_skin_selector()
{
	global $context, $txt;

	loadPluginLanguage('Wedge:SkinSelector', 'SkinSelector');

	$skin = $context['skin_actual'];
	$skin_selector = wedge_show_skins($context['skin_selector']['skins'], true);
	$current_skin = isset($context['skin_names'][$skin]) ? $context['skin_names'][$skin] : ($skin == '/' ? 'Weaving' : basename($skin));

	echo '
	<section>
		<we:title>
			', $txt['skin_selector'], '
		</we:title>
		<p>
			<select name="skinse" id="skinse"', $current_skin ? 'data-default="' . westr::safe($current_skin) . '"' : '', '>
				<option value=""', $context['skin_actual'] == '' ? ' selected' : '', '>', $txt['skin_overall_default'], '</option>
				', $skin_selector, '
			</select>
		</p>
	</section>';

	if (we::$is_guest)
		add_js('
	$("#skinse").change(function () {
		var len, sAnchor = "", sUrl = location.href.replace(/skin=([^;]+);?/i, ""), search = sUrl.indexOf("#");
		if (search != -1)
		{
			sAnchor = sUrl.slice(search);
			sUrl = sUrl.slice(0, search);
		}
		location = sUrl + (sUrl.search(/[?;]$/) != -1 ? "" : sUrl.indexOf("?") < 0 ? "?" : ";") + "skin=" + encodeURIComponent(this.value) + sAnchor;
	});');
	else
		add_js('
	$("#skinse").change(function () {
		location = weUrl("action=skin;skin=" + encodeURIComponent(this.value) + ";" + we_sessvar + "=" + we_sessid);
	});');
}
