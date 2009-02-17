<?php
if(!defined('OSTSCPINC') || !@$thisuser->isStaff()) die('Access Denied');

//Get ready for some deep shit..(I admit..this could be done better...but the shit just works... so shutup for now).

$qstr='&'; //Query string collector
if($_REQUEST['status']) { //Query string status has nothing to do with the real status used below; gets overloaded.
    $qstr.='status='.urlencode($_REQUEST['status']);
}

//See if this is a search
$search=$_REQUEST['a']=='search'?true:false;
$searchTerm='';
//make sure the search query is 3 chars min...defaults to no query with warning message
if($search) {
  $searchTerm=$_REQUEST['query'];
  if( ($_REQUEST['query'] && strlen($_REQUEST['query'])<3) 
      || (!$_REQUEST['query'] && isset($_REQUEST['basic_search'])) ){ //Why do I care about this crap...
      $search=false; //Instead of an error page...default back to regular query..with no search.
      $errors['err']='Search term must be more than 3 chars';
      $searchTerm='';
  }
}
$showoverdue=false;
$staffId=0; //Nothing for now...TODO: Allow admin and manager to limit tickets to single staff level.
//Get status we are actually going to use on the query...making sure it is clean!
//We also overload the status
$status=null;
switch(strtolower($_REQUEST['status'])){
    case 'open':
        $status='open';
        break;
    case 'closed':
        $status='closed';
        break;
    case 'overdue':
        $status='open';
        $showoverdue=true;
        $results_type='Overdue Tickets';
        break;
    case 'assigned':
        //$status='Open'; //
        $staffId=$thisuser->getId();
        break;
    default:
        if(!$search)
            $status='open';
}

$qwhere ='';
/* DEPTS
   STRICT DEPARTMENTS BASED (a.k.a Categories) PERM. starts the where 
   if dept returns nothing...show only tickets without dept which could mean..none?
   Note that dept selected on search has nothing to do with departments allowed.
   User can also see tickets assigned to them regardless of the ticket's dept.
*/
$depts=$thisuser->getDepts(); //if dept returns nothing...show only tickets without dept which could mean..none...and display an error. huh?
if(!$depts or !is_array($depts) or !count($depts)){
    //if dept returns nothing...show only orphaned tickets (without dept) which could mean..none...and display an error.
    $qwhere =' WHERE ticket.dept_id IN ( 0 ) ';
}else if($thisuser->isadmin()){
    //user allowed acess to all departments.
    $qwhere =' WHERE 1'; // Brain fart...can not thing of a better way other than selecting all depts + 0 ..wasted query in my book?
}else{
    //limited depts....user can access tickets assigned to them regardless of the dept.
    $qwhere =' WHERE (ticket.dept_id IN ('.implode(',',$depts).') OR ticket.staff_id='.$thisuser->getId().')';
}


//STATUS
if($status){
    $qwhere.=' AND status='.db_input($status);    
}
//Overdue
if($showoverdue) {
     $qwhere.=' AND isoverdue=1 ';
}
//Staff's assigned tickets.
if($staffId && ($staffId==$thisuser->getId())) {
    $results_type='Assigned Tickets';
    $qwhere.=' AND ticket.staff_id='.db_input($staffId);    
}
 

//Show assigned?? Admin can not be limited.
if(!$cfg->showAssignedTickets() && !$thisuser->isadmin()) {
    $qwhere.=' AND (ticket.staff_id=0 OR ticket.staff_id='.db_input($thisuser->getId()).' OR dept.manager_id='.db_input($thisuser->getId()).') ';
}

