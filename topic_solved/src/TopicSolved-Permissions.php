<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSolvedIllegalGuestPerms()
{
	global $context;

	$context['non_guest_permissions'][] = 'topicsolved_own';
	$context['non_guest_permissions'][] = 'topicsolved_any';
}
?>