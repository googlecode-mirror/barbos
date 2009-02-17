<?php
/*********************************************************************
    class.email.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

include_once(INCLUDE_DIR.'class.dept.php');

class Email {
    var $id;
    var $email;
    var $address;
    var $name;

    var $autoresp;
    var $deptId;
    var $priorityId;
    
    var $dept;
    var $info;
    
    function Email($id,$fetch=true){
        $this->id=$id;
        if($fetch)
            $this->load();
    }
    
    function load() {

        if(!$this->id)
            return false;
        
        $sql='SELECT * FROM '.EMAIL_TABLE.' WHERE email_id='.db_input($this->id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['email_id'];
            $this->email=$info['email'];
            $this->name=$info['name'];
            $this->address=$info['name']?($info['name'].'<'.$info['email'].'>'):$info['email'];
            $this->deptId=$info['dept_id'];
            $this->priorityId=$info['priority_id'];
            $this->autoresp=$info['noautoresp']?false:true;
            $this->info=$info;
            return true;
        }
        $this->id=0;

        return false;
    }
  
    function reload() {
        return $this->load();
    }
    
    function getId(){
        return $this->id;
    }

    function getEmail(){
        return $this->email;
    }
    
    function getAddress() {
        return $this->address;
    }
    
    function getName(){
        return $this->name;
    }

    function getPriorityId() {
        return $this->priorityId;
    }

    function getDeptId() {
        return $this->deptId;
    }

    function getDept() {

        if(!$this->dept && $this->dept_id)
            $this->dept= new Dept($this->dept_id);
        
        return $this->dept;
    }

    function autoRespond() {
          return $this->autoresp;
    }
    
    function getInfo() {
        return $this->info;
    }

    function getIdByEmail($email) {
        
        $resp=db_query('SELECT email_id FROM '.EMAIL_TABLE.' WHERE email='.db_input($email));
        if($resp)   list($id)=db_fetch_row($resp);
        
        return $id;
    }

    function deleteEmail($id) {
        global $cfg;
        //Make sure we are not trying to delete default email.
        if($id==$cfg->getDefaultEmailId()) //double...double check.
            return 0;

        $sql='DELETE FROM '.EMAIL_TABLE.' WHERE email_id='.db_input($id);
        if(db_query($sql) && ($num=db_affected_rows())){
            // DO SOME HOUSE CLEANING..should be taken care already...but doesn't hurt to make sure.
            //Move Depts using the email to default email.
            db_query('UPDATE '.DEPT_TABLE.' SET email_id='.db_input($cfg->getDefaultEmailId()).' WHERE email_id='.db_input($id));
            //Clear POP info.
            db_query('DELETE FROM '.POP3_TABLE.' WHERE email_id='.db_input($id));
            return $num;
        }
        return 0;
    }
    
}
?>
