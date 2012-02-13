<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function readability_main()
{
	global $settings;

	if (empty($settings['rdb_position']) || $settings['rdb_position'] == 'abovetopic')
		wetem::first('default', 'readability');
	else
		wetem::add('sidebar', 'readability');
}

// I don't see why this should be a separate file when it's this short.
function template_readability()
{
	global $settings;

	$position = empty($settings['rdb_position']) || $settings['rdb_position'] == 'abovetopic' ? 'abovetopic' : 'sidebar';

	// Don't hate me for not making this a language string. Considering that it is a brand, there's really no translation going to occur!
	if ($position == 'sidebar')
		echo '
		<we:title>Readability</we:title>';

	echo '
	<div class="rdbWrapper" data-show-read="', !empty($settings['rdb_nowlater']) ? 1 : 0, '" data-show-send-to-kindle="', !empty($settings['rdb_kindle']) ? 1 : 0, '" data-show-print="', !empty($settings['rdb_print']) ? 1 : 0, '" data-show-email="', !empty($settings['rdb_email']) ? 1 : 0, '" data-orientation="', $position == 'abovetopic' ? 0 : 1, '" data-version="1" data-text-color="', !empty($settings['rdb_text_fg']) ? $settings['rdb_text_fg'] : '#5c5c5c', '" data-bg-color="', !empty($settings['rdb_text_bg']) ? $settings['rdb_text_bg'] : 'transparent', '"></div><script type="text/javascript">(function() {var s = document.getElementsByTagName("script")[0],rdb = document.createElement("script"); rdb.type = "text/javascript"; rdb.async = true; rdb.src = document.location.protocol + "//www.readability.com/embed.js"; s.parentNode.insertBefore(rdb, s); })();</script>';
}

?>