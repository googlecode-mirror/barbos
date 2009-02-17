<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Access Denied');
//Get the config info.
$config=($errors && $_POST)?Format::htmlchars($_POST):$cfg->getConfig();
?>
<div class="msg">API Settings</div>
<table border="0" cellspacing=0 cellpadding=0>
    <form action="admin.php?t=api" method="post">
    <input type="hidden" name="t" value="api">
    <tr>
        <td valign="top" >Pass Phrase:</td>
        <td>
            <input type="text" name="api_key" value="<?=$config['api_key']?>" size=35> 
                        &nbsp;<font class="error"><?=$errors['api_key']?></font>
        </td>
    </tr>
    <tr>
        <td valign="top">Allowed Hosts:</td>
        <td>
            <i>Enter IP addresses separated by a comma. e.g 192.168.1.2,192.168.1.4 </i>
            <textarea name="api_whitelist" cols="21" rows="4" style="width: 65%;" wrap=HARD ><?=$config['api_whitelist']?></textarea>
            <br/><font class="error">&nbsp;<?=$errors['api_whitelist']?></font>
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>
            <input class="button" type="submit" name="submit" value="Submit">
            <input class="button" type="reset" name="reset" value="Reset">

        </td>
    </tr>
  </form>
</table>
