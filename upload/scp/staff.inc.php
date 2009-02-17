<?php
/*************************************************************************
    staff.inc.php
    
    File included on every staff page...handles logins (security) and file path issues.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
if(basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Habari/Jambo rafiki wangu? '); //Say hi to our friend..
if(!file_exists('../main.inc.php')) die('Fatal error..get tech support');
define('ROOT_PATH','../'); //Path to the root dir.
require_once('../main.inc.php');

if(!defined('INCLUDE_DIR')) die('Fatal error');
/*Some more include defines specific to staff only */
define('STAFFINC_DIR',INCLUDE_DIR.'staff/');



/* Define tag that included files can check */
define('OSTSCPINC',TRUE);
define('OSTSTAFFINC',TRUE);



/* Tables used by staff only */
define('KB_PREMADE_TABLE',TABLE_PREFIX.'kb_premade');


/* include what is needed on staff control panel */

require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.nav.php');


/* First order of the day is see if the user is logged in and with a valid session.
   User must be valid beyond this point 
   ONLY super admins can access the helpdesk on offline state.
*/

function staffLoginPage($msg) {
    $dest=THISPAGE;
    $msg=$msg;
    require('login.php');
    exit;
}
$thisuser = new StaffSession($_SESSION['_staff']['userID']); /*always reload???*/
//1) is the user Logged in for real && is staff.
if(!is_object($thisuser) || !$thisuser->getId() || !$thisuser->isValid()){
    $msg=(!$thisuser || !$thisuser->isValid())?'Authentication Required':'Session timed out due to inactivity';
    staffLoginPage($msg);
    exit;
}

//2) if not super admin..check system and group status
if(!$thisuser->isadmin()){
    if($cfg->isHelpDeskOffline()){
        staffLoginPage('System Offline');
        exit;
    }

    if(!$thisuser->isactive() || !$thisuser->isGroupActive()) {
        staffLoginPage('Access Denied. Contact Admin');
    }
}

//Keep the session activity alive
$thisuser->refreshSession();
//Set staff's timezone offset.
$_SESSION['TZ_OFFSET']=$thisuser->getTZoffset();
//Clear some vars. we use in all pages.
$errors=array();
$msg=$warn='';
$tabs=array();
$submenu=array();

$nav = new StaffNav(strcasecmp(basename($_SERVER['SCRIPT_NAME']),'admin.php')?'staff':'admin');
//Check for forced password change.
if($thisuser->forcePasswdChange()){
    require('profile.php'); //profile.php must request this file as require_once to avoid problems.
    exit;
}
?>
