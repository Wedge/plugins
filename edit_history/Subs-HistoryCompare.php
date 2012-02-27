<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function compareStrings(&$lines)
{
	// Do some set up.
	$len_prev = count($lines['prev']);
	$len_cur = count($lines['cur']);

	// Make a big list of all the changes
	$changes = array('prev' => array(), 'cur' => array());
	$min_length = min($len_prev, $len_cur);

	// Start by figuring out where the first change is.
	for ($i = 0; $i < $min_length; $i++)
		if ($lines['prev'][$i] == $lines['cur'][$i])
			$changes['prev'][$i] = $changes['cur'][$i] = false;
		else
			break;
	$skip = $i;
	$skip_start = $min_length;
	// Now the last point of change
	for ($i = 0; --$skip_start > $skip; $i++)
		if ($lines['prev'][$skip_start] == $lines['cur'][$skip_start])
			$changes['prev'][$skip_start] = $changes['cur'][$skip_start] = false;
		else
			break;

}

?>