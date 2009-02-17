<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Access Denied');

//Get the config info.
$config=Format::htmlchars(($errors && $_POST)?$_POST:$cfg->getConfig());
//Basic checks for warnings...
$warn=array();
if($config['allow_attachments'] && !$config['upload_dir']) {
    $errors['allow_attachments']='You need to setup upload dir.';    
}else{
    if(!$config['allow_attachments'] && $config['allow_email_attachments'])
        $warn['allow_email_attachments']='*Attachments Disabled.';
    if(!$config['allow_attachments'] && ($config['allow_online_attachments'] or $config['allow_online_attachments_onlogin']))
        $warn['allow_online_attachments']='<br>*Attachments Disabled.';
}

//Not showing err on post to avoid alarming the user...after an update.
if(!$errors['err'] &&!$msg && $warn )
    $errors['err']='Possible errors detected, please check the warnings below';
    
$gmtime=Misc::gmtime();
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1');
$templates=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id=1 AND cfg_id='.db_input($cfg->getId()));
?>
<div class="msg">System Preferences and Settings&nbsp;&nbsp;(v<?=$config['ostversion']?>)</div>
<table width="98%" border="0" cellspacing=0 cellpadding=0>
 <form action="admin.php?t=pref" method="post">
 <input type="hidden" name="t" value="pref">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header" ><td colspan=2>General Settings</td></tr>
        <tr class="subheader">
            <td colspan=2">Offline mode will disable client interface and <b>only</b> allow <b>super admin</b> to login to Staff Control Panel</td>
        </tr>
        <tr><th width="120"><b>Helpdesk Status</b></th>
            <td>
                <input type="radio" name="isonline"  value="1"   <?=$config['isonline']?'checked':''?> /><b>Online</b> (Active)
                <input type="radio" name="isonline"  value="0"   <?=!$config['isonline']?'checked':''?> /><b>Offline</b> (Disabled)
                &nbsp;<font class="warn">&nbsp;<?=$config['isoffline']?'osTicket offline':''?></font>
            </td>
        </tr>
        <tr><th>Helpdesk URL:</th>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="<?=$config['helpdesk_url']?>"> 
                &nbsp;<font class="error">*&nbsp;<?=$errors['helpdesk_url']?></font></td>
        </tr>
        <tr><th>Helpdesk Name/Title:</th>
            <td><input type="text" size="40" name="helpdesk_title" value="<?=$config['helpdesk_title']?>"> </td>
        </tr>
        <tr><th>Default Page Size:</th>
            <td>
                <select name="max_page_size">
                    <?
                     $pagelimit=$config['max_page_size'];
                    for ($i = 5; $i <= 50; $i += 5) {
                        ?>
                        <option <?=$config['max_page_size'] == $i ? 'SELECTED':''?>><?=$i?></option>
                        <?
                    }?>
                </select>
            </td>
        </tr>
        <tr><th>Default Email Template:</th>
            <td>
                <select name="default_template">
                    <option value=0>Select Default Template</option>
                    <?
                    while (list($id,$name) = db_fetch_row($templates)){
                        $selected = ($config['default_template']==$id)?'SELECTED':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['default_template']?></font>
            </td>
        </tr>
        <tr><th>Default Department:</th>
            <td>
                <select name="default_dept">
                    <option value=0>Select Default Dept</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                    $selected = ($config['default_dept']==$id)?'SELECTED':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?> Dept</option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['default_dept']?></font>
            </td>
        </tr>
        <tr><th>Staff Session Timeout:</th>
            <td>
              <input type="text" name="staff_session_timeout" size=6 value="<?=$config['staff_session_timeout']?>">
                (<i>Staff's max Idle time in minutes. Enter 0 to disable timeout</i>)
            </td>
        </tr>
        <tr><th>Client Session Timeout:</th>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?=$config['client_session_timeout']?>">
                (<i>Client's max Idle time in minutes. Enter 0 to disable timeout</i>)
            </td>
        </tr>
        <tr><th>Clickable URLs:</th>
            <td>
              <input type="checkbox" name="clickable_urls" <?=$config['clickable_urls']?'checked':''?>>
                Make URLs clickable
            </td>
        </tr>
        <tr><th>Enable Auto Cron:</th>
            <td>
              <input type="checkbox" name="enable_auto_cron" <?=$config['enable_auto_cron']?'checked':''?>>
                Enable cron call on staff's activity
            </td>
        </tr>
    </table>
    
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Date &amp; Time</td></tr>
        <tr class="subheader">
            <td colspan=2>Please refer to <a href="http://php.net/date" target="_blank">PHP Manual</a> for supported parameters.</td>
        </tr>
        <tr><th>Time Format:</th>
            <td>
                <input type="text" name="time_format" value="<?=$config['time_format']?>">
                    &nbsp;<font class="error">*&nbsp;<?=$errors['time_format']?></font>
                    <i><?=Format::date($config['time_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i></td>
        </tr>
        <tr><th>Date Format:</th>
            <td><input type="text" name="date_format" value="<?=$config['date_format']?>">
                        &nbsp;<font class="error">*&nbsp;<?=$errors['date_format']?></font>
                        <i><?=Format::date($config['date_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i>
            </td>
        </tr>
        <tr><th>Date &amp; Time Format:</th>
            <td><input type="text" name="datetime_format" value="<?=$config['datetime_format']?>">
                        &nbsp;<font class="error">*&nbsp;<?=$errors['datetime_format']?></font>
                        <i><?=Format::date($config['datetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i>
            </td>
        </tr>
        <tr><th>Day, Date &amp; Time Format:</th>
            <td><input type="text" name="daydatetime_format" value="<?=$config['daydatetime_format']?>">
                        &nbsp;<font class="error">*&nbsp;<?=$errors['daydatetime_format']?></font>
                        <i><?=Format::date($config['daydatetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i>
            </td>
        </tr>
        <tr><th>Default Timezone:</th>
            <td>
                <select name="timezone_offset">
                    <?
                    $gmoffset = date("Z") / 3600; //Server's offset.
                    echo"<option value=\"$gmoffset\">Server Time (GMT $gmoffset:00)</option>"; //Default if all fails.
                    $timezones= db_query('SELECT offset,timezone FROM '.TIMEZONE_TABLE);
                    while (list($offset,$tz) = db_fetch_row($timezones)){
                        $selected = ($config['timezone_offset'] ==$offset) ?'SELECTED':'';
                        $tag=($offset)?"GMT $offset ($tz)":" GMT ($tz)";
                        ?>
                        <option value="<?=$offset?>"<?=$selected?>><?=$tag?></option>
                        <?
                    }?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Daylight Saving:</th>
            <td>
                <input type="checkbox" name="enable_daylight_saving" <?=$config['enable_daylight_saving'] ? 'checked': ''?>>Observe daylight savings
            </td>
        </tr>
    </table>
   
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Ticket Options &amp; Settings</td></tr>
        <tr class="subheader"><td colspan=2>If enabled ticket lock get auto-renewed on form activity.</td></tr>
        <tr><th valign="top">Ticket IDs:</th>
            <td>
                <input type="radio" name="random_ticket_ids"  value="0"   <?=!$config['random_ticket_ids']?'checked':''?> /> Sequential
                <input type="radio" name="random_ticket_ids"  value="1"   <?=$config['random_ticket_ids']?'checked':''?> />Random  (recommended)
            </td>
        </tr>
        <tr><th>Maximum <b>Open</b> Tickets:</th>
            <td>
              <input type="text" name="max_open_tickets" size=4 value="<?=$config['max_open_tickets']?>"> 
                per email. (<i>Helps with spam and flood control. Enter 0 for unlimited</i>)
            </td>
        </tr>
        <tr><th>Auto-Lock Time:</td>
            <td>
              <input type="text" name="autolock_minutes" size=4 value="<?=$config['autolock_minutes']?>">
                 <font class="error"><?=$errors['autolock_minutes']?></font>
                (<i>Minutes to lock a ticket on activity. Enter 0 to disable locking</i>)
            </td>
        </tr>
        <tr><th>Ticket Grace Period:</th>
            <td>
              <input type="text" name="overdue_grace_period" size=4 value="<?=$config['overdue_grace_period']?>">
                (<i>Hours before ticket is marked overdue. Enter 0 to disable aging.</i>)
            </td>
        </tr>
        <tr><th>Reopened Tickets:</th>
            <td>
              <input type="checkbox" name="auto_assign_reopened_tickets" <?=$config['auto_assign_reopened_tickets'] ? 'checked': ''?>> 
                Auto-assign reopened tickets to last respondent 'available'. (<i> 3 months limit</i>)
            </td>
        </tr>
        <tr><th>Assigned Tickets:</th>
            <td>
              <input type="checkbox" name="show_assigned_tickets" <?=$config['show_assigned_tickets']?'checked':''?>>
                Show assigned tickets on open queue.
            </td>
        </tr>
        <tr><th valign="top">Ticket Priority:</th>
            <td>
                <select name="default_priority">
                    <?
                    $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                    while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                        <option value="<?=$id?>"<?=($config['default_priority']==$id)?'selected':''?>><?=$tag?></option>
                    <?
                    }?>
                </select> &nbsp;Default priority<br/>
                <input type="checkbox" name="allow_priority_change" <?=$config['allow_priority_change'] ?'checked':''?>>
                    Allow client to overwrite/set priority (online tickets)<br/>
                <input type="checkbox" name="use_email_priority" <?=$config['use_email_priority'] ?'checked':''?> >
                    Use email priority when available (emailed tickets)

            </td>
        </tr>
    </table>

    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2 >Email Settings</td></tr>
        <tr class="subheader"><td colspan=2>For POP3 mail fetch to work you must set a cron job or simply enable auto-cron</td></tr>
        <tr><th valign="top">Mail Methods:</th>
            <td>
                <input type="checkbox" name="enable_pop3_fetch" value=1 <?=$config['enable_pop3_fetch']? 'checked': ''?>  > Enable POP3 email fetch
                    &nbsp;&nbsp;(<i>Global setting which can be disabled at email level</i>) <br/>
                <input type="checkbox" name="enable_email_piping" value=1 <?=$config['enable_email_piping']? 'checked': ''?>  > Enable email piping
                   &nbsp;(<i>You pipe we accept policy</i>)<br/>
            </td>
        </tr>
        <tr><th>Save Email Headers:</th>
            <td>
              <input type="checkbox" name="save_email_headers" <?=$config['save_email_headers'] ? 'checked': ''?>> Save raw headers
            </td>
        </tr>
        <tr><th>Strip Quoted Reply:</th>
            <td>
              <input type="checkbox" name="strip_quoted_reply" <?=$config['strip_quoted_reply'] ? 'checked':''?>> 
                    Removing quoted reply (<i>depends on the tag below</i>)
            </td>
        </tr>
        <tr><th>Reply Separator Tag:</th>
            <td>
              <input type="text" name="reply_separator" value="<?=$config['reply_separator']?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['reply_separator']?></font>
            </td>
        </tr>
        <tr><th>Default Email:</th>
            <td>
                <select name="default_email">
                    <option value=0>Select One</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE);
                    while (list($id,$email,$name) = db_fetch_row($emails)){ 
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?=$id?>"<?=($config['default_email']==$id)?'selected':''?>><?=$email?></option>
                    <?
                    }?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?=$errors['default_email']?></font></td>
        </tr>
        <tr><th>No Reply Email Address:</th>
            <td>
                <input type="text" size=25 name="noreply_email" value="<?=$config['noreply_email']?>">
                     &nbsp;<font class="error">*&nbsp;<?=$errors['noreply_email']?></font></td>
        </tr>
        <tr><th>Alert(s) FROM Email Address:</th>
            <td>
                <input type="text" size=25 name="alert_email" value="<?=$config['alert_email']?>">
                    &nbsp;<font class="error">*&nbsp;<?=$errors['alert_email']?></font></td>
        </tr>
        <tr><th>System Admin Email Address:</th>
            <td>
                <input type="text" size=25 name="admin_email" value="<?=$config['admin_email']?>">
                    &nbsp;<font class="error">*&nbsp;<?=$errors['admin_email']?></font></td>
        </tr>
    </table>

    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Autoresponders &nbsp;(Global Setting)</td></tr>
        <tr class="subheader"><td colspan=2">This is global setting which can be disabled at department level.</td></tr>
        <tr><th valign="top">New Ticket:</th>
            <td><i>Autoresponse includes the ticket ID required to check status of the ticket</i><br>
                <input type="radio" name="ticket_autoresponder"  value="1"   <?=$config['ticket_autoresponder']?'checked':''?> />Enable
                <input type="radio" name="ticket_autoresponder"  value="0"   <?=!$config['ticket_autoresponder']?'checked':''?> />Disable
            </td>
        </tr>
        <tr><th valign="top">New Message:</th>
            <td><i>Message appended to an existing ticket confirmation</i><br>
                <input type="radio" name="message_autoresponder"  value="1"   <?=$config['message_autoresponder']?'checked':''?> />Enable
                <input type="radio" name="message_autoresponder"  value="0"   <?=!$config['message_autoresponder']?'checked':''?> />Disable
            </td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>&nbsp;Alerts &amp; Notices</td></tr>
        <tr class="subheader"><td colspan=2>
            Notices sent to user use 'No Reply Email' whereas alerts to staff use 'Alert Email' set above as FROM address respectively.</td>
        </tr>
        <tr><th valign="top">New Ticket Alert:</th>
            <td>
                <input type="radio" name="ticket_alert_active"  value="1"   <?=$config['ticket_alert_active']?'checked':''?> />Enable
                <input type="radio" name="ticket_alert_active"  value="0"   <?=!$config['ticket_alert_active']?'checked':''?> />Disable
                <br><i>Select recipients</i>&nbsp;<font class="error">&nbsp;<?=$errors['ticket_alert_active']?></font><br>
                <input type="checkbox" name="ticket_alert_admin" <?=$config['ticket_alert_admin']?'checked':''?>> Admin Email
                <input type="checkbox" name="ticket_alert_dept_manager" <?=$config['ticket_alert_dept_manager']?'checked':''?>> Department Manager
                <input type="checkbox" name="ticket_alert_dept_members" <?=$config['ticket_alert_dept_members']?'checked':''?>> Department Members (spammy)
            </td>
        </tr>
        <tr><th valign="top">New Message Alert:</th>
            <td>
              <input type="radio" name="message_alert_active"  value="1"   <?=$config['message_alert_active']?'checked':''?> />Enable
              <input type="radio" name="message_alert_active"  value="0"   <?=!$config['message_alert_active']?'checked':''?> />Disable
              <br><i>Select recipients</i>&nbsp;<font class="error">&nbsp;<?=$errors['message_alert_active']?></font><br>
              <input type="checkbox" name="message_alert_laststaff" <?=$config['message_alert_laststaff']?'checked':''?>> Last Respondent
              <input type="checkbox" name="message_alert_assigned" <?=$config['message_alert_assigned']?'checked':''?>> Assigned Staff
              <input type="checkbox" name="message_alert_dept_manager" <?=$config['message_alert_dept_manager']?'checked':''?>> Department Manager (spammy)
            </td>
        </tr>
        <tr><th valign="top">Overdue Ticket Alert:</th>
            <td>
              <input type="radio" name="overdue_alert_active"  value="1"   <?=$config['overdue_alert_active']?'checked':''?> />Enable
              <input type="radio" name="overdue_alert_active"  value="0"   <?=!$config['overdue_alert_active']?'checked':''?> />Disable
              <br><i>Admin Email gets an alert by default. Select additional recipients below</i>&nbsp;<font class="error">&nbsp;<?=$errors['overdue_alert_active']?></font><br>
              <input type="checkbox" name="overdue_alert_assigned" <?=$config['overdue_alert_assigned']?'checked':''?>> Assigned Staff
              <input type="checkbox" name="overdue_alert_dept_manager" <?=$config['overdue_alert_dept_manager']?'checked':''?>> Department Manager
              <input type="checkbox" name="overdue_alert_dept_members" <?=$config['overdue_alert_dept_members']?'checked':''?>> Department Members (spammy)
            </td>
        </tr>
        <tr><th valign="top">Ticket Denied Notice:</th>
            <td class="mainTableAlt"><i>Sent <b>only once</b> on limit violation to the user. <b>Admin gets alerts on ALL denials by default</b>.</i><br>
                <input type="checkbox" name="overlimit_notice_active" <?=$config['overlimit_notice_active'] ?'checked':''?>> Send overlimit notice
            </td>      
        </tr>
        <tr><th valign="top">System Errors:</th>
            <td><i>Enabled errors are sent to admin email set above</i><br>
              <input type="checkbox" name="send_sql_errors" <?=$config['send_sql_errors']?'checked':''?>>SQL errors
              <input type="checkbox" name="send_mailparse_errors" <?=$config['send_mailparse_errors']?'checked':''?>>Mail Parse Errors
              <input type="checkbox" name="send_login_errors" <?=$config['send_login_errors']?'checked':''?>>Excessive Login attempts
            </td>
        </tr> 
        
    </table>
 </td></tr>
 <tr>
    <td style="padding:10px 0 10px 240px;">
        <input class="button" type="submit" name="submit" value="Save Changes">
        <input class="button" type="reset" name="reset" value="Reset Changes">
    </td>
 </tr>
 </form>
</table>
