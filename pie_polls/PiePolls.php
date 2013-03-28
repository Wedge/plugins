<?php

if (!defined('WEDGE'))
	die('Cannot be accessed directly.');

function piepolls()
{
	global $context;

	if (empty($context['is_poll']))
		return;

	if ($context['poll']['show_results'] || !$context['allow_vote'])
	{
		// Before we do anything, we need to see if this is IE 7 or 8, who need a polyfill.
		// But we need it earlier than normal deferring.
		if (we::is('ie7,ie8'))
			$context['remote_js_files'][] = $context['plugins_url']['Arantor:PiePolls'] . '/js/excanvas.compiled.js';

		loadPluginLanguage('Arantor:PiePolls', 'lang/PiePolls');
		// It doesn't seem to play nice with minification.
		add_plugin_js_file('Arantor:PiePolls', 'js/Chart.min.js', true);
		add_plugin_css_file('Arantor:PiePolls', 'piepolls', true);
		wetem::replace('topic_poll', 'piepolls');
	}
}

// Yes, I'm well aware that this should be a separate file. But the logic and presentation are separated - and performance counts for something too.
function template_piepolls()
{
	global $context, $settings, $txt;

	// Drawing the chart itself.
	echo '
		<canvas id="poll" width="400" height="400" class="floatleft w50"></canvas>';

	// Legendary.
	echo '
		<div class="roundframe floatright w50" id="pie_legend">
			<h6>', $txt['pie_legend'], '</h6>';

	$col_index = 0;
	foreach ($context['poll']['options'] as $key => $option)
	{
		$col_index++;
		if (!isset($settings['pie_fg' . $col_index]))
			$col_index = 1;

		$context['poll']['options'][$key]['color'] = $settings['pie_fg' . $col_index];

		echo '
				<div>
					<span style="color:', $settings['pie_fg' . $col_index], '">&diams;</span> ', $option['option'], '
					<div>', sprintf($txt['pie_votes'], $option['votes'], $option['percent'] . '%');

		if (!empty($option['voters']))
		{
			foreach ($option['voters'] as $k => $v)
				$option['voters'][$k] = '<a href="<URL>?action=profile;u=' . $k . '">' . $v . '</a>';
			echo ' - ', sprintf($txt['pie_voted'], implode(', ', $option['voters']));
		}
					echo '</div>
				</div>';
	}

	echo '
		</div>';

	$index = 1;
	$first = true;

	$js = '
var ctx = document.getElementById("poll").getContext("2d");
var myNewChart = new Chart(ctx).Pie([';

	$total = 0;
	foreach ($context['poll']['options'] as $option)
	{
		$total += $option['votes'];
		$js .= (!$first ? ',' : '') . '
	{ value: ' . $option['votes'] . ', color: ' . JavaScriptEscape($option['color']) . '}';

		$first = false;
		$index++;
		if (!isset($settings['pie_fg' . $index]))
			$index = 1;
	}

	$js .= '
], {
	segmentShowStroke: false
});';

	add_js($js);

	echo '<br class="clear">';
}
