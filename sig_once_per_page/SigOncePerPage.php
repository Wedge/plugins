<?php

function sig_once_per_page(&$counter, &$output)
{
	static $users = array();

	if (isset($users[$output['member']['id']]))
		$output['member']['signature'] = '';
	$users[$output['member']['id']] = true;
}
