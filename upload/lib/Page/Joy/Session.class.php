<?php

class JobSession {

    public $db;
        
    public $cookies = array();
    public $id = 0;
    public $password = '';
    public $user = '';
	public $active = false;
    
    public function __construct($id = false)
    {
        $this->db = Main::getInstance()->getDB();
                
        $id = (int) $id;
        if (!$id) return;
        
        $this->id = $id;
        
        $sql = "SELECT * FROM `job_sessions` WHERE `session_id`=:input";
        $line = $this->db->fetchRow($sql, array('input' => $id)); 

        if (!$line) { 
            
            $this->id = 0;
            return;
        }
        
        $this->cookies = Ajax::jsonDecode($line['session_cookies']);
        
        $this->user = $line['session_user'];
        $this->password = $line['session_password'];
        $this->active = ((int) $line['session_active']) ? true : false;
        
        //$result = $this->db->ask("UPDATE `{$this->db}` SET `time_start`=NOW(), `ban_until`=NOW()+INTERVAL $days DAY WHERE `id`='{$this->id}'");
    }
    
    public function exist() {
        if ($this->id) return true;
        else return false;
    }
    
    public function update($var) 
    {
        if (!sizeof($var)) return false;
        
        $check = array('cookies', 'password', 'user');        
        $sqlVars = array(); $sqlNames = '';
        
        foreach ($var as $key => &$value) {
            if (array_search($key, $check) === false) continue;
            
            $sqlKey = 'session_' . $key;
            
            // check input
            
            if ($key == 'user') {
                
                if ($value) {
                
                    $count = $this->db->fetchRow("SELECT COUNT(*) FROM `job_sessions` WHERE `session_user`=:user AND `session_id` != '{$this->id}'", 
                    array('user' => $value), 'num');
                     
                    $count = (int) $count[0];                    
                    if ($count) return 1;
                    
                } else $value = '';   

                $sqlVars[$sqlKey] = $value;
            }
            
            // apply sql vars
            
            if ($key === 'cookies') {
                $sqlVars[$sqlKey] = Ajax::jsonEncode($var[$key]);
            }
            else {
                $sqlVars[$sqlKey] = Filter::str($var[$key], 'stringLow');
            }
            
            $sqlNames .= ($sqlNames) ? ",`$sqlKey`=:" . $sqlKey : "`$sqlKey`=:" . $sqlKey;   

            // apply local vars
            
            if ($key == 'cookies') {
                $this->{$key} = $var[$key];
            } else {
                $this->{$key} = $sqlVars[$sqlKey];
            }
        }
        
        if (!sizeof($sqlVars)) return 3;
        
        if (!$this->id) {
            $keys = array_keys($sqlVars);
            $sql = "INSERT INTO `job_sessions` (`".implode("`, `", $keys)."`) "
                 . "VALUES (:".implode(", :", $keys).")";            
            
        } else {
            $sql = "UPDATE `job_sessions` SET {$sqlNames}, `session_last_update`= NOW() WHERE `session_id`='{$this->id}'";
        }
        
        $result = $this->db->ask($sql, $sqlVars);
        
        if ($result and !$this->id) $this->id = (int) $this->db->lastInsertId();           
        return $result ? true : 2;
    }
    
    public function getTitle() {
        return $this->user ? $this->user : 'Гость ID ' . $this->id;
    }
    
    public function setCookies($cookies) 
    {
        if (!is_array($cookies)) return false;
        if (!$this->exist()) return false;
        
        return $this->update(array('cookies' => $cookies));        
    }
    
    public function setActive($state)
    {
        if ($this->id) {
            $this->active = (bool) $state;
            
            $newState = $state ? 1 : 0; 
            
            $result = $this->db->ask("UPDATE `job_sessions` SET `session_active` = '{$newState}', `session_last_active` = NOW() WHERE `session_id`='{$this->id}'");  
            if ($result)  {
                return true;
            }
        }
        
        return false;
    }
    
    public function delete() 
    {
        if (!$this->id) return false;

        $result = $this->db->ask("DELETE FROM `job_sessions` WHERE `session_id`='" . $this->id . "'");
        
        if ($result) {
            $this->id = false;
            return true;
        } else return false;
    }
    
    public static function getFreeSession($user = null)
    {
        $where = '';
        
        if ($user === true) {
            $where = " AND `session_user` != ''";
        } elseif ($user === false) {
            $where = " AND `session_user` = ''";
        }
        
        $sql = "SELECT `session_id` FROM `job_sessions` WHERE (`session_active`='0' OR (`session_active`='1' AND `session_last_active` + INTERVAL 12 MINUTE < NOW()))" . $where; 
        $line = Main::getInstance()->getDB()->fetchRow($sql); 

        if (!$line['session_id']) {            
            return false;
        } else {
            return new JobSession((int) $line['session_id']);
        }
    }
}
