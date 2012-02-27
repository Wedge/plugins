<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function saveEditedPost(&$msgOptions, &$topicOptions, &$posterOptions)
{
	if (empty($msgOptions['body']))
		return;

	// So, right now we have the new post contents. What we need is the current post contents, which we have to push into our table
	// then allow the new post to overwrite the master entry.
	$query = wesql::query('SELECT body, modified_member, modified_name, modified_time, id_member, poster_name, poster_time
		FROM {db_prefix}messages
		WHERE id_msg = {int:id_msg}',
		array(
			'id_msg' => $msgOptions['id'],
		)
	);
	$row = wesql::fetch_assoc($query);
	wesql::free_result($query);

	wesql::insert('insert',
		'{db_prefix}edit_history',
		array(
			'id_msg' => 'int',
			'modified_member' => 'int',
			'modified_name' => 'string-255',
			'modified_time' => 'int',
			'body' => 'string',
		),
		array(
			$msgOptions['id'],
			!empty($row['modified_member']) ? $row['modified_member'] : $row['id_member'],
			!empty($row['modified_name']) ? $row['modified_name'] : $row['poster_name'],
			!empty($row['modified_time']) ? $row['modified_time'] : $row['poster_time'],
			$row['body'],
		),
		array('id_edit')
	);
	$edit_id = wesql::insert_id();
	call_hook('history_save_other', array(&$edit_id, &$msgOptions, &$topicOptions, &$posterOptions));
}

?>