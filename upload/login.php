<?php
/*********************************************************************
    index.php

    Client Login 

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require_once('main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error');
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE); //make includes happy

require_once(INCLUDE_DIR.'class.client.php');
require_once(INCLUDE_DIR.'class.ticket.php');
//We are ready baby
$loginmsg='Authentication Required';
if($_POST && (!empty($_POST['lemail']) && !empty($_POST['lticket']))):
    $loginmsg='Authentication Required';
    $email=trim($_POST['lemail']);
    $ticketID=trim($_POST['lticket']);
    //$_SESSION['_client']=array(); #Uncomment to disable login strikes.
    
    //Check time for last max failed login attempt strike.
    //Must wait for 5 minutes after each strike.
    if($_SESSION['_client']['laststrike'] && (time()-$_SESSION['_client']['laststrike']<5*60))
        $errors['err']='You\'ve reached maximum failed login attempts allowed. Try again after 5 minutes or <a href="open.php">open a new ticket</a>';
    //See if we can fetch local ticket id associated with the ID given
    if(!$errors && is_numeric($ticketID) && Validator::is_email($email) && ($tid=Ticket::getIdByExtId($ticketID))) {
        //At this point we know the ticket is valid.
        $ticket= new Ticket($tid);
        
        //TODO: 1) Check how old the ticket is...3 months max?? 2) Must be the latest 5 tickets?? 
        
        //Check the email given.
        if($ticket->getId() && strcasecmp($ticket->getEMail(),$email)==0){
            //valid match...create session goodies for the client.
            $user = new ClientSession($email,$ticket->getId());
          
            $_SESSION['_client']=array(); //clear.
            $_SESSION['_client']['userID']   =$ticket->getEmail(); //Email
            $_SESSION['_client']['key']      =$ticket->getExtId(); //Ticket ID --acts as password when used with email. See above.
            $_SESSION['_client']['token']    =$user->getSessionToken();
            $_SESSION['TZ_OFFSET']=$cfg->getTZoffset();
            $_SESSION['daylight']=$cfg->observeDaylightSaving();

            //Redirect view.php
            @header("Location: view.php");
            require('view.php'); //Just incase. of header already sent error.
            exit;
        }
    }
    //If we get to this point we know the login failed.
    //TODO: login strikes should be DB based for better security checks ( session can be reset!)
    $loginmsg='Invalid login';
    $_SESSION['_client']['strikes']+=1;
    if(!$errors && $_SESSION['_client']['strikes']>3) {
        $errors['err']='Forgot your login info? Please <a href="open.php">open a new ticket</a>.';
        $_SESSION['_client']['laststrike']=time();
        if($cfg->alertONLoginError()) {
            $alert='Excessive login attempts by a client'."\n".
                'Email: '.$_POST['lemail']."\n".'Ticket#: '.$_POST['lticket']."\n".
                'IP: '.$_SERVER['REMOTE_ADDR']."\n".'Time:'.date('M j, Y, g:i a T')."\n\n".
                'Attempts #'.$_SESSION['_client']['strikes'];
            Misc::alertAdmin('Excessive login attempts (client)',$alert);
        }
    }
endif;
require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.'login.inc.php');
require(CLIENTINC_DIR.'footer.inc.php');
?>