//Search?? Somebody...get me some coffee 
if($search):
    $qstr.='&a='.urlencode($_REQUEST['a']);
    $qstr.='&t='.urlencode($_REQUEST['t']);
    //query
    if($searchTerm){
        //Match only what we see on the screen...
        //No messages or answers search at this level ONLY when viewing ticket.
        $qstr.='&query='.urlencode($searchTerm);
        $queryterm=db_real_escape($searchTerm,false); //escape the term ONLY...no quotes.
        if(is_numeric($searchTerm)){
            $qwhere.=" AND ticket.ticketID LIKE '$queryterm%'";
        }elseif(strpos($searchTerm,'@') && Validator::is_email($searchTerm)){ //pulling all tricks!
            $qwhere.=" AND ticket.email='$queryterm'";
        }else{ 
        //This sucks..mass scan! search anything that moves!
            $qwhere.=" AND ( ticket.email LIKE '%$queryterm%'".
                        " OR ticket.name LIKE '%$queryterm%'".
                        " OR ticket.subject LIKE '%$queryterm%'".
                        ' ) ';
        }
    }
    //department
    if($_REQUEST['dept'] && in_array($_REQUEST['dept'],$thisuser->getDepts())) {
    //This is dept based search..perm taken care above..put the sucker in.
        $qwhere.=' AND ticket.dept_id='.db_input($_REQUEST['dept']);
        $qstr.='&dept='.urlencode($_REQUEST['dept']);
    }
    //dates
    $startTime  =($_REQUEST['startDate'] && (strlen($_REQUEST['startDate'])>=8))?strtotime($_REQUEST['startDate']):0;
    $endTime    =($_REQUEST['endDate'] && (strlen($_REQUEST['endDate'])>=8))?strtotime($_REQUEST['endDate']):0;
    if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0)){
        $errors['err']='Entered date span is invalid. Selection ignored.';
        $startTime=$endTime=0;
    }else{
        //Have fun with dates.
        if($startTime){
            $qwhere.=' AND ticket.created>=FROM_UNIXTIME('.$startTime.')';
            $qstr.='&startDate='.urlencode($_REQUEST['startDate']);
                        
        }
        if($endTime){
            $qwhere.=' AND ticket.created<=FROM_UNIXTIME('.$endTime.')';
            $qstr.='&endDate='.urlencode($_REQUEST['endDate']);
        }
}

endif;

//I admit this crap sucks...but who cares??
$sortOptions=array('date'=>'ticket.created','ID'=>'ticketID','pri'=>'priority_urgency','dept'=>'dept_name');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

//Sorting options...
if($_REQUEST['sort']) {
        $order_by =$sortOptions[$_REQUEST['sort']];
}
if($_REQUEST['order']) {
    $order=$orderWays[$_REQUEST['order']];
}
if($_GET['limit']){
    $qstr.='&limit='.urlencode($_GET['limit']);
}

$order_by =$order_by?$order_by:'priority_urgency,ticket.created';
$order=$order?$order:'DESC';
$pagelimit=$_GET['limit']?$_GET['limit']:$thisuser->getPageLimit();
$pagelimit=$pagelimit?$pagelimit:PAGE_LIMIT; //true default...if all fails.
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;


$qselect = 'SELECT ticket.ticket_id,lock_id,ticketID,ticket.dept_id,ticket.staff_id,subject,name,email,dept_name '.
           ',status,source,isoverdue,ticket.created,pri.* ';
$qfrom=' FROM '.TICKET_TABLE.' ticket LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id '.
       ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON ticket.priority_id=pri.priority_id '.
       ' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW() ';

