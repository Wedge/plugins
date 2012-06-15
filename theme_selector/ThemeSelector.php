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
	global $txt, $user_info, $language, $modSettings, $context, $scripturl;

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

	// So, now we have a list of all the themes.
	$context['themes'] = $temp;
	wetem::add('sidebar', 'sidebar_theme_selector');
}

function template_sidebar_theme_selector()
{
	global $context, $theme, $txt;

	loadPluginLanguage('Arantor:ThemeSelector', 'SkinSelector');

	echo '
			', $txt['select_skin'], ' <select name="boardtheme" id="boardtheme" onchange="changeTheme(this);" class="bbc_tt">';

	foreach ($context['themes'] as $th)
	{
		echo '<option value="', $th['id'], '"', $theme['theme_id'] == $th['id'] && (empty($context['skin']) || $context['skin'] == 'skins') ? ' selected' : '', '>', $th['name'], '</option>';
		if (!empty($th['skins']))
			wedge_show_skins($th, $th['skins'], 1, $theme['theme_id'], $context['skin']);
	}

	echo '
			</select>';

	add_js('
	function changeTheme(obj)
	{
		var
			len,
			sUrl = window.location.href.replace(/theme=([0-9]+_[A-Z0-9+/=]+);?/i, ""),
			sAnchor = "",
			search = sUrl.search("#");

		if (search != -1)
		{
			sAnchor = sUrl.substr(search);
			sUrl = sUrl.substr(0, search);
		}

		len = sUrl.length - 1;
		while ((sUrl.charAt(len) == "?" || sUrl.charAt(len) == ";") && len > 0)
			len--;
		sUrl = sUrl.substr(0, ++len);

		len = sUrl.length;

		var themelink = "theme=" + obj.value + sAnchor, indexsearch = sUrl.search("/index.php");

		if (indexsearch < len && indexsearch != -1)
			window.location.href = sUrl + ((indexsearch == (len - 10)) ? "?" : ";") + themelink;
		else
			window.location.href = sUrl + ((sUrl.charAt(len-1) != "/") ? "/" : "") + "index.php?" + themelink;

		return false;
	}');
}

?>