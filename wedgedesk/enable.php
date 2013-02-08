<?php
/**
 * WedgeDesk
 *
 * This script makes last minute, specific alterations to the database that aren't covered by Wedge's Add-on Manager's own functionality.
 *
 * @package wedgedesk
 * @copyright 2011-2 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

if (!defined('WEDGE_PLUGIN'))
	die('This is an installation file for WedgeDesk, and should not be accessed directly.');

// We have a lot to do. Make sure as best we can that we have the time to do so.
@set_time_limit(600);

global $modSettings, $txt;

// WedgeDesk specific, after schema changes
// If this is an upgraded SD installation, we won't have any departments. Make sure we create one, if possible using the right language strings.
//loadLanguage('SimpleDesk', 'english', false);
//loadLanguage('SimpleDesk', '', false);
$query = wesql::query('SELECT COUNT(*) FROM {db_prefix}helpdesk_depts');
list($count) = wesql::fetch_row($query);
wesql::free_result($query);
if (empty($count))
{
	wesql::insert('replace',
		'{db_prefix}helpdesk_depts',
		array(
			'dept_name' => 'string', 'board_cat' => 'int', 'description' => 'string', 'before_after' => 'int', 'dept_order' => 'int', 'dept_theme' => 'int',
		),
		array(
			!empty($txt['shd_helpdesk']) ? $txt['shd_helpdesk'] : 'Helpdesk', 0, '', 0, 1, 0,
		),
		array('id_dept')
	);
}

// Move any outstanding tickets into the last department we had, which will be the last one we created.
$query = wesql::query('SELECT MAX(id_dept) FROM {db_prefix}helpdesk_depts');
list($new_dept) = wesql::fetch_row($query);
wesql::free_result($query);
if (!empty($new_dept))
{
	wesql::query('
		UPDATE {db_prefix}helpdesk_tickets
		SET id_dept = {int:new_dept}
		WHERE id_dept = {int:old_dept}',
		array(
			'new_dept' => $new_dept,
			'old_dept' => 0,
		)
	);
}

// Do we need to flag that a new search index is needed? If there are any pre-existing tickets, we will...
$query = wesql::query('SELECT COUNT(*) FROM {db_prefix}helpdesk_tickets');
list($count) = wesql::fetch_row($query);
if (!empty($count))
	updateSettings(array('shd_new_search_index' => 1));

// If we're updating an existing install, we need to make sure there is a normalised value in the last_updated column.
wesql::query('
	UPDATE {db_prefix}helpdesk_tickets AS hdt, {db_prefix}helpdesk_ticket_replies AS hdtr
	SET hdt.last_updated = hdtr.poster_time
	WHERE hdt.id_last_msg = hdtr.id_msg AND hdt.last_updated = 0');

?>