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
    <p>Welcome to the support center.</p>
    <p>In order to streamline support requests and better serve you, we utilize a support ticket system. Every support request is assigned a unique ticket number which you can use to track the progress and responses online. For your reference we provide complete archives and history of all your support requests.</p>
    <br>
    <div id="index">
        <div class="box">
          <img src="./images/new_ticket_title.jpg" width="186" height="50" align="left">
          <p>Submit a new support request. Please provide as much detail as possible so we can best assist you. To update a previously submitted ticket, please use the form to the right. A valid email address is required.</p>
            <p><a class="btn" href="open.php">Open New Ticket</a>
        </div>
        <img id="bar" src="./images/verticalbar.jpg" width="21" height="266" alt="|">
        <div class="box">
          <img src="./images/ticket_status_title.jpg" width="186" height="50" align="right">
          <p>Check status of previously opened ticket. we provide archives and history of all your support requests complete with responses.</p>
          <form action="view.php" method="post">
            <fieldset>
              <label>Email:</label>
              <input type="text" name="lemail">
            </fieldset>
            <fieldset>
              <label>Ticket#:</label>
              <input type="text" name="lticket">
            </fieldset>
            <br>
          <input type="submit" class="btn" value="Check Status">
        </form>
        </div>
    </div>
    <div style="clear:both"></div>
 </div>
<?require(CLIENTINC_DIR.'footer.inc.php'); ?>
