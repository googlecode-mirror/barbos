<?php
/*********************************************************************
    class.dept.php
    
    Department class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
class Dept {
    var $id;
    var $name;
    var $signature;
    
    var $emailId;
    var $email;
    
    var $managerId;
    var $manager;
    
    var $row;
  
    function Dept($id=0){
        $this->id=0;
        if($id && ($info=$this->getInfoById($id))){
            $this->row=$info;
            $this->id=$info['dept_id'];
            $this->emailId=$info['email_id'];
            $this->managerId=$info['manager_id'];
            $this->deptname=$info['dept_name'];
            $this->signature=$info['dept_signature'];
            $this->getEmail(); //Auto load email struct.
        }
    }

    function getId(){
        return $this->id;
    }
    
    function getName(){
        return $this->deptname;
    }

        
    function getEmailId(){
        return $this->email_id;
    }

    function getEmail(){
        
        if(!$this->email && $this->emailId)
            $this->email= new Email($this->emailId);
            
        return $this->email;
    }
    
    function getEmailAddress() {
        return $this->email?$this->email->getAddress():null;
    }

    function getSignature() {
        
        return $this->signature;
    }

    function canAppendSignature() {
        return ($this->signature && $this->row['can_append_signature'])?true:false;
    }
    
    function getManagerId(){
        return $this->managerId;
    }

    function getManager(){
     
        if(!$this->manager && $this->managerId)
            $this->manager= new Staff($this->managerId);
        
        return $this->manager;
    }

    function isPublic() {
         return $this->row['ispublic']?true:false;
    }
    
    function autoRespONNewTicket() {
        return $this->row['ticket_auto_response']?true:false;
    }
        
    function autoRespONNewMessage() {
        return $this->row['message_auto_response']?true:false;
    }

    function noreplyAutoResp(){
         return $this->row['noreply_autoresp']?true:false;
    }
    
    function getInfo() {
        return $this->row;
    }
    
	function getInfoById($id) {
		$sql='SELECT * FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id);
		if(($res=db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        
        return null;
	}

    
	function getIdByName($name) {
        $id=0;
        $sql ='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function getIdByEmail($email) {
        $id=0;
        $sql ='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_email='.db_input($email);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }


     
    function getNameById($id) {
        $sql ='SELECT dept_name FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($name)=db_fetch_row($res);
        return $name;
    }

    function getDefaultDeptName() {
        global $cfg;
        return Dept::getNameById($cfg->getDefaultDeptId());
    }

    function delete($id) {
        global $cfg; 
        if($id==$cfg->getDefaultDeptId())
            return 0;
        
        $sql='DELETE FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id);
        if(db_query($sql) && ($num=db_affected_rows())){
            // DO SOME HOUSE CLEANING
            //TODO: Do insert select internal note...
            //Move tickets to default Dept.
            db_query('UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //Move Dept members 
            //This should never happen..since delete should be issued only to empty Depts...but check it anyways 
            db_query('UPDATE '.STAFF_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //make help topic using the dept default to default-dept.
            db_query('UPDATE '.TOPIC_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            return $num;
        }
        return 0;
        
    }
}
?>
