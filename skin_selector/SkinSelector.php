<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

/* This is the 'Skin Selector' plugin for Wedge. */

function skinSelector()
{
	global $context, $board_info;

	if (!empty(we::$user['possibly_robot']) || (isset($board_info) && !empty($board_info['skin']) && $board_info['override_skin']))
		return;

	// Will need this.
	loadSource('Themes');

	// Get a list of all the skins.
	$context['skin_selector'] = wedge_get_skin_list();

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
			<select name="skinse" id="skinse">
				<option value=""', $context['skin_actual'] == '' ? ' selected' : '', '>', $txt['skin_overall_default'], '</option>
				', $skin_selector, '
			</select>
		</p>
	</section>';

	add_js('
	$("#skinse").change(function () {
		location = weUrl("action=skin;skin=" + encodeURIComponent(this.value) + ";" + we_sessvar + "=" + we_sessid);
	});');
}
