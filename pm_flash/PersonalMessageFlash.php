<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function flashPMs(&$items)
{
	global $context;

	// If you want to animate the text instead of the button, target $("#m_pm").next()
	if ($context['allow_pm'] && !empty($context['user']['unread_messages']))
		add_js('
	function flashPM()
	{
		$("#m_pm").animate({ opacity: 0.33 }, 500, function() {
			$("#m_pm").animate({ opacity: 1 }, 500, flashPM)
		});
	};
	flashPM();');
}

?>