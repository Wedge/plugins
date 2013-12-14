<?php

function postsperday_moderation_rules(&$known_variables, $admin)
{
	loadPluginLanguage('Arantor:PostsPerDay', 'PostsPerDay');
	loadPluginTemplate('Arantor:PostsPerDay', 'PostsPerDay');
	$known_variables['postsperday'] = array(
		'type' => 'range',
		'current' => 0,
		'func_val' => 'count_postsperday',
		'function' => create_function('$criteria', '
			global $txt;
			return $txt[\'modfilter_cond_\' . $criteria[\'name\']] . \': \' . $txt[\'modfilter_range_\' . $criteria[\'term\']] . \' \' . $criteria[\'value\'];
		'),
	);
}

function displayRow_postsperday($rule)
{
	return simpleRange_displayRow($rule, 'postsperday');
}

function count_postsperday($subject, $body)
{
	if (we::$is_guest)
		return 0;

	$request = wesql::query('
		SELECT COUNT(id_msg)
		FROM {db_prefix}messages
		WHERE id_member = {int:member}
			AND poster_time >= {int:time}',
		array(
			'member' => MID,
			'time' => time() - 86400,
		)
	);
	list ($count) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $count;
}

?>