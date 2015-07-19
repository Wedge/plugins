<?php

function npfg_display_main()
{
	global $context;

	// If user is guest, delete print button
	if (we::$is_guest)
		unset($context['nav_buttons']['normal']['print']);
}

function npfg_load_theme()
{
	global $context;

	log_error(print_r($context, true));

	// If user is guest, and action is printpage, abort it
	if (we::$is_guest && $context['action'] == 'printpage')
		redirectexit();
}
