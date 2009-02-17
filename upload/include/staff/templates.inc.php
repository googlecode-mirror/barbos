<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Access Denied');
$tpl=null;
$id=1;
$sql='SELECT * FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id=1 AND cfg_id='.db_input($cfg->getId());
$tpl=Format::htmlchars(($errors && $_POST)?$_POST:db_fetch_array(db_query($sql)));
?>
<div class="msg">Email Templates</div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
  <form action="admin.php?t=templates" method="post">
    <input type="hidden" name="t" value="templates">
    <input type="hidden" name="do" value="update">
    <input type="hidden" name="id" value="1">
    <tr><td>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform tpl">
            <tr class="header"><td colspan=2 >New Ticket Autoresponse</td></tr>
            <tr class="subheader"><td colspan=2 >
                Autoresponse sent to user on new ticket if enabled. 
                Meant to give the user the ticket ID which can be used to check the status of the ticket.</td>
                </tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="ticket_autoresp_subj" value="<?=$tpl['ticket_autoresp_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_autoresp_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="ticket_autoresp_body"><?=$tpl['ticket_autoresp_body']?></textarea>
                        &nbsp;<font class="error">&nbsp;<?=$errors['ticket_autoresp_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >New Message Autoresponse</td></tr>
            <tr class="subheader"><td colspan=2 > 
                Confirmation sent to user when a new message is appended to an existing ticket. (Email and online replies)</td>
            </tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="message_autoresp_subj" value="<?=$tpl['message_autoresp_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['message_autoresp_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="message_autoresp_body"><?=$tpl['message_autoresp_body']?></textarea>
                            &nbsp;<font class="error">&nbsp;<?=$errors['message_autoresp_body']?></font></td>
            </tr>
            <tr class="header"><td  colspan=2 >Over Ticket limit Notice</td></tr>
            <tr class="subheader"><td colspan=2 >
                A one time notice sent when user has reached the max allowed open tickets defined in preferences.
                <br/>Admin email gets alert each time a support ticket request is denied.
            </td></tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="ticket_overlimit_subj" value="<?=$tpl['ticket_overlimit_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overlimit_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="ticket_overlimit_body"><?=$tpl['ticket_overlimit_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overlimit_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >&nbsp;Ticket Response/Reply</td></tr>
            <tr class="subheader"><td colspan=2 >
                Message template used when responding to a ticket or simply alerting the user about a response/answer availability.
            </td></tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="ticket_reply_subj" value="<?=$tpl['ticket_reply_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_reply_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</td>
                <td><textarea rows="7" cols="65" name="ticket_reply_body"><?=$tpl['ticket_reply_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_reply_body']?></font></td>
            </tr>
        </table>
        <span class="msg">Staff Alerts Templates.</span>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform tpl">
            <tr class="header"><td colspan=2 >New Ticket Alert</td></tr>
            <tr class="subheader"><td colspan=2 >Alert sent to staff ( if enabled) on new ticket.</td></tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="ticket_alert_subj" value="<?=$tpl['ticket_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="ticket_alert_body"><?=$tpl['ticket_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_alert_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >New Message Alert</td></tr>
            <tr class="subheader"><td colspan=2 >Alert sent to staff ( if enabled) when user replies to an existing ticket.</td></tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="message_alert_subj" value="<?=$tpl['message_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['message_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="message_alert_body"><?=$tpl['message_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['message_alert_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >Ticket Assigned Alert/Notice</td></tr>
            <tr class="subheader"><td colspan=2 >Alert sent to staff on ticket assignment.</td></tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="assigned_alert_subj" value="<?=$tpl['assigned_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['assigned_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="assigned_alert_body"><?=$tpl['assigned_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['assigned_alert_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >Overdue/Stale Ticket Alert/Notice</td></tr>
            <tr class="subheader"><td colspan=2 >Alert sent to staff on stale or overdue tickets.</td></tr>
            <tr>
                <th>Subject</th>
                <td>
                    <input type="text" size="65" name="ticket_overdue_subj" value="<?=$tpl['ticket_overdue_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overdue_subj']?></font></td>
            </tr>
            <tr>
                <th>Message Body:</th>
                <td><textarea rows="7" cols="65" name="ticket_overdue_body"><?=$tpl['ticket_overdue_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overdue_body']?></font></td>
            </tr>
        </table>
    </td></tr>
    <tr><td style="padding-left:165px">
        <input class="button" type="submit" name="submit" value="Save Changes">
        <input class="button" type="reset" name="reset" value="Reset Changes">
        <input class="button" type="button" name="cancel" value="Cancel Edit" onClick='window.location.href="admin.php?t=email"'></td>
    </tr>
  </form>
</table>
