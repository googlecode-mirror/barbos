<?php
$title=($cfg && is_object($cfg))?$cfg->getTitle():'osTicket :: Support Ticket System';

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?=Format::htmlchars($title)?></title>
    <link rel="stylesheet" href="./styles/main.css" media="screen">
    <link rel="stylesheet" href="./styles/colors.css" media="screen">
</head>
<body>
<div id="container">
    <div id="header">
    <a id="logo" href="index.php" title="<?=$lang['global']['logo_alt']?>"><img src="./images/logo2.jpg" border=0 alt="<?=$lang['global']['logo_alt']?>"></a>
        <p><?=$lang['global']['title']?></p>
    </div>
    <ul id="nav">
         <?                    
         if(is_object($thisclient) && $thisclient->isValid()) {?>
           <li><a class="log_out" href="logout.php"><?=$lang['global']['logout'] ?></a></li>
           <li><a class="my_tickets" href="view.php"><?=$lang['global']['my_tickets'] ?></a></li>
         <?}else {?>
         <li><a class="ticket_status" href="view.php"><?=$lang['global']['ticket_status'] ?></a></li>
         <?}?>
         <li><a class="new_ticket" href="new.php"><?=$lang['global']['new_ticket'] ?></a></li>
         <li><a class="home" href="index.php"><?=$lang['global']['home'] ?></a></li>
    </ul>
    <div id="content">
