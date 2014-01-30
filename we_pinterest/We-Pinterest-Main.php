<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function we_pinterest_main()
{
	global $settings, $language, $txt;

	if (empty($settings['we_pinterest_on']))
		return;

	loadPluginTemplate('Pandos:We-Pinterest', 'We-Pinterest-Main');

	$lang = isset(we::$user['language']) ? we::$user['language'] : $language;
	switch ($lang)
	{
		case 'german':
			$txt['we_pinterest_on'] = 'Pin It button aktivieren';
			break;
		case 'french':
			$txt['we_pinterest_on'] = 'Activer le bouton Pin It';
			break;
		case 'english':
		default:
			$txt['we_pinterest_on'] = 'Enable Pin It button';
			break;
	}

		$dest = 'we_pinterest_topic';
		wetem::after('title_upper', array($dest => array()));
	}

		
?>