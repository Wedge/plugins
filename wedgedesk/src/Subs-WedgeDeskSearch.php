<?php
/**
 * WedgeDesk
 *
 * This file handles the backbone of searches, such as the tokeniser and manages getting the tables actually maintained.
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/**
 *	Identify and return the character set parameters for searching.
 *
 *	@return An array of two items, the overall character set currently in use and the list of characters to be permitted in searches in the form of a regular expression character class.
 *	@see shd_return_exclude_regex()
*/
function shd_search_charset()
{
	global $settings;

	if (empty($settings['shd_search_charset']))
		$settings['shd_search_charset'] = '0..9, A..Z, a..z, &, ~';

	$settings['shd_search_min_size'] = !empty($settings['shd_search_min_size']) ? $settings['shd_search_min_size'] : 3;
	$settings['shd_search_max_size'] = !empty($settings['shd_search_max_size']) ? $settings['shd_search_max_size'] : 8;
	$settings['shd_search_prefix_size'] = !empty($settings['shd_search_prefix_size']) ? $settings['shd_search_prefix_size'] : 0;

	$terms = explode(',', $settings['shd_search_charset']);
	$exclude_regex = '';
	foreach ($terms as $k => $v)
	{
		$v = trim($v);
		if (preg_match('~^(.)$~iu', $v, $match)) // Single character
			$exclude_regex .= preg_quote($match[1], '~');
		elseif (preg_match('~^(.)\.\.(.)$~iu', $v, $match)) // It's a ranged component.
			$exclude_regex .= preg_quote($match[1], '~') . '-' . preg_quote($match[2], '~');
	}
	if (empty($exclude_regex))
		$exclude_regex = '';
	else
		$exclude_regex = '~[^' . $exclude_regex . ']+~u';

	return $exclude_regex;
}

/**
 *	Takes an input string and returns a large array of word and word position identifiers.
 *
 *	@param string $string A regular post's contents, or that of the subject of a post.
 *	@return array An array containing the word identifiers.
*/
function shd_tokeniser($string)
{
	global $settings;
	static $exclude_regex = null;

	$result = array();

	if ($exclude_regex === null)
		$exclude_regex = shd_search_charset();

	// Step 1. Convert entities back to characters, regardless of what they are.
	$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

	// Step 2. Strip wiki code then bbcode.
	$string = preg_replace('~\[\[[^\]]+\]\]~Uu', '', $string);
	$string = preg_replace('~\[[^\]]+\]~Uu', '', $string);

	// Step 3. Strip certain minimal HTML.
	$string = preg_replace('~</?(img|br|hr|b|i|u|strike|s|ins|del|ol|ul|li|p|div|span|table|tr|th|td|code|pre)[^>]+>~iUu', ' ', $string);

	// Step 3. Strip characters we're not interested in.
	if ($exclude_regex === '') // If we have no character types, we can't match anything.
		return array();

	$string = preg_replace($exclude_regex, ' ', $string);
	$string = trim(preg_replace('~\s+~', ' ', $string));

	// Step 4. Break into an array and start tokenising.
	$array = explode(' ', $string);

	$i = 0;
	foreach ($array as $position => $word)
	{
		$len = westr::strlen($word);
		if ($len >= $settings['shd_search_min_size'] && $len <= $settings['shd_search_max_size'])
		{
			$word = westr::strtolower($word);
			$result[shd_hash($word)] = $i++;
			if (!empty($settings['shd_search_prefix_size']) && $len >= $settings['shd_search_prefix_size'])
			{
				for ($j = $settings['shd_search_prefix_size']; $j <= $len; $j++)
				{
					$prefixword = substr($word, 0, $j) . chr(7);
					$result[shd_hash($prefixword)] = $i++;
				}
			}
		}
	}

	return array_flip($result); // This gets us a unique array but done faster than $result[] = shd_hash($word); $result = array_unique($result);
}

/**
 *	Creates our hash. Due to the way floats can be used, we can safely store an integer equal to 2^52 in a float, so we'll use this. It should be relatively free from avalanching.
 *
 *	Theoretically, a 32 bit hash (a la CRC32) would be suitable if it didn't have the collision incidence factor it does, so we have to do it this way.
 *	If we didn't permit prefix matching it would probably be suitable, actually.
 *
 *	@param string $string The string to take the hash of.
 *	@return string $hash The 52 bit number as a string to prevent it being mashed by any more formatting.
*/
function shd_hash($string)
{
	return sprintf('%0.0f', hexdec(substr(sha1($string), -13)));
}

?>