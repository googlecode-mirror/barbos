<?php
if(!defined('OSTADMININC') || basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Habari/Jambo rafiki? '); //Say hi to our friend..
if(!$thisuser || !$thisuser->isadmin()) die('Access Denied');

$info=($_POST && $errors)?$_POST:array(); //Re-use the post info on error...savekeyboards.org
if($email && $_REQUEST['a']!='new'){
    $title='Edit Email'; 
    $action='update';
    $sql='SELECT *,e.email_id FROM '.EMAIL_TABLE.' e LEFT JOIN '.POP3_TABLE.' USING(email_id) WHERE e.email_id='.db_input($email->getId());
    $info=$info?$info:db_fetch_array(db_query($sql));
}else {
   $title='New Email';
   $action='create';
   $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
}

$info=Format::htmlchars($info);
//get the goodies.
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);
$priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
?>
<div class="msg"><?=$title?></div>
<table width="98%" border="0" cellspacing=0 cellpadding=0>
<form action="admin.php" method="post">
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'])?>">
 <input type="hidden" name="t" value="email">
 <input type="hidden" name="email_id" value="<?=$info['email_id']?>">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Email Info</td></tr>
        <tr class="subheader">
            <td colspan=2 >Settings are mainly for emailed tickets (POP3 &amp; Pipe). For online/web tickets see help topics.</td>
        </tr>
        <tr><th>Email Address</th>
            <td>
                <input type="text" name="email" size=30 value="<?=$info['email']?>">&nbsp;<font class="error">*&nbsp;<?=$errors['email']?></font>
            </td>
        </tr>
        <tr><th>Email Name:</th>
            <td>
                <input type="text" name="name" size=30 value="<?=$info['name']?>">&nbsp;<font class="error">&nbsp;<?=$errors['name']?></font>
                &nbsp;&nbsp;(<i>Optional email's FROM name.</i>)
            </td>
        </tr>
        <tr><th>New Ticket Priority</th>
            <td>
                <select name="priority_id">
                    <option value=0>Select Priority</option>
                    <?
                    while (list($id,$name) = db_fetch_row($priorities)){
                        $selected = ($info['priority_id']==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['priority_id']?></font>
            </td>
        </tr>
        <tr><th>New Ticket Dept.</th>
            <td>
                <select name="dept_id">
                    <option value=0>Select Department</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                        $selected = ($info['dept_id']==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?> Dept</option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">&nbsp;<?=$errors['dept_id']?></font>&nbsp;
            </td>
        </tr>
        <tr><th>Auto Response</th>
            <td>
                <input type="checkbox" name="noautoresp" value=1 <?=$info['noautoresp']? 'checked': ''?> ><b>Disable</b> autoresponse for this email.
                &nbsp;&nbsp;(<i>Overwrite Dept setting</i>)
            </td>
        </tr>
        <tr class="header"><td colspan=2>POP3 Setting (Optional)</b></td></tr>
        <tr class="subheader"><td colspan=2>
            <b>Please be patient, the system will try to login to mail server to validate the entered login info.</b></td></tr>
        <tr><th>POP3 Status</th>
            <td>
                <input type="radio" name="popenabled"  value="1"   <?=$info['popenabled']?'checked':''?> />Enable
                <input type="radio" name="popenabled"  value="0"   <?=!$info['popenabled']?'checked':''?> />Disable
                &nbsp;<font class="error">&nbsp;<?=$errors['popenabled']?></font>
            </td>
        </tr>
        <tr><th>POP3 Host</th>
            <td><input type="text" name="pophost" size=25 value="<?=$info['pophost']?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['pophost']?></font>
            </td>
        </tr>
        <tr><th>POP3 User</th>
            <td class="mainTableAlt"><input type="text" name="popuser" size=25 value="<?=$info['popuser']?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['popuser']?></font>
            </td>
        </tr>
        <tr><th>POP3 Password</th>
            <td><input type="password" name="poppasswd" size=25 AUTOCOMPLETE=OFF  value="<?=$info['poppasswd']?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['poppasswd']?></font>
            </td>
        </tr>
        <tr><th>Fetch Frequency</th>
            <td>
                <input type="text" name="fetchfreq" size=6 value="<?=$info['fetchfreq']?>"> Delay intervals in minutes
                &nbsp;<font class="error">&nbsp;<?=$errors['fetchfreq']?></font>
            </td>
        </tr>
        <tr><th>Delete Messages</th>
            <td>
                <input type="checkbox" name="delete_msgs" value=1 <?=$info['delete_msgs']? 'checked': ''?> > Delete fetched message(s)
                &nbsp;<font class="error">&nbsp;<?=$errors['delete_msgs']?></font>
            </td>
        </tr>
    </table>
   </td></tr>
   <tr><td style="padding:10px 0 10px 220px;">
            <input class="button" type="submit" name="submit" value="Submit">
            <input class="button" type="reset" name="reset" value="Reset">
            <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="admin.php?t=staff"'>
        </td>
     </tr>
</form>
</table>
