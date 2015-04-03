<?php

function npfg_display_main(){

	global $context;
	if(we::$is_guest){ // If user is guest, delete print button
		unset($context['nav_buttons']['normal']['print']);
	}

}

function npfg_load_theme(){
	global $context;
	log_error(print_r($context, true));
	if(we::$is_guest && $context['action'] == 'printpage'){ // If user is guest, and action is printpage, abort it
		redirectexit();
	}	
	
}
