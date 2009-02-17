<?php
/*********************************************************************
    class.ticket.php

    The most important class! Don't play with fire please.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
include_once(INCLUDE_DIR.'class.staff.php');
include_once(INCLUDE_DIR.'class.email.php');
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.topic.php');
include_once(INCLUDE_DIR.'class.lock.php');
include_once(INCLUDE_DIR.'class.banlist.php');


class Ticket{

    var $id;
    var $extid;
    var $email;
    var $status;
    var $created;
    var $updated;
    var $priority;
    var $priority_id;
    var $fullname;
    var $staff_id;
    var $dept_id;
    var $dept_name;
    var $subject;
    var $overdue;

    var $lastMsgId;
    
    var $dept;  //Dept class
    var $staff; //Staff class
    var $tlock; //TicketLock class
    
    function Ticket($id,$exid=false){
        $this->load($id);
    }
    
    function load($id) {
       
       
        $sql =' SELECT  ticket.*,lock_id,dept_name,priority_desc FROM '.TICKET_TABLE.' ticket '.
              ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id '.
              ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON ticket.priority_id=pri.priority_id '.
              ' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW() '.
              ' WHERE ticket.ticket_id='.db_input($id); 
        //echo $sql;
        if(($res=db_query($sql)) && db_num_rows($res)):
            $row=db_fetch_array($res);
            $this->id       =$row['ticket_id'];
            $this->extid    =$row['ticketID'];
            $this->email    =$row['email'];
            $this->fullname =$row['name'];
            $this->status   =$row['status'];
            $this->created  =$row['created'];
            $this->updated  =$row['updated'];
            $this->lock_id  =$row['lock_id'];
            $this->priority_id=$row['priority_id'];
            $this->priority=$row['priority_desc'];
            $this->staff_id =$row['staff_id'];
            $this->dept_id  =$row['dept_id'];
            $this->dept_name    =$row['dept_name'];
            $this->subject =$row['subject'];
            $this->overdue =$row['isoverdue'];
            $this->row=$row;
            //Reset the sub classes (initiated ondemand)...good for reloads.
            $this->staff=array();
            $this->dept=array();
        endif;

    }
        
    function reload() {
        return $this->load($this->id);
    }
    
    function isOpen(){
        return (strcasecmp($this->getStatus(),'Open')==0)?true:false;
    }

    function isClosed() {
        return (strcasecmp($this->getStatus(),'Closed')==0)?true:false;
    }

    function isAssigned() {
        return $this->getStaffId()?true:false;
    }

    function isOverdue() {
        return $this->overdue?true:false;
    }
    
    function isLocked() {
        return $this->lock_id?true:false;
    }
     
    //GET
    function getId(){
        return  $this->id;
    }

    function getExtId(){
        return  $this->extid;
    }
   
    function getEmail(){
        return $this->email;
    }

    function getName(){
        return $this->fullname;
    }

    function getSubject() {
        return $this->subject;
    }
   
    function getCreateDate(){
        return $this->created;
    }    
    
    function getUpdateDate(){
        return $this->updated;
    }

    function getStatus(){
        return $this->status;
    }
   
    function getDeptId(){
       return $this->dept_id;
    }
   
    function getDeptName(){
       return $this->dept_name;
    }

    function getPriorityId() {
        return $this->priority_id;
    }
    
    function getPriority() {
        return $this->priority;
    }
     
    function getPhone() {
        return $this->row['phone'];
    }

    function getSource() {
        return $this->row['source'];
    }
    
    function getIP() {
        return $this->row['ip_address'];
    }
    
    function getLock(){
        
        if(!$this->tlock && $this->lock_id)
            $this->tlock= new TicketLock($this->lock_id);
        
        return $this->tlock;
    }
    
    function acquireLock() {
        global $thisuser,$cfg;
       
        if(!$thisuser or !$cfg->getLockTime()) //Lockig disabled?
            return null;

        //Check if the ticket is already locked.
        if(($lock=$this->getLock()) && !$lock->isExpired()) {
            if($lock->getStaffId()!=$thisuser->getId()) //someone else locked the ticket.
                return null;
            //Lock already exits...renew it
            $lock->renew(); //New clock baby.
            
            return $lock;
        }
        //No lock on the ticket or it is expired
        $this->tlock=null; //clear crap
        $this->lock_id=TicketLock::acquire($this->getId(),$thisuser->getId()); //Create a new lock..
        //load and return the newly created lock if any!
        return $this->getLock();
    }
    
    function getDept(){
        
        if(!$this->dept && $this->dept_id)
            $this->dept= new Dept($this->dept_id);
        return $this->dept;
    }
    
    function getStaffId(){
        return $this->staff_id;
    }

    function getStaff(){

        if(!$this->staff && $this->staff_id)
            $this->staff= new Staff($this->staff_id);
        return $this->staff;
    }

    function getLastRespondent() {

        $sql ='SELECT  resp.staff_id FROM '.TICKET_RESPONSE_TABLE.' resp LEFT JOIN '.STAFF_TABLE. ' USING(staff_id) '.
            ' WHERE  resp.ticket_id='.db_input($this->getId()).' AND resp.staff_id>0  ORDER BY resp.created DESC LIMIT 1';
        $res=db_query($sql);
        if($res && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return ($id)?new Staff($id):null;

    }

    function getLastMessageDate() {

        $createDate=0;
        $sql ='SELECT created FROM '.TICKET_MESSAGE_TABLE.' WHERE ticket_id='.db_input($this->getId()).' ORDER BY created DESC LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($createDate)=db_fetch_row($res);

        return $createDate;
    }

    function getLastResponseDate() {

        $createDate=0;
        $sql ='SELECT created FROM '.TICKET_RESPONSE_TABLE.' WHERE ticket_id='.db_input($this->getId()).' ORDER BY created DESC LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($createDate)=db_fetch_row($res);

        return $createDate;
    }

    function getRelatedTicketsCount(){

        $num=0;
        $sql='SELECT count(*)  FROM '.TICKET_TABLE.' WHERE email='.db_input($this->getEmail());
        if(($res=db_query($sql)) && db_num_rows($res))
            list($num)=db_fetch_row($res);

        return $num;
    }

    function getLastMsgId() {
        return $this->lastMsgId;
    }

    //SET

    function setLastMsgId($msgid) {
        return $this->lastMsgId=$msgid;
    }
    function setPriority($priority_id){
        
        if(!$priority_id) 
            return false;
        
        $sql='UPDATE '.TICKET_TABLE.' SET priority_id='.db_input($priority_id).',updated=NOW() WHERE ticket_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows($res)){
           //TODO: escalate the ticket params??
            return true;
        }
        return false;

    }
    //DeptId can NOT be 0. No orphans please!
    function setDeptId($deptId){
        
        if(!$deptId)
            return false;
        
        $sql= 'UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($deptId).' WHERE ticket_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }
 
    //set staff ID...assign/unassign/release (staff id can be 0)
    function setStaffId($staffId){
      $sql= 'UPDATE '.TICKET_TABLE.' SET staff_id='.db_input($staffId).' WHERE ticket_id='.db_input($this->getId());
      return (db_query($sql)  && db_affected_rows())?true:false;
    }


    //Status helper.
    function setStatus($status){

        if(strcasecmp($this->getStatus(),$status)==0)
            return true; //No changes needed.

        switch(strtolower($status)):
        case 'reopen':
        case 'open':
            return $this->reopen();
            break;
        case 'close':
            return $this->close();
         break;
        endswitch;

        return false;
    }

    //Close the ticket
    function close(){
        
        $sql= 'UPDATE '.TICKET_TABLE.' SET status='.db_input('closed').',staff_id=0,isoverdue=0,updated=NOW(),closed=NOW() '.
              ' WHERE ticket_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }
    //set status to open on a closed ticket.
    function reopen(){
        global $thisuser;
        $sql= 'UPDATE '.TICKET_TABLE.' SET status='.db_input('open').',updated=NOW(),reopened=NOW() WHERE ticket_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }

    function markOverdue($bitch=false) {
        global $cfg;

        if($this->isOverdue())
            return true;

        $sql= 'UPDATE '.TICKET_TABLE.' SET isoverdue=1,updated=NOW() WHERE ticket_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows()) {
         
            //if requested && enabled fire nasty alerts.
            if($bitch && $cfg->alertONOverdueTicket()){
                $sql='SELECT ticket_overdue_subj,ticket_overdue_body FROM '.EMAIL_TEMPLATE_TABLE.
                     ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                $resp=db_query($sql);
                if($resp && list($subj,$body)=db_fetch_row($resp)){
                    $body = str_replace("%ticket", $this->getExtId(),$body);
                    $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                   
                    $dept= $this->getDept();        
                    //Fire and email to admin.
                    $alert = str_replace("%staff",'Admin',$body);
                    Misc::sendmail($cfg->getAdminEmail(),$subj,$alert,$cfg->getAlertEmail());

                    /*** Build list of recipients and fire the alerts ***/
                    $recipients=array();
                    //Assigned staff... if any
                    if($this->isAssigned() && $cfg->alertAssignedONOverdueTicket()){
                        $recipients[]=$this->getStaff();
                    }elseif($cfg->alertDeptMembersONOverdueTicket()){ //Alert assigned or dept members not both
                        //All dept members.
                        $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE dept_id='.db_input($dept->getId());
                        if(($users=db_query($sql)) && db_num_rows($users)) {
                            while(list($id)=db_fetch_row($users))
                                $recipients[]= new Staff($id);     //possible mem issues with a large number of staff?
                        }
                    }
                    //Always blame the manager
                    if($cfg->alertDeptManagerONOverdueTicket() && $dept) {
                        $recipients[]=$dept->getManager();
                    }
                    
                    //Ok...we are ready to go....
                    $sentlist=array();
                    foreach( $recipients as $k=>$staff){
                        if(!$staff || !is_object($staff) || !$staff->isAvailable()) continue;
                        if(in_array($staff->getEmail(),$sentlist)) continue; //avoid duplicate emails.
                        $alert = str_replace("%staff",$staff->getFirstName(),$body);
                        Misc::sendmail($staff->getEmail(),$subj,$alert,$cfg->getAlertEmail());
                        $sentlist[]=$staff->getEmail();
                    }
                }
            }
            return true;
        }
        return false;
    }


    //Dept Tranfer...with alert..
    function transfer($deptId){
        global $cfg;
        /*
        TODO:
            1) Figure out what to do when ticket is assigned
                Is the assignee allowed to access target dept?  (At the moment assignee will have access to the ticket anyways regardless of Dept)
            2) Send alerts to new Dept manager/members??
            3) Other crap I don't have time to think about at the moment.
        */
        return $this->setDeptId($deptId)?true:false;
    }

    //Assign: staff
    function assignStaff($staffId,$message,$alertstaff=true) {
        global $thisuser,$cfg;


        $staff = new Staff($staffId);
        if(!$staff || !$staff->isAvailable() || !$thisuser)
            return false;

        if($this->setStaffId($staff->getId())){
            //Reopen the ticket if cloed.                
            if($this->isClosed()) //Assigned ticket Must be open.
                $this->reopen();
            //Send Notice + Message to assignee. (if directed)
            if($alertstaff && $staff->getId()!=$thisuser->getId()) { //No alerts for self assigned.
                //Send Notice + Message to assignee.
                $sql='SELECT assigned_alert_subj,assigned_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                 ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                $resp=db_query($sql);
                if(db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){
                    $subj = str_replace("%ticket", $this->getExtId(),$subj);
                    $body = str_replace("%ticket", $this->getExtId(),$body);
                    $body = str_replace("%assignee", $staff->getName(),$body);
                    $body = str_replace("%assigner", $thisuser->getName(),$body);
                    $body = str_replace("%message", $message,$body);
                    $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                    Misc::sendmail($staff->getEmail(),$subj,$body,$cfg->getAlertEmail());
                }
            }
            //Save the message as internal note...(record).
            $this->postNote('Ticket Assigned to '.$staff->getName(),$message);
            return true;
        }
        return false;
    }
    //unassign
    function release(){
        global $thisuser;

        if(!$this->getStaffId())
            return true;

        return $this->setStaffId(0)?true:false;
    }

    //Insert message from client
    function postMessage($msg,$headers='',$source='',$newticket=false){
        global $cfg;
       
        if(!$this->getId())
            return 0;
        
        //We don't really care much about the source at message level
        $source=$source?$source:$_SERVER['REMOTE_ADDR'];
        
        $sql='INSERT INTO '.TICKET_MESSAGE_TABLE.' SET created=NOW() '.
             ',ticket_id='.db_input($this->getId()).
             ',message='.db_input(Format::striptags($msg)). //Tags/code stripped...meaning client can not send in code..etc
             ',headers='.db_input($headers). //Raw header.
             ',source='.db_input($source).
             ',ip_address='.db_input($_SERVER['REMOTE_ADDR']);
    
        if(db_query($sql) && ($msgid=db_insert_id())) {
            $this->setLastMsgId($msgid);
            if(!$newticket){
                //Success and the message is being appended to previously opened ticket.
                //Alerts for new tickets are sent on create.
                $dept =$this->getDept();
                //Reopen if the status is closed...
                if(!$this->isOpen()) {
                    $this->reopen();
                    //If enabled..auto-assign the ticket to last respondent...if they still have access to the Dept.
                    if($cfg->autoAssignReopenedTickets() && ($lastrep=$this->getLastRespondent())) {
                        //3 months elapsed time limit on auto-assign. Must be available and have access to Dept.
                        if($lastrep->isAvailable() && $lastrep->canAccessDept($this->getDeptId()) 
                                && (time()-strtotime($this->getLastResponseDate()))<=90*24*3600) {
                            $this->setStaffId($lastrep->getId()); //Direct Re-assign!!!!????
                        }
                        //TODO: Worry about availability...may be lastlogin also? send an alert??
                    }
                }
                //If enabled...send confirmation to user. ( New Message AutoResponse)
                if($cfg->autoRespONNewMessage() && $dept->autoRespONNewMessage()){
                     
                    $sql='SELECT message_autoresp_subj,message_autoresp_body FROM '.EMAIL_TEMPLATE_TABLE.
                         ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                    $resp=db_query($sql);
                    
                    if($resp && list($subj,$body)=db_fetch_row($resp)){
                        
                        $subj = str_replace("%ticket", $this->getExtId(),$subj);
                        $body = str_replace("%ticket", $this->getExtId(),$body);
                        $body = str_replace("%name", $this->getName(),$body);
                        $body = str_replace("%email", $this->getEmail(),$body);
                        $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                        $body = str_replace("%signature",$dept->isPublic()?$dept->getSignature():'',$body);

                        $from=$fromName=null;
                        if($dept->noreplyAutoResp()){
                            $from=$cfg->getNoReplyEmail();
                        }else{
                            $email=$dept->isPublic()?$dept->getEmail():$cfg->getDefaultEmail();
                            $from=$email->getEmail();
                            $fromName=$email->getName();
                            //Reply separator tag.
                            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                                $body ="\n$tag\n\n".$body;
                        }
                        Misc::sendmail($this->getEMail(),$subj,$body,$from,$fromName);
                    }
                }
                
                //If enabled...send alert to staff (New Message Alert)
                
                if($cfg->alertONNewMessage()){
                    $sql='SELECT message_alert_subj,message_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                         ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                    $resp=db_query($sql);
                    if($resp && list($subj,$body)=db_fetch_row($resp)){
                        $body = str_replace("%ticket", $this->getExtId(),$body);
                        $body = str_replace("%name", $this->getName(),$body);
                        $body = str_replace("%email", $this->getEmail(),$body);
                        $body = str_replace("%dept", $dept->getName(),$body);
                        $body = str_replace("%message", $msg,$body);
                        $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                        
                        //Build list of recipients and fire the alerts.
                        $recipients=array();
                        //Last respondent.
                        if($cfg->alertLastRespondentONNewMessage() || $cfg->alertAssignedONNewMessage())
                            $recipients[]=$this->getLastRespondent();
                        //Assigned staff if any...could be the last respondent
                        if($this->isAssigned())
                            $recipients[]=$this->getStaff();
                        //Dept manager
                        if($cfg->alertDeptManagerONNewMessage() && $dept)
                            $recipients[]=$dept->getManager();
                    
                        //Baby we are ready...take me
                        $sentlist=array(); //I know it sucks...but..it works.
                        foreach( $recipients as $k=>$staff){
                            //TODO: log error messages.
                            if(!$staff || !is_object($staff) || !$staff->getEmail() || !$staff->isAvailable()) continue;
                            if(in_array($staff->getEmail(),$sentlist)) continue; //avoid duplicate emails.
                            $alert = str_replace("%staff",$staff->getFirstName(),$body);
                            Misc::sendmail($staff->getEmail(),$subj,$alert,$cfg->getAlertEmail());
                            $sentlist[]=$staff->getEmail();
                        }
                    }
                }

            }
        } 
        return $msgid;
    }

    //Insert Staff Reply
    function postResponse($msgid,$response,$signature='none',$attachment=false,$canalert=true){
        global $thisuser,$cfg;

        if(!$thisuser || !$thisuser->getId() || !$thisuser->isStaff()) //just incase
            return 0;

    
        $sql= 'INSERT INTO '.TICKET_RESPONSE_TABLE.' SET created=NOW() '.
                ',ticket_id='.db_input($this->getId()).
                ',msg_id='.db_input($msgid).
                ',response='.db_input(Format::striptags($response)).
                ',staff_id='.db_input($thisuser->getId()).
                ',staff_name='.db_input($thisuser->getName()).
                ',ip_address='.db_input($thisuser->getIP());
        $resp_id=0;
        //echo $sql;
        if(db_query($sql) && ($resp_id=db_insert_id())):

            if(!$canalert) //No alert/response 
                return $resp_id;
                
            
            $dept=$this->getDept();
            //Send Response to client...based on the template...
            //TODO: check department level templates...if set.
            $sql='SELECT ticket_reply_subj,ticket_reply_body FROM '.EMAIL_TEMPLATE_TABLE.
                ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
            $resp=db_query($sql);
            if(db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){
                
                $subj = str_replace("%ticket", $this->getExtId(),$subj);
                $subj = str_replace("%subject", $this->getSubject(),$subj);
                
                $body = str_replace("%ticket", $this->getExtId(),$body);
                $body = str_replace("%name", $this->getName(),$body);
                $body = str_replace("%email", $this->getEmail(),$body);
                $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                $body = str_replace("%message",$response,$body);
                
                //Figure out the signature to use...if any.
                switch(strtolower($signature)):
                case 'mine';
                $signature=$thisuser->getSignature();
                break;
                case 'dept':
                $signature=$dept->isPublic()?$dept->getSignature():''; //make sure it is public
                break;
                case 'none';
                default:
                $signature='';
                break;
                endswitch;
                $body = str_replace("%signature",$signature,$body);
                
                //Email attachment when attached AND if emailed.
                if(($attachment && is_file($attachment['tmp_name'])) && $cfg->emailAttachments()) {
                    $semi_rand = md5(time());
                    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                    $headers="MIME-Version: 1.0\n" .
                             "Content-Type: multipart/mixed;\n" .
                             " boundary=\"{$mime_boundary}\"";

                    $body = "This is a multi-part message in MIME format.\n\n" .
                            "--{$mime_boundary}\n" .
                            "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
                            "Content-Transfer-Encoding: 7bit\n\n".
                            $body . "\n\n";

                    $body.= "--{$mime_boundary}\n" .
                            "Content-Type: " . $attachment['type'] . ";\n" .
                            " name=\"" . $attachment['name'] . "\"\n" .
                            "Content-Disposition: attachment;\n" .
                            " filename=\"" . $attachment['name'] . "\"\n" .
                            "Content-Transfer-Encoding: base64\n\n" .
                            chunk_split(base64_encode(file_get_contents($attachment['tmp_name']))). "\n\n" .
                            "--{$mime_boundary}--\n";
                }
                $email=$from=$fromNamenull;
                if(($email=$dept->getEmail())) { //Dept email if set!
                    $from=$email->getEmail();
                    $fromName=$email->getName();
                    //Reply separator tag.
                    if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))    
                        $body ="\n$tag\n\n".$body;
                }else{//No emails means it is a noreply...
                    $from=$cfg->getNoReplyEmail();
                }
                Misc::sendmail($this->getEmail(),$subj,$body,$from,$fromName,$headers);
            }else{
                //We have a big problem...alert admin...
                $msg='Problems fetching response template for ticket#'.$this->getId().' Possible config error';
                Misc::alertAdmin('System Error',$msg);
            }
            return $resp_id;
            
        endif;
        
        return 0;
    }

    //Insert Internal Notes 
    function postNote($title,$note,$poster='') {        
        global $thisuser;

        $sql= 'INSERT INTO '.TICKET_NOTE_TABLE.' SET created=NOW() '.
                ',ticket_id='.db_input($this->getId()).
                ',title='.db_input(Format::striptags($title)).
                ',note='.db_input(Format::striptags($note)).
                ',staff_id='.db_input($thisuser?$thisuser->getId():0).
                ',source='.db_input(($poster || !$thisuser)?$poster:$thisuser->getName());
        //echo $sql;
        return db_query($sql)?db_insert_id():0;
    }


    //online based attached files.
    function uploadAttachment($file,$refid,$type){
        global $cfg;
     
        if(!$file['tmp_name'] || !$refid || !$type)
            return 0;
        
        $dir=$cfg->getUploadDir();
        $rand=Misc::randCode(16);
        $file['name']=Format::file_name($file['name']);
        $filename=rtrim($dir,'/').'/'.$rand.'_'.$file['name'];
        if(move_uploaded_file($file['tmp_name'],$filename)){
            $sql ='INSERT INTO '.TICKET_ATTACHMENT_TABLE.' SET created=NOW() '.
                  ',ticket_id='.db_input($this->getId()).
                  ',ref_id='.db_input($refid).
                  ',ref_type='.db_input($type).
                  ',file_size='.db_input($file['size']).
                  ',file_name='.db_input($file['name']).
                  ',file_key='.db_input($rand);
            if(db_query($sql)) 
                return db_insert_id();
            
            //DB  insert failed!--remove the file..
            @unlink($filename);
        }
        return 0;
    }
    
    //incoming email or json/xml bases attachments.
    function saveAttachment($name,$data,$refid,$type){
       global $cfg;
        
        if(!$refid ||!$name || !$data)
            return 0;

        $dir=$cfg->getUploadDir();
        $rand=Misc::randCode(16);
        $name=Format::file_name($name);
        $filename=rtrim($dir,'/').'/'.$rand.'_'.$name;
        if(($fp=fopen($filename,'w'))) {
            fwrite($fp,$data);
            fclose($fp);
            $size=@filesize($filename);
            $sql ='INSERT INTO '.TICKET_ATTACHMENT_TABLE.' SET created=NOW() '.
                  ',ticket_id='.db_input($this->getId()).
                  ',ref_id='.db_input($refid).
                  ',ref_type='.db_input($type).
                  ',file_size='.db_input($size).
                  ',file_name='.db_input($name).
                  ',file_key='.db_input($rand);
            if(db_query($sql)) return db_insert_id();
             @unlink($filename); //insert failed...remove the link.
        }
        return 0;
    }

    function delete(){
        
        
        if(db_query('DELETE FROM '.TICKET_TABLE.' WHERE ticket_id='.$this->getId()) && db_affected_rows()):
            db_query('DELETE FROM '.TICKET_MESSAGE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
            db_query('DELETE FROM '.TICKET_RESPONSE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
            db_query('DELETE FROM '.TICKET_NOTE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
            $this->deleteAttachments();
            return TRUE;
        endif;
  
        return FALSE;
    }
    
    function deleteAttachments(){
        global $cfg;
        
        $sql='SELECT attach_id,file_name,file_key FROM '.TICKET_ATTACHMENT_TABLE.' WHERE ticket_id='.db_input($this->getId());
        $res=db_query($sql);
        if($res && db_num_rows($res)) {
            while(list($id,$name,$key)=db_fetch_row($res)){
                @unlink($cfg->getUploadDir().'/'.$key.'_'.$name);
                $ids[]=$id;
            }
            db_query('DELETE FROM '.TICKET_ATTACHMENT_TABLE.' WHERE attach_id IN('.implode(',',$ids).') AND ticket_id='.db_input($this->getId()));
            return TRUE;
        }
        return FALSE;
    }
    
    function getAttachmentStr($refid,$type){
        
        $sql ='SELECT attach_id,file_size,file_name FROM '.TICKET_ATTACHMENT_TABLE.
             ' WHERE deleted=0 AND ticket_id='.db_input($this->getId()).' AND ref_id='.db_input($refid).' AND ref_type='.db_input($type);
        $res=db_query($sql);
        if($res && db_num_rows($res)){
            while(list($id,$size,$name)=db_fetch_row($res)){
                $hash=MD5($this->getId()*$refid.session_id());
                $size=Format::file_size($size);
                $attachstr.= "<a class='Icon file' href='attachment.php?id=$id&ref=$hash' target='_blank'><b>$name</b></a>&nbsp;(<i>$size</i>)&nbsp;&nbsp;";
            }
        }
        return ($attachstr);
    }

   /*============== Functions below do not require an instance of the class to be used. To call it use Ticket::function(params); ==================*/
    function getIdByExtId($extid) {
        $sql ='SELECT  ticket_id FROM '.TICKET_TABLE.' ticket WHERE ticketID='.db_input($extid);
        $res=db_query($sql);
        if($res && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function genExtRandID() {
        global $cfg;

        //We can allow collissions...extId and email must be unique ...so same id with diff emails is ok..
        // But for clarity...we are going to make sure it is unique.
        $id=Misc::randNumber(EXT_TICKET_ID_LEN);
        if(db_num_rows(db_query('SELECT ticket_id FROM '.TICKET_TABLE.' WHERE ticketID='.db_input($id))))
            return Ticket::genExtRandID();

        return $id;
    }

    function getOpenTicketsByEmail($email){

        $sql='SELECT count(*) as open FROM '.TICKET_TABLE.' WHERE status='.db_input('open').' AND email='.db_input($email);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($num)=db_fetch_row($res);

        return $num;
    }

    /*
     * The mother of all functions...You break it you fix it!
     *
     *  $autorespond and $alertstaff overwrites config info...
     */      
    function create($var,&$errors,$origin,$autorespond=true,$alertstaff=true) {
        global $cfg,$thisclient,$_FILES;
        
        $id=0;
        $fields=array();
        $fields['name']     = array('type'=>'string',   'required'=>1, 'error'=>'Name required');
        $fields['email']    = array('type'=>'email',    'required'=>1, 'error'=>'Valid email required');
        $fields['subject']  = array('type'=>'string',   'required'=>1, 'error'=>'Subject required');
        $fields['message']  = array('type'=>'text',     'required'=>1, 'error'=>'Message required');
        if(strcasecmp($origin,'web')==0) { //Help topic only applicable on web tickets.
            $fields['topicId']  = array('type'=>'int',      'required'=>1, 'error'=>'Select help topic');
        }elseif(strcasecmp($origin,'staff')==0){ //tickets created by staff...e.g on callins.
            $fields['deptId']   = array('type'=>'int',      'required'=>1, 'error'=>'Dept. required');
            $fields['source']   = array('type'=>'string',   'required'=>1, 'error'=>'Indicate source');
        }else { //Incoming emails (PIPE or POP.
            $fields['emailId']  = array('type'=>'int',  'required'=>1, 'error'=>'Email unknown');
        }
        $fields['pri']      = array('type'=>'int',      'required'=>0, 'error'=>'Invalid Priority');
        $fields['phone']    = array('type'=>'phone',    'required'=>0, 'error'=>'Phone # required');
        
        $validate = new Validator($fields);
        if(!$validate->validate($var)){
            $errors=array_merge($errors,$validate->errors());
        }
        
        //Make sure the email is not banned
        if(!$errors && BanList::isbanned($var['email']))
            $errors['err']='Ticket denied Error #403';

        if(!$errors && $thisclient && strcasecmp($thisclient->getEmail(),$var['email']))
            $errors['email']='Email mismatch.';
    
        //check attachment..if any is set ...only set on webbased tickets..
        if($_FILES['attachment']['name'] && $cfg->allowOnlineAttachments()) {
            if(!$cfg->canUploadFileType($_FILES['attachment']['name']))
                $errors['attachment']='Invalid file type [ '.$_FILES['attachment']['name'].' ]';
            elseif($_FILES['attachment']['size']>$cfg->getMaxFileSize())
                $errors['attachment']='File is too big. Max '.$cfg->getMaxFileSize().' bytes allowed';
        }
        //check ticket limits..if limit set is >0 
        //TODO: Base ticket limits on SLA...
        if(($var['email'] && !$errors && $cfg->getMaxOpenTickets()>0)){
            $openTickets=Ticket::getOpenTicketsByEmail($var['email']);
            if($openTickets>=$cfg->getMaxOpenTickets()) {
                $errors['err']="You've reached the maximum open tickets allowed.";
                //Send the notice only once (when the limit is reached) incase of autoresponders at client end.
                if($cfg->getMaxOpenTickets()==$openTickets && $cfg->sendOverlimitNotice()) {
                    $sql='SELECT ticket_overlimit_subj,ticket_overlimit_body FROM '.EMAIL_TEMPLATE_TABLE.
                        ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                    $resp=db_query($sql);
                    if(db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){
                        $body = str_replace("%name", $var['name'],$body);
                        $body = str_replace("%email",$var['email'],$body);
                        $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                        Misc::sendmail($var['email'],$subj,$body,$cfg->getNoReplyEmail());
                    }
                }
                //Alert admin...this might be spammy (no option to disable)...but it is helpful..I think.
                $msg='Support ticket request denied for '.$var['email']."\n".
                     'Open ticket:'.$openTickets."\n".
                     'Max Allowed:'.$cfg->getMaxOpenTickets()."\n";
                Misc::alertAdmin('Overlimit Notice',$msg);
            }
        }
        //Any error above is fatal.
        if($errors) { return 0; }
        
        // OK...just do it.
        $deptId=$var['deptId']; //pre-selected Dept if any.
        $priorityId=$var['pri'];
        $source=ucfirst($var['source']);
        // Intenal mapping magic...see if we need to overwrite anything
        if(isset($var['topicId']) && !$var['deptId']) { //Ticket created via web by user
            if($var['topicId'] && ($topic= new Topic($var['topicId'])) && $topic->getId()) {
                $deptId=$topic->getDeptId();
                $priorityId=$priorityId?$priorityId:$topic->getPriorityId();
                $autorespond=$topic->autoRespond();
            }
            $topic=null;
            $source='Web';
        }elseif($var['emailId'] && !$var['deptId']) { //Emailed Tickets
            $email= new Email($var['emailId']);
            if($email && $email->getId()){
                $deptId=$email->getDeptId();
                $autorespond=$email->autoRespond();
                $priorityId=$priorityId?$priorityId:$email->getPriorityId();
            }
            $email=null;
            $source='Email';
        }elseif($var['deptId']){ //Opened by staff.
            $deptId=$var['deptId'];
            $source=ucfirst($var['source']);
        }
        //Last minute checks
        $priorityId=$priorityId?$priorityId:$cfg->getDefaultPriorityId();
        $deptId=$deptId?$deptId:$cfg->getDefaultDeptId();
        $ipaddress=$var['ip']?$var['ip']:$_SERVER['REMOTE_ADDR'];
        
        //We are ready son...hold on to the rails.
        $extId=Ticket::genExtRandID();
        $sql=   'INSERT INTO '.TICKET_TABLE.' SET created=NOW() '.
                ',ticketID='.db_input($extId).
                ',dept_id='.db_input($deptId).
                ',priority_id='.db_input($priorityId).
                ',email='.db_input($var['email']).
                ',name='.db_input(Format::striptags($var['name'])).
                ',subject='.db_input(Format::striptags($var['subject'])).
                ',phone='.db_input($var['phone']).
                ',ip_address='.db_input($ipaddress).        
                ',source='.db_input($source);
        //echo $sql;
        $ticket=null;
        //return $ticket;
        if(db_query($sql) && ($id=db_insert_id())){

            if(!$cfg->useRandomIds()){
                //Sequential ticketIDs support really..really suck arse.
                $extId=$id; //To make things really easy we are going to use autoincrement ticket_id.
                db_query('UPDATE '.TICKET_TABLE.' SET ticketID='.db_input($extId).' WHERE ticket_id='.$id); 
                //TODO: RETHING what happens if this fails?? [At the moment on failure random ID is used...making stuff usable]
            }
            //Load newly created ticket.
            $ticket = new Ticket($id);
            //post the message.
            $msgid=$ticket->postMessage($var['message'],$var['header'],$source,true);
            //TODO: recover from postMessage error??
            //Upload attachments...web based.
            if($_FILES['attachment']['name'] && $cfg->allowOnlineAttachments() && $msgid) {    
                if(!$cfg->allowAttachmentsOnlogin() || ($cfg->allowAttachmentsOnlogin() && ($thisclient && $thisclient->isValid()))) {
                    $ticket->uploadAttachment($_FILES['attachment'],$msgid,'M');
                    //TODO: recover from upload issues?
                }
            }
            
            $dept=$ticket->getDept();     
            //SEND OUT NEW TICKET AUTORESP && ALERTS.
            //New Ticket AutoResponse..
            if($autorespond && $cfg->autoRespONNewTicket() && $dept->autoRespONNewTicket()){

                $sql='SELECT ticket_autoresp_subj,ticket_autoresp_body FROM '.EMAIL_TEMPLATE_TABLE.
                    ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                $resp=db_query($sql);
                if($resp && list($subj,$body)=db_fetch_row($resp)){
                    $subj = str_replace("%ticket", $ticket->getExtId(),$subj);
                    $body = str_replace("%ticket", $ticket->getExtId(),$body);
                    $body = str_replace("%name", $ticket->getName(),$body);
                    $body = str_replace("%email", $ticket->getEmail(),$body);
                    $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                    $body = str_replace("%signature",$dept?$dept->getSignature():'',$body);
                    $email=$from=$fromName=null;
                    if(!$dept->noreplyAutoResp() && ($email=$dept->getEmail())){
                        $from=$email->getEmail();
                        $fromName=$email->getName();
                        //Reply separator tag.                        
                        if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                            $body ="\n$tag\n\n".$body;
                    }else{
                        $from=$cfg->getNoReplyEmail();
                    }
                    Misc::sendmail($ticket->getEmail(),$subj,$body,$from,$fromName);
                }
            }

            //If enabled...send alert to staff (New Ticket Alert)
            if($alertstaff && $cfg->alertONNewTicket() && is_object($ticket)){

                $sql='SELECT ticket_alert_subj,ticket_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                    ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($cfg->getDefaultTemplateId());
                $resp=db_query($sql);
                if($resp && list($subj,$body)=db_fetch_row($resp)){
                    $body = str_replace("%ticket", $ticket->getExtId(),$body);
                    $body = str_replace("%name", $ticket->getName(),$body);
                    $body = str_replace("%email", $ticket->getEmail(),$body);
                    $body = str_replace("%subject", $ticket->getSubject(),$body);
                    $body = str_replace("%dept", $dept?$dept->getName():'',$body);
                    $body = str_replace("%message",$var['message'],$body);
                    $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                    $sentlist=array();
                    //Admin Alert.
                    if($cfg->alertAdminONNewTicket()){
                        $alert = str_replace("%staff",'Admin',$body);    
                        Misc::sendmail($cfg->getAdminEmail(),$subj,$alert,$cfg->getAlertEmail());
                        $sentlist[]=$cfg->getAdminEmail();
                    }
                    //get the list
                    $recipients=array();
                    //Dept. Manager
                    if($cfg->alertDeptManagerONNewTicket()) {
                        $recipients[]=$dept->getManager();
                    }
                    //Staff members
                    if($cfg->alertDeptMembersONNewTicket()) {
                        $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE onvacation=0 AND dept_id='.db_input($dept->getId());
                        if(($users=db_query($sql)) && db_num_rows($users)) {
                            while(list($id)=db_fetch_row($users))
                                $recipients[]= new Staff($id);
                        }
                    }
                    //Ok...we are ready to go...baby!
                    foreach( $recipients as $k=>$staff){
                        if(!$staff || !is_object($staff) || !$staff->isAvailable()) continue;
                        if(in_array($staff->getEmail(),$sentlist)) continue; //avoid duplicate emails.
                        $alert = str_replace("%staff",$staff->getFirstName(),$body);
                        Misc::sendmail($staff->getEmail(),$subj,$alert,$cfg->getAlertEmail());
                        $sentlist[]=$staff->getEmail();
                    }
                } 
            }
            
        }
        
        return $ticket;
    }

   function checkOverdue(){
       
        global $cfg;

        if(!($hrs=$cfg->getGracePeriod()))
            return 0;
        
        $sec=$hrs*3600;
        $sql='SELECT ticket_id FROM '.TICKET_TABLE.' WHERE status=\'open\' AND isoverdue=0 '.
             ' AND ((reopened is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),created))>='.$sec.')  '.
             ' OR (reopened is NOT NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),reopened))>='.$sec.') '.
             ') ORDER BY created LIMIT 10'; //Age upto 10 tickets at a time?
        //echo $sql;
        if(($stale=db_query($sql)) && db_num_rows($stale)){
            while(list($id)=db_fetch_row($stale)){
                $ticket = new Ticket($id);
                $ticket->markOverdue(true);
            }
        }
   }
}
?>
