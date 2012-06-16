<?php

// Attempt to disable the user using the right mouse button in the vain hopes of preventing them from copying.
// If I can't convince you it's a bad idea, I might as well give you enough rope.

// This is bound to the load_theme hook, as it requires to be loaded after theme setup but before action evaluation.
function disable_right_click()
{
	add_js('$("body").bind("contextmenu", function (btn) { return false; }).mousedown(function (btn) { if (btn.which & 2 == 2) { btn.stopImmediatePropagation(); } });');
}

?>