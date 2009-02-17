<?php
/*********************************************************************
    class.misc.php

    Misc collection of useful generic helper functions.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
class Misc {
    
	function randCode($len=8) {
		return substr(strtoupper(base_convert(microtime(),10,16)),0,$len);
	}
    
    /* Helper used to generate ticket IDs */
    function randNumber($len=6,$start=false,$end=false) {

        mt_srand ((double) microtime() * 1000000);
        $start=(!$len && $start)?$start:str_pad(1,$len,"0",STR_PAD_RIGHT);
        $end=(!$len && $end)?$end:str_pad(9,$len,"9",STR_PAD_RIGHT);
        
        return mt_rand($start,$end);
    }

    /* misc date helpers...this will go away once we move to php 5 */ 
    function db2gmtime($var){
        global $cfg;
        if(!$var) return;
        
        $dbtime=is_int($var)?$var:strtotime($var);
        return $dbtime-($cfg->getMysqlTZoffset()*3600);
    }
    
    /*Helper get GM time based on timezone offset*/
    function gmtime() {
        return time()-date('Z');
    }
   
    /* Helper to send an alert to admin EMAIL */
    function alertAdmin($subj,$msg) {
        global $cfg;
        $to=$cfg?$cfg->getAdminEmail():ADMIN_EMAIL;
        $from=$cfg?$cfg->getAlertEmail():ADMIN_EMAIL;
        //Send alert to admin.
        Misc::sendmail($to,$subj,$msg,$from);   
    }
 
    /* Send email out after minor cleanups..*/   
	function sendmail($to, $subject, $message, $fromaddress,$fromname='', $xheaders = '') {
        //TODO: provide an option to use SMTP server. Log all outgoing emails?? 
    
        $eol="\n";
        $fromname=$fromname?$fromname:$fromaddress;
        //do some cleanup...avoid stupid errors.
        $to=preg_replace("/(\r\n|\r|\n)/s",'', trim($to));
        $subject=preg_replace("/(\r\n|\r|\n)/s",'', trim($subject));
        $message = preg_replace("/(\r\n|\r)/s", "\n", trim($message));
        #Headers
        $headers .= "From: ".$fromname."<".$fromaddress.">".$eol;
        $headers .= "Reply-To: ".$fromname."<".$fromaddress.">".$eol;
        $headers .= "Return-Path: ".$fromname."<".$fromaddress.">".$eol;
        $headers .= "Message-ID: <".time()."-".$fromaddress.">".$eol;
        $headers .= "X-Mailer: osTicket v 1.6".$eol;
        if($xheaders) { //possibly attachments...does mess with content type
            $headers .= $xheaders;
        }else{
            $headers .= "Content-Type: text/plain; charset=utf-8".$eol;
            $headers .= "Content-Transfer-Encoding: 8bit".$eol;
        }
        //echo "[$to,$subject,$message ".$headers.']';
        mail($to,$subject,$message,trim($headers));
    }

}
?>
