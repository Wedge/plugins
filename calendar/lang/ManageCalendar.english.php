<?php
// Version: 2.0; ManageCalendar

$txt['manage_calendar'] = 'Calendar';
$txt['manage_holidays'] = 'Manage Holidays';
$txt['calendar_settings'] = 'Calendar Settings';

$txt['calendar_desc'] = 'From here you can modify all aspects of the calendar.';

// !!! Convert this into what's needed for the admin panel as $helptxt
$helptxt['cal_enabled'] = 'The calendar can be used for showing important moments happening in your community.<br><br>
		<strong>Show days as link to \'Post Event\'</strong>:<br>This will allow members to post events for that day, when they click on that date<br>
		<strong>Max days in advance on board index</strong>:<br>If this is set to 7, the next week\'s worth of events will be shown.<br>
		<strong>Show holidays on board index</strong>:<br>Show today\'s holidays in a calendar bar on the board index.<br>
		<strong>Show events on board index</strong>:<br>Show today\'s events in a calendar bar on the board index.<br>
		<strong>Default Board to Post In</strong>:<br>What\'s the default board to post events in?<br>
		<strong>Allow events not linked to posts</strong>:<br>Allow members to post events without requiring it to be linked with a post in a board.<br>
		<strong>Minimum year</strong>:<br>Select the "first" year on the calendar list.<br>
		<strong>Maximum year</strong>:<br>Select the "last" year on the calendar list<br>
		<strong>Allow events to span multiple days</strong>:<br>Check to allow events to span multiple days.<br>
		<strong>Max number of days an event can span</strong>:<br>Select the maximum days that an event can span.<br><br>
		Remember that usage of the calendar (posting events, viewing events, etc.) is controlled by permissions set on the permissions screen.';

// Calendar Settings
$txt['calendar_settings_desc'] = 'Here you can enable the calendar, and determine the settings that it should use.';
$txt['save_settings'] = 'Save Settings';
$txt['groups_calendar_view'] = 'Membergroups allowed to view the calendar';
$txt['groups_calendar_post'] = 'Membergroups allowed to create events';
$txt['groups_calendar_edit_own'] = 'Membergroups allowed to edit their own events';
$txt['groups_calendar_edit_any'] = 'Membergroups allowed to edit any events';
$txt['setting_cal_daysaslink'] = 'Show days as links to \'Post Event\'';
$txt['setting_cal_days_for_index'] = 'Max days in advance on board index';
$txt['setting_cal_showholidays'] = 'Show holidays';
$txt['setting_cal_showevents'] = 'Show events';
$txt['setting_cal_show_never'] = 'Never';
$txt['setting_cal_show_cal'] = 'In calendar only';
$txt['setting_cal_show_index'] = 'On board index only';
$txt['setting_cal_show_all'] = 'On board index and calendar';
$txt['setting_cal_defaultboard'] = 'Default board to post events in';
$txt['setting_cal_allow_unlinked'] = 'Allow events not linked to posts';
$txt['setting_cal_minyear'] = 'Minimum year';
$txt['setting_cal_maxyear'] = 'Maximum year';
$txt['setting_cal_allowspan'] = 'Allow events to span multiple days';
$txt['setting_cal_maxspan'] = 'Max number of days an event can span';
$txt['setting_cal_showInTopic'] = 'Show linked events in topic display';

// Adding/Editing/Viewing Holidays
$txt['manage_holidays_desc'] = 'From here you can add and remove holidays from your forum calendar.';
$txt['predefined_holidays'] = 'Predefined Holidays';
$txt['custom_holidays'] = 'Custom Holidays';
$txt['holidays_title'] = 'Holiday';
$txt['holidays_title_label'] = 'Title';
$txt['holidays_delete_confirm'] = 'Are you sure you wish to remove these holidays?';
$txt['holidays_add'] = 'Add New Holiday';
$txt['holidays_edit'] = 'Edit Existing Holiday';
$txt['holidays_button_add'] = 'Add';
$txt['holidays_button_edit'] = 'Edit';
$txt['holidays_button_remove'] = 'Remove';
$txt['holidays_no_entries'] = 'There are currently no custom holidays configured.';
$txt['every_year'] = 'Every Year';

// Maintenance
$txt['repair_operation_missing_calendar_topics'] = 'Events linked to non-existent topics';
$txt['repair_missing_calendar_topics'] = 'Event #%1$d is tied to topic #%2$d, which is missing.';

// Permissions
$txt['permissiongroup_calendar'] = 'Calendar';
$txt['permissionname_calendar_view'] = 'View the calendar';
$txt['permissionhelp_calendar_view'] = 'The calendar shows for each month the events and holidays. This permission allows access to this calendar. When this permission is enabled, a button will be added to the top button bar and a list will be shown at the bottom of the board index with current and upcoming events and holidays. The calendar needs be enabled from \'Configuration - Core Features\'.';
$txt['permissionname_calendar_post'] = 'Create events in the calendar';
$txt['permissionhelp_calendar_post'] = 'An Event is a topic linked to a certain date or date range. Creating events can be done from the calendar. An event can only be created if the user that creates the event is allowed to post new topics.';
$txt['permissionname_calendar_edit'] = 'Edit events in the calendar';
$txt['permissionhelp_calendar_edit'] = 'An Event is a topic linked to a certain date or date range. The Event can be edited by clicking the red asterisk (*) next to the event in the calendar view. In order to be able to edit an event, a user must have sufficient permissions to edit the first message of the topic that is linked to the event.';
$txt['permissionname_calendar_edit_own'] = 'Own events';
$txt['permissionname_calendar_edit_any'] = 'Any events';

// Reporting
$txt['group_perms_name_calendar_edit_any'] = 'Edit any event';
$txt['group_perms_name_calendar_edit_own'] = 'Edit own events';
$txt['group_perms_name_calendar_post'] = 'Post events';
$txt['group_perms_name_calendar_view'] = 'View events';

?>