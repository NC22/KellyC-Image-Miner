<?php
// todo Use Kelly prefix for all default classes

// error reporting sets to errorHandler

class Main
{
    const ENCODE = 'UTF-8';
    const VERSION = '1.0';
    const PROGNAME = 'Kelly';
    
    public $locData = array();
    
    /* static vars */   
    
    public static $ways = array( 
        'system' => 'data/system/',
        'style' => 'data/style/', 
        'data' => 'data/', 
        'lib' => 'lib/', 
        'tmp' => 'data/tmp/',
        'root' => '',
        
        '_default' => true,
        'theme' => 'data/style/default/',
    ); 
        
    private $cfg = null;    
    private $db = null;
    
    public $execInfo = null;
    //public $exitClean = false;
    
    private $drivers = array(); 
    public $page = null;
    
    protected static $__instance;
    
    public function init($rootDir) 
    {
        define('KELLY_ROOT', $rootDir);
        
        include_once(KELLY_ROOT . 'config.php' );
        
        if (empty($config)) exit('Config file not initialized (' . self::getShortcut('system') . 'config.php)');
        
        $this->cfg = $config;
        define('KELLY_DEBUG', $this->cfg('debug'));
        
        $this->execInfo = array('start_time' => microtime(true), 'mem_use' => memory_get_usage());          
        
        if ($this->cfg('log')) {
            define('KELLY_LOG', 1);
        }
        
        if (isset($dbN)) {
            self::$dbN = $dbN;
        }
        
        if (isset($siteWays)) {
            self::$ways = $siteWays;
        }        

        $libWay = self::getShortcut('lib');
        
        include_once($libWay . 'Ajax.class.php' );
        include_once($libWay . 'Filter.class.php' );
        include_once($libWay . 'Token.class.php' );
        include_once($libWay . 'Router.class.php' );
        include_once($libWay . 'Tool.class.php' );
        include_once($libWay . 'View.class.php' );
        include_once($libWay . 'User.class.php' );
        
        if ($this->cfg('s_theme')) {
            self::$ways['theme'] = self::$ways['style'] . $this->cfg('s_theme') . '/';
        }
        
        if ($this->cfg('s_root')) {
            define('KELLY_ROOT_URL', $this->cfg('s_root'));            
        } else {
            define('KELLY_ROOT_URL', 'http://' . $_SERVER['SERVER_NAME'] . '/');
        }     
        
        View::$themeWay = self::getShortcut('theme');
        View::$themeUrl = self::getShortcut('theme', true);
                
        define('KELLY_JSON', Ajax::isAjaxRequest());
                
        mb_internal_encoding(self::ENCODE);
        date_default_timezone_set($this->cfg('timezone'));
		set_error_handler(array($this, 'errorHandler'));
    }
	
	public function errorHandler($errno, $errstr, $errfile, $errline) 
	{
		// not catches fatal errors
		
		switch ($errno) {
		case E_USER_ERROR:
			Tool::log('[ER][ERROR] in ' . $errfile . ' | ' . $errstr . '[' . $errno . ']' , $errline);
			exit(1);
			break;
		case E_USER_WARNING:
			Tool::log('[ER][WARNING] in ' . $errfile . ' | ' . $errstr . '[' . $errno . ']' , $errline);
			break;

		case E_USER_NOTICE:
			Tool::log('[ER][NOTICE] in ' . $errfile . ' | ' . $errstr . '[' . $errno . ']' , $errline);
			break;
		default:
			Tool::log('[ER][WARNING] in ' . $errfile . ' | ' . $errstr . '[' . $errno . ']' , $errline);
			break;
		}

		return true;		
	}

	
    public function exec($rootDir = '../../')
    {       
        if (!$this->cfg) $this->init($rootDir);
                
        Token::validateRequest();        
        $route = Router::getRoute();  
                
        $controller = $route['c'];
        
        if (!$controller) {
            $controller = $this->cfg('s_dpage'); 
        }
        
        View::$content['user'] = KellyUser::getCurrentUser();
        
        $page = $this->includePage($controller);
        if (!$page and $controller != $this->cfg('s_dpage')) $this->exitWithRedirect(); 
        elseif (!$page){
            View::$content['main'] = 'Error loading controller - ' .  $controller;
            echo View::show('index', View::$content);   
        }
        
        foreach($this->cfg('plugins') as $plugin) {
            $plugin = ucfirst($plugin);
            $className = $plugin . 'Main';
            include_once(self::getShortcut('lib') . 'Plugins/' . $plugin . '/Main.class.php' );     
            
            $plugin = new $className();
            $plugin->exec();
        }   
    }
    
