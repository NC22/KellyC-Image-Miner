<?php
class Ajax 
{
    public static $showed = false;
    
    public static $message = array(
        'code' => 0, 
        'output' => 'none', // default message - if that output - script return none
        'action' => 'rewrite'
    );
    
    // todo move to main
    
    public static function unexpectedShow() 
    {
        if (self::isAjaxRequest()) {
            self::$message['code'] = 24;
            self::$message['short'] = 'unexpected exit';
            self::$message['message'] = 'unexpected exit';
            self::show(false);
        } else {
            echo 'unexpected exit';
            self::$showed = true;
        }
    }

    public static function isAjaxRequest()
    {  
       /**    
        * $_SERVER['REQUEST_METHOD'] == 'POST' ) && 
        * (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0)
        */
        
        $method = false;
        
        if (sizeof($_POST)) {
           $method = Filter::input('ajaxQuery', 'post', 'string');           
        }
        
        if (!$method and sizeof($_GET)) {
            $method = Filter::input('ajaxQuery', 'get', 'string');       
        }
        
        return $method;
    }
    
    /**
     * compiled commands for usual actions, supported by Kelly JS packages
     */ 
     
    public static function commandRewrite($output) 
    {
        self::$message['code'] = 0;
        self::$message['output'] = $output;
        self::$message['action'] = 'rewrite'; 

        $this->show();
    }
    
    public static function commandReload() 
    {
        self::$message['code'] = 0;
        self::$message['output'] = '';
        self::$message['action'] = 'reload'; 

        self::show();
    }
    
    public static function commandRoute($url) 
    {
        self::$message['code'] = 0;
        self::$message['output'] = $url;
        self::$message['action'] = 'route'; 

        self::show();
    }
    
    public static function commandMessage($code = 0, $message = false)
    {
        self::$message['code'] = $code;
        self::$message['output'] = $message;
        self::$message['action'] = 'message';
        
        self::show();
    }
    
    public static function set($key, $value)
    {
        self::$message[$key] = $value;
    }

    public static function show($exit = true)
    {    
        if (self::$showed) return;
        else self::$showed = true;
        //if (KELLY_DEBUG) {
        //    $env = Enviroment::getInstance();
        //    \Kelly\Tool::log((microtime(true) - $env->debugInfo['start_time']) . ' '
        //    . '| ' . (memory_get_usage() - $env->debugInfo['mem_use']) . ' | AJAX | ' . $this->message['code'], '', true);
        //}
        
        header("HTTP/1.0 200 OK");
        header("HTTP/1.1 200 OK");
        header("Cache-Control: no-cache, must-revalidate, max-age=0");
        header("Expires: 0");
        header("Pragma: no-cache");
        
        // header("Access-Control-Allow-Origin: *");	
        header("Content-type: text/html; charset=" . Main::ENCODE);

        $result = self::jsonEncode(self::$message);

        if (KELLY_JSON === 'iframe') {

            $result = self::escapeJsonString($result);
            $result = '<html><head><title>json response</title>'
                    . '<script type="text/javascript"> var json_response = "' . $result . '"</script>'
                    . '</head><body></body></html>';
        }
        
        if ($exit) exit($result);
        else echo($result);
    }
    
    public static function jsonDecode($data) 
    {
        return json_decode($data, true);
    }
    
    public static function jsonEncode($data) 
    {
        if (defined('JSON_HEX_QUOT')) {
            $result = json_encode($data, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG);
        } else {
            $result = json_encode($data);
        }
        
        return $result;
    }
    
    public static function escapeJsonString($value)
    {
        // list from json.org: (\b backspace, \f formfeed) 

        $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");

        $result = str_replace($escapers, $replacements, $value);
        return $result;
    }
}
