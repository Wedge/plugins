<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*
	This is the 'Theme Selector' plugin for Wedge.
	Note that the structure of this file is not typical: the source and template are in the same file.
	The contents are still structurally separate but the two are in the same file for efficiency.
	-- Arantor
*/

function themeSelector()
{
	global $txt, $language, $context, $board_info;

	if (!empty(we::$user['possibly_robot']) || (isset($board_info) && !empty($board_info['theme']) && $board_info['override_theme']))
		return;

	// Will need this whatever.
	loadSource('Themes');

	$temp = cache_get_data('arantor_theme_listing', 180);
	if ($temp === null)
	{
		// Get all the themes...
		$request = wesql::query('
			SELECT id_theme AS id, value AS name
			FROM {db_prefix}themes
			WHERE variable = {string:name}',
			array(
				'name' => 'name',
			)
		);
		$temp = array();
		while ($row = wesql::fetch_assoc($request))
			$temp[$row['id']] = $row;
		wesql::free_result($request);

		// Get theme dir for all themes
		$request = wesql::query('
			SELECT id_theme AS id, value AS dir
			FROM {db_prefix}themes
			WHERE variable = {string:dir}',
			array(
				'dir' => 'theme_dir',
			)
		);
		while ($row = wesql::fetch_assoc($request))
			$temp[$row['id']]['skins'] = wedge_get_skin_list($row['dir'] . '/skins');
		wesql::free_result($request);

		cache_put_data('arantor_theme_listing', $temp, 180);
	}

	// So, now we have a list of all the skins.
	$context['skin_selector'] = $temp;
	wetem::add('sidebar', 'sidebar_theme_selector');
}

function template_sidebar_theme_selector()
{
	global $context, $theme, $txt;

	loadPluginLanguage('Arantor:ThemeSelector', 'SkinSelector');

	echo '
	<section>
		<we:title>
			', $txt['skin_selector'], '
		</we:title>
		<p>
			<select name="boardtheme" id="boardtheme" data-default="', substr(strrchr($context['skin'], '/'), 1), '">';

	foreach ($context['skin_selector'] as $th)
	{
		echo '<option value="', $th['id'], '"';
		if (empty($context['do_not_select_skin']) && $theme['theme_id'] == $th['id'] && (empty($context['skin']) || $context['skin'] == 'skins'))
		{
			echo ' selected';
			$context['do_not_select_skin'] = true;
		}
		echo '>', $th['name'], '</option>';
		if (!empty($th['skins']))
			wedge_show_skins($th, $th['skins'], $theme['theme_id'], $context['skin']);
	}

	echo '
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
		location.href = sUrl + (sUrl.search(/[?;]$/) != -1 ? "" : sUrl.indexOf("?") < 0 ? "?" : ";") + "theme=" + this.value + sAnchor;
	});');
	else
		add_js('
	$("#boardtheme").change(function () {
		location.href = weUrl("action=skin;th=" + this.value + ";" + we_sessvar + "=" + we_sessid);
	});');
}

?>