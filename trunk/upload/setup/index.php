<?php
/*********************************************************************
    index.php

    osTicket Installer.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('setup.inc.php');
$errors=array();
$fp=null;
define('VERSION','1.6 RC4'); //Current version number
define('CONFIGFILE','../ostconfig.php'); //osTicket config file full path.
define('SCHEMAFILE','./osticket.sql'); //osTicket schema.
$install='<strong>Need help?</strong> <a href="http://www.osticket.com/support/" target="_blank">Professional Installation Available</a>';
$support='<strong>Get a peace of mind</strong> <a href="http://www.osticket.com/support/" target="_blank">Commercial Support Available</a>';

//Basic checks 
$inc='install.inc.php';
$info=$install;
if((double)phpversion()<4.3){ //Old PHP installation
    $errors['err']='PHP installation seriously out of date';
    $inc='upgradephp.inc.php';
}elseif(!file_exists(CONFIGFILE) || !is_writable(CONFIGFILE)) { //writable config file??
    $errors['err']='Configuration file is not writable';
    $inc='chmod.inc.php';
}else {
    $configfile=file_get_contents(CONFIGFILE); //Get the goodies...peek and tell.
    //Make SURE this is a new installation. 
    if(preg_match("/define\('OSTINSTALLED',TRUE\)\;/i",$configfile) || !strpos($configfile,'%CONFIG-DBHOST')){
        $errors['err']='Configuration file already modified!';
        $inc='unclean.inc.php';
    }elseif($_POST){
        $f=array();
        $f['title']     = array('type'=>'string', 'required'=>1, 'error'=>'Title required');
        $f['url']       = array('type'=>'url',    'required'=>1, 'error'=>'URL required.');
        $f['sysemail']  = array('type'=>'email',  'required'=>1, 'error'=>'Valid email required');
        $f['username']  = array('type'=>'username', 'required'=>1, 'error'=>'Username required');
        $f['password']  = array('type'=>'password', 'required'=>1, 'error'=>'Password required');
        $f['password2'] = array('type'=>'password', 'required'=>1, 'error'=>'Confirm password');
        $f['email']     = array('type'=>'email',  'required'=>1, 'error'=>'Valid email required');
        $f['dbhost']    = array('type'=>'string', 'required'=>1, 'error'=>'Hostname required');
        $f['dbname']    = array('type'=>'string', 'required'=>1, 'error'=>'Database name required');
        $f['dbuser']    = array('type'=>'string', 'required'=>1, 'error'=>'Username required');
        $f['dbpass']    = array('type'=>'string', 'required'=>1, 'error'=>'password required');
        $f['prefix']    = array('type'=>'string', 'required'=>1, 'error'=>'Table prefix required');
        
        $validate = new Validator($f);
        if(!$validate->validate($_POST)){
            $errors=array_merge($errors,$validate->errors());
        }
        if($_POST['sysemail'] && $_POST['email'] && !strcasecmp($_POST['sysemail'],$_POST['email']))
            $errors['email']='Conflicts with system email above';
        if(!$errors && strcasecmp($_POST['password'],$_POST['password2']))
            $errors['password2']='passwords to not match!';
        //Check table prefix underscore required at the end!
        if($_POST['prefix'] && substr($_POST['prefix'], -1)!='_')
            $errors['prefix']='Bad prefix. Must have underscore (_) at the end. e.g \'ost_\'';
        
        //Connect to the DB
        if(!$errors && !db_connect($_POST['dbhost'],$_POST['dbuser'],$_POST['dbpass']))
            $errors['mysql']='Unable to connect to MySQL server. Possibly invalid login info. <br>'; 
        //check mysql version
        if(!$errors && (db_version()<'4.1.1'))
            $errors['mysql']='osTicket requires MySQL 4.1.1 or better! Please upgrade';
        
        //Select the DB
        if(!$errors && !db_select_database($_POST['dbname'])) {
            //Try creating the missing DB
            if(!db_query('CREATE DATABASE '.$_POST['dbname'])) {
                $errors['dbname']='Database doesn\'t exist';
                $errors['mysql']='Unable to create the database due to permission';
            }elseif(!db_select_database($_POST['dbname'])) {
                $errors['dbname']='Unable to select the database';
            }
        }
        //Get database schema
        if(!$errors && (!file_exists(SCHEMAFILE) || !($schema=file_get_contents(SCHEMAFILE)))) {
            $errors['err']='Internal error. Please make sure your download is the latest';
            $errors['mysql']='Missing SQL schema';
        }
        //Open the file for writing..
        if(!$errors && !($fp = @fopen(CONFIGFILE,'r+'))){
            $errors['err']='Unable to open config file for writting. Permission denied!';
        }

        //IF no errors..Do the install. Let the fun start...
        if(!$errors && $schema && $fp) {
            define('ADMIN_EMAIL',$_POST['email']); //Needed to report SQL errors during install.
            define('PREFIX',$_POST['prefix']); //Table prefix
            //Loadup SQL schema.
            $queries =array_map('replace_table_prefix',array_filter(array_map('trim',explode(';',$schema)))); //Don't fail me bro!
            foreach($queries as $k=>$sql) {
                if(!db_query($sql)){
                    //Aborting on error.
                    $errors['err']='Invalid SQL schema. Get help from Developers';
                    $errors['mysql']='You have an error in your SQL syntax ';
                    break;
                }
            }
            if(!$errors) {
                $info=$support;
                $configfile= str_replace("define('OSTINSTALLED',FALSE);","define('OSTINSTALLED',TRUE);",$configfile);
                $configfile= str_replace('%ADMIN-EMAIL',$_POST['email'],$configfile);
                $configfile= str_replace('%CONFIG-DBHOST',$_POST['dbhost'],$configfile);
                $configfile= str_replace('%CONFIG-DBNAME',$_POST['dbname'],$configfile);
                $configfile= str_replace('%CONFIG-DBUSER',$_POST['dbuser'],$configfile);
                $configfile= str_replace('%CONFIG-DBPASS',$_POST['dbpass'],$configfile);
                $configfile= str_replace('%CONFIG-PREFIX',$_POST['prefix'],$configfile);
                if(ftruncate($fp,0) && fwrite($fp,$configfile)){
                    $tzoffset= date("Z")/3600; //Server's offset.
                    list($uname,$domain)=explode('@',$_POST['sysemail']);
                    //Create admin user. Dummy last name.
                    $sql='INSERT INTO '.PREFIX.'staff SET created=NOW(), isadmin=1,change_passwd=0,group_id=1,dept_id=1 '.
                        ',email='.db_input($_POST['email']).',lastname='.db_input('Admin').
                        ',username='.db_input($_POST['username']).',passwd='.db_input(MD5($_POST['password'])).
                        ',timezone_offset='.db_input($tzoffset);
                    db_query($sql);
                    //Add support email.
                    db_query('INSERT INTO '.PREFIX.'email VALUES (1,0,2,1,'.db_input($_POST['sysemail']).',"",NOW(),NOW())');
                    //Update config info
                    $sql='UPDATE '.PREFIX.'config SET default_email=1,default_dept=1,timezone_offset='.db_input($tzoffset).
                         ',ostversion='.db_input(VERSION).',admin_email='.db_input($_POST['email']).
                         ',alert_email='.db_input('alerts@'.$domain).',noreply_email='.db_input('noreply@'.$domain).
                         ',helpdesk_url='.db_input($_POST['url']).',helpdesk_title='.db_input($_POST['title']);
                    db_query($sql);
                    //Create a ticket to make the system warm and happy.
                    $tid=Misc::randNumber(6);
                    $sql='INSERT INTO '.PREFIX.'ticket SET created=NOW(),ticketID='.db_input($tid).
                        ",priority_id=2,dept_id=1,email='support@osticket.com',name='osTicket Support' ".
                        ",subject='osTicket Installed!',status='open',source='Web'";
                    if(db_query($sql) && ($id=db_insert_id())){
                        $intro="\nThank you for choosing osTicket.\n
                            Make sure you join osTicket forums http://osticket.com/forums to stay upto date on the latest news, security alerts and updates. osTicket forums is also a great place to get assistance, guidance and help. In addition to the forums, osTicket wiki provides useful collection of educational materials, documentation, and notes from the community.\n\n If you are looking for greater level of support, we provide professional services and custom commercial support with guaranteed response times and access to the core development team. We can also customize the system to meet your unique needs.\n
                                For more information or to discuss your needs, please contact us today. Any feedback will be appreciated!
                                \nosTicket Team";
                        db_query('INSERT INTO '.PREFIX."ticket_message VALUES (1,$id,".db_input($intro).",NULL,'Web','',NOW(),NULL)");
                    }
                    $msg='Congratulations osTicket basic installation completed!';
                    $inc='done.inc.php';
                }else{
                    $errors['err']='Unable to write to config file!';
                }
            }
            @fclose($fp);
        }else{
            
            $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Please correct them and try again';
        }
    }
}
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>osTicket Installer</title>
<link rel="stylesheet" href="style.css" media="screen">
</head>
<body>
<div id="container">
    <div id="header">
        <a id="logo" href="index.php" title="osTicket"><img src="images/ostlogo.jpg" width="188" height="72" alt="osTicket"></a>
        <p id="info"><?=$info?></p>
    </div>
    <div id="nav">
        <ul id="sub_nav">
            <li>osTicket Basic Installation</li>
        </ul>
    </div>
    <div class="clear"></div>
    <div id="content" width="100%">
       <div>
            <?if($errors['err']) {?>
                <p align="center" id="errormessage"><?=$errors['err']?></p>
            <?}elseif($msg) {?>
                <p align="center" id="infomessage"><?=$msg?></p>
            <?}elseif($warn) {?>
                <p align="center" id="warnmessage"><?=$warn?></p>
            <?}?>
        </div>
        <div>
        <?php
            require("./inc/$inc");
        ?>
        </div>
    </div>
    <div id="footer">Copyright &copy; <?=date('Y')?>&nbsp;osTicket.com. &nbsp;All Rights Reserved.</div>
</div>
</body>
</html>
