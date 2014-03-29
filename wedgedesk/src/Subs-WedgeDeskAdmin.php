<?php
/**
 * WedgeDesk
 *
 * This file deals with some of the items required by the helpdesk, but are primarily supporting
 * functions; they're not the principle functions that drive the admin area.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/**
 *	Perform all the operations required for WedgeDesk to safely start operations inside the admin panel.
 *
 *	@param array &$admin_areas The full admin area array from Wedge's Admin.php.
 *	@since 2.0
*/
function shd_admin_bootstrap()
{
	global $settings, $txt, $context, $admin_areas;

	// Load the main admin language files and any needed for add-ons in the admin panel.
	loadPluginSource('Arantor:WedgeDesk', 'src/WedgeDesk-Admin');
	loadPluginLanguage('Arantor:WedgeDesk', 'lang/WedgeDeskAdmin');
	call_lang_hook('shd_lang_admin');

	// Now add the main WedgeDesk menu
	if (!empty($settings['helpdesk_active']))
	{
		// The helpdesk action log
		if (empty($settings['shd_disable_action_log']))
			$admin_areas['maintenance']['areas']['logs']['subsections']['helpdesklog'] = array($txt['shd_admin_helpdesklog'], 'admin_forum', 'url' => '<URL>?action=admin;area=helpdesk_info;sa=actionlog');

		// The main menu
		$admin_areas['helpdesk_info'] = array(
			'title' => $txt['shd_helpdesk'],
			'enabled' => allowedTo('admin_forum') || shd_allowed_to('admin_helpdesk', 0),
			'areas' => array(
				'helpdesk_info' => array(
					'label' => $txt['shd_admin_info'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/wedgedesk.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
						'main' => array($txt['shd_admin_info']),
						'actionlog' => array($txt['shd_admin_actionlog'], 'enabled' => empty($settings['shd_disable_action_log'])),
					),
				),
				'helpdesk_options' => array(
					'label' => $txt['shd_admin_options'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/options.png',
					'bigicon' => 'server_settings.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
						'display' => array($txt['shd_admin_options_display']),
						'posting' => array($txt['shd_admin_options_posting']),
						'admin' => array($txt['shd_admin_options_admin']),
						'standalone' => array($txt['shd_admin_options_standalone']),
						'actionlog' => array($txt['shd_admin_options_actionlog']),
						'notifications' => array($txt['shd_admin_options_notifications']),
					),
				),
				'helpdesk_cannedreplies' => array(
					'label' => $txt['shd_admin_cannedreplies'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/cannedreplies.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
					),
				),
				'helpdesk_customfield' => array(
					'label' => $txt['shd_admin_custom_fields'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/custom_fields.png',
					'bigicon' => 'custom_fields.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
					),
				),
				'helpdesk_depts' => array(
					'label' => $txt['shd_admin_departments'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/departments.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
					),
				),
				'helpdesk_permissions' => array(
					'label' => $txt['shd_admin_permissions'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/permissions.png',
					'bigicon' => 'permissions.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
					),
				),
				'helpdesk_maint' => array(
					'label' => $txt['shd_admin_maint'],
					'file' => array('Arantor:WedgeDesk', 'src/WedgeDesk-Admin'),
					'icon' => $context['plugins_url']['Arantor:WedgeDesk'] . '/images/admin/maintenance.png',
					'bigicon' => 'maintenance.png',
					'function' => 'shd_admin_main',
					'subsections' => array(
						'main' => array($txt['shd_admin_maint']),
						'search' => array($txt['shd_maint_search_settings']),
					),
				),
			),
		);

		// Now engage any hooks.
		call_hook('shd_hook_adminmenu', array(&$admin_areas));
	}
}

/**
 *	Adds items to the search range of the admin search area.
 */
function shd_admin_search(&$settings_search)
{
	$settings_search['plugins'][] = array('shd_modify_display_options', 'area=helpdesk_options;sa=display');
	$settings_search['plugins'][] = array('shd_modify_posting_options', 'area=helpdesk_options;sa=posting');
	$settings_search['plugins'][] = array('shd_modify_admin_options', 'area=helpdesk_options;sa=admin');
	$settings_search['plugins'][] = array('shd_modify_standalone_options', 'area=helpdesk_options;sa=standalone');
	$settings_search['plugins'][] = array('shd_modify_actionlog_options', 'area=helpdesk_options;sa=actionlog');
	$settings_search['plugins'][] = array('shd_modify_notifications_options', 'area=helpdesk_options;sa=notifications');
}

/**
 *	Perform any processing on Wedge permissions subject to WedgeDesk options (namely removing permissions that make no sense in helpdesk-only mode)
 *
 *	All of the parameters are the normal variables provided by ManagePermissions.php to its integration hook.
 *	@since 2.0
 *	@param array &$permissionGroups The array of groups of permissions
 *	@param array &$permissionList The master list of permissions themselves
 *	@param array &$leftPermissionGroups The list of permission groups that are displayed on the left hand side of the screen in Classic Mode
 *	@param array &$hiddenPermissions A list of permissions to be hidden in the event of features being disabled
 */
function shd_admin_old_perms(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions)
{
	global $settings;

	if (!$settings['helpdesk_active'] || empty($settings['shd_helpdesk_only']))
		return;

	$perms_disable = array(
		'view_stats',
		'who_view',
		'search_posts',
		'karma_edit',
		'calendar_view',
		'calendar_post',
		'calendar_edit',
		'manage_boards',
		'manage_attachments',
		'manage_smileys',
		'edit_news',
		'access_mod_center',
		'moderate_forum',
		'send_mail',
		'issue_warning',
		'moderate_board',
		'approve_posts',
		'post_new',
		'post_unapproved_topics',
		'post_unapproved_replies',
		'post_reply',
		'merge_any',
		'split_any',
		'send_topic',
		'make_sticky',
		'move',
		'lock',
		'remove',
		'modify_replies',
		'delete_replies',
		'announce_topic',
		'delete',
		'modify',
		'report_any',
		'poll_view',
		'poll_vote',
		'poll_post',
		'poll_add',
		'poll_edit',
		'poll_lock',
		'poll_remove',
		'mark_any_notify',
		'mark_notify',
		'view_attachments',
		'post_unapproved_attachments',
		'post_attachment',
	);

	// that's the generic stuff, now for specific options
	if (!empty($settings['shd_disable_pm']))
	{
		$perms_disable[] = 'pm_read';
		$perms_disable[] = 'pm_send';
	}

	$hiddenPermissions = array_merge($hiddenPermissions, $perms_disable);
}

?>