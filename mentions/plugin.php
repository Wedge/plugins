<?php
/**
 * WeMentions' plugins main file
 *
 * @package Dragooon:WeMentions
 * @author Shitiz "Dragooon" Garg <Email mail@dragooon.net> <Url http://smf-media.com>
 * @author René-Gilles "Nao" Deberdt
 * @copyright 2013, Shitiz Garg & René-Gilles Deberdt
 * @license
 *		Original code by Dragooon
 *			Licensed under the New BSD License (3-clause version)
 *			http://www.opensource.org/licenses/BSD-3-Clause
 *		Modified code by Nao
 *			Licensed under the Wedge license (sorry about that...)
 *			http://wedge.org/license/
 * @version 1.0
 */

/**
 * Hook callback for post_form_pre
 *
 * @return void
 */
function wementions_post_form_pre()
{
	add_js('
	', str_replace(': ', ' = ', min_chars()), ';');

	add_plugin_js_file('Dragooon:WeMentions', 'plugin.js');
}

/**
 * Hook callback for load_permissions
 *
 * @param array &$permissionGroups
 * @param array &$permissionList
 * @return void
 */
function wementions_load_permissions(&$permissionGroups, &$permissionList)
{
	$permissionList['board']['mention_member'] = array(false, 'post', 'mention');
}

/**
 * Hook callback for create_post_before and modify_post_before
 * Parses a post, actually looks for mentions and issues notifications.
 *
 * Names are tagged by "@<username>" format in post, but they can contain
 * any type of character up to 60 characters length. So we extract, starting from @
 * up to 60 characters in length (or if we encounter another @) and make
 * several combination of strings after splitting it by anything that's not a word and join
 * by having the first word, first and second word, first, second and third word and so on and
 * search every name.
 *
 * One potential problem with this is something like "@Admin Space" can match
 * "Admin Space" as well as "Admin", so we sort by length in descending order.
 * One disadvantage of this is that we can only match by one column, hence I've chosen
 * real_name since it's the most obvious.
 *
 * Names having "@" in there names are expected to be escaped as "\@",
 * otherwise it'll break seven ways from Sunday.
 *
 * @param array &$msgOptions
 * @param array &$topicOptions
 * @param array &$posterOptions
 * @param bool $new_topic
 * @return void
 */
function wementions_post(&$msgOptions, &$topicOptions, &$posterOptions, $new_topic = false)
{
	if (!allowedTo('mention_member'))
		return;

	// Attempt to match all the @<username> type mentions in the post
	if (!preg_match_all('~@((?:[^@\\\\]|\\\@){1,60})~', strip_tags(str_replace('\@', '@', $msgOptions['body']), '<br>'), $matches))
		return;

	// Names can have spaces, other breaks, or they can't... We try to match every possible combination.
	$names = array();
	foreach ($matches[1] as $match)
	{
		$match = preg_split('~([^\w])~', $match, -1, PREG_SPLIT_DELIM_CAPTURE);
		for ($i = 1; $i <= count($match); $i++)
			$names[] = implode('', array_slice($match, 0, $i));
	}

	$names = array_flip(array_flip(array_map('trim', $names)));

	// Attempt to fetch all the valid usernames
	$request = wesql::query('
		SELECT id_member, real_name
		FROM {db_prefix}members
		WHERE real_name IN ({array_string:names})
		ORDER BY LENGTH(real_name) DESC
		LIMIT {int:count}',
		array(
			'names' => $names,
			'count' => count($names),
		)
	);
	$members = array();
	while ($row = wesql::fetch_assoc($request))
		$members[$row['id_member']] = array(
			'id' => $row['id_member'],
			'real_name' => str_replace('@', '\@', $row['real_name']),
			'original_name' => $row['real_name'],
		);
	wesql::free_result($request);
	if (empty($members))
		return;

	// Replace all the tags with BBCode ([member=<id>]<username>[/member])
	$msgOptions['mentions'] = array();
	foreach ($members as $member)
	{
		if (strpos($msgOptions['body'], '@' . $member['real_name']) === false)
			continue;

		$msgOptions['body'] = str_replace('@' . $member['real_name'], '[member=' . $member['id'] . ']' . $member['original_name'] . '[/member]', $msgOptions['body']);
		$msgOptions['mentions'][] = $member['id'];
	}

	// Issue the notifications now if we are not a new post.
	if (!empty($msgOptions['id']))
		wementions_create_post_after($msgOptions, $topicOptions, $posterOptions);
}

/**
 * Hook callback for create_post_after, in case we're to be creating a new post previously.
 *
 * @param array &$msgOptions
 * @param array &$topicOptions
 * @param array &$posterOptions
 * @param bool $new_topic
 * @return void
 */
function wementions_create_post_after(&$msgOptions, &$topicOptions, &$posterOptions, $new_topic = false)
{
	if (empty($msgOptions['mentions']))
		return;

	// Issue the notifications
	Notification::issue($msgOptions['mentions'], weNotif::getNotifiers('mentions'), $msgOptions['id'], array(
		'topic' => $topicOptions['id'],
		'subject' => $msgOptions['subject'],
		'member' => array(
			'id' => we::$id,
			'name' => we::$user['name'],
		),
	));
}

/**
 * Hook for notification_callback, registers the notifier.
 *
 * @param array &$notifiers
 * @return void
 */
function wementions_notification_callback(array &$notifiers)
{
	$notifiers['mentions'] = new Mentions_Notifier();
}

/**
 * Hook callback for display_message_list, marks unread mentions as read for these messages
 *
 * @param array &$messages
 * @param array &$times
 * @param array &$all_posters
 * @return void
 */
function wementions_display_message_list(&$messages, &$times, &$all_posters)
{
//	Notification::markReadForNotifier(we::$id, weNotif::getNotifiers('mentions'), $messages);
}

/**
 * Notifier interface
 */
class Mentions_Notifier extends Notifier
{
	/**
	 * Constructor, loads this plugin's language
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		loadPluginLanguage('Dragooon:WeMentions', 'plugin');
	}

	/**
	 * Returns the URL for this notification
	 *
	 * @access public
	 * @param Notification $notification
	 * @return string
	 */
	public function getURL(Notification $notification)
	{
		$data = $notification->getData();
		return 'topic=' . $data['topic'] . '.msg' . $notification->getObject() . '#msg' . $notification->getObject();
	}

	/**
	 * Returns this notifier's identifier
	 *
	 * @access public
	 * @return string
	 */
	public function getName()
	{
		return 'mentions';
	}

	/**
	 * Returns this notification's text to be displayed
	 *
	 * @access public
	 * @param Notification $notification
	 * @return string
	 */
	public function getText(Notification $notification)
	{
		global $txt;

		$data = $notification->getData();
		return sprintf($txt['wementions_notification'], '<a href="<URL>?action=profile;u=' . $data['member']['id'] . '">' . $data['member']['name'] . '</a>', '<a href="<URL>?topic=' . $data['topic'] . '.msg' . $notification->getObject() . '#msg' . $notification->getObject() . '">' . $data['subject'] . '</a>');
	}

	/**
	 * Returns this notification's avatar to be displayed
	 *
	 * @access public
	 * @param Notification $notification
	 * @return string
	 */
	public function getIcon(Notification $notification)
	{
		global $txt, $memberContext;

		$data = $notification->getData();
		if (empty($memberContext[$data['member']['id']]['avatar']))
			loadMemberAvatar($data['member']['id'], true);
		if (empty($memberContext[$data['member']['id']]['avatar']))
			return '';
		return $memberContext[$data['member']['id']]['avatar']['image'];
	}

	/**
	 * Callback for handling multiple notifications, we basically ignore this since the
	 * mentions are per-post and we need no multiple mentions
	 *
	 * @access public
	 * @param Notification $notification
	 * @param array &$data
	 * @param array &$email_data
	 * @return bool
	 */
	public function handleMultiple(Notification $notification, array &$data, array &$email_data)
	{
		return false;
	}

	/**
	 * Returns elements for profile area
	 *
	 * @access public
	 * @param int $id_member
	 * @return array
	 */
	public function getProfile($id_member)
	{
		global $txt;

		return array($txt['wementions_title'], $txt['wementions_desc'], array());
	}

	public function getPreview(Notification $notification)
	{
		$data = $notification->getData();
		return get_single_post($notification->getObject());
	}

	/**
	 * E-mail handler for instantanous notification
	 *
	 * @access public
	 * @param Notification $notification
	 * @param array $email_data
	 * @return array(subject, body)
	 */
	public function getEmail(Notification $notification, array $email_data)
	{
		global $txt;

		return array($txt['wementions_subject'], $this->getText($notification));
	}
}
