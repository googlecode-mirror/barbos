<?php
/*********************************************************************
    mysql.php

    Collection of MySQL helper interface functions. 
    Mostly wrappers with error checking.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

    function db_connect($dbhost,$dbuser, $dbpass,$dbname = "") {
        
        if(!strlen($dbuser) || !strlen($dbpass) || !strlen($dbhost))
      	    return NULL;

        @$$dblink =mysql_connect($dbhost, $dbuser, $dbpass);
        if($$dblink && $dbname)
            @mysql_select_db($dbname);
        //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
        if($$dblink) {
            @mysql_query('SET NAMES "UTF8"');
            @mysql_query('SET COLLATION_CONNECTION=utf8_general_ci');
        }
        return $$dblink;	
    }

    function db_close(){
        global $$dblink;
        return @mysql_close($$dblink);
    }

    function db_select_database($dbname) {
         return @mysql_select_db($dbname);
    }

    function db_version(){
        preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', mysql_result(db_query('SELECT VERSION()'),0,0),$matches);
        return $matches[1];
    }
       
	// execute sql query
	function db_query($query, $database="",$conn=""){
        global $cfg;
       
		if($conn){ /* connection is provided*/
            $response=($database)?mysql_db_query($database,$query,$conn):mysql_query($query,$conn);
   	    }else{
            $response=($database)?mysql_db_query($database,$query):mysql_query($query);
   	    }

   	    if(!$response && (is_object($cfg) && $cfg->alertONSQLError())) { //error reporting
            $msg='['.$query.'] - '.db_error();
            Misc::alertAdmin('DB Error',$msg);
            //echo $msg; #uncomment during debuging or dev.
        }
        return $response;
	}

	function db_squery($query){ //smart db query...utilizing args and sprintf
	
		$args  = func_get_args();
  		$query = array_shift($args);
  		$query = str_replace("?", "%s", $query);
  		$args  = array_map('db_real_escape', $args);
  		array_unshift($args,$query);
  		$query = call_user_func_array('sprintf',$args);
		return db_query($query);
	}

	function db_count($query){		
		$res=db_fetch_row(db_query($query));
		return ($res)?$res[0]:0;
	}

	function db_fetch_array($result,$mode=false) {
   	    return ($result)?db_output(mysql_fetch_array($result,($mode)?$mode:MYSQL_ASSOC)):null;
  	}

    function db_fetch_row($result) {
        return ($result)?db_output(mysql_fetch_row($result)):NULL;
    }

    function db_fetch_fields($db_res) {
        return mysql_fetch_field($db_res);
    }   

    function db_assoc_array($res,$mode=false){
	    if($res && db_num_rows($res)){
      	    while ($row=db_fetch_array($res,$mode))
         	    $results[]=$row;
        }
        return $results;
    }

    function db_num_rows($db_res) {
   	    return ($db_res)?mysql_num_rows($db_res):0;
    }

	function db_affected_rows() {
      return mysql_affected_rows();
    }

  	function db_data_seek($db_res, $row_number) {
   	    return mysql_data_seek($db_res, $row_number);
  	}

  	function db_data_reset($db_res){
   	    return mysql_data_seek($db_res,0);
  	}

  	function db_insert_id() {
   	    return mysql_insert_id();
  	}

	function db_free_result($db_res) {
   	    return mysql_free_result($db_res);
  	}
  
	function db_output($param) {

        return $param;
        //Note: NO need to strip slashes since we are escaping strings using mysql_real_escape ..03/14/08 peter.
   	    if(is_string($param)) {
      	    return trim(stripslashes($param));
  	    }elseif (is_array($param)) {
      	    reset($param);
      	    while(list($key, $value) = each($param)) {
        	    $param[$key] = db_output($value);
      	    }
      	    return $param;
    	}
        return $param;
  	}

    //Do not call this function directly...use db_input
    function db_real_escape($val,$quote=false){
        global $$dblink;

        //We turn off magic_quotes_gpc in main.inc.php BUT leaving the check here just incase some idiot removes it.
        if(get_magic_quotes_gpc()) //Avoid double quotes...
            $val= stripslashes($val);

        $val=($$dblink)?mysql_real_escape_string($val):mysql_escape_string($val);

        return ($quote)?"'$val'":$val;
    }

    function db_input($param,$quote=true) {

        //is_numeric doesn't work all the time...9e8 is considered numeric..which is correct...but not expected.
        if($param && preg_match("/^\d+(\.\d+)?$/",$param))
            return $param;

        if($param && is_array($param)){
            reset($param);
            while (list($key, $value) = each($s)) {
                $param[$key] = db_input($value,$quote);
            }
            return $param;
        }
        return db_real_escape($param,$quote);
    }

	function db_error(){
   	    return mysql_error();   
	}
   
    function db_errno(){
        global $$dblink;
        return mysql_errno($$dblink);
    }
?>
