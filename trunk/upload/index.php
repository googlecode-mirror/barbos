<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('client.inc.php');
require(CLIENTINC_DIR.'header.inc.php');
?>
 <div>
    <p><?=$lang['index']['welcome_title']?></p>
    <p><?=$lang['index']['welcome_text']?></p>
    <br>
    <div id="index">
        <div class="box">
          <img src="./images/new_ticket_title.jpg" width="186" height="50" align="left">
            <p><?=$lang['index']['new_ticket_text']?></p>
            <p><a class="btn" href="new.php"><?=$lang['index']['new_ticket_button']?></a>
        </div>
        <img id="bar" src="./images/verticalbar.jpg" width="21" height="266" alt="|">
        <div class="box">
          <img src="./images/ticket_status_title.jpg" width="186" height="50" align="right">
            <p><?=$lang['index']['open_ticket_text']?></p>
          <form action="view.php" method="post">
            <fieldset>
            <label><?=$lang['index']['open_ticket_form_email']?></label>
              <input type="text" name="lemail">
            </fieldset>
            <fieldset>
            <label><?=$lang['index']['open_ticket_form_ticket']?></label>
              <input type="text" name="lticket">
            </fieldset>
            <br>
            <input type="submit" class="btn" value="<?=$lang['index']['open_ticket_form_submit']?>">
        </form>
        </div>
    </div>
    <div style="clear:both"></div>
 </div>
<?require(CLIENTINC_DIR.'footer.inc.php'); ?>
