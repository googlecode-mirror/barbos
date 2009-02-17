<?php
/*********************************************************************
    class.pop3.php

    POP3 mail class. Uses IMAP ext for now.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    TODO: Add pear pop3/imap as alternative for user without IMAP ext.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');

class POP3 {
    var $hostname;
    var $username;
    var $password;

    var $servertype;
    var $port;

    var $mbox;

    var $charset;
    
    function POP3($username,$password,$hostname='localhost',$servertype='pop',$port=0) {
        $this->hostname=$hostname;
        $this->username=$username;
        $this->password=$password;
        $this->servertype=$servertype;
        //Set connection string.
        if(strpos(strtolower($hostname),'pop.gmail.com')!==false){
            $this->port=995; //overwrite the port;
            $this->serverstr='{pop.gmail.com:995/pop3/ssl/novalidate-cert}INBOX';
        }elseif($this->servertype=='pop3') {
            $this->port=$port?$port:110;
            $this->serverstr='{'.$this->hostname.':'.$this->port.'}INBOX';
        }elseif($this->servertype=='imap') {
            $this->port=$port?$port:143;
            $this->serverstr='{'.$this->hostname.':'.$this->port.'/pop3}INBOX';
        }else{
            $this->serverstr='{'.$this->hostname.'/pop3/notls}INBOX';
        }
        //Charset to convert the mail to.
        $this->charset='UTF-8';
        //Set timeouts 
        if(function_exists('imap_timeout'))
            imap_timeout(1,20); //Open timeout.
    }

    function connect() {
        return $this->open()?true:false;
    }

    function open() {
        
        if($this->mbox && imap_ping($this->mbox))
            return $this->mbox;
            
        $this->mbox =@imap_open($this->serverstr,$this->username,$this->password);

        return $this->mbox;
    }

    function close() {
        imap_close($this->mbox,CL_EXPUNGE);
    }

    function mailcount(){
        return count(imap_headers($this->mbox));
    }


    function decode($encoding,$text) {

        switch($encoding) {
            case 1:
            $text=imap_8bit($text);
            break;
            case 2:
            $text=imap_binary($text);
            break;
            case 3:
            $text=imap_base64($text);
            break;
            case 4:
            $text=imap_qprint($text);
            break;
            case 5:
            default:
             $text=$text;
        } 
        return $text;
    }

    //Conver text to desired encoding..defaults to utf8
    function mime_encode($text,$charset=null,$enc='uft-8') { //Thank in part to afterburner  
                
        $encodings=array('UTF-8','WINDOWS-1251', 'ISO-8859-5', 'ISO-8859-1');
        
        if(function_exists("iconv") and $text) {
            if($charset)
                return iconv($charset,$enc.'//IGNORE',$text);
            elseif(function_exists("mb_detect_encoding"))
                return iconv(mb_detect_encoding($text,$encodings),$enc,$text);
        }
        return imap_utf8($text);
    }

    function getLastError(){
        return imap_last_error();
    }

    function getMimeType($struct) {
        $mimeType = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER');
        if(!$struct || !$struct->subtype)
            return 'TEXT/PLAIN';
        
        return $mimeType[(int) $struct->type].'/'.$struct->subtype;
    }

    function getHeaderInfo($mid) {
        
        $headerinfo=imap_headerinfo($this->mbox,$mid);
        $sender=$headerinfo->from[0];

        //Parse what we need...
        $header=array(
                      'from'   =>array('name'  =>@$sender->personal,'email' =>strtolower($sender->mailbox).'@'.$sender->host),
                      'subject'=>@$headerinfo->subject,);
        return $header;
    }

    //search for specific mime type parts....encoding is the desired encoding.
    function getPart($mid,$mimeType,$encoding=false,$struct=null,$partNumber=false){
          
        if(!$struct)
            $struct=imap_fetchstructure($this->mbox, $mid);
        //Match the mime type.
        if($struct && strcasecmp($mimeType,$this->getMimeType($struct))==0){
            $partNumber=$partNumber?$partNumber:1;
            if(($text=imap_fetchbody($this->mbox, $mid, $partNumber))){
                if($struct->encoding==3 or $struct->encoding==4) //base64 and qp decode.
                    $text=$this->decode($struct->encoding,$text);
                $charset=null;
                if($encoding && 0) { //Convert text to desired mime encoding...
                    if($struct->parameters[0] 
                            && !strcasecmp($struct->parameters[0]->attribute,'CHARSET') && strcasecmp($struct->parameters[0]->value,'US-ASCII')) {
                        $charset=trim($struct->parameters[0]->value);
                    }
                    $text=$this->mime_encode($text,$charset,$encoding);
                }
                return $text;
            }
        }
        //Do recursive search 
        if($struct && $struct->parts){
            while(list($i, $substruct) = each($struct->parts)) {
                if($partNumber) 
                    $prefix = $partNumber . '.';
                if(($text=$this->getPart($mid,$mimeType,$encoding,$substruct,$prefix.($i+1))))
                    return $text;
            }
        }
        //No luck.
        
        return false;
    }

    function getHeader($mid){
        return imap_fetchheader($this->mbox, $mid,FT_PREFETCHTEXT);
    }

    
    function getPriority($mid){
        return Mail_Parse::parsePriority($this->getHeader($mid));
    }

    function getBody($mid) {
        
        $body ='';
        if(!($body = $this->getpart($mid,'TEXT/PLAIN',$this->charset))) {
            if(($body = $this->getPart($mid,'TEXT/HTML',$this->charset))) {
                //Convert tags of interest before we striptags
                $body=str_replace("</DIV><DIV>", "\n", $body);
                $body=str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $body);
                $body=Format::striptags($body); //Strip tags??
            }
        }
        return $body;
    }

    function createTicket($mid,$emailid=0){
        global $cfg;

        $mailinfo=$this->getHeaderInfo($mid);
        $var['name']=imap_utf8($mailinfo['from']['name']);
        $var['email']=$mailinfo['from']['email'];
        $var['subject']=$mailinfo['subject']?imap_utf8($mailinfo['subject']):'[No Subject]';
        $var['message']=Format::stripEmptyLines($this->getBody($mid));
        $var['header']=$cfg->saveEmailHeaders()?$this->getHeader($mid):'';
        $var['emailId']=$emailid?$emailid:$cfg->getDefaultEmailId(); //ok to default?
        $var['name']=$var['name']?$var['name']:$var['email']; //No name? use email
        if($cfg->useEmailPriority())
            $var['pri']=$this->getPriority($mid);
       
        $ticket=null;
        $newticket=true;
        //Check the subject line for possible ID.
        if(ereg ("[[][#][0-9]{1,10}[]]",$var['subject'],$regs)) {
            $extid=trim(preg_replace("/[^0-9]/", "", $regs[0]));
            $ticket= new Ticket(Ticket::getIdByExtId($extid));
            //Allow mismatched emails?? For now NO.
            if(!$ticket || strcasecmp($ticket->getEmail(),$var['email']))
                $ticket=null;
        }
        
        $errors=array();
        if(!$ticket) {
            if(!($ticket=Ticket::create($var,$errors,'Email')) || $errors)
                return null;
            $msgid=$ticket->getLastMsgId();
        }else{
            $message=$var['message'];
            //Strip quoted reply...TODO: figure out how mail clients do it without special tag..
            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()) && strpos($var['message'],$tag))
                list($message)=split($tag,$var['message']);
            $msgid=$ticket->postMessage($message,$var['header'],'Email');
        }
        //Save attachments if any.
        if($msgid && $cfg->allowEmailAttachments()){
            if(($struct = imap_fetchstructure($this->mbox,$mid)) && $struct->parts) {
                //We've got something...do a search
                foreach($struct->parts as $k=>$part) {
                    if($part && $part->ifdparameters && ($filename=$part->dparameters[0]->value)){ //attachment
                        if($cfg->canUploadFileType($filename) && $cfg->getMaxFileSize()>=$part->bytes) {
                            //extract the attachments...and do the magic.
                            $data=$this->decode($part->encoding, imap_fetchbody($this->mbox,$mid,$k+1));
                            $ticket->saveAttachment($filename,$data,$msgid,'M');
                        }
                    }
                }
            }
        } 
        return $ticket;
    }

    function fetchTickets($emailid,$max=20,$deletemsgs=false){
        
        $nummsgs=imap_num_msg($this->mbox);
        //echo 'New Emails:  '. $nummsgs;
        for($i=1; $i<=$nummsgs; $i++){
            if($this->createTicket($i,$emailid)){
                if($deletemsgs)
                    imap_delete($this->mbox,$i);
            }
            if($max && $i>=$max) 
                break;
        }
        @imap_expunge($this->mbox);
        db_query('UPDATE '.POP3_TABLE.' SET errors=0, lastfetch=NOW() WHERE email_id='.db_input($emailid));
     
        return $i;
    }

    function fetchMail(){
        global $cfg;
      
        if(!$cfg->popAutoFetch())
            return;
      
        //TODO: move hardcoded into DB.
        $MAX_ERRORS=5; //Max errors before we start delayed fetch attempts        
        $sql=' SELECT email.email_id,pophost,popuser,poppasswd,delete_msgs,errors '.
             ' FROM '.EMAIL_TABLE.' email LEFT JOIN '.POP3_TABLE.' pop3  USING(email_id) '.
             ' WHERE popenabled=1 AND (errors<='.$MAX_ERRORS.' OR (TIME_TO_SEC(TIMEDIFF(NOW(),lasterror))>10*60) )'.
             ' AND (lastfetch=0 OR TIME_TO_SEC(TIMEDIFF(NOW(),lastfetch))>fetchfreq*60) ';
        //echo $sql;
        if(!($accounts=db_query($sql)) || !db_num_rows($accounts))
            return;

        while($row=db_fetch_array($accounts)) {
            $pop3 = new POP3($row['popuser'],$row['poppasswd'],$row['pophost']);
            if($pop3->connect()){   
                $pop3->fetchTickets($row['email_id'],30,$row['delete_msgs']?true:false);
                $pop3->close();
            }else{
                $errors=$row['errors']+1;
                db_query('UPDATE '.POP3_TABLE.' SET errors=errors+1, lasterror=NOW() WHERE email_id='.db_input($row['email_id']));
                if($errors>=$MAX_ERRORS){
                    //We've reached the MAX consecutive errors...will attempt logins at delayed intervals
                    $msg="Admin,\n The system is having trouble fetching emails from the following POP account: \n".
                        "\nUser: ".$row['popuser'].
                        "\nHost: ".$row['pophost'].
                        "\nError: ".$pop3->getLastError().
                        "\n\n ".$errors.' consecutive errors. Maximum of '.$MAX_ERRORS. ' allowed'.
                        "\n\n This could be connection issues related to the host. Next delayed login attempt in aprox. 10 minutes";
                    Misc::alertAdmin('POP Failure Alert',$msg);
                }
            }
        }
    }
}
?>
