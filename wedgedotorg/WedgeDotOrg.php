<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

// Add the revision number to the footer. It's pretty quick and dirty.
function wedgedotorg_theme()
{
	global $theme, $txt;

	$rev = @file_get_contents($theme['default_theme_dir'] . '/rev.txt');
	if (!empty($rev))
		$txt['copyright'] .= ' (rev <a href="http://wedge.org/pub/feats/6108/new-revs/">' . $rev . '</a>)';
}
