<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function template_flitter_topic_above()
{
	add_css('#flitter { margin: 0; -webkit-padding-start: 0; margin-left: 0; padding-left: 0 } #flitter li { display: inline-block; vertical-align: top }');
	echo '
		<div>
			<ul id="flitter">';
}

function template_flitter_topic_below()
{
	echo '
			</ul>
		</div>';
}

function template_flitter_sidebar_above()
{
	global $txt;

	add_css('#flitter { list-style:none; -webkit-padding-start: 0; margin-left: 0; padding-left: 0 }');
	echo '
		<we:title>
			', $txt['flitter_share'], '
		</we:title>
		<div>
			<ul id="flitter">';
}

function template_flitter_sidebar_below()
{
	echo '
			</ul>
		</div>';
}

function template_flitter_fb()
{
	global $context;

	echo '
	<li>
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/', $txt['lang_locale'], '/all.js#xfbml=1";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, \'script\', \'facebook-jssdk\'));</script>

		<div class="fb-like" data-href="', $context['canonical_url'], '" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>
	</li>';
}

function template_flitter_twitter()
{
	global $settings;

	echo '
	<li>
		<a href="https://twitter.com/share" class="twitter-share-button" data-count="horizontal"', !empty($settings['flitter_twitter_via']) ? ' data-via="' . $settings['flitter_twitter_via'] . '"' : '', empty($settings['flitter_twitter_related']) ? '' : ' data-related="' . $settings['flitter_twitter_related'] . (!empty($settings['flitter_twitter_related_desc']) ? ':' . $settings['flitter_twitter_related_desc'] : '') . '"', '>Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
	</li>';
}

function template_flitter_google()
{
	global $context, $settings;

	echo '
	<li>
		<div class="g-plusone" data-size="medium" data-href="', $context['canonical_url'], '"></div>
	</li>';

	add_js('
	(function() {
		var po = document.createElement(\'script\'); po.type = \'text/javascript\'; po.async = true;
		po.src = \'https://apis.google.com/js/plusone.js\';
		var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
	})();');
}
?>