//get ticket count based on the query so far..
$total=db_count("SELECT count(*) $qfrom $qwhere");
//pagenate
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('tickets.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//
//Ok..lets roll...create the actual query
//ADD attachment count crap..
$qselect.=' ,count(attach_id) as attachments ';
$qfrom.=' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON  ticket.ticket_id=attach.ticket_id ';
$qgroup=' GROUP BY ticket.ticket_id';
$query="$qselect $qfrom $qwhere $qgroup ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$tickets_res = db_query($query);
$showing=db_num_rows($tickets_res)?$pageNav->showing():"";
if(!$results_type) {
    $results_type=($search)?'Search Results':ucfirst($status).' Tickets';
}
$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..

//Permission  setting we are going to reuse.
$canDelete=$canClose=false;
$canDelete=$thisuser->canDeleteTickets();
$canClose=$thisuser->canCloseTickets();
$basic_display=!isset($_REQUEST['advance_search'])?true:false;

//Give me some soap...could use a shower right about now...YOU BREAK IT YOU FIX IT.
?>
<div>
    <?if($errors['err']) {?>
        <p align="center" class="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" class="infomessage"><?=$msg?></p>
    <?}?>
</div>
<!-- SEARCH FORM START -->
<div id='basic' style="display:<?=$basic_display?'block':'none'?>">
    <form action="tickets.php" method="get">
    <input type="hidden" name="a" value="search">
    <table>
        <tr>
            <td>Query: </td>
            <td><input type="text" id="query" name="query" size=30 value="<?=Format::htmlchars($_REQUEST['query'])?>"></td>
            <td><input type="submit" name="basic_search" class="button" value="Search">
             &nbsp;[<a href="#" onClick="showHide('basic','advance'); return false;">Advanced</a> ] </td>
        </tr>
    </table>
    </form>
</div>
<div id='advance' style="display:<?=$basic_display?'none':'block'?>">
 <form action="tickets.php" method="get">
 <input type="hidden" name="a" value="search">
  <table>
    <tr>
        <td>Query: </td><td><input type="text" id="query" name="query" value="<?=Format::htmlchars($_REQUEST['query'])?>"></td>
        <td>Dept:</td>
        <td><select name="dept"><option value=0>All Departments</option>
            <?
                //Showing only departments the user has access to...
                $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE dept_id IN ('.implode(',',$thisuser->getDepts()).')');
                while (list($deptId,$deptName) = db_fetch_row($depts)){
                $selected = ($_GET['dept']==$deptId)?'selected':''; ?>
                <option value="<?=$deptId?>"<?=$selected?>><?=$deptName?></option>
            <?
            }?>
            </select>
        </td>
        <td>Status is:</td><td>
    
        <select name="status">
            <option value='any' selected >Any status</option>
            <option value="Open" <?= $_REQUEST['status'] =='Open'?'selected':''?>>Open</option>
            <option value="Closed" <?= $_REQUEST['status']=='Closed'?'selected':''?>>Closed</option>
        </select>
        </td>
     </tr>
    </table>
    <div>
        Date Span:
        &nbsp;From&nbsp;<input name="startDate" onclick="displayDatePicker('startDate');" value="<?=Format::htmlchars($_REQUEST['startDate'])?>" 
            autocomplete=OFF >
                <img src="../images/cal.gif" id="startPick" style="cursor: pointer;" onclick="displayDatePicker('startDate');" align="absmiddle">
            &nbsp;&nbsp; to &nbsp;&nbsp;
            <input name="endDate" onclick="displayDatePicker('endDate');" value="<?=Format::htmlchars($_REQUEST['endDate'])?>" autocomplete=OFF >
                 <img src="../images/cal.gif" id="endPick" style="cursor: pointer;" onclick="displayDatePicker('endDate');" align="absmiddle">
            &nbsp;&nbsp;
    </div>
    <table>
    <tr>
       <td>Sort by:</td><td>
        <? 
         $sort=$_GET['sort']?$_GET['sort']:'date';
        ?>
        <select name="sort">
    	    <option value="ID" <?= $sort== 'ID' ?'selected':''?>>Ticket #</option>
            <option value="pri" <?= $sort == 'pri' ?'selected':''?>>Priority</option>
            <option value="date" <?= $sort == 'date' ?'selected':''?>>Date</option>
            <option value="dept" <?= $sort == 'dept' ?'selected':''?>>Dept.</option>
        </select>
        <select name="order">
            <option value="DESC"<?= $_REQUEST['order'] == 'DESC' ?'selected':''?>>Descending</option>
            <option value="ASC"<?= $_REQUEST['order'] == 'ASC'?'selected':''?>>Ascending</option>
        </select>
       </td>
        <td>Results Per Page:</td><td>
        <select name="limit">
        <?
         $sel=$_REQUEST['limit']?$_REQUEST['limit']:15;
         for ($x = 5; $x <= 25; $x += 5) {?>
            <option  value="<?=$x?>" <?=($sel==$x )?'selected':''?>><?=$x?></option>
        <?}?>
        </select>
     </td>
     <td>
     <input type="submit" name="advance_search" class="button" value="Search">
       &nbsp;[ <a href="#" onClick="showHide('advance','basic'); return false;" >Basic</a> ]
    </td>
  </tr>
 </table>
 </form>
</div>
<script type="text/javascript">

    var options = {
        script:"ajax.php?api=tickets&f=search&limit=10&",
        varname:"input",
        shownoresults:false,
        maxresults:10,
        callback: function (obj) { document.getElementById('query').value = obj.id; document.forms[0].submit();}
    };
    var autosug = new bsn.AutoSuggest('query', options);
</script>
<!-- SEARCH FORM END -->

<div style="margin-bottom:20px">
 <table width="100%" border="0" cellspacing=0 cellpadding=0 align="center">
    <tr>
        <td width="60%" class="msg" >&nbsp;<b><?=$showing?>&nbsp;&nbsp;&nbsp;<?=$results_type?></b></td>
        <td nowrap >
            <a href="tickets.php?status=open"> <img src="images/view_open.gif" alt="View Open" border=0></a>            
            <a href="tickets.php?status=closed"> <img src="images/view_closed.gif" alt="View Closed" border=0></a>            
            <a href="tickets.php"> <img src="images/refresh.gif" alt="Refresh" border=0></a>
        </td>
    </tr>
 </table>
 <table width="100%" border="0" cellspacing=1 cellpadding=2>
    <form action="tickets.php" method="POST" name='tickets' onSubmit="return checkbox_checker(this,1,0);">
    <input type="hidden" name="a" value="mass_process" >
    <input type="hidden" name="status" value="<?=$statusss?>" >
    <tr><td>
       <table width="100%" border="0" cellspacing=0 cellpadding=2 class="dtable" align="center">
        <tr>
            <?if($canDelete || $canClose) {?>
	        <th width="8px">&nbsp;</th>
            <?}?>
	        <th width="70" >
                <a href="tickets.php?sort=ID&order=<?=$negorder?><?=$qstr?>" title="Sort By Ticket ID <?=$negorder?>">Ticket</a></th>
	        <th width="70">
                <a href="tickets.php?sort=date&order=<?=$negorder?><?=$qstr?>" title="Sort By Date <?=$negorder?>">Date</a></th>
	        <th width="250">Subject</th>
	        <th width="110">
                <a href="tickets.php?sort=dept&order=<?=$negorder?><?=$qstr?>" title="Sort By Category <?=$negorder?>">Department</a></th>
	        <th width="70">
                <a href="tickets.php?sort=pri&order=<?=$negorder?><?=$qstr?>" title="Sort By Priority <?=$negorder?>">Priority</a></th>
            <th width="150" >From</th>
        </tr>
        <?
        $class = "row1";
        $total=0;
        if($tickets_res && ($num=db_num_rows($tickets_res))):
            while ($row = db_fetch_array($tickets_res)) {
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['staff_id'])
                    $flag='assigned';
                elseif($row['isoverdue'])
                    $flag='overdue';
           
                ?>
            <tr class="<?=$class?> " id="<?=$row['ticket_id']?>">
                <?if($canDelete || $canClose) {?>
                <td align="center" class="nohover">
                    <input type="checkbox" name="tids[]" value="<?=$row['ticket_id']?>" onClick="highLight(this.value,this.checked);">
                </td>
                <?}?>
                <td align="center" title="<?=$row['email']?>" nowrap>
                  <a class="Icon <?=strtolower($row['source'])?>Ticket" title="<?=$row['source']?> Ticket: <?=$row['email']?>" 
                    href="tickets.php?id=<?=$row['ticket_id']?>"><?=$row['ticketID']?></a></td>
                <td align="center" nowrap><?=Format::db_date($row['created'])?></td>
                <td><a <?if($flag) { ?> class="Icon <?=$flag?>Ticket" title="<?=ucfirst($flag)?> Ticket" <?}?> 
                    href="tickets.php?id=<?=$row['ticket_id']?>"><?=Format::truncate($row['subject'],30)?></a>
                    &nbsp;<?=$row['attachments']?"<span class='Icon file'>&nbsp;</span>":''?></td>
                <td nowrap><?=Format::truncate($row['dept_name'],30)?></td>
                <td class="nohover" align="center" style="background-color:<?=$row['priority_color']?>;"><?=$row['priority_desc']?></td>
                <td nowrap><?=Format::truncate($row['name'],22,strpos($row['name'],'@'))?>&nbsp;</td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: //not tickets found!! ?> 
            <tr class="<?=$class?>"><td colspan=8><b>Query returned 0 results.</b></td></tr>
        <?
        endif; ?>
       </table>
    </td></tr>
    <?
    if($num>0){ //if we actually had any tickets returned.
    ?>
        <tr><td style="padding-left:20px">
            <?if($canDelete || $canClose) { ?>
            Select:
                <a href="#" onclick="return select_all(document.forms['tickets'],true)">All</a>&nbsp;
                <a href="#" onclick="return reset_all(document.forms['tickets'])">None</a>&nbsp;
                <a href="#" onclick="return toogle_all(document.forms['tickets'],true)">Toggle</a>&nbsp;
            <?}?>
            page:<?=$pageNav->getPageLinks()?>
        </td></tr>
        <? if($canClose or $canDelete) { ?>
        <tr><td align="center"> <br>
            <?
            //If the user can close the ticket...mass reopen is allowed.
            //If they can delete tickets...they are allowed to close--reopen..etc.
            switch ($status) {
                case "Closed": ?>
                    <input class="button" type="submit" name="reopen" value="Reopen"
                        onClick=' return confirm("Are you sure you want to reopen selected tickets?");'>
                    <?
                    break;
                case "Open":?>
                    <input class="button" type="submit" name="overdue" value="Overdue"
                        onClick=' return confirm("Are you sure you want to mark selected tickets overdue/stale?");'>
                    <input class="button" type="submit" name="close" value="Close"
                        onClick=' return confirm("Are you sure you want to close selected tickets?");'>
                    <?
                    break;
                default: //search??
                    ?>
                    <input class="button" type="submit" name="close" value="Close"
                        onClick=' return confirm("Are you sure you want to close selected tickets?");'>
                    <input class="button" type="submit" name="reopen" value="Reopen"
                        onClick=' return confirm("Are you sure you want to reopen selected tickets?");'>
            <?
            }
            if($canDelete) {?>
                <input class="button" type="submit" name="delete" value="Delete" 
                    onClick=' return confirm("Are you sure you want to DELETE selected tickets?");'>
            <?}?>
        </td></tr>
        <? }
    } ?>
    </form>
 </table>
</div>

<?
