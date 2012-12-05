<?php

function template_modfilter_postsperday()
{
	global $context, $txt;

	$js_conds = array();
	echo '
		<br>', $txt['modfilter_postsperday_is'], '
		<select name="rangesel" onchange="validatepostsperday();">';

	foreach (array('lt', 'lte', 'eq', 'gte', 'gt') as $item)
	{
		echo '
			<option value="', $item, '">', $txt['modfilter_range_' . $item], '</option>';
		$js_conds[] = $item . ': ' . JavaScriptEscape($txt['modfilter_range_' . $item]);
	}

	echo '
		</select>
		<input type="text" size="5" name="postsperday" style="padding: 3px 5px 5px 5px" onchange="validatepostsperday();">
		<div class="pagesection ruleSave">
			<div class="floatright">
				<input class="new" type="submit" value="', $txt['modfilter_condition_done'], '" onclick="addpostsperday(e);">
			</div>
		</div>';

	add_js('
	function validatepostsperday()
	{
		var
			applies_type = $("#rulecontainer select[name=rangesel]").val(),
			postsperday = $("#rulecontainer input[name=postsperday]").val(),
			pc_num = parseInt(postsperday);

		$("#rulecontainer .ruleSave").toggle(in_array(applies_type, ["lt", "lte", "eq", "gte", "gt"]) && postsperday == pc_num && pc_num >= 0);
	};

	function addpostsperday(e)
	{
		e.preventDefault();
		var
			range = {' . implode(',', $js_conds) . '},
			pc = ' . JavaScriptEscape($txt['modfilter_cond_postsperday']) . ',
			applies_type = $("#rulecontainer select[name=rangesel]").val(),
			postsperday = $("#rulecontainer input[name=postsperday]").val(),
			pc_num = parseInt(postsperday);

		if (in_array(applies_type, ["lt", "lte", "eq", "gte", "gt"]) && postsperday == pc_num && pc_num >= 0)
			addRow(pc, range[applies_type] + " " + postsperday, "postsperday", applies_type + ";" + postsperday);
	};');
}

?>