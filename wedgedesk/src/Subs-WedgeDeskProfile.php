<?php
/**
 * WedgeDesk
 *
 * This file handles initialisation of the WedgeDesk profile area, including adding it to the profile area.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

if (!defined('WEDGE'))
	die('If only you could draw like a drunken monkey...');

function shd_profile_areas(&$profile_areas)
{
	global $settings, $txt, $context;

	if (empty($settings['helpdesk_active']))
		return;

	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskProfile');

	// Temporarily add permissions into the main permissions if we have them.
	if (!we::$is_admin)
	{
		$perms = array('shd_view_profile', 'shd_view_preferences', 'shd_view_preferences');
		foreach ($perms as $perm)
		{
			if (shd_allowed_to($perm . '_own', 0))
				we::$user['permissions'][] = $perm . '_own';
			if (shd_allowed_to($perm . '_any', 0))
				we::$user['permissions'][] = $perm . '_any';
		}
		if (shd_allowed_to('admin_helpdesk', 0))
			we::$user['permissions'][] = 'admin_helpdesk';
	}

	// Put it here so we can reuse it for the left menu a bit
	$context['helpdesk_menu'] = array(
		'title' => $txt['shd_profile_area'],
		'areas' => array(
			'helpdesk' => array(
				'label' => $txt['shd_profile_main'],
				'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Profile'),
				'function' => 'shd_profile_main',
				'permission' => array(
					'own' => 'shd_view_profile_own',
					'any' => 'shd_view_profile_any',
				),
			),
			'hd_prefs' => array(
				'label' => $txt['shd_profile_preferences'],
				'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Profile'),
				'function' => 'shd_profile_main',
				'permission' => array(
					'own' => 'shd_view_preferences_own',
					'any' => 'shd_view_preferences_any',
				),
			),
			'hd_showtickets' => array(
				'label' => $txt['shd_profile_show_tickets'],
				'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Profile'),
				'function' => 'shd_profile_main',
				'permission' => array(
					'own' => 'shd_view_ticket_own',
					'any' => 'shd_view_ticket_any',
				),
			),
			'hd_permissions' => array(
				'label' => $txt['shd_profile_permissions'],
				'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Profile'),
				'function' => 'shd_profile_main',
				'permission' => array(
					'own' => 'admin_helpdesk',
					'any' => 'admin_helpdesk',
				),
			),
			'hd_actionlog' => array(
				'label' => $txt['shd_profile_actionlog'],
				'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Profile'),
				'function' => 'shd_profile_main',
				'permission' => array(
					'own' => 'shd_view_profile_log_own',
					'any' => 'shd_view_profile_log_any',
				),
				'enabled' => empty($settings['shd_disable_action_log']),
			),
		),
	);

	// Kill the existing profile menu but save it in a temporary place first.
	$old_profile_areas = $profile_areas;
	$profile_areas = array();

	// Now, where we add this depends very much on what mode we're in. In HD only mode, we want our menu first before anything else.
	if (!empty($settings['shd_helpdesk_only']))
	{
		loadSource('Profile-Modify');

		// Move some stuff around.
		$context['helpdesk_menu']['areas']['permissions'] = array(
			'label' => $txt['shd_show_forum_permissions'],
			'file' => 'Profile-View',
			'function' => 'showPermissions',
			'permission' => array(
				'own' => 'manage_permissions',
				'any' => 'manage_permissions',
			),
		);
		$context['helpdesk_menu']['areas']['tracking'] = array(
			'label' => $txt['trackUser'],
			'file' => 'Profile-View',
			'function' => 'tracking',
			'subsections' => array(
				'activity' => array($txt['trackActivity'], 'moderate_forum'),
				'ip' => array($txt['trackIP'], 'moderate_forum'),
				'edits' => array($txt['trackEdits'], 'moderate_forum'),
			),
			'permission' => array(
				'own' => 'moderate_forum',
				'any' => 'moderate_forum',
			),
		);

		$profile_areas['helpdesk'] = $context['helpdesk_menu'];
		$profile_areas += $old_profile_areas;

		unset($profile_areas['info']['areas']['permissions'], $profile_areas['info']['areas']['tracking']);

		$remove = array(
			'info' => array(
				'summary',
				'statistics',
				'showposts',
				'viewwarning',
			),
			'edit_profile' => array(
				'forumprofile',
				'ignoreboards',
				'lists',
				'notification',
			),
			'profile_action' => array(
				'issuewarning',
			),
		);
		if (!empty($settings['shd_disable_pm']))
		{
			$remove['profile_action'][] = 'sendpm';
			$remove['edit_profile'][] = 'pmprefs';
		}

		foreach ($remove as $area => $items)
			foreach ($items as $item)
				if (!empty($profile_areas[$area]['areas'][$item]))
					$profile_areas[$area]['areas'][$item]['enabled'] = false;

		$profile_areas['edit_profile']['areas']['theme']['file'] = array('Arantor:WedgeDesk', 'src/WedgeDesk-Profile');
		$profile_areas['edit_profile']['areas']['theme']['function'] = 'shd_profile_theme_wrapper';
	}
	else
	// In non HD only, put it before the editing stuff menu
	{
		foreach ($old_profile_areas as $area => $details)
		{
			if ($area == 'edit_profile')
				$profile_areas['helpdesk'] = $context['helpdesk_menu'];
			$profile_areas[$area] = $details;
		}
	}
}

?>