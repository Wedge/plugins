<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*
	This is the 'Skin Selector' plugin for Wedge.
	Note that the structure of this file is not typical: the source and template are in the same file.
	The contents are still structurally separate but the two are in the same file for efficiency.
	-- Arantor
*/

function skinSelector()
{
	global $context, $board_info;

	if (!empty(we::$user['possibly_robot']) || (isset($board_info) && !empty($board_info['theme']) && $board_info['override_theme']))
		return;

	// Will need this.
	loadSource('Themes');

	// So, now we have a list of all the skins.
	if (($context['skin_selector'] = cache_get_data('wedgeward_skin_listing', 180)) === null)
		cache_put_data('wedgeward_skin_listing', $context['skin_selector'] = wedge_get_skin_list(SKINS_DIR), 180);

	wetem::add('sidebar', 'sidebar_skin_selector');
}

function template_sidebar_skin_selector()
{
	global $context, $txt;

	loadPluginLanguage('Wedgeward:SkinSelector', 'SkinSelector');

	$current_skin = isset($context['skin_names'][$context['skin']]) ? $context['skin_names'][$context['skin']] : substr(strrchr($context['skin'], '/'), 1);

	// !! westr::safe($current_skin), maybe..? Probably not useful.
	echo '
	<section>
		<we:title>
			', $txt['skin_selector'], '
		</we:title>
		<p>
			<select name="boardtheme" id="boardtheme" data-default="', $current_skin, '">',
				wedge_show_skins($context['skin_selector'], $context['skin'], '', true), '
			</select>
		</p>
	</section>';

	if (we::$is_guest)
		add_js('
	$("#boardtheme").change(function () {
		var len, sAnchor = "", sUrl = location.href.replace(/theme=([\w+/=]+);?/i, ""), search = sUrl.indexOf("#");
		if (search != -1)
		{
			sAnchor = sUrl.slice(search);
			sUrl = sUrl.slice(0, search);
		}
		location = sUrl + (sUrl.search(/[?;]$/) != -1 ? "" : sUrl.indexOf("?") < 0 ? "?" : ";") + "theme=" + this.value + sAnchor;
	});');
	else
		add_js('
	$("#boardtheme").change(function () {
		location = weUrl("action=skin;th=" + this.value + ";" + we_sessvar + "=" + we_sessid);
	});');
}
