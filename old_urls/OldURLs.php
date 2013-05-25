<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function fixOldURLs()
{
	if (empty($_GET['action']))
		return;

	// For compatibility with SMF-era Aeva Media URLs...
	if ($_GET['action'] === 'mgallery')
		$_GET['action'] = 'media';

	// Compatibility with SMF feeds
	if ($_GET['action'] === '.xml')
		$_GET['action'] = 'feed';

	// Compatibility with SMF-era Aeva Media feeds
	elseif (isset($_GET['sa']) && $_GET['action'] === 'media' && $_GET['sa'] === 'feed')
	{
		$_GET['action'] = 'feed';
		$_GET['sa'] = 'media';
	}
}
