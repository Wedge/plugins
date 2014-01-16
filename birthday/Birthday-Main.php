<?php
/**
 * Wedge: Birthdays
 *
 * This forms the info center handling.
 *
 * @package wedge-birthdays
 * @copyright 2010-2011 Wedge Team, wedge.org, 2011 Simple Machines, simplemachines.org
 * @license http://wedge.org/license/
 */

function birthdayInfoCenter()
{
	global $context, $settings;

	if (empty($settings['birthday_info_center']) || empty($settings['birthday_info_center_days']))
		return;

	$low_date = strftime('%Y-%m-%d', forum_time(false) - 24 * 3600);
	$high_date = strftime('%Y-%m-%d', forum_time(false) + $settings['birthday_info_center_days'] * 24 * 3600);
	$birthdays = getBirthdayRange($low_date, $high_date);

	if (!empty($birthdays))
	{
		// Load dependencies
		loadPluginTemplate('Wedge:Birthdays', 'Birthday-Main');
		loadPluginLanguage('Wedge:Birthdays', 'Birthday-Main');
		$context['birthdays_to_display'] = array();

		// Make sure we set the "it's your birthday TODAY!" flag for folks, as well as transposing it into a more useful structure for templating.
		$today_date = strftime('%Y-%m-%d', forum_time());
		foreach ($birthdays as $day => $members)
		{
			$is_today = $day == $today_date;
			foreach ($members as $k => $v)
			{
				$v['is_today'] = $is_today;
				$context['birthdays_to_display'][] = $v;
			}
		}

		wetem::before('info_center_statistics', 'birthdays_info_center');
	}
}

/*
	array getBirthdayRange(string earliest_date, string latest_date)
		- finds all the birthdays in the specified range of days.
		- earliest_date and latest_date are inclusive, and should both be in
		  the YYYY-MM-DD format.
		- works with birthdays set for no year, or any other year, and
		  respects month and year boundaries.
		- returns an array of days, each of which an array of birthday
		  information for the context.
*/

// Get all birthdays within the given time range.
function getBirthdayRange($low_date, $high_date)
{
	global $settings;

	// Was this cached lately? (We don't need to be clever and figure out expiry, etc. if the cache key is actually including the dates.
	// That said, if we did something externally to force birthdays to be changed, make sure we deal with it.)
	$cache_id = 'birthdays_' . $low_date . '_' . $high_date;
	$temp = cache_get_data($cache_id, 3600);
	if ($temp !== null && (empty($settings['birthdays_updated']) || (time() - $settings['birthdays_updated'] > 3600)))
		return $temp;

	// We need to search for any birthday in this range, and whatever year that birthday is on.
	$year_low = (int) substr($low_date, 0, 4);
	$year_high = (int) substr($high_date, 0, 4);

	// Collect all of the birthdays for this month.  I know, it's a painful query.
	$result = wesql::query('
		SELECT id_member, real_name, YEAR(birthdate) AS birth_year, birthdate
		FROM {db_prefix}members
		WHERE YEAR(birthdate) != {string:year_one}
			AND MONTH(birthdate) != {int:no_month}
			AND DAYOFMONTH(birthdate) != {int:no_day}
			AND YEAR(birthdate) <= {int:max_year}
			AND (
				DATE_FORMAT(birthdate, {string:year_low}) BETWEEN {date:low_date} AND {date:high_date}' . ($year_low == $year_high ? '' : '
				OR DATE_FORMAT(birthdate, {string:year_high}) BETWEEN {date:low_date} AND {date:high_date}') . '
			)
			AND is_activated = {int:is_activated}',
		array(
			'is_activated' => 1,
			'no_month' => 0,
			'no_day' => 0,
			'year_one' => '0001',
			'year_low' => $year_low . '-%m-%d',
			'year_high' => $year_high . '-%m-%d',
			'low_date' => $low_date,
			'high_date' => $high_date,
			'max_year' => $year_high,
		)
	);
	$bday = array();
	while ($row = wesql::fetch_assoc($result))
	{
		if ($year_low != $year_high)
			$age_year = substr($row['birthdate'], 5) < substr($high_date, 5) ? $year_high : $year_low;
		else
			$age_year = $year_low;

		$bday[$age_year . substr($row['birthdate'], 4)][] = array(
			'id' => $row['id_member'],
			'name' => $row['real_name'],
			'age' => empty($settings['birthday_show_ages']) || ($row['birth_year'] > 4 && $row['birth_year'] <= $age_year) ? $age_year - $row['birth_year'] : null,
			'is_last' => false
		);
	}
	wesql::free_result($result);

	// Set is_last, so the themes know when to stop placing separators.
	foreach ($bday as $mday => $array)
		$bday[$mday][count($array) - 1]['is_last'] = true;

	cache_put_data($cache_id, $bday, 3600);
	return $bday;
}

?>