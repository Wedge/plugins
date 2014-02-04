<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

// There are more functions to come. It's to extend!
function template_we_pinterest_topic_after()
{
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
	$("img").each(function () {
		if ($(this).width() < 300)
			return;
		$(this).wrap("<div style=\"display: inline-block; position: relative\"/>").after(\'\
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