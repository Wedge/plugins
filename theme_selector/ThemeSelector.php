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
	loadBlock('header_theme_selector', 'header', 'add');
}

function template_header_theme_selector()
{
	global $context, $settings, $txt;

	echo '
			Select a skin: <select name="boardtheme" id="boardtheme" onchange="changeTheme(this);" style="font-family: \'dejavu sans mono\',\'monaco\',\'lucida console\',\'courier new\',monospace">';

	foreach ($context['themes'] as $theme)
	{
		echo '<option value="', $theme['id'], '"', $settings['theme_id'] == $theme['id'] && (empty($context['skin']) || $context['skin'] == 'skins') ? ' selected' : '', '>', $theme['name'], '</option>';
		if (!empty($theme['skins']))
			wedge_show_skins($theme, $theme['skins'], 1, $settings['theme_id'], $context['skin']);
	}

	echo '
			</select>';

	add_js('
function changeTheme (obj)
{
	var sUrl = new String(window.location);
	sUrl = sUrl.replace(/theme=([0-9]+\_[A-Z0-9\+\/\=]+);?/i, "");
	var sAnchor = "";
	var search = sUrl.search("#");

	if(search != -1)
	{
		sAnchor = sUrl.substr(search);
		sUrl = sUrl.substr(0, search);
	}

	var len = sUrl.length;
	var lastchr = sUrl.charAt(len-1);
	while ((lastchr == "?" || lastchr == ";") && len > 1)
	{
		len--;
		lastchr = sUrl.charAt(len-1);
	}
	sUrl = sUrl.substr(0, len);

	len = sUrl.length;

	var themelink = "theme=" + obj.value + sAnchor;
	var indexsearch = sUrl.search("/index.php");

	if (indexsearch < len && indexsearch != -1)
		window.location = sUrl + ((indexsearch == (len - 10)) ? "?" : ";") + themelink;
	else
		window.location = sUrl + ((sUrl.charAt(len-1) != "/") ? "/" : "") + "index.php?" + themelink;

	return false;
}');
}

?>