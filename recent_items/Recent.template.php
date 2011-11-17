<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function template_recentitems_infocenter()
{
	global $context, $settings, $options, $txt, $modSettings;

	echo '
		<we:title2>
			<a href="<URL>?action=recent"><img src="', $settings['images_url'], '/post/xx.gif"></a>
			', $txt['recent_items_' . $modSettings['recentitems_posttopic']], '
		</we:title2>
		<div id="recent_posts_content">
			<dl id="ic_recentposts" class="middletext">';

		/* Each post in latest_posts has:
			board (with an id, name, and link.), topic (the topic's id.), poster (with id, name, and link.),
			subject, short_subject (shortened with...), time, link, and href. */
	foreach ($context['latest_posts'] as $post)
		echo '
				<dt><strong>', $post['link'], '</strong> ', $txt['by'], ' ', $post['poster']['link'], ' (', $post['board']['link'], ')</dt>
				<dd>', $post['time'], '</dd>';

	echo '
			</dl>
		</div>';
}

?>