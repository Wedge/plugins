<?php
// Version: 2.0; ManageCalendar

$txt['manage_calendar'] = 'Kalender';
$txt['manage_holidays'] = 'Feiertage bearbeiten';
$txt['calendar_settings'] = 'Kalender Einstellungen';

$txt['calendar_desc'] = 'Hier können alle relevanten Einstellungen des Kalenders bearbeitet werden.';

// !!! Convert this into what's needed for the admin panel as $helptxt
$helptxt['cal_enabled'] = 'Der Kalender kann für Ereignisse, Feierstage und Geburtstage in deinem Forum verwendet werden.<br><br>
		<strong>Anzeige von Tagen als Link als \'Ereignis veröffentlichen\'</strong>:<br>Erlaubt Mitgliedern ein neues Ereignis zu veröffentlichen.<br>
		<strong>Max. TAge im Voraus im Foren-Index</strong>:<br>Wenn diese Option auf 7 gesetzt wird, werden die Ereignisse der nächsten Woche angezeigt.<br>
		<strong>Anzeige von Feiertagen auf dem Foren-Index</strong>:<br>Anzeige der Feiertage auf der Foren-Hauptseite.<br>
		<strong>Anzeige von Ereignissen auf dem Foren-Index</strong>:<br>Anzeige der heutigen Ereignisse auf der Foren-Hauptseite.<br>
		<strong>Standard Forum zum erstellen neuer Ereignisse</strong>:<br>Forum in dem die Ereignisse gepostet werden<br>
		<strong>Ereignisse ohne Verlinkung erlauben</strong>:<br>Erlaubt Mitgliedern das Erstellen von Ereignissen ohne ein dazugehöriges Posting.<br>
		<strong>Min. Jahr</strong>:<br>Das erste angezeigte Jahr im Kalender.<br>
		<strong>Max. Jahr</strong>:<br>Das letzte angezeigte JAhr im Kalender.<br>
		<strong>Ereignisse dürfen mehrere Tage in Anspruch nehmen</strong>:<br>Ereignisse können über eine gewisse Zeitspanne gehen.<br>
		<strong>Maximale ANzahl von Tagen die ein Ereignis beanspruchen kann</strong>:<br>Wähle die maximale Zeitspanne aus, die ein Ereignis dauern kann.<br><br>';

// Calendar Settings
$txt['calendar_settings_desc'] = 'Hier kann der Kalender aktiviert werden und Einstellungen angepasst werden.';
$txt['save_settings'] = 'Einstellungen speichern';
$txt['groups_calendar_view'] = 'Mitgliedergruppen denen es erlaubt ist den Kalender zu betrachten';
$txt['groups_calendar_post'] = 'Mitgliedergruppen, denen es erlaubt ist Ereignisse zu veröffentlichen';
$txt['groups_calendar_edit_own'] = 'Mitgliedergruppen, die eigene Ereignisse editieren dürfen';
$txt['groups_calendar_edit_any'] = 'Mitgliedergruppen die alle Ereignisse editieren dürfen';
$txt['setting_cal_daysaslink'] = 'Anzeige der Tage als Link zu \'Ereignis veröffentlichen\'';
$txt['setting_cal_days_for_index'] = 'Max. Anzal von Tagen im Voraus auf dem Index';
$txt['setting_cal_showholidays'] = 'Feierstage anzeigen';
$txt['setting_cal_showevents'] = 'Ereignisse anzeigen';
$txt['setting_cal_show_never'] = 'Niemals';
$txt['setting_cal_show_cal'] = 'Nur im Kalender';
$txt['setting_cal_show_index'] = 'Nur auf dem Foren-Index';
$txt['setting_cal_show_all'] = 'Auf dem Foren-Index und im Kalender';
$txt['setting_cal_defaultboard'] = 'Standard-Forum in denen Ereignisse veröffentlicht werden';
$txt['setting_cal_allow_unlinked'] = 'Ereignisse dürfen nicht zu einem Beitrag verlinkt werden';
$txt['setting_cal_minyear'] = 'Min. Jahr';
$txt['setting_cal_maxyear'] = 'Max. Jahr';
$txt['setting_cal_allowspan'] = 'Ereignisse dürfen über mehrere Tage gehen';
$txt['setting_cal_maxspan'] = 'Maximale Anzahl von Tagen, die ein Ereignis erfassen darf';
$txt['setting_cal_showInTopic'] = 'Anzeige der verlinkten Ereignisse in der Themenübersicht';

