<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');
//There are more functions to come. It's to extend!
function template_we_pinterest_topic_after()
{
	echo '<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>';
	add_js('
	(function(d){
    var f = d.getElementsByTagName(\'SCRIPT\')[0], p = d.createElement(\'SCRIPT\');
    p.type = \'text/javascript\';
    p.async = true;
    p.src = \'//assets.pinterest.com/js/pinit.js\';
    f.parentNode.insertBefore(p, f);
	}(document));');
	}
?>