    /**
     * Get option value by option index
     * @param string $key
     * @param boolean $sqlConfig
     * @return mixed|null
     */
    
    public function cfg($key)
    {
        
        if (!isset($this->cfg[$key])){
            return null;
        }
        
        return $this->cfg[$key];
    }

    public function cfgSet($key, $value) 
    {        
        if (!isset($this->cfg[$key])){
            return false;
        }
        
        $this->cfg[$key] = $value;
        return true;        
    }
    
    public function cfgSave() 
    {
        $txt = '<?php' . PHP_EOL;
        $txt .= '$config = ' . var_export($this->cfg, true) . ';' . PHP_EOL;       
        if (!isset(self::$ways['_default'])) $txt .= '$siteWays = ' . var_export(self::$ways, true) . ';' . PHP_EOL;        
        $cfgDir = KELLY_ROOT . 'config.php';
        
        if (file_put_contents($cfgDir, $txt) === false) return false;
        return true;
    }    
    
    /**
     * Before use make shure that enviroment is initialized by Kelly\Enviroment::getInstance()->init($kellyRoot);
     * Return instance of DataBase object
     * @return DataBase\DataBaseInterface
     */
    
    public function getDB()
    {
        if ($this->db) return $this->db;     
        
        $driver = 'Kelly' . ucfirst($this->cfg('db_driver'));
        $dbLibWay = self::getShortcut('lib') . 'DataBase/';
        $driverWay =  $dbLibWay . ucfirst($this->cfg('db_driver')) . '/';
        
        include_once($dbLibWay . 'DataBaseInterface.class.php' );
        include_once($dbLibWay . 'StatementInterface.class.php' );
        include_once($driverWay . 'Module.class.php' );
        include_once($driverWay . 'Statement.class.php' );              
               
        try {
            $className = $driver . 'Module';
        
            $this->db = new $className();    
            $this->db->connect(array(
                'host' => $this->cfg('db_host'),
                'port' => $this->cfg('db_port'),
                'db' => $this->cfg('db_name'),
                'login' => $this->cfg('db_login'),
                'password' => $this->cfg('db_passw'),
            ));
        } catch (\Exception $e) {
            Tool::log('getDB : ' . $e->getMessage());
            exit($this->loc('init_db_fail', 'DB init fail, see log for more information'));
        }
        
        return $this->db;
    }
  
    public function loc($key, $default) 
    {
        if (empty($this->locData[$key])) return $default;
        else return $this->locData[$key];
    }
  
    public function includePage($controller = false) 
    {  
        $controller = ucfirst(trim($controller)); // must be CamelCased        
        if (!$controller) return false;
        
        $controllerWay = self::getShortcut('lib') . 'Page/' . $controller . '/Main.class.php';
        
        if (file_exists($controllerWay)) {
        
            include_once $controllerWay;
            $className = $controller . 'Main';
            
            $this->page = new $className();
            $this->page->exec();
            
            return true;
        } else return false;        
    }
    
    /**
     * @param string $redirect link to set as new location. false - page not found
     */
    
    public function exitWithRedirect($redirect = false, $notice = false) 
    {
        if ($notice) {
            Tool::log('ExitWithRedirect: ' . $notice);
        }
        
        header("Location: " . ($redirect ? $redirect : KELLY_ROOT_URL));
        exit;
    }   
    
    public function exitClean() 
    {
        $this->getDB()->close();  
        exit;
    }
    
    /**
     * Get path or url to dirrectory by short key with check global ways
     * output example : Z:\home\kelly.ru\www\ - always with back slash at the end 
     * @param string $key short key
     *  - system
     *  - style
     *  - lib
     *  - tmp
     *  - user
     *  - plugin
     *  - theme
     *  - root - base dirrectory
     * 
     * @param bool $asLink default <b>false</b>. Return patch as url if <b>true</b>
     * @return string
     */    
    
    public static function getShortcut($key, $asLink = false) 
    {
        $base = ($asLink) ? KELLY_ROOT_URL : KELLY_ROOT;
        if (!$key or !isset(self::$ways[$key])) return $base;
        return $base . self::$ways[$key];
    }
    
    /**
     * Get table name with global prefix check
     * @param string $key
     */
    
    public static function dbN($key) 
    {
        return isset(self::$dbN[$key]) ? self::$dbN[$key] : self::$dbN['_prefix'] . $key; 
    }
        
    /**
     * @return Enviroment
     */
    
    public static function getInstance()
    {
        if (!isset(self::$__instance)) {
            $class = __CLASS__;
            self::$__instance = new $class();
        }

        return self::$__instance;
    }
}
