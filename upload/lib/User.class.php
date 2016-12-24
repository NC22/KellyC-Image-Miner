<?php
Class KellyUser {

    public static $user = false;
    
    private $db = 'controll_users';
	
	public $id = 0;
	public $login = 'Deleted';
	public $password;
    public $role = 'guest';
    public $ip = '127.0.0.1';
    
    public $trys = 0; // dead trys to login
    
    public $permissions = 0;
	
	public $exist = false;

    public function __construct($input = false, $method = 'user_id')
    {		
		$this->sql = Main::getInstance()->getDB();
        
        if ($input === false) return;
        
        if (!$method or (
			$method !== 'user_id' and 
			$method !== 'user_login' and 
			$method !== 'user_cookie'
			)) {
            $method = 'user_id';
        }
		
        if ($method === 'user_id') {
            $input = (int) $input;
        }

        if (!$input) {
            return;
        }

        $sql = "SELECT `user_id`,"
					. "`user_login`,"
                    . "`user_role`,"
                    . "`user_permissions`,"
                    . "`user_ip`,"
                    . "`user_try`,"
					. "`user_password` FROM `{$this->db}` " . "WHERE `$method`={$this->sql->quote($input)}";
		
		$sql = $this->sql->query($sql);
		$line = $sql->fetch();
		
        if (!$line) {
            return;
        }
		
		$this->exist = true;

        $this->id = (int) $line['user_id'];
        $this->trys = (int) $line['user_try'];
        $this->permissions = (int) $line['user_permissions'];
        
        $this->password = $line['user_password'];	
		$this->login = $line['user_login'];
        
        $this->ip = $line['user_ip'];
        
		$this->role = $line['user_role'];
    }
	
	public function setSynced($state) {
		$state = (bool) $state;
		$this->synced = $state;
		
		if ($state === true) {
			$result = $this->sql->query("UPDATE `{$this->db}` SET `user_synced` = '1', `user_last_sync` = NOW() WHERE `user_id`='{$this->id}'");
			
		} else {
			$result = $this->sql->query("UPDATE `{$this->db}` SET `user_synced` = '0', `user_last_sync` WHERE `user_id`='{$this->id}'");
		}
		
		if ($result) return true;  
		else return false;
	}
    
	public function update($vars)
	{		
		
		if (!empty($vars['user_password'])) {
			$vars['user_password'] = Tool::createPasswordHash($vars['user_password']);	
			$this->password = $vars['user_password'];	
		}
        
		if (!empty($vars['user_login'])) {
			$sql = $this->sql->query("SELECT COUNT(*) AS `count` FROM `{$this->db}` WHERE `user_login`={$this->sql->quote($vars['user_login'])} AND `user_id` != '{$this->id}'");
			$row = $sql->fetch();

			$num = (int) $row['count'];
			
			if ($num) return 1;
			$this->login = $vars['user_login'];
		}
			
	
        foreach ($vars as &$value) {
            $value = $this->sql->quote($value);
        }
        
		if ($this->exist) {            
            $sqlNames = '';
            foreach ($vars as $key => &$value) {
                $sqlNames .= ($sqlNames) ? ",`$key`=" . $value : "`$key`=" . $value;
            }
            
            $result = $this->sql->query("UPDATE `{$this->db}` SET {$sqlNames} WHERE `user_id`='{$this->id}'");
            if ($result) return true;
        } else {
			
			$keys = array_keys($vars);
			$vars = array_values($vars);
			
			$sql = "INSERT INTO `{$this->db}` (`".implode("`, `", $keys)."`, `user_create`) "
				 . "VALUES (" . implode(", ", $vars) . ", NOW())";
			
			$result = $this->sql->query($sql);		
			$this->id = (int) $this->sql->lastInsertId(); 
			$this->exist = true;
			if ($result) return true;   
		}
		
		return 2;
	}
	
	public function checkPassword($password) 
    {
		if (Tool::createPasswordHash($password) == $this->password) {
			return true;
		} else return false;
	}
    
    public function logout()
    {
        $this->sql->query("UPDATE `" . $this->db . "` SET `user_cookie`=''"
                           . " WHERE `user_id`='" . $this->id . "'");
        
        if (isset($_COOKIE[Main::PROGNAME . "Auth"]))
            setcookie(Main::PROGNAME . "Auth", "", time() - 3600);
    }
	
	public function login($password) 
	{		
		if ($this->checkPassword($password)) {
			
			$cookie = Tool::randString(32);	
            $ip = Tool::getClientIp();
            
            $this->ip = $ip;
            $this->trys = 0;
            
			$this->sql->query("UPDATE {$this->db} SET `user_try`='0', `user_cookie`=" .  $this->sql->quote($cookie) . ", `user_ip` = {$this->sql->quote($ip)}, `user_last_auth` = NOW() WHERE `user_id` = '{$this->id}'");	
			
            setcookie(Main::PROGNAME . "Auth", $cookie, time() + 60 * 60 * 24 * 30 * 12, '/');
            
			return $cookie;
			
		} else {
            $this->sql->query("UPDATE {$this->db} SET `user_try`= user_try + 1 WHERE`user_id` = '{$this->id}'");	
			$this->trys++;
			return false;
		}
	}
	
    public function delete()
    {
        if (!$this->exist)
            return false;
		
        $result = $this->sql->query("DELETE FROM `{$this->db}` WHERE `user_id`='" . $this->id . "'");
        
        if ($result) {
		
            $this->id = false;
			$this->exist = false;
            
            return true;
        } else return false;
    }
	
    public static function getCurrentUser()
    {
        if (self::$user === null) return false;
        if (self::$user) return self::$user;
        
        $cookie = isset($_COOKIE[Main::PROGNAME . "Auth"]) ? $_COOKIE[Main::PROGNAME . "Auth"] : false;

        if (!$cookie) { 
            self::$user = null;
            return false;           
        }  
        
        $user = new KellyUser($cookie, 'user_cookie');
        if ($user->exist) {
            self::$user = $user;
            return self::$user;  
        } else {
            self::$user = null;
            return false;
        } 
    }
}