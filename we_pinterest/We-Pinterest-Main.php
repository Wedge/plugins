<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function we_pinterest_main()
{
	global $settings, $language, $txt;

	loadPluginTemplate('Pandos:We-Pinterest', 'We-Pinterest-Main');

	$lang = isset(we::$user['language']) ? we::$user['language'] : $language;
	switch ($lang)
	{
		case 'german':
			$txt['we_pinterest_on'] = 'Pin It button aktivieren';
			break;
		case 'french':
			$txt['we_pinterest_on'] = 'Activer le bouton Pin It';
			break;
		case 'english':
		default:
			$txt['we_pinterest_on'] = 'Enable Pin It button';
			break;
	}

	// IE<10-compatible version of <script async>.
	add_js_inline('
	(function(d){
		var f = d.getElementsByTagName(\'SCRIPT\')[0], p = d.createElement(\'SCRIPT\');
		p.type = \'text/javascript\';
		p.async = true;
		p.src = \'//assets.pinterest.com/js/pinit.js\';
		f.parentNode.insertBefore(p, f);
	}(document));');

	add_js('
	$("img").load(function () {
		if ($(this).width() < 300)
			return;
		($(this).parent("a") || $(this)).wrap("<div style=\"display: inline-block; position: relative\"/>").after(\'\
			<div style="display: none; position: absolute; right: 8px; bottom: 8px">\
				<a href="//www.pinterest.com/pin/create/button/?url=%url%&media=%img%" data-pin-do="buttonPin" data-pin-config="none">\
					<img src="//assets.pinterest.com/images/pidgets/pinit_fg_en_rect_gray_20.png">\
				</a>\
			</div>\
		\'.wereplace({
			url: location.href.php_htmlspecialchars(),
			img: this.src.php_htmlspecialchars()
		})).parent().hover(function () { $(this).children().last().stop(true, true).fadeToggle(500); });
	});');
}

?>