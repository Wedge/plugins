<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

// Add the revision number to the footer. It's pretty quick and dirty.
function wedgedotorg_theme()
{
	global $context, $txt;

	$rev = @file_get_contents(CORE_DIR . '/rev.txt');
	if (!empty($rev))
		$txt['copyright'] .= ', ' . (we::$user['language'] == 'french' ? 'révision' : 'revision') . ' <a href="http://wedge.org/pub/feats/6108/new-revs/">' . $rev . '</a>';

	$context['custom_credits'] = (empty($context['custom_credits']) ? '' : $context['custom_credits']) . ' |
				' . (we::$user['language'] == 'french' ? 'Aimez-nous sur' : 'Like us on') . ' <img src="' . $context['plugins_url']['Wedge:Wedge.org'] . '/fb.gif" style="width: 12px; height: 12px; vertical-align: -1px; border: 0"> <a href="http://www.facebook.com/wedgebook">Facebook</a>.';
}
