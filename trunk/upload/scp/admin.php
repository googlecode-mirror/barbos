<?php
/*********************************************************************
    admin.php

    Handles all admin related pages....everything admin!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('staff.inc.php');
//Make sure the user is admin type LOCKDOWN BABY!
if(!$thisuser or !$thisuser->isadmin()){
    header('Location: index.php');
    require('index.php'); // just in case!
    exit;
}
//Access checked out OK...lets do the do 
define('OSTADMININC',TRUE); //checked by admin include files
define('ADMINPAGE',TRUE);   //Used by the header to swap menus.

//Files we might need.
//TODO: Do on-demand require...save some mem.
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.email.php');
require_once(INCLUDE_DIR.'class.pop3.php');
require_once(INCLUDE_DIR.'class.banlist.php');

//Handle a POST.
if($_POST && $_REQUEST['t']):
    //print_r($_POST);
    //WELCOME TO THE HOUSE OF PAIN.
    $errors=array();
    if(!$thisuser->isadmin())
        $errors['err']='You do not have permission for requested action';
    switch(strtolower($_REQUEST['t'])):
        case 'pref':
            //Do the dirty work behind the scenes.
            if($cfg->updatePref($_POST,$errors)){
                $msg='Preferences Updated Successfully';
                $cfg->reload();
            }else{
                $errors['err']=$errors['err']?$errors['err']:'Internal Error';
            }
            break;
        case 'attach':
            if($_POST['allow_attachments'] or $_POST['upload_dir']) {
                if(!$_POST['upload_dir'] or !is_writable($_POST['upload_dir'])) {
                    $errors['upload_dir']='Directory must be valid and writeable';
                    if($_POST['allow_attachments'])
                        $errors['allow_attachments']='Invalid upload dir';
                }elseif(!ini_get('file_uploads')) {
                    $errors['allow_attachments']='The \'file_uploads\' directive is disabled in php.ini';
                }
                
                if(!is_numeric($_POST['max_file_size']))
                    $errors['max_file_size']='Maximum file size required';

                if(!$_POST['allowed_filetypes'])
                    $errors['allowed_filetypes']='Allowed file extentions required';
            }
            if(!$errors) {
               $sql= 'UPDATE '.CONFIG_TABLE.' SET allow_attachments='.db_input(isset($_POST['allow_attachments'])?1:0).
                    ',upload_dir='.db_input($_POST['upload_dir']). 
                    ',max_file_size='.db_input($_POST['max_file_size']).
                    ',allowed_filetypes='.db_input(strtolower(ereg_replace("/\n\r|\r\n|\n|\r/", '',$_POST['allowed_filetypes']))).
                    ',email_attachments='.db_input(isset($_POST['email_attachments'])?1:0).
                    ',allow_email_attachments='.db_input(isset($_POST['allow_email_attachments'])?1:0).
                    ',allow_online_attachments='.db_input(isset($_POST['allow_online_attachments'])?1:0).
                    ',allow_online_attachments_onlogin='.db_input(isset($_POST['allow_online_attachments_onlogin'])?1:0).
                    ' WHERE id='.$cfg->getId();
               //echo $sql;
               if(db_query($sql)) {
                   $cfg->reload();
                   $msg='Attachments settings updated';
               }else{
                    $errors['err']='Update error!';
               }
            }else {
                $errors['err']='Error occured. See error messages below.';
                    
            }
            break;
        case 'api':
            if(!$_POST['api_whitelist']) {
                $errors['api_whitelist']='You must enter hosts to whitelist';
            }else {
                foreach(array_filter(explode(",",ereg_replace("/\n\r|\r\n|\n|\r/", '',$_POST['api_whitelist']))) as $ip) {
                    if(!Validator::is_ip($ip)) {
                        $errors['api_whitelist']='Invalid IP ['.$ip.']';
                        break;
                    }
                }
            }
            if(!$_POST['api_key'])
                $errors['api_key']='API key required';
            elseif(str_word_count($_POST['api_key'])<3)
                $errors['api_key']='Phrase of 2+ words required';
            elseif(strlen($_POST['api_key'])<8)
                $errors['api_key']='phrase is too short!';
        
            if(!$errors) {
                $sql='UPDATE '.CONFIG_TABLE.' SET api_whitelist='.db_input(ereg_replace("/\n\r|\r\n|\n|\r/", ' ',$_POST['api_whitelist'])).
                    ',api_key='.db_input(Format::striptags($_POST['api_key'])).
                    ' WHERE id='.$cfg->getId();
                if(db_query($sql)) {
                    $cfg->reload();
                    $msg='API settings updated';                   
                }else{
                    $errors['err']='Update error!';
                }
            }
            break;
        case 'banlist': //BanList.
            switch(strtolower($_POST['a'])) {
                case 'add':
                    if(!$_POST['email'] || !Validator::is_email($_POST['email']))
                        $errors['err']='Please enter a valid email.';
                    elseif(BanList::isbanned($_POST['email']))
                        $errors['err']='Email already banned';
                    else{
                        if(BanList::add($_POST['email'],$thisuser->getName()))
                            $msg='Email added to banlist';
                        else
                            $errors['err']='Unable to add email to banlist. Try again';
                    }
                    break;
                case 'remove':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='You must select at least one email';
                    }else{
                        //TODO: move mass remove to Banlist class when needed elsewhere...at the moment this is the only place.
                        $sql='DELETE FROM '.BANLIST_TABLE.' WHERE id IN ('.implode(',',$_POST['ids']).')';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected emails removed from banlist";
                        else
                            $errors['err']='Unable to make remove selected emails. Try again.';
                    }
                    break;
                default:
                    $errors['err']='Uknown banlist command!';
            }
            break;
        case 'email':
            $do=strtolower($_POST['do']);
            switch($do){
                case 'update':
                case 'create':
                    //very basic checks
                    if(!$_POST['email'] || !Validator::is_email($_POST['email'])){
                        $errors['email']='Valid email required';
                    }else{
                        $sql='SELECT email_id FROM '.EMAIL_TABLE.' WHERE email='.db_input($_POST['email']);
                        if($_POST['email_id'])
                            $sql.=' AND email_id!='.db_input($_POST['email_id']);
                        if(db_num_rows(db_query($sql)))
                            $errors['email']='Email already exits';
                    }
                    if(!is_numeric($_POST['dept_id']))
                        $errors['dept_id']='You must select a Dept.';
                    if(!$_POST['priority_id'])
                        $errors['priority_id']='You must select a priority';

                    if($_POST['popenabled']) {
                        //Check pop info only when enabled.
                        if(!function_exists('imap_open'))
                            $errors['popenabled']= "IMAP doesn't exist. PHP must be compiled with IMAP enabled.";
                        if(!$_POST['pophost'])
                            $errors['pophost']='Host missing';
                        if(!$_POST['popuser'])
                            $errors['popuser']='Username missing';
                        if(!$_POST['poppasswd'])
                            $errors['poppasswd']='Password required';
                        if(!$_POST['fetchfreq'])
                            $errors['fetchfreq']='Fetch interval required';
                    }
                        
                    if(!$errors && ($_POST['pophost'] && $_POST['popuser'])){
                        $sql='SELECT email_id FROM '.POP3_TABLE.' WHERE pophost='.db_input($_POST['pophost']).' AND popuser='.db_input($_POST['popuser']);
                        if($_POST['email_id'])
                             $sql.=' AND email_id!='.db_input($_POST['email_id']);
                        if(db_num_rows(db_query($sql)))
                            $errors['popuser']=$errors['pophost']='Another department using host/user combination.';
                        elseif($_POST['popenabled']) { //DISABLED for testing.
                            $pop3 = new POP3($_POST['popuser'],$_POST['poppasswd'],$_POST['pophost']);
                            if(!$pop3->connect())
                                $errors['poppasswd']='Invalid login ['.$pop3->getLastError().']';
                        }
                    }

                    if(!$errors) {
                        $sql='updated=NOW(),email='.db_input($_POST['email']).',name='.db_input(Format::striptags($_POST['name'])).
                            ',dept_id='.db_input($_POST['dept_id']).
                            ',priority_id='.db_input($_POST['priority_id']).
                            ',noautoresp='.db_input(isset($_POST['noautoresp'])?1:0);
                        if($do=='create'){ //create
                            $sql='INSERT INTO '.EMAIL_TABLE.' SET '.$sql.',created=NOW()';
                            if(!db_query($sql) or !($emailID=db_insert_id()))
                                $errors['err']='Unable to add email. Internal error';
                            else
                                $msg='Email added successfully';
                        }elseif($do=='update' && $_POST['email_id']){ //update
                            $sql='UPDATE '.EMAIL_TABLE.' SET '.$sql.' WHERE email_id='.db_input($_POST['email_id']);
                            if(!db_query($sql) || !db_affected_rows())
                                $errors['err']='Unable to update email. Internal error occured';
                            else
                                $msg='Email updated successfully';
                        }else{
                            $errors['err']='Internal error occured';
                        }

                        //Save/update POP3 info
                        if(!$errors && ($_POST['email_id'] or $emailID)){
                            $id=$_POST['email_id']?$_POST['email_id']:$emailID;
                            $sql='REPLACE INTO '.POP3_TABLE.' SET errors=0,email_id='.db_input($id).
                                 ',popenabled='.db_input($_POST['popenabled']).
                                 ',delete_msgs='.db_input(isset($_POST['delete_msgs'])?1:0).
                                 ',fetchfreq='.db_input($_POST['fetchfreq']).
                                 ',pophost='.db_input($_POST['pophost']).
                                 ',popuser='.db_input($_POST['popuser']).
                                 ',poppasswd='.db_input($_POST['poppasswd']);
                            db_query($sql);
                            //Optimize the pop3 table...we are doing replace for now. ( this will change shortly)
                            @db_query('OPTIMIZE TABLE '.POP3_TABLE);
                        }
                            
                    }else{
                        $errors['err']='Error(s) Occured. Try again';
                    }
                    break;
                case 'mass_process':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='You must select at least one email to process';
                    }else{
                        $count=count($_POST['ids']);
                        $ids=implode(',',$_POST['ids']);
                        list($depts)=db_fetch_row(db_query('SELECT count(dept_id) FROM '.DEPT_TABLE.' WHERE email_id IN ('.$ids.')'));
                        if($depts>0){
                            $errors['err']='One or more of the selected emails is being used by a Dept. Remove association first.';    
                        }elseif($_POST['delete']){
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if(Email::deleteEmail($v)) $i++;
                            }
                            if($i>0){
                                $msg="$i of $count selected email(s) deleted";
                            }else{
                                $errors['err']='Unable to delete selected email(s).';
                            }
                        }else{
                            $errors['err']='Unknown command';
                        }
                    }
                    break;
                default:
                    $errors['err']='Unknown topic action!';
            }
            break;
        case 'templates':
            $do=strtolower($_POST['do']);
            switch($do){
                    case 'update':
                        if(!$_POST['id'] && $do=='update')
                            $errors['err']='Internal Error';
                        if(!$_POST['ticket_autoresp_subj'])
                            $errors['ticket_autoresp_subj']='subject missing';
                        if(!$_POST['ticket_autoresp_body'])
                            $errors['ticket_autoresp_body']='Template message required';
                        if(!$_POST['message_autoresp_subj'])
                            $errors['message_autoresp_subj']='subject missing';
                        if(!$_POST['message_autoresp_body'])
                            $errors['message_autoresp_body']='Template message required';
                        if(!$_POST['ticket_alert_subj'])
                            $errors['ticket_alert_subj']='Alert subject missing';
                        if(!$_POST['ticket_alert_body'])
                            $errors['ticket_alert_body']='Template alert message required';
                        if(!$_POST['message_alert_subj'])
                            $errors['message_alert_subj']='Alert subject missing';
                        if(!$_POST['message_alert_body'])
                            $errors['message_alert_body']='Template alert message required';
                        if(!$_POST['assigned_alert_subj'])
                            $errors['assigned_alert_subj']='Alert subject missing';
                        if(!$_POST['assigned_alert_body'])
                            $errors['assigned_alert_body']='Template alert message required';

                        if(!$_POST['ticket_overlimit_subj'])
                            $errors['ticket_overlimit_subj']='Subject missing';
                        if(!$_POST['ticket_overlimit_body'])
                            $errors['ticket_overlimit_body']='Template message required';

                        if(!$_POST['ticket_overdue_subj'])
                            $errors['ticket_overdue_subj']='Subject missing';
                        if(!$_POST['ticket_overdue_body'])
                            $errors['ticket_overdue_body']='Template message required';

                        if(!$_POST['ticket_reply_subj'])
                            $errors['ticket_reply_subj']='Subject missing';
                        if(!$_POST['ticket_reply_body'])
                            $errors['ticket_reply_body']='Template message required';

                        if(!$errors) {
                            $sql='UPDATE '.EMAIL_TEMPLATE_TABLE.' SET updated=NOW() '.
                                ',ticket_autoresp_subj='.db_input(Format::striptags($_POST['ticket_autoresp_subj'])).
                                ',ticket_autoresp_body='.db_input(Format::striptags($_POST['ticket_autoresp_body'])).
                                ',message_autoresp_subj='.db_input(Format::striptags($_POST['message_autoresp_subj'])).
                                ',message_autoresp_body='.db_input(Format::striptags($_POST['message_autoresp_body'])).
                                ',ticket_alert_subj='.db_input(Format::striptags($_POST['ticket_alert_subj'])).
                                ',ticket_alert_body='.db_input(Format::striptags($_POST['ticket_alert_body'])).
                                ',message_alert_subj='.db_input(Format::striptags($_POST['message_alert_subj'])).
                                ',message_alert_body='.db_input(Format::striptags($_POST['message_alert_body'])).
                                ',assigned_alert_subj='.db_input(Format::striptags($_POST['assigned_alert_subj'])).
                                ',assigned_alert_body='.db_input(Format::striptags($_POST['assigned_alert_body'])).
                                ',ticket_overdue_subj='.db_input(Format::striptags($_POST['ticket_overdue_subj'])).
                                ',ticket_overdue_body='.db_input(Format::striptags($_POST['ticket_overdue_body'])).
                                ',ticket_overlimit_subj='.db_input(Format::striptags($_POST['ticket_overlimit_subj'])).
                                ',ticket_overlimit_body='.db_input(Format::striptags($_POST['ticket_overlimit_body'])).
                                ',ticket_reply_subj='.db_input(Format::striptags($_POST['ticket_reply_subj'])).
                                ',ticket_reply_body='.db_input(Format::striptags($_POST['ticket_reply_body'])).
                                ' WHERE tpl_id='.db_input($_POST['id']);

                            if(!db_query($sql) || !db_affected_rows())
                                $errors['err']='Unable to update. Internal error occured';
                            else
                                $msg='Templates updated successfully';
                        }else{
                            $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Please correct errors below and try again.';
                        }
                        break;
                    default:
                        $errors['err']='Unknown action';
            }
            break;
    case 'topics':
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
            case 'create':
                if(!$_POST['topic'])
                    $errors['topic']='Help topic required';
                elseif(strlen($_POST['topic'])<5)
                    $errors['topic']='Topic is too short. 5 chars minimum';
                if(!$_POST['dept_id'])
                    $errors['dept_id']='You must select a department';
                if(!$_POST['priority_id'])
                    $errors['priority_id']='You must select a priority';
                if(!$errors) {
                    $sql='updated=NOW(),topic='.db_input(Format::striptags($_POST['topic'])).
                        ',dept_id='.db_input($_POST['dept_id']).
                        ',priority_id='.db_input($_POST['priority_id']).
                        ',isactive='.db_input($_POST['isactive']).
                        ',noautoresp='.db_input(isset($_POST['noautoresp'])?1:0);
                    if($do=='create'){ //create
                        $sql='INSERT INTO '.TOPIC_TABLE.' SET '.$sql.',created=NOW()';
                        if(!db_query($sql) or !($topicID=db_insert_id()))
                            $errors['err']='Unable to create the topic. Internal error';
                        else
                            $msg='Help topic created successfully';
                    }elseif($do=='update' && $_POST['topic_id']){ //update
                        $sql='UPDATE '.TOPIC_TABLE.' SET '.$sql.' WHERE topic_id='.db_input($_POST['topic_id']);
                        if(!db_query($sql) || !db_affected_rows())
                            $errors['err']='Unable to update topic. Internal error occured';
                        else
                            $msg='Help topic updated successfully';
                    }else{
                        $errors['err']='Internal error occured';
                    }
                }else{
                    $errors['err']='Error(s) Occured. Try again';
                }
                break;
            case 'mass_process':
                if(!$_POST['tids'] || !is_array($_POST['tids'])) {
                    $errors['err']='You must select at least one topic';
                }else{
                    $count=count($_POST['tids']);
                    $ids=implode(',',$_POST['tids']);
                    if($_POST['enable']){
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=1, updated=NOW() WHERE topic_id IN ('.$ids.') AND isactive=0 ';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected services enabled";
                        else
                            $errors['err']='Unable to complete the action.';
                    }elseif($_POST['disable']){
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=0, updated=NOW() WHERE topic_id IN ('.$ids.') AND isactive=1 ';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected topics disabled";
                        else
                            $errors['err']='Unable to disable selected topics';
                    }elseif($_POST['delete']){
                        $sql='DELETE FROM '.TOPIC_TABLE.' WHERE topic_id IN ('.$ids.')';        
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected topics deleted!";
                        else
                            $errors['err']='Unable to delete selected topics';
                    }
                }
                break;
            default:
                $errors['err']='Unknown topic action!';
        }
        break;
    case 'groups':
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
            case 'create':
                //Basic checks
                if(!$_POST['group_id'] && $do=='update')
                    $errors['err']='Missing or invalid group ID';
                if(!$_POST['group_name']) {
                    $errors['group_name']='Group name required';
                }elseif(strlen($_POST['group_name'])<5) {
                     $errors['group_name']='Group name must be at least 5 chars.';
                }elseif($_POST['old_name']!=$_POST['group_name']){
                    $sql='SELECT group_id FROM '.GROUP_TABLE.' WHERE group_name='.db_input($_POST['group_name']);
                    if(db_num_rows(db_query($sql)))
                        $errors['group_name']='Group name already exists';
                }

                if(!$errors){
                    $sql=' SET updated=NOW(), group_name='.db_input(Format::striptags($_POST['group_name'])).
                         ', group_enabled='.db_input($_POST['group_enabled']).
                         ', dept_access='.db_input($_POST['depts']?implode(',',$_POST['depts']):'').
                         ', can_delete_tickets='.db_input($_POST['can_delete_tickets']).
                         ', can_transfer_tickets='.db_input($_POST['can_transfer_tickets']).
                         ', can_close_tickets='.db_input($_POST['can_close_tickets']).
                         ', can_ban_emails='.db_input($_POST['can_ban_emails']).
                         ', can_manage_kb='.db_input($_POST['can_manage_kb']);
                    //echo $sql;
                    if($do=='create'){ //create
                        $res=db_query('INSERT INTO '.GROUP_TABLE.' '.$sql.',created=NOW()');
                        if(!$res or !($gID=db_insert_id()))
                            $errors['err']='Unable to create the group. Internal error';
                        else
                            $msg='Group '.Format::htmlchars($_POST['group_name']).' created';
                            
                    }elseif($do=='update'){ //update
                
                        $res=db_query('UPDATE '.GROUP_TABLE.' '.$sql.' WHERE group_id='.db_input($_POST['group_id']));
                        if(!$res || !db_affected_rows())
                            $errors['err']='Internal error occured';
                        else
                            $msg='Group '.Format::htmlchars($_POST['group_name']).' updated';
                    }
                }else{
                    $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Try again';
                }
                break;
            default:
                //ok..at this point..look WMA.
                if($_POST['grps'] && is_array($_POST['grps'])) {
                    $ids=implode(',',$_POST['grps']);
                    $selected=count($_POST['grps']);
                    if(isset($_POST['activate_grps'])) {
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=1,updated=NOW() WHERE group_enabled=0 AND group_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected groups Enabled";
                    }elseif(in_array($thisuser->getDeptId(),$_POST['grps'])) {
                          $errors['err']="Trying to 'Disable' or 'Delete' your group? Doesn't make any sense!";
                    }elseif(isset($_POST['disable_grps'])) {
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=0, updated=NOW() WHERE group_enabled=1 AND group_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected groups Disabled"; 
                    }elseif(isset($_POST['delete_grps'])) {
                        $res=db_query('SELECT staff_id FROM '.STAFF_TABLE.' WHERE group_id IN('.$ids.')');
                        if(!$res || db_num_rows($res)) { //fail if any of the selected groups has users.
                            $errors['err']='One or more of the selected groups have users. Only empty groups can be deleted.';
                        }else{
                            db_query('DELETE FROM '.GROUP_TABLE.' WHERE group_id IN('.$ids.')');    
                            $msg=db_affected_rows()." of  $selected selected groups Deleted";
                        }
                    }else{
                         $errors['err']='Uknown command!';
                    }
                    
                }else{
                    $errors['err']='No groups selected.';
                }
        }
    break;
    case 'staff':
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
            case 'create':
                if(!$_POST['staff_id'] && $do=='update')
                    $errors['err']='Internal Error';
                if(!$_POST['firstname'] || !$_POST['lastname'])
                    $errors['name']='First and last name required';

                if(!$_POST['username'] || strlen($_POST['username'])<3)
                    $errors['username']='Username required';
                else{
                    //check if the username is already in-use.
                    $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE username='.db_input($_POST['username']);
                    if($_POST['staff_id'])
                        $sql.=' AND staff_id!='.db_input($_POST['staff_id']);
                    if(db_num_rows(db_query($sql)))
                        $errors['username']='Username already in-use';
                }
                if(!$_POST['email'] || !Validator::is_email($_POST['email']))
                    $errors['email']='Valid email required';
                                
                if($_POST['phone'] && !Validator::is_phone($_POST['phone']))
                    $errors['phone']='Valid number required';
                                
                if($_POST['mobile'] && !Validator::is_phone($_POST['mobile']))
                    $errors['mobile']='Valid number required';
                                
                
                if($_POST['npassword'] || $_POST['vpassword'] || $do=='create'){
                    if(!$_POST['npassword'] && $do=='create')
                        $errors['npassword']='Temp password required';
                    elseif($_POST['npassword'] && $_POST['npassword']!==$_POST['vpassword'])
                        $errors['vpassword']='Password(s) do not match';
                    elseif($_POST['npassword'] && strlen($_POST['npassword'])<6)
                        $errors['npassword']='Must be atleast 6 characters';
                }
                if(!$_POST['dept_id'])
                    $errors['dept']='Department required';
                if(!$_POST['group_id'])
                    $errors['group']='Group required';
                    
                if(!$errors){

                   $sql=' SET updated=NOW() '.
                        ',isadmin='.db_input($_POST['isadmin']).
                        ',isactive='.db_input($_POST['isactive']).
                        ',isvisible='.db_input(isset($_POST['isvisible'])?1:0).
                        ',onvacation='.db_input(isset($_POST['onvacation'])?1:0).
                        ',dept_id='.db_input($_POST['dept_id']).
                        ',group_id='.db_input($_POST['group_id']).
                        ',username='.db_input(Format::striptags($_POST['username'])).
                        ',firstname='.db_input(Format::striptags($_POST['firstname'])).
                        ',lastname='.db_input(Format::striptags($_POST['lastname'])).
                        ',email='.db_input($_POST['email']).
                        ',phone='.db_input($_POST['phone']).
                        ',phone_ext='.db_input($_POST['phone_ext']).
                        ',mobile='.db_input($_POST['mobile']).
                        ',signature='.db_input(Format::striptags($_POST['signature']));
                   if($_POST['npassword'])
                       $sql.=',passwd='.db_input(md5($_POST['npassword']));
                   if(isset($_POST['resetpasswd']))
                       $sql.=',change_passwd=1';


                    if($do=='create'){ //create
                        $sql='INSERT INTO '.STAFF_TABLE.' '.$sql.',created=NOW()';
                        if(!db_query($sql) or !($uID=db_insert_id()))
                            $errors['err']='Unable to create user. Internal error';
                        else
                            $msg='User created successfully';
                    }elseif($do=='update'){ //update
                        $sql='UPDATE '.STAFF_TABLE.' '.$sql.' WHERE staff_id='.db_input($_POST['staff_id']);
                        if(!db_query($sql) || !db_affected_rows())
                            $errors['err']='Unable to update the user. Internal error occured';
                        else
                            $msg='Staff profile updated';
                    }
                    //echo $sql;
                }else{
                    $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Try again.';
                }
            break;
            case 'mass_process':
                //ok..at this point..look WMA.
                if($_POST['uids'] && is_array($_POST['uids'])) {
                    $ids=implode(',',$_POST['uids']);
                    $selected=count($_POST['uids']);
                    if(isset($_POST['enable'])) {
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=1,updated=NOW() WHERE isactive=0 AND staff_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected users enabled";
                    
                    }elseif(in_array($thisuser->getId(),$_POST['uids'])) {
                        //sucker...watch what you are doing...why don't you just DROP the DB?
                        $errors['err']='You can not lock or delete yourself!';  
                    }elseif(isset($_POST['disable'])) {
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=0, updated=NOW() '.
                            ' WHERE isactive=1 AND staff_id IN('.$ids.') AND staff_id!='.$thisuser->getId();
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected users locked";
                        //Release tickets assigned to the user?? NO? could be a temp thing 
                        // May be auto-release if not logged in for X days? 
                    }elseif(isset($_POST['delete'])) {
                        db_query('DELETE FROM '.STAFF_TABLE.' WHERE staff_id IN('.$ids.') AND staff_id!='.$thisuser->getId());
                        $msg=db_affected_rows()." of  $selected selected users deleted";
                        //Demote the user 
                        db_query('UPDATE '.DEPT_TABLE.' SET manager_id=0 WHERE manager_id IN('.$ids.') ');
                        db_query('UPDATE '.TICKET_TABLE.' SET staff_id=0 WHERE staff_id IN('.$ids.') ');
                    }else{
                        $errors['err']='Uknown command!';
                    }
                }else{
                    $errors['err']='No users selected.';
                }
            break;
            default:
                $errors['err']='Uknown command!';
        }
    break;
    case 'dept':
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
            case 'create':
                //Basic checks
                if(!$_POST['dept_id'] && $do=='update')
                    $errors['err']='Missing or invalid Dept ID';
                if(!is_numeric($_POST['email_id'])) //email_id 0 means use no reply email.
                    $errors['email_id']='Dept email required';
                if(!$_POST['dept_name']) {
                    $errors['dept_name']='Dept name required';
                }elseif(strlen($_POST['dept_name'])<4) {
                     $errors['dept_name']='Dept name must be at least 4 chars.';
                }else{
                    $sql='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_name='.db_input($_POST['dept_name']);
                    if($_POST['dept_id'])
                        $sql.=' AND dept_id!='.db_input($_POST['dept_id']);
                    if(db_num_rows(db_query($sql)))
                        $errors['dept_name']='Department already exist';
                }
                
                if($_POST['ispublic'] && !$_POST['dept_signature'])
                    $errors['dept_signature']='Signature required';
                
                if(!$_POST['ispublic'] && ($_POST['dept_id']==$cfg->getDefaultDeptId()))
                    $errors['ispublic']='Default department can not be private';
                
                if(!$errors){

                    $sql=' SET updated=NOW() '.
                        ',ispublic='.db_input($_POST['ispublic']).
                        ',email_id='.db_input($_POST['email_id']).
                        ',manager_id='.db_input($_POST['manager_id']?$_POST['manager_id']:0).
                        ',dept_name='.db_input(Format::striptags($_POST['dept_name'])).
                        ',dept_signature='.db_input(Format::striptags($_POST['dept_signature'])).
                        ',noreply_autoresp='.db_input($_POST['noreply_autoresp']).
                        ',ticket_auto_response='.db_input($_POST['ticket_auto_response']).
                        ',message_auto_response='.db_input($_POST['message_auto_response']).
                        ',can_append_signature='.db_input(isset($_POST['can_append_signature'])?1:0);
                    
                    if($do=='create'){ //create
                        $sql='INSERT INTO '.DEPT_TABLE.' '.$sql.',created=NOW()';
                        if(!db_query($sql) or !($deptID=db_insert_id()))
                            $errors['err']='Unable to create department. Internal error';
                        else
                            $msg='Department created successfully';
                    }elseif($do=='update' && $_POST['dept_id']){ //update
                        $sql='UPDATE '.DEPT_TABLE.' '.$sql.' WHERE dept_id='.db_input($_POST['dept_id']);
                        if(!db_query($sql) || !db_affected_rows())
                            $errors['err']='Unable to update '.$_POST['dept_name'].' Dept. Internal error occured';
                        else
                            $msg=$_POST['dept_name'].' Department updated';
                    }else{
                        $errors['err']='Internal error occured';
                    }
                }else{
                    $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Try again.'; 
                }
                break;
            case 'mass_process':
                if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                    $errors['err']='You must select at least one department';
                }elseif(!$_POST['public'] && in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                    $errors['err']='You can not disable/delete a default department. Remove default Dept and try again.';
                }else{
                    $count=count($_POST['ids']);
                    $ids=implode(',',$_POST['ids']);
                    if($_POST['public']){
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 WHERE dept_id IN ('.$ids.')';  
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $warn="$num of $count selected departments made public";
                        else
                            $errors['err']='Unable to make depts public.';
                    }elseif($_POST['private']){
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0 WHERE dept_id IN ('.$ids.') AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            $warn="$num of $count selected departments made private";
                        }else
                            $errors['err']='Unable to make selected department(s) private. Possibly already private!';
                            
                    }elseif($_POST['delete']){
                        //Deny all deletes if one of the selections has members in it.
                        $sql='SELECT count(staff_id) FROM '.STAFF_TABLE.' WHERE dept_id IN ('.$ids.')';
                        list($members)=db_fetch_row(db_query($sql));
                        $sql='SELECT count(topic_id) FROM '.TOPIC_TABLE.' WHERE dept_id IN ('.$ids.')';
                        list($topics)=db_fetch_row(db_query($sql));
                        if($members){
                            $errors['err']='Can not delete Dept. with members. Move staff first.';
                        }elseif($topic){
                             $errors['err']='Can not delete Dept. associated with a help topics. Remove association first.';
                        }else{
                            //We have to deal with individual selection because of associated tickets and users.
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if($v==$cfg->getDefaultDeptId()) continue; //Don't delete default dept. Triple checking!!!!!
                                if(Dept::delete($v)) $i++;
                            }
                            if($i>0){
                                $warn="$i of $count selected departments deleted";
                            }else{
                                $errors['err']='Unable to delete selected departments.';
                            }
                        }
                    }
                }
            break;            
            default:
                $errors['err']='Unknown Dept action';
        }
    break;
    default:
        $errors['err']='Uknown command!';
    endswitch;
endif;
    
//================ADMIN MAIN PAGE LOGIC==========================
//Process requested tab.
$thistab=strtolower($_REQUEST['t']?$_REQUEST['t']:'settings');
$inc=$page=''; //No outside crap please!
$submenu=array();
switch($thistab){
    //Preferences & settings
    case 'settings':
    case 'pref':
    case 'attach':
    case 'api':
        $nav->setTabActive('settings');
        $nav->addSubMenu(array('desc'=>'Preferences','href'=>'admin.php?t=pref','iconclass'=>'preferences'));
        $nav->addSubMenu(array('desc'=>'Attachments','href'=>'admin.php?t=attach','iconclass'=>'attachment'));
        $nav->addSubMenu(array('desc'=>'API','href'=>'admin.php?t=api','iconclass'=>'api'));
        switch($thistab):
        case 'settings':            
        case 'pref':        
            $page='preference.inc.php';
            break;
        case 'attach':
            $page='attachment.inc.php';
            break;
        case 'api':
            $page='api.inc.php';
        endswitch;
        break;    
    case 'email':
    case 'templates':
    case 'banlist':
        $nav->setTabActive('emails');
        $nav->addSubMenu(array('desc'=>'Email Config','href'=>'admin.php?t=email','iconclass'=>'emailSettings'));
        $nav->addSubMenu(array('desc'=>'Add New Email','href'=>'admin.php?t=email&a=new','iconclass'=>'newEmail'));
        $nav->addSubMenu(array('desc'=>'Templates','href'=>'admin.php?t=templates','title'=>'Email Templates','iconclass'=>'emailTemplates')); 
        $nav->addSubMenu(array('desc'=>'Banlist','href'=>'admin.php?t=banlist','title'=>'Blocked Emails','iconclass'=>'banList')); 
        switch(strtolower($_REQUEST['t'])){
            case 'templates':
                $page='templates.inc.php';
                break;
            case 'banlist':
                $page='banlist.inc.php';
                break;
            case 'email':
            default:
                include_once(INCLUDE_DIR.'class.email.php');
                $email=null;
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['email_id']) && is_numeric($id)) {
                    $email= new Email($id,false);
                    if(!$email->load()) {
                        $email=null;
                        $errors['err']='Unable to fetch info on email ID#'.$id;
                    }
                }
                $page=($email or ($_REQUEST['a']=='new' && !$emailID))?'email.inc.php':'emails.inc.php';
        }
        break;
    case 'topics':
        require_once(INCLUDE_DIR.'class.topic.php');
        $topic=null;
        $nav->setTabActive('topics');
        $nav->addSubMenu(array('desc'=>'Help Topics','href'=>'admin.php?t=topics','iconclass'=>'helpTopics'));
        $nav->addSubMenu(array('desc'=>'Add New Topic','href'=>'admin.php?t=topics&a=new','iconclass'=>'newHelpTopic'));
        if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['topic_id']) && is_numeric($id)) {
            $topic= new Topic($id);
            if(!$topic->load() && $topic->getId()==$id) {
                $topic=null;
                $errors['err']='Unable to fetch info on topic #'.$id;
            }
        }
        $page=($topic or ($_REQUEST['a']=='new' && !$topicID))?'topic.inc.php':'helptopics.inc.php';
        break;
    //Staff (users, groups and teams)
    case 'grp':
    case 'groups':
    case 'staff':
        $group=$staff=null;
        //Tab and Nav options.
        $nav->setTabActive('staff');
        $nav->addSubMenu(array('desc'=>'Staff Members','href'=>'admin.php?t=staff','iconclass'=>'users'));
        $nav->addSubMenu(array('desc'=>'Add New User','href'=>'admin.php?t=staff&a=new','iconclass'=>'newuser'));
        $nav->addSubMenu(array('desc'=>'User Groups','href'=>'admin.php?t=groups','iconclass'=>'groups'));
        $nav->addSubMenu(array('desc'=>'Add New Group','href'=>'admin.php?t=groups&a=new','iconclass'=>'newgroup'));
        $page='';
        switch($thistab){
            case 'grp':
            case 'groups':
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['group_id']) && is_numeric($id)) {
                    $res=db_query('SELECT * FROM '.GROUP_TABLE.' WHERE group_id='.db_input($id));
                    if(!$res or !db_num_rows($res) or !($group=db_fetch_array($res)))
                        $errors['err']='Unable to fetch info on group ID#'.$id;
                }
                $page=($group or ($_REQUEST['a']=='new' && !$gID))?'group.inc.php':'groups.inc.php';
                break;
            case 'staff':
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['staff_id']) && is_numeric($id)) {
                    $staff = new Staff($id);
                    if(!$staff || !is_object($staff) || $staff->getId()!=$id) {
                        $staff=null;
                        $errors['err']='Unable to fetch info on rep ID#'.$id;
                    }
                }
                $page=($staff or ($_REQUEST['a']=='new' && !$uID))?'staff.inc.php':'staffmembers.inc.php';
                break;
            default:
                $page='staffmembers.inc.php';
        }
        break;
    //Departments
    case 'dept': //lazy
    case 'depts':
        $dept=null;
        if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['dept_id']) && is_numeric($id)) {
            $dept= new Dept($id);
            if(!$dept || !$dept->getId()) {
                $dept=null;
                $errors['err']='Unable to fetch info on Dept ID#'.$id;
            }
        }
        $page=($dept or ($_REQUEST['a']=='new' && !$deptID))?'dept.inc.php':'depts.inc.php';
        $nav->setTabActive('depts');
        $nav->addSubMenu(array('desc'=>'Departments','href'=>'admin.php?t=depts','iconclass'=>'departments'));
        $nav->addSubMenu(array('desc'=>'Add New Dept.','href'=>'admin.php?t=depts&a=new','iconclass'=>'newDepartment'));
        break;
    // (default)
    default:
        $page='pref.inc.php';
}
//========================= END ADMIN PAGE LOGIC ==============================//

$inc=($page)?STAFFINC_DIR.$page:'';
//Now lets render the page...
require(STAFFINC_DIR.'header.inc.php');
?>
<div>
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p align="center" id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<table width="100%" border="0" cellspacing="0" cellpadding="1">
    <tr><td>
        <div style="margin:0 5px 5px 5px;">
        <?
            if($inc && file_exists($inc)){
                require($inc);
            }else{?>
                <p align="center">
                    <font class="error">Problems loading requested admin page. (<?=$thistab?>)</font>
                    <br>Possibly access denied, if you believe this is in error please get technical support.
                </p>
            <?}?>
        </div>
    </td></tr>
</table>
<?
include_once(STAFFINC_DIR.'footer.inc.php');
?>