// Adding/Editing/Viewing Holidays
$txt['manage_holidays_desc'] = 'Hier können Feiertage hinzugefügt oder entfernt werden.';
$txt['predefined_holidays'] = 'Vordefinierte Feiertage';
$txt['custom_holidays'] = 'Eigene Feiertage';
$txt['holidays_title'] = 'Feiertag';
$txt['holidays_title_label'] = 'Titel';
$txt['holidays_delete_confirm'] = 'Möchtest Du wirklich diese Feiertage löschen?';
$txt['holidays_add'] = 'Feiertag hinzufügen';
$txt['holidays_edit'] = 'Existierenden Feiertag bearbeiten';
$txt['holidays_button_add'] = 'Hinzufügen';
$txt['holidays_button_edit'] = 'Bearbeiten';
$txt['holidays_button_remove'] = 'Entfernen';
$txt['holidays_no_entries'] = 'Es sind zur Zeit keine eigenen Feiertage konfiguriert.';
$txt['every_year'] = 'Jedes Jahr';

// Maintenance
$txt['repair_operation_missing_calendar_topics'] = 'Ereignisse die zu nicht mehr existierenden Themen verlinkt sind';
$txt['repair_missing_calendar_topics'] = 'Das Ereignis #%1$d ist mit dem nicht mehr existierenden Thema #%2$d verknüpft.';

// Permissions
$txt['permissiongroup_calendar'] = 'Kalender';
$txt['permissionname_calendar_view'] = 'Kalender betrachten';
$txt['permissionhelp_calendar_view'] = 'Der Kalender beinhaltet für jeden Monat Ereignisse und Feiertage. Diese Berechtigung erlaubt den Zugriff auf den Kalender. Wenn diese Berechtigung gesetzt wurde, erscheint ein Button in der oberen Navigationsbar. Der Kalender muss in \'Plugins-Kalender\' aktiviert werden.';
$txt['permissionname_calendar_post'] = 'Create events in the calendar';
$txt['permissionhelp_calendar_post'] = 'Ein Ereignis ist ein Thema, welches zu einem bestimmten Datum und/oder Zeitspanne verlinkt wurde. Ereignisse können direkt aus dem Kalender von Usern mit entsprechenden Rechten erstellt werden.';
$txt['permissionname_calendar_edit'] = 'Ereignisse im Kalender editieren';
$txt['permissionhelp_calendar_edit'] = 'Ein Ereignis ist ein Thema, welches zu einem bestimmten Datum und/oder Zeitspanne verlinkt wurde. Das Ereignis kann durch den Klick auf die mit dem roten Stern (*) markierten Ereignisse bearbeitet werden. Um ein Ereignis editieren zu können, müssen entsprechende Rechte vorhanden sein.';
$txt['permissionname_calendar_edit_own'] = 'Eigene Ereignisse';
$txt['permissionname_calendar_edit_any'] = 'Jedes Ereignis';

// Reporting
$txt['group_perms_name_calendar_edit_any'] = 'Ein Ereignis editieren';
$txt['group_perms_name_calendar_edit_own'] = 'Eigene Ereignisse editieren';
$txt['group_perms_name_calendar_post'] = 'Ereignisse veröffentlichen';
$txt['group_perms_name_calendar_view'] = 'Ereignisse anschauen';

?>