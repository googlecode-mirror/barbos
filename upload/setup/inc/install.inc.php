<?php

$info=($errors && $_POST)?Format::htmlchars($_POST):array(); //use post data.
if(!isset($info['url'])) {
   $info['url']=rtrim('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']),'setup'); //coolio 
}
if(!isset($info['title'])) {
    $info['title']='osTicket :: Support Ticket System';
}
if(!isset($info['dbhost'])) {
    $info['dbhost']='localhost';
}
if(!isset($info['prefix'])) {
    $info['prefix']='ost_';
}


?>
<form action=index.php method=post name=setup>
<table width="100%" cellspacing="0" cellpadding="2" class="setup">
    <tr class="title"><td>Helpdesk Title</td></tr>
    <tr class="subtitle"><td>Title that will be shown as the title tag.</td></tr>
    <tr>
        <td>HelpDesk Title: &nbsp; <input type=text name=title size=40 value="<?=$info['title']?>">
            &nbsp;<font class="error"><?=$errors['title']?></font></td>
    </tr>
    <tr class="title"><td>Web Path to osTicket</td></tr>
    <tr class="subtitle"><td>This is the url to osTicket installation on your server.</td></tr>
    <tr><td>HelpDesk URL: &nbsp; <input type=text name=url size=60 value="<?=$info['url']?>">
            &nbsp;<font class="error"><?=$errors['url']?></font></td>
    </tr>
    <tr class="title"><td>System Email</td></tr>
    <tr class="subtitle"><td>Default system email (e.g support@yourdomain.com) You can change or add more emails later.</td></tr>
    <tr><td>Default Email: &nbsp; <input type=text name=sysemail size=45 value="<?=$info['sysemail']?>">
            &nbsp;<font class="error"><?=$errors['sysemail']?></font></td>
    </tr> 
    <tr class="title"><td>Admin User</td></tr>
    <tr class="subtitle"><td>Min of six characters for the password. You can change or add more users later.</td></tr>
    <tr>
        <td>
         <table border=0 cellspacing=0 cellpadding=2 class="clean">
            <tr><td>Username:</td>
                <td><input type=text name=username size=20 value="<?=$info['username']?>">
                    &nbsp;<font class="error"><?=$errors['username']?></font></td></tr>
            <tr><td>Password:</td>
                <td><input type=password name=password size=20 value="<?=$info['password']?>">
                    &nbsp;<font class="error"><?=$errors['password']?></font></td></tr>
            <tr><td>Password (again):</td>
                <td><input type=password name=password2 size=20 value="<?=$info['password2']?>">
                    &nbsp;<font class="error"><?=$errors['password2']?></font></td>
            </tr>
            <tr><td>Email:</td><td><input type=text name=email size=40 value="<?=$info['email']?>">
                    &nbsp;<font class="error"><?=$errors['email']?></font></td></tr>
         </table>
        </td>
    </tr>
    <tr class="title"><td>Database</td></tr>
    <tr class="subtitle"><td>MySQL is the only database supported at the moment.</td></tr>
    <tr>
        <td><span class="error"><b><?=$errors['mysql']?></b></span>
         <table cellspacing=1 cellpadding=2 border=0>
            <tr><td>MySQL Table Prefix:</td><td><input type=text name=prefix size=20 value="<?=$info['prefix']?>" >
                    <font class="error"><?=$errors['prefix']?></font></td></tr>
            <tr><td>MySQL Hostname:</td><td><input type=text name=dbhost size=20 value="<?=$info['dbhost']?>" >
                    <font class="error"><?=$errors['dbhost']?></font></td></tr>
            <tr><td>MySQL Database:</td><td><input type=text name=dbname size=20 value="<?=$info['dbname']?>">
                    <font class="error"><?=$errors['dbname']?></font></td></tr>
            <tr><td>MySQL Username:</td><td><input type=text name=dbuser size=20 value="<?=$info['dbuser']?>">
                    <font class="error"><?=$errors['dbuser']?></font></td></tr>
            <tr><td>MySQL Password:</td><td><input type=password name=dbpass size=20 value="<?=$info['dbpass']?>">
                    <font class="error"><?=$errors['dbpass']?></font></td></tr>
         </table>
        </td>
    </tr>
</table>
<div align="center">
    <input class="button" type=submit value="Install">
    <input class="button" type=reset name=reset value="Reset">
</div>
</form>
