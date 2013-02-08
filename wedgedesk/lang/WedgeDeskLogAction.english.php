<?php
/**
 * WedgeDesk
 *
 * This file contains all of the base language strings used by the helpdesk action log.
 * Unlike other language files, many of the strings here are parameterised, enabling them to be extended in the future.
 * @see shd_log_action()
 *
 * @package wedgedesk
 * @copyright 2011 Peter Spicer, portions SimpleDesk 2010-11 used under BSD licence
 * @license http://wedgedesk.com/index.php?action=license
 *
 * @since 1.0
 * @version 1.0
 */

// Important! Before editing these language files please read the text at the top of index.english.php.

//! @name General strings
//@{
$txt['shd_action_log_disabled'] = '<strong>Note:</strong> Logging of actions is currently <strong>disabled</strong>, so no new log entries will be added.';
//@}

//! @name Ticket resolution
//@{
$txt['shd_log_resolve'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; marked as <strong>resolved</strong>.';
$txt['shd_log_unresolve'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; marked as <strong>not yet resolved</strong>.';
//@}

//! @name Ticket assignation
//@{
$txt['shd_log_assign'] = 'Assigned &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; to {profile_link}.';
$txt['shd_log_unassign'] = 'Assigned &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; to no-one.';
//@}

//! @name Ticket privacy
//@{
$txt['shd_log_markprivate'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; marked as <strong>private</strong>.';
$txt['shd_log_marknotprivate'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; marked as <strong>not private</strong>.';
//@}

//! @name Ticket urgency
//@{
$txt['shd_log_urgency_increase'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; increased to <strong>{urgency}</strong>.';
$txt['shd_log_urgency_decrease'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; decreased to <strong>{urgency}</strong>.';
//@}

//! @name Ticket/topic, topic/ticket moves
//@{
$txt['shd_log_tickettotopic'] = 'Moved &quot;<a href="<URL>?topic={ticket}.0">{subject}</a>&quot; to &quot;<strong><a href="<URL>?board={board_id}.0">{board_name}</a></strong>&quot; in the forum.';
$txt['shd_log_topictoticket'] = 'Moved the topic &quot;<strong><a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a></strong>&quot; from the forum to the helpdesk.';
//@}

//! @name Ticket deletion, restoration, permadeletion
//@{
$txt['shd_log_delete'] = 'Deleted &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; to the recycle bin.';
$txt['shd_log_restore'] = 'Restored &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; from the recycle bin.';
$txt['shd_log_permadelete'] = '<strong>Permanently</strong> deleted &quot;{subject}&quot; (ticket {ticket}).';
$txt['shd_log_delete_reply'] = 'Deleted reply in &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg};recycle">{subject}</a>&quot; to the recycle bin.';
$txt['shd_log_restore_reply'] = 'Restored reply in &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">{subject}</a>&quot; from the recycle bin.';
$txt['shd_log_permadelete_reply'] = '<strong>Permanently</strong> deleted a reply from &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;.';
//@}

//! @name Ticket relationships
//@{
$txt['shd_log_rel_linked'] = 'Marked &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as linked to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_duplicated'] = 'Marked &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as duplicate of &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_parent'] = 'Marked &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as parent of &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_child'] = 'Marked &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as child of &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_re_linked'] = 'Updated &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as being linked to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_re_duplicated'] = 'Updated &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as being a duplicate of &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_re_parent'] = 'Updated &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as being the parent of &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_re_child'] = 'Updated &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; as being a child of &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
$txt['shd_log_rel_delete'] = 'Removed relationship between &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot; and &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={otherticket}.0">{othersubject}</a>&quot;.';
//@}

//! @name Custom fields being edited (done this way to preserve filtering at the broadest level, sorry)
//@{
$txt['shd_log_cf_tktchange_admin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchange_staffadmin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchange_useradmin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchange_userstaffadmin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchange_admin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchange_staffadmin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchange_useradmin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchange_userstaffadmin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchgdef_admin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchgdef_staffadmin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchgdef_useradmin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_tktchgdef_userstaffadmin'] = 'On &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchgdef_admin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchgdef_staffadmin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchgdef_useradmin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_log_cf_rplchgdef_userstaffadmin'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;, the value of &quot;{fieldname}&quot; was changed from <strong>{oldvalue}</strong> to the default of <strong>{newvalue}</strong>';
$txt['shd_none_selected'] = '<em>none selected</em>';
$txt['shd_empty_item'] = '<em>empty</em>';
//@}

//! @name Other ticket events
//@{
$txt['shd_log_newticket'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; opened.';
$txt['shd_log_editticket'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; was edited.';
$txt['shd_log_newreply'] = '<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">New reply</a> to &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot;.';
$txt['shd_log_editreply'] = 'A <a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.msg{msg}#msg{msg}">reply</a> was edited in &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}.0">{subject}</a>&quot;.';
$txt['shd_log_newticketproxy'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; opened on behalf of {profile_link}.';
$txt['shd_log_move_dept'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; was moved from <a href="<URL>?{shd_home};dept={old_dept_id}">{old_dept_name}</a> to <a href="<URL>?{shd_home};dept={new_dept_id}">{new_dept_name}</a>.';

$txt['shd_logpart_att_added'] = 'Files added';
$txt['shd_logpart_att_removed'] = 'Files removed';

$txt['shd_log_autoclose'] = '&quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; marked as <strong>resolved</strong> due to inactivity.';

//! @name Notifications and monitoring, there's a lot of them.
$txt['shd_log_notify'] = 'Sent email notification regarding &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot;. ';
$txt['shd_log_notify_to'] = 'Message sent to: ';
$txt['shd_log_notify_hiddenemail'] = '%1$d other email addresses';
$txt['shd_log_notify_hiddenemail_1'] = '1 other email address';
$txt['shd_log_notify_users'] = 'users';
$txt['shd_log_notify_email'] = 'email';
$txt['shd_log_notifications'] = 'Notifications';
$txt['shd_log_unknown_user_1'] = '1 former user';
$txt['shd_log_unknown_user_n'] = '%1$d former users';
$txt['shd_log_monitor'] = 'Added &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; to their monitor list.';
$txt['shd_log_unmonitor'] = 'Removed &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; from their monitor list.';
$txt['shd_log_ignore'] = 'Added &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; to their do-not-notify (ignore) list.';
$txt['shd_log_unignore'] = 'Removed &quot;<a href="<URL>?action=helpdesk;sa=ticket;ticket={ticket}">{subject}</a>&quot; from their do-not-notify (ignore) list.';
//@}
?>