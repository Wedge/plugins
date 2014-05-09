<?php

if (!defined('WEDGE'))
	die('If only you could code like a drunken monkey...');

function LangCache()
{
	if (we::$is_admin)
	{
		checkSession('get');
		foreach (glob(CACHE_DIR . '/lang_*.php') as $filename)
			@unlink($filename);
	}

	redirectexit();
}

function LangCache_menu(&$items)
{
	global $context;

	$items['admin']['items'] = array_merge(
		$items['admin']['items'],
		array(
			'',
			'langcache' => array(
				'title' => 'Flush Lang Cache', // Don't need to load something every page just for this!
				'href' => '<URL>?action=langcache;' . $context['session_query'],
				'show' => we::$is_admin,
			)
		)
	);
}
