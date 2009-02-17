<?php
/*************************************************************************
    tickets.php
    
    Handles all tickets related actions.
 
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.banlist.php');

$page='';
$ticket=null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['ticket_id']) && is_numeric($id)) {
    $deptID=0;
    $ticket= new Ticket($id);
    if(!$ticket or !$ticket->getDeptId())
        $err='Unknown ticket ID#'.$id; //Sucker...invalid id
    elseif(!$thisuser->isAdmin()  && (!$thisuser->canAccessDept($ticket->getDeptId()) && $thisuser->getId()!=$ticket->getStaffId()))
        $err='Access denied. Contact admin if you believe this is in error';
    
    if(!$err && $ticket->getId()==$id)
        $page='viewticket.inc.php';
}elseif($_REQUEST['a']=='open') {
    //TODO: Check perm here..
    $page='newticket.inc.php';
}
//At this stage we know the access status. we can process the post.
if($_POST && !$err):

    if($ticket && $ticket->getId()) {
        //More tea please.
        $errors=array();
        $lock=$ticket->getLock(); //Ticket lock if any
        $statusKeys=array('open'=>'Open','Reopen'=>'Open','Close'=>'Closed');
        switch(strtolower($_POST['a'])):
        case 'reply':
            $fields=array();
            $fields['msg_id']       = array('type'=>'int',  'required'=>1, 'error'=>'Missing message ID');
            $fields['response']     = array('type'=>'text', 'required'=>1, 'error'=>'Response message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }
            //Use locks to avoid double replies
            if($lock && $lock->getStaffId()!=$thisuser->getId())
                $errors['err']='Action Denied. Ticket is locked by someone else!';

            //Check attachments restrictions.
            if($_FILES['attachment'] && $_FILES['attachment']['size']) {
                if(!$_FILES['attachment']['name'] || !$_FILES['attachment']['tmp_name'])
                    $errors['attachment']='Invalid attachment';
                elseif(!$cfg->canUploadFiles()) //TODO: saved vs emailed attachments...admin config??
                    $errors['attachment']='upload dir invalid. Contact admin.';
                elseif(!$cfg->canUploadFileType($_FILES['attachment']['name']))
                    $errors['attachment']='Invalid file type';
            }

            //Make sure the email is not banned
            if(!$errors && BanList::isbanned($ticket->getEmail()))
                $errors['err']='Email is in banlist. Must be removed to reply';

            //If no error...do the do.
            if(!$errors && ($respId=$ticket->postResponse($_POST['msg_id'],$_POST['response'],$_POST['signature'],$_FILES['attachment']))){
                $msg='Response Posted Successfully';
                //Set status if any.
                if(isset($_POST['ticket_status']) && $_POST['ticket_status'])
                    $ticket->setStatus($_POST['ticket_status']);
                //Finally upload attachment if any
                if($_FILES['attachment'] && $_FILES['attachment']['size']){
                    $ticket->uploadAttachment($_FILES['attachment'],$respId,'R');
                }
            }else{
                $errors['err']=$errors['err']?$errors['err']:'Unable to post the response.';
            }
            break;
        case 'transfer':
            $fields=array();
            $fields['dept_id']      = array('type'=>'int',  'required'=>1, 'error'=>'Select Department');
            $fields['message']      = array('type'=>'text',  'required'=>1, 'error'=>'Note/Message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }

            if(!$errors && ($_POST['dept_id']==$ticket->getDeptId()))
                $errors['dept_id']='Ticket already in the Dept.';
       
            if(!$errors && !$thisuser->canTransferTickets())
                $errors['err']='Action Denied. You are not allowed to transfer tickets.';
            
            if(!$errors && $ticket->transfer($_POST['dept_id'])){
                //Send out alerts??
                $title='Dept. Transfer: '.$ticket->getDeptName().' to '.Dept::getNameById($_POST['dept_id']);
                $ticket->postNote($title,$_POST['message']);
                $msg='Ticket Transfered Sucessfully';
            }else{
                $errors['err']=$errors['err']?$errors['err']:'Unable to complete the transfer';
            }
            break;
        case 'assign':
            $fields=array();
            $fields['staffId']          = array('type'=>'int',  'required'=>1, 'error'=>'Select assignee');
            $fields['assign_message']   = array('type'=>'text',  'required'=>1, 'error'=>'Message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }
            if(!$errors && $ticket->isAssigned()){
                if($_POST['staffId']==$ticket->getStaffId())
                    $errors['staffId']='Ticket already assigned to the staff.';
            }
            //if already assigned.
            if(!$errors && $ticket->isAssigned()) { //Re assigning.
                //Already assigned to the user?
                if($_POST['staffId']==$ticket->getStaffId())
                    $errors['staffId']='Ticket already assigned to the staff.';
                //Admin, Dept manager (any) or current assigneee ONLY can reassign
                if(!$thisuser->isadmin()  && !$thisuser->isManager() && $thisuser->getId()!=$ticket->getStaffId())
                    $errors['err']='Ticket already assigned. You do not have permission to re-assign assigned tickets';
            }
            if(!$errors && $ticket->assignStaff($_POST['staffId'],$_POST['assign_message'])){
                $msg='Ticket Assigned to staff';
            }else{
                $errors['err']=$errors['err']?$errors['err']:'Unable to assign the ticket';
            }
            break; 
        case 'postnote':
            $fields=array();
            $fields['title']    = array('type'=>'string',   'required'=>1, 'error'=>'Title required');
            $fields['note']     = array('type'=>'string',   'required'=>1, 'error'=>'Note message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST))
                $errors=array_merge($errors,$params->errors());

            if(!$errors && $ticket->postNote($_POST['title'],$_POST['note'])){
                $msg='Internal note posted';
                if(isset($_POST['ticket_status']) && $_POST['ticket_status'])
                    $ticket->setStatus($_POST['ticket_status']);
            }else{
                $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Unable to post the note.';
            }
            break;
        case 'process':
            $isdeptmanager=($deptId==$thisuser->getDeptId())?true:false;
            switch(strtolower($_POST['do'])):
                case 'change_priority':
                    if(!$thisuser->isadmin() && !$isdeptmanager){
                        $errors['err']='Perm. Denied. You are not allowed to change ticket\'s priority';
                    }elseif(!$_POST['ticket_priority'] or !is_numeric($_POST['ticket_priority'])){
                        $errors['err']='You must select priority';
                    }
                    if(!$errors){
                        if($ticket->setPriority($_POST['ticket_priority'])){
                            $msg='Priority Changed Successfully';
                        }else{
                            $errors['err']='Problems changing priority. Try again';
                        }
                    }
                    break;
                case 'close':
                    if(!$thisuser->isadmin() && !$thisuser->canCloseTickets()){
                        $errors['err']='Perm. Denied. You are not allowed to close tickets.';
                    }else{
                        if($ticket->close()){
                            $msg='Ticket status set to CLOSED';
                        }else{
                            $errors['err']='Problems closing the ticket. Try again';
                        }
                    }
                    break;
                case 'reopen':
                    //if they can close...then assume they can reopen.
                    if(!$thisuser->isadmin() && !$thisuser->canCloseTickets()){
                        $errors['err']='Perm. Denied. You are not allowed to reopen tickets.';
                    }else{
                        if($ticket->reopen()){
                            $msg='Ticket status set to OPEN';
                            if($_POST['ticket_priority'])
                                $ticket->setPriority($_POST['ticket_priority']);
                        }else{
                            $errors['err']='Problems reopening the ticket. Try again';
                        }
                    }
                    break;
                case 'release':
                    if(!($staff=$ticket->getStaff()))
                        $errors['err']='Ticket is not assigned!';
                    elseif($ticket->release())
                        $msg='Ticket released (unassigned) from '.$staff->getName();
                    else
                        $errors['err']='Problems releasing the ticket. Try again';
                    break;
                case 'overdue':
                    //Mark the ticket as overdue
                    if(!$thisuser->isadmin() && !$thisuser->isManager()){
                        $errors['err']='Perm. Denied. You are not allowed to flag tickets overdue';
                    }else{
                        if($ticket->markOverdue()){
                            $msg='Ticket flagged as overdue';
                            if($_POST['ticket_priority'])
                                $ticket->setPriority($_POST['ticket_priority']);
                        }else{
                            $errors['err']='Problems marking the the ticket overdue. Try again';
                        }
                    }
                    break;
                case 'banemail':
                    if(!$thisuser->isadmin() && !$thisuser->canManageBanList()){
                        $errors['err']='Perm. Denied. You are not allowed to ban emails';
                    }elseif(Banlist::add($ticket->getEmail(),$thisuser->getName())){
                        $msg='Email added to banlist';
                        if($ticket->isOpen() && $ticket->close())
                            $msg.=' & ticket status set to closed';
                    }else{
                        $errors['err']='Unable to add the email to banlist';
                    }
                    break;
                case 'unbanemail':
                    if(!$thisuser->isadmin() && !$thisuser->canManageBanList()){
                        $errors['err']='Perm. Denied. You are not allowed to remove emails from banlist.';
                    }elseif(Banlist::remove($ticket->getEmail())){
                        $msg='Email removed from banlist';
                    }else{
                        $errors['err']='Unable to remove the email from banlist. Try again.';
                    }
                    break;
                case 'delete': // Dude what are you trying to hide? bad customer support??
                    if(!$thisuser->isadmin() && !$thisuser->canDeleteTickets()){
                        $errors['err']='Perm. Denied. You are not allowed to DELETE tickets!!';
                    }else{
                        if($ticket->delete()){
                            $page='tickets.inc.php'; //ticket is gone...go back to the listing.
                            $msg='Ticket Deleted Forever';
                            $ticket=null; //clear the object.
                        }else{
                            $errors['err']='Problems deleting the ticket. Try again';
                        }
                    }
                    break;
                default:
                    $errors['err']='You must select action to perform';
            endswitch;
            break;
        default:
            $errors['err']='Unknown action';
        endswitch;
        if($ticket && is_object($ticket))
            $ticket->reload();//Reload ticket info following post processing
    }elseif(!strcasecmp($_POST['a'],'mass_process')) { //Mass process;
        if(!$thisuser->canManageTickets())
            $errors['err']='You do not have permission to mass manage tickets. Contact admin for such access';    
        elseif(!$_POST['tids'] || !is_array($_POST['tids']))
            $errors['err']='No tickets selected. You must select at least one ticket.';
        elseif(($_POST['reopen'] || $_POST['close']) && !$thisuser->canCloseTickets())
            $errors['err']='You do not have permission to close/reopen tickets';
        elseif($_POST['delete'] && !$thisuser->canDeleteTickets())
            $errors['err']='You do not have permission to delete tickets';
        elseif(!$_POST['tids'] || !is_array($_POST['tids']))
            $errors['err']='You must select at least one item';

        if(!$errors) {
            $count=count($_POST['tids']);
            if(isset($_POST['reopen'])){
                $i=0;
                foreach($_POST['tids'] as $k=>$v) {
                    $t = new Ticket($v);
                    if($t && @$t->reopen()) $i++;
                }
                $msg="$i of $count selected tickets reopened";
            }elseif(isset($_POST['close'])){
                $i=0;
                foreach($_POST['tids'] as $k=>$v) {
                    $t = new Ticket($v);
                    if($t && @$t->close()) $i++;
                }
                $msg="$i of $count selected tickets closed";
            }elseif(isset($_POST['overdue'])){
                $i=0;
                foreach($_POST['tids'] as $k=>$v) {
                    $t = new Ticket($v);
                    if($t && !$t->isoverdue())
                        if($t->markOverdue()) $i++;
                }
                $msg="$i of $count selected tickets marked overdue";
            }elseif(isset($_POST['delete'])){
                $i=0;
                foreach($_POST['tids'] as $k=>$v) {
                    $t = new Ticket($v);
                    if($t && @$t->delete()) $i++;
                }
                $msg="$i of $count selected tickets deleted";
            }
        }
    }elseif(!strcasecmp($_POST['a'],'open')) { //Open ticket
        $ticket=null;
        $_POST['emailId']=$_POST['topicId']=0; //clean crap.
        $_POST['message']='Ticket created by staff';
        if(!$_POST['issue'])
            $errors['issue']='Summary of the issue required';
        if($_POST['source'] && !in_array($_POST['source'],array('email','phone','other')))
            $errors['source']='Invalid source';
        
        if(($ticket=Ticket::create($_POST,$errors,'staff',isset($_POST['alertuser'])?true:false,false))){
            //post issue as a response...but no alerts are sent to the user. ?? TODO: Add optional alert
            $msgId=$ticket->getLastMsgId();
            if(($respId=$ticket->postResponse($msgId,$_POST['issue'],'none',null,false))) {
                //Upload attachment if any...
                if($_FILES['attachment'] && $_FILES['attachment']['size']){
                    $ticket->uploadAttachment($_FILES['attachment'],$respId,'R');
                }
            }
            if($_POST['staffId']) { //Assign ticket to staff if any. (internal note as message)
                $ticket->assignStaff($_POST['staffId'],$_POST['note'],isset($_POST['alertstaff'])?true:false);
            }elseif($_POST['note']){ //Not assigned...save optionl note if any
                $ticket->postNote('New Ticket',$_POST['note']);
            }
            $msg='Ticket created successfully';
            if($thisuser->canAccessDept($ticket->getDeptId())) {
                //View the sucker
                $page='viewticket.inc.php';
                $ticket->reload(); //Reload ...possibly assigned!
            }else {
                //Staff doesn't have access to the newly created ticket's department.
                $page='tickets.inc.php';
                $ticket=null;
            }
        }else{
            $errors['err']=$errors['err']?$errors['err']:'Unable to create the ticket. Correct the error(s) and try again';
        }
    }
    $crap='';
endif;
//Navigation 
$submenu=array();
/*quick stats...*/
list($assigned)=db_fetch_row(db_query('SELECT count(*) FROM '.TICKET_TABLE.' WHERE staff_id='.db_input($thisuser->getId())));
list($overdue)=db_fetch_row(db_query('SELECT count(*) FROM '.TICKET_TABLE.' WHERE isoverdue=1 AND dept_id IN('.implode(',',$thisuser->getDepts()).')'));
$nav->setTabActive('tickets');
$nav->addSubMenu(array('desc'=>'Tickets','title'=>'Tickets', 'href'=>'tickets.php', 'iconclass'=>'Ticket'));
if($assigned) {
    $nav->addSubMenu(array('desc'=>'My Tickets ('.$assigned.')','title'=>'Assigned Tickets',
                    'href'=>'tickets.php?status=assigned','iconclass'=>'assignedTickets'));
}
if($overdue) {
    $nav->addSubMenu(array('desc'=>'Overdue Tickets ('.$overdue.')','title'=>'Stale Tickets',
                    'href'=>'tickets.php?status=overdue','iconclass'=>'overdueTickets'));
}
$nav->addSubMenu(array('desc'=>'New Ticket','href'=>'tickets.php?a=open','iconclass'=>'newTicket'));

//Render the page...
$inc=$page?$page:'tickets.inc.php';
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
