<?php

function template_modfilter_words()
{
	template_word_filter_selector('words');
}

function template_modfilter_chars()
{
	template_word_filter_selector('chars');
}

function template_word_filter_selector($type)
{
	global $context, $txt;

	$js_conds = array();
	echo '
		<br>', $txt['modfilter_' . $type . '_is'], '
		<select name="rangesel" onchange="validate', ucfirst($type), '();">';

	foreach (array('lt', 'lte', 'eq', 'gte', 'gt') as $item)
	{
		echo '
			<option value="', $item, '">', $txt['modfilter_range_' . $item], '</option>';
		$js_conds[] = $item . ': ' . JavaScriptEscape($txt['modfilter_range_' . $item]);
	}

	echo '
		</select>
		<input type="text" size="5" name="' . $type . '" style="padding: 3px 5px 5px 5px" onchange="validate' . ucfirst($type) . '();">
		<div class="pagesection ruleSave">
			<div class="floatright">
				<input class="new" type="submit" value="', $txt['modfilter_condition_done'], '" onclick="add', ucfirst($type), '(e);">
			</div>
		</div>';

	add_js('
	function validate', ucfirst($type), '()
	{
		var
			applies_type = $("#rulecontainer select[name=rangesel]").val(),
			' . $type . ' = $("#rulecontainer input[name=' . $type . ']").val(),
			pc_num = parseInt(' . $type . ');

		$("#rulecontainer .ruleSave").toggle(in_array(applies_type, ["lt", "lte", "eq", "gte", "gt"]) && ' . $type . ' == pc_num && pc_num >= 0);
	};

	function add', ucfirst($type), '(e)
	{
		e.preventDefault();
		var
			range = {' . implode(',', $js_conds) . '},
			pc = ' . JavaScriptEscape($txt['modfilter_cond_' . $type]) . ',
			applies_type = $("#rulecontainer select[name=rangesel]").val(),
			' . $type . ' = $("#rulecontainer input[name=' . $type . ']").val(),
			pc_num = parseInt(' . $type . ');

		if (in_array(applies_type, ["lt", "lte", "eq", "gte", "gt"]) && ' . $type . ' == pc_num && pc_num >= 0)
			addRow(pc, range[applies_type] + " " + ' . $type . ', "' . $type . '", applies_type + ";" + ' . $type . ');
	};');
}

?>