<?php
namespace AJAXQR;

class Integration
{
	public static function load_theme()
	{
		global $topic;

		if (!empty($topic))
			add_plugin_js_file('live627:ajax_qr', 'ajaxqr.js');
	}

	public static function display_message_list(&$messages, &$times, &$all_posters)
	{
		global $context;

		// If we are attempting to view a single post.. Lets make sure its safe to do so..
		if (isset($_REQUEST['ajaxqr']) && INFINITE)
		{
			$messages = array($context['topic_last_message']);
			$times = array($context['topic_last_message'] => $times[$context['topic_last_message']]);
			$all_posters = array($context['topic_last_message'] => $all_posters[$context['topic_last_message']]);
		}
	}

	public static function create_post_after(&$msgOptions, &$topicOptions, &$posterOptions, &$new_topic)
	{
		global $context;

		if (!empty($_REQUEST['from_qr']) && AJAX)
			$context['message'] = $msgOptions['id'];
	}

	public static function redirect(&$setLocation, &$refresh)
	{
		global $context;

		if (!empty($_REQUEST['from_qr']) && AJAX)
		{
			if (!empty($context['message']))
				return_xml('<we>
	<msg>', $context['message'], '</msg></we>');
			elseif (!empty($context['post_error']['messages']))
				return_xml('<we>
	<error><![CDATA[', implode('<br>', $context['post_error']['messages']), ']]></error></we>');
		}
	}
}

?>