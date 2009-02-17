<h1>Basic Installation Completed</h1>
<P>Congratulations your new support ticket system is installed. Your next step is to fully configure your new support ticket system for use, but before you get to it please take a minute to cleanup.
<ul>
    <li><strong>Change permission of ostconfig.php to remove write access</strong><br>
        Chmod 644 ostconfig.php</li>
    <li><strong>Delete install directory</strong><br>
     After verifying that your installation completed correctly please delete setup folder.</li>
    <?if(ini_get('register_globals')) { ?>
     <li><strong>Disable register globals (optional)</strong><br>
     If you don't have a good reason why register globals is enabled then please disable it.
     </li> 
    <?}?>
</ul>
</p>
<h3>Post-Install Setup</h3>
<p>You can now log in to <a href="../scp/admin.php" target="_blank">Admin Panel</a> with the username and password you created during the install process. After a successful log in, you can proceed with post-install setup. For complete and upto date guide see <a href="http://osticket.com/wiki/Post-Install_Setup_Guide" target="_blank">osTicket wiki</a></p>
<h3>Commercial Support Available</h3>
<p>Don't let technical problems impact your customer support experience. Get guaranteed response times and access to the core development team.
We can also customize the system to meet your unique needs. For more information or to discuss your needs, please <a href="http://osticket.com/support/contact.php" target="_blank">contact us today</a></p>
<br/> <br>
<div align="center"><b>
    <a href="../scp/admin.php" target="_blank">Admin Panel</a>
    &nbsp;&nbsp;&nbsp;&nbsp;
    <a href="http://osticket.com/wiki/Post-Install_Setup_Guide" target="_blank">Post Install Guide</a>
    </b>
</div
