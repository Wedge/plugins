<?php
// Version: 2.0; ManageCalendar

$txt['manage_calendar'] = 'Calendar';
$txt['manage_holidays'] = 'Manage Holidays';
$txt['calendar_settings'] = 'Calendar Settings';

$txt['calendar_desc'] = 'From here you can modify all aspects of the calendar.';

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
$txt['current_holidays'] = 'Current Holidays';
$txt['holidays_title'] = 'Holiday';
$txt['holidays_title_label'] = 'Title';
$txt['holidays_delete_confirm'] = 'Are you sure you wish to remove these holidays?';
$txt['holidays_add'] = 'Add New Holiday';
$txt['holidays_edit'] = 'Edit Existing Holiday';
$txt['holidays_button_add'] = 'Add';
$txt['holidays_button_edit'] = 'Edit';
$txt['holidays_button_remove'] = 'Remove';
$txt['holidays_no_entries'] = 'There are currently no holidays configured.';
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
$txt['permissiongroup_simple_post_calendar'] = 'Post events onto the calendar';
$txt['permissionname_simple_calendar_edit_own'] = 'Edit their own calendar events';
$txt['permissionname_simple_calendar_edit_any'] = 'Edit anyone\'s calendar events';

// Reporting
$txt['group_perms_name_calendar_edit_any'] = 'Edit any event';
$txt['group_perms_name_calendar_edit_own'] = 'Edit own events';
$txt['group_perms_name_calendar_post'] = 'Post events';
$txt['group_perms_name_calendar_view'] = 'View events';

?>