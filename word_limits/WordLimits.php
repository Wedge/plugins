<?php

function wordcount_moderation_rules(&$known_variables, $admin)
{
	loadPluginLanguage('Arantor:WordCount', 'WordLimits');
	loadPluginTemplate('Arantor:WordCount', 'WordLimits');
	$known_variables['words'] = array(
		'type' => 'range',
		'current' => 0,
		'func_val' => 'count_words_post',
		'function' => function ($criteria) {
			global $txt;
			return $txt['modfilter_cond_' . $criteria['name']] . ': ' . $txt['modfilter_range_' . $criteria['term']] . ' ' . $criteria['value'];
		},
	);
	$known_variables['chars'] = array(
		'type' => 'range',
		'current' => 0,
		'func_val' => 'count_chars_post',
		'function' => function ($criteria) {
			global $txt;
			return $txt['modfilter_cond_' . $criteria['name']] . ': ' . $txt['modfilter_range_' . $criteria['term']] . ' ' . $criteria['value'];
		},
	);
}

function displayRow_words($rule)
{
	return simpleRange_displayRow($rule, 'words');
}

function displayRow_chars($rule)
{
	return simpleRange_displayRow($rule, 'chars');
}

function count_words_post($subject, $body)
{
	$body = trim(un_htmlspecialchars(strip_tags(parse_bbc($body, 'word-limits'))));
	if (empty($body))
		return 0;
	$count = @preg_match_all('~\p{L}[\p{L}\p{Mn}\'\x{2019}]{2,}~u', $body, $matches);
	if ($count == 0)
	{
		// can mean this one failed, so fallback
		$str = preg_replace('/[\x21-\x26\x28-\x40\x5B-\x60\x7B-\x7F]+/', ' ', $str);
		$count = 0;
		$words = str_word_count($str, 1); // but this doesn't give us min 3 letters!
		foreach($words as $word)
			if(strlen($word) >= 3)
				$count++;
	}
	return $count;
}

function count_chars_post($subject, $body)
{
	// We can't use raw strlen, which will be byte count. We want *character* count,
	// which in multibyte cases will potentially be different to byte count.
	return westr::strlen($body);
}

?>