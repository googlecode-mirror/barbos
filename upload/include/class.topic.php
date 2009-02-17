<?php
/*********************************************************************
    class.topic.php

    Help topic helper

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006,2007,2008 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

/*
 * Mainly used as a helper...
 */

class Topic {
    var $id;
    var $topic;
    var $dept_id;
    var $priority_id;
    var $autoresp;
 
    var $info;
    
    function Topic($id,$fetch=true){
        $this->id=$id;
        if($fetch)
            $this->load();
    }

    function load() {

        if(!$this->id)
            return false;
        
        $sql='SELECT * FROM '.TOPIC_TABLE.' WHERE topic_id='.db_input($this->id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['topic_id'];
            $this->topic=$info['topic'];
            $this->dept_id=$info['dept_id'];
            $this->priority_id=$info['priority_id'];
            $this->active=$info['enabled'];
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
    
    function getName(){
        return $this->topic;
    }
    
    function getDeptId() {
        return $this->dept_id;
    }

    function getPriorityId() {
        return $this->priority_id;
    }
    
    function autoRespond() {
        return $this->autoresp;
    }

    function isEnabled() {
         return $this->active?true:false;
    }

    function getInfo() {
        return $this->info;
    }
}
?>
