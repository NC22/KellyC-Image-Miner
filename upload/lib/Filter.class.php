<?php
class Filter 
{
    private static $methods = array(
        'server' => INPUT_SERVER,
        'post' => INPUT_POST, 
        'get' => INPUT_GET, 
        'cookie' => INPUT_COOKIE        
    );
    
    /**
     * Native filters
     */
    
    private static $rules = array(
        'bool' => array(
            'default' => false,
            'sanitize' => FILTER_SANITIZE_STRING,
            'validate' => FILTER_VALIDATE_BOOLEAN,            
        ),
        'int' => array(
            'default' => 0,
            'sanitize' => FILTER_SANITIZE_NUMBER_INT,
            'validate' => FILTER_VALIDATE_INT,            
        ),
        'float' => array(
            'default' => 0,
            'sanitize' => false, // FILTER_SANITIZE_NUMBER_FLOAT, прослеживается странное поведение фильтра. Первоначальное значение искажается
            'validate' => FILTER_VALIDATE_FLOAT,            
        ),
        
        'stringLow' => array(
            'default' => '',
            'sanitize' => FILTER_SANITIZE_STRING,
            'sanitizeFlag' => FILTER_FLAG_STRIP_LOW,
            'validate' => null,        
        ),
        
        /**
         * Used for plain UTF-8 text, if you want get html tags, use 'html' instead
         * strip low spec symbols except new line and c carrage return
         */ 
        
        'string' => array(
            'default' => '',
            'sanitize' => FILTER_SANITIZE_STRING,
            'sanitizeFlag' => FILTER_FLAG_NO_ENCODE_QUOTES,
            'validate' => null, 
        ),
        
        'none' => array(
            'default' => '',
            'sanitize' => null,
            'validate' => null,                
        )
    );

    /**
     * create \ rewrite filter logic
     */
    
    public static function str($var, $type = 'string', $falseOnFail = false) 
    {   
            $var = self::sanitizeStr($var, $type);      
            return self::validateStr($var, $type, $falseOnFail);
    }
        
    /**
     * Get variable after applay 'validation' and 'sanitize' filter
     * 
     * @param string $key name of variable to get
     * @param string $method One of <b>post</b> (by default), <b>get</b>, <b>cookie</b>
     * @param string $type type of filter in order self::$rules (string by default)
     * @param bool $falseOnFail return <b>false</b> if input data is empty of incorrect
     * @return mixed return default value for <i>$type</i> on fail (if variable isnt setted  or empty; 'validation' or 'sanitize' filter return fail result) <br>
     * return <b>false</b> on fail if variable $falseOnFail = true<br>
     * return validated variable in order of self::$rules
     */
    
    public static function input($key, $method = 'post', $type = 'string', $falseOnFail = false)
    {    
        $method = self::$methods[$method];
        $var = filter_input($method, $key);
                
        return self::str($var, $type, $falseOnFail); 
    } 
    
    private static function sanitizeStr($var, $type) 
    {
        $filter = self::$rules[$type]['sanitize'];
        
        if ($filter) {            
            
            if (isset(self::$rules[$type]['sanitizeFlag'])) {
                return filter_var($var, $filter, self::$rules[$type]['sanitizeFlag']);
            } else return filter_var($var, $filter);
            
        } else {
            return filter_var($var);
        }
    }
    
    private static function validateStr($var, $type, $falseOnFail)
    {       
        // input is not set or filter fail or variable is empty - exit with default or optional value

        if ($var === null or $var === false or !strlen($var)) { 
            return ($falseOnFail) ? false : self::$rules[$type]['default'];
        }
        
        $filter = self::$rules[$type]['validate'];
        if ($filter) {        
            $var = filter_var($var, $filter);      
            
            if ($var === false) { // validation fail
                return ($falseOnFail) ? false : self::$rules[$type]['default'];
            }
        }

        switch ($type) {
            case 'int':
            case 'float':
            case 'bool':
                settype($var, $type);
                break;
            case 'string':
                preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $var); // remove all control symbols except new line and c return
            default:
                $var = trim($var);
                break;
        } 
        
        return $var;
    }
}
