<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

// Add the revision number to the footer. It's pretty quick and dirty.
function wedgedotorg_theme()
{
	global $context, $txt;

	$context['main_css_files'][$context['plugins_dir']['Wedge:Wedge.org'] . '/wedge.org.css'] = false;

	$rev = @file_get_contents(ROOT_DIR . '/core/rev.txt');
	if (!empty($rev))
		$txt['copyright'] .= ', ' . (we::$user['language'] == 'french' ? 'révision' : 'revision') . ' <a href="http://wedge.org/pub/feats/6108/new-revs/">' . $rev . '</a>';

	$context['custom_credits'] = (empty($context['custom_credits']) ? '' : $context['custom_credits']) . ' |
				' . (we::$user['language'] == 'french' ? 'Aimez-nous sur' : 'Like us on') . ' <span class="likeus"><a href="http://www.facebook.com/wedgebook">Facebook</a></span>.';
}
