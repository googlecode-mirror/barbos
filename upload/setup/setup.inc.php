<?php
/*********************************************************************
    setup.inc.php

    Master include file for setup/install scripts.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
#php setting we might want to control.
error_reporting(E_ALL ^ E_NOTICE); //turn on errors
ini_set('magic_quotes_gpc', 0);
ini_set('session.use_trans_sid', 0);
ini_set('session.cache_limiter', 'nocache');
ini_set('display_errors',1); //We want the user to see errors during install process.
ini_set('display_startup_errors',1);

#start session
session_start();

#define paths
define('ROOT_PATH','../');
define('ROOT_DIR','../');
define('INCLUDE_DIR',ROOT_DIR.'include/');

#required files
require(INCLUDE_DIR.'mysql.php');
require(INCLUDE_DIR.'class.validator.php');
require(INCLUDE_DIR.'class.format.php');
require(INCLUDE_DIR.'class.misc.php');

#Table Prefix: TABLE_PREFIX must be defined by the caller 
function replace_table_prefix($query) {
    return str_replace('%TABLE_PREFIX%',PREFIX, $query);
}

?>
