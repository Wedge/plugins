<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function template_flitter_topic_before()
{
	add_css('#flitter, #flitter > div { display: inline-block; height: 33px; vertical-align: middle }');

	echo '
		<div id="flitter">';
}

function template_flitter_topic_after()
{
	echo '
		</div>';
}

function template_flitter_sidebar_before()
{
	global $txt;

	add_css('#flitter, #flitter > div { display: inline-block; height: 33px; vertical-align: middle }');

	echo '
	<section>
		<we:title>
			', $txt['flitter_share'], '
		</we:title>
		<div id="flitter">';
}

function template_flitter_sidebar_after()
{
	echo '
		</div>
	</section>';
}

function template_flitter_fb()
{
	global $context, $txt;

	echo '
		<div id="fb-root"></div>
		<div class="fb-like" data-href="', $context['canonical_url'], '" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>';

	add_js('
	(function(d, s, id) {
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) return;
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.net/', $txt['lang_locale'], '/all.js#xfbml=1";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, \'script\', \'facebook-jssdk\'));');
}

function template_flitter_twitter()
{
	global $settings;

	echo '
		<div>
			<a href="https://twitter.com/share" class="twitter-share-button" data-count="horizontal"', !empty($settings['flitter_twitter_via']) ? ' data-via="' . $settings['flitter_twitter_via'] . '"' : '', empty($settings['flitter_twitter_related']) ? '' : ' data-related="' . $settings['flitter_twitter_related'] . (!empty($settings['flitter_twitter_related_desc']) ? ':' . $settings['flitter_twitter_related_desc'] : '') . '"', '>Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
		</div>';
}

function template_flitter_google()
{
	global $context;

	echo '
		<div class="g-plusone" data-size="medium" data-href="', $context['canonical_url'], '"></div>';

	add_js('
	(function() {
		var po = document.createElement(\'script\'); po.type = \'text/javascript\'; po.async = true;
		po.src = \'https://apis.google.com/js/plusone.js\';
		var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
	})();');
}
?>