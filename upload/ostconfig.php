<?php
/*********************************************************************
    ostconfig.php

    Static osTicket configuration file. Mainly useful for mysql login info.
    Created during installation process and shouldn't change even on upgrades.
   
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
#Disable direct access.
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__)) || !defined('ROOT_PATH')) die('kwaheri rafiki!');

#Install flag
define('OSTINSTALLED',FALSE);
if(OSTINSTALLED!=TRUE){
 Header('Location: '.ROOT_PATH.'setup/');
 exit;
}

#Default admin email. Used only on db connection issues and related alerts.
define('ADMIN_EMAIL','%ADMIN-EMAIL');

#Mysql Login info
define('DBTYPE','mysql');
define('DBHOST','%CONFIG-DBHOST'); 
define('DBNAME','%CONFIG-DBNAME');
define('DBUSER','%CONFIG-DBUSER');
define('DBPASS','%CONFIG-DBPASS');
#Table prefix
define('TABLE_PREFIX','%CONFIG-PREFIX');

#Default language
define('LANG','English');

?>
