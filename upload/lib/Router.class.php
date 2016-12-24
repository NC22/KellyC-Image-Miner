<?php

class Router
{
    const ROUTER = 'c'; // index in $_GET array that controll route, also replace in FormValidator.js
    private static $route = null; // buffer
  
    /** 
     * Get called controller and params; 
     * parse url like :     
     * plugin/key-value/key-value/default
     * plugin/default
     *       
     * where 'default' is value for 'defaultParam' key
     * 
     * @return Url|bool return <b>false</b> if route not setted
     */
     
    public static function getRoute()
    {
        if (self::$route) return self::$route;
        self::$route = array('get' => array(), 'c' => false, 'default' => false);
        
        $data = strtolower(Filter::input(self::ROUTER, 'get', 'string'));
     
        foreach($_GET as $key => $value) {
            if ($key === self::ROUTER) continue;      
            self::$route['get'][$key] = Filter::str($value, 'stringLow');
        }
        
        if (empty($data) or !preg_match('/^[a-zA-Z0-9-_\/]+$/', $data)) {
            return self::$route;
        }
        
        $data = explode('/', $data);
        $getParam = false;
        
        foreach($data as $i => $value) {
            if (!$value) continue;
            if ($getParam) {
                $param = explode('-', $value);
                if (sizeof($param) != 2) continue;
                self::$route['get'][$param[0]] = $param[1];
                $param = null;
            } else {
                self::$route['c'] = $value;
                $getParam = true;
            }
        }        
        
        if (isset($param) and sizeof($param) == 1) self::$route['default'] = $param[0];
        return self::$route;
    }
}
