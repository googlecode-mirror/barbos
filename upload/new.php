<?php
/*********************************************************************
    new.php

    New tickets handle.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com
    
    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
include('client.inc.php');
define('SOURCE','Web'); //Ticket source.
$inc='new.inc.php';    //default include.
$errors=array();
if($_POST):
    $_POST['deptId']=$_POST['emailId']=0; //Just Making sure we don't accept crap...only topicId is expected.
    //Ticket::create...checks for errors..
    if(($ticket=Ticket::create($_POST,$errors,SOURCE))){
        $msg=$lang['message']['request_created'];
        if($thisclient && $thisclient->isValid()) //Logged in...simply view the newly created ticket.
            @header('Location: view.php?id='.$ticket->getExtId());
        //Thank the user and promise speedy resolution!
        $inc='thankyou.inc.php';
    }else{
        $errors['err']=$errors['err']?$errors['err']:$lang['error']['unable_to_create'];
    }
endif;

//page
require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.$inc);
require(CLIENTINC_DIR.'footer.inc.php');
?>
