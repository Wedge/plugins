<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function flashPMs(&$items)
{
	global $context;

	if ($context['allow_pm'] && !empty($context['user']['unread_messages']))
		add_js('
	function flashPM()
	{
		$(".m_pm").animate({
			opacity: 0.33
		}, 650, function() {
			$(".m_pm").animate({
				opacity: 1
			}, 650, function() {
				flashPM();
			})
		});
	};
	flashPM();');
}