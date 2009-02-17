<?php
/*********************************************************************
    login.php

    Handles staff authentication/logins

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error. Kwaheri!');

require_once(INCLUDE_DIR.'class.staff.php');

$msg=$msg?$msg:'Authentication Required';
if($_POST && (!empty($_POST['username']) && !empty($_POST['passwd']))){
    //$_SESSION['_staff']=array(); #Uncomment to disable login strikes.
    $msg='Invalid login';
    if($_SESSION['_staff']['laststrike'] && (time()-$_SESSION['_staff']['laststrike']<3*60)) {
        $msg='Max failed login Reached';
        $errors['err']='You\'ve reached maximum failed login attempts allowed.';
    }
    if(!$errors && ($user=new StaffSession($_POST['username'])) && $user->getId() && $user->check_passwd($_POST['passwd'])){
        db_query('UPDATE '.STAFF_TABLE.' SET lastlogin=NOW() WHERE staff_id='.db_input($user->getId()));
        //We got a matching user and the password matched!! Nice.
        //Now set session crap and lets roll baby!
        $_SESSION['_staff']=array(); //clear.
        $_SESSION['_staff']['userID']=$_POST['username'];
        $user->refreshSession(); //set the hash.
        $_SESSION['TZ_OFFSET']=$user->getTZoffset();
        $_SESSION['daylight']=$cfg->observeDaylightSaving();
        //Redirect to the original destination. (make sure it is not redirecting to login page.)
        $dest=($_POST['dest'] && !strstr($_POST['dest'],'login.php'))?$_POST['dest']:'index.php';
        @header("Location: $dest");
        require('index.php'); //Just incase header is messed up.
        exit;
    }
    //If we get to this point we know the login failed.
    //TODO: login strikes should be DB based for better security checks ( session can be reset!)
    $msg='Invalid login';
    $_SESSION['_staff']['strikes']+=1;
    if(!$errors && $_SESSION['_staff']['strikes']>3) {
        $msg='Access Denied';
        $errors['err']='Forgot your login info? Contact IT Dept.';
        $_SESSION['_staff']['laststrike']=time();
        //Send alerts
        if($cfg->alertONLoginError()) {
            $alert='Excessive login attempts by a staff member?'."\n".
                'Username: '.$_POST['username']."\n".'IP: '.$_SERVER['REMOTE_ADDR']."\n".'TIME: '.date('M j, Y, g:i a T')."\n\n".
                'Attempts #'.$_SESSION['_staff']['strikes'];
            Misc::alertAdmin('Excessive login attempts (staff)',$alert);
        }
    }
}
define("OSTSCPINC",TRUE); //Make includes happy!
$login_err=($_POST)?true:false; //error displayed only on post
include_once(INCLUDE_DIR.'staff/login.tpl.php');
?>
