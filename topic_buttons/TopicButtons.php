<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topic_buttons_display()
{
	global $context, $txt, $scripturl;

	if ($context['user']['is_logged'])
	{
		if (allowedTo('post_new'))
			$context['nav_buttons']['normal'] = array_merge(
				array(
					'new_topic' => array('text' => 'new_topic', 'url' => $scripturl . '?action=post;board=' . $context['current_board'] . '.0', 'class' => 'active'),
				), $context['nav_buttons']['normal']);
	}
	else
	{
		loadPluginLanguage('Arantor:TopicButtons', 'TopicButtons');
		$context['nav_buttons']['normal']['reply'] = array('text' => 'register_post_reply', 'url' => $scripturl . '?action=register', 'class' => 'active');
	}
}

function topic_buttons_msgindex()
{
	global $context, $txt, $scripturl;

	// If there are buttons, the user isn't a guest.
	if (empty($context['button_list']))
	{
		loadPluginLanguage('Arantor:TopicButtons', 'TopicButtons');
		$context['button_list'] = array(
			'new_topic' => array('text' => 'register_post_new_topic', 'url' => $scripturl . '?action=register', 'class' => 'active'),
		);
	}
}

?>