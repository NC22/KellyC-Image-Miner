<?php
// При POST запросе пройти можно только по ключу -
// ключ для конкретной формы в поле token_data

// todo check returned result for ipv6 host (without server name) and correct router and token validator functions - result probably like http://[::1]/ or http://::1/

class Token 
{
    // public static $limit = 100; // max 100 uniquie forms at one moment may be opened by one user, data drops after logout
    // reset($array);
    // $key = key($array);
    // unset($array[$key]);
    
    /**
     * Validate client connection by compare called HTTP_HOST with host of KELLY_ROOT_URL and check token for post data
     * 
     * - Close connection and redirect to KELLY_ROOT_URL url host if http query host is differ
     * - Close connection if token check fail
     */
    
    public static function validateRequest() 
    {
        // get http_host that called by client
        
        $host = Filter::input('HTTP_HOST', 'server', 'stringLow', true);  // may contain port                 
        if (!$host) {
            header(Filter::input('SERVER_PROTOCOL', 'server') . ' 400 Bad Request');
            exit;
        }
        
        $host = 'http' . ((Filter::input('HTTPS', 'server') == 'on') ? 's' : '') . '://' . $host;
        // get allowed host name (site address setted in config)

        $host = parse_url($host, PHP_URL_HOST);
        $allowedHost = parse_url(KELLY_ROOT_URL, PHP_URL_HOST);
        
        if ($host != $allowedHost) {
            header('Location: ' . KELLY_ROOT_URL . Filter::input('REQUEST_URI', 'server'));
            exit;
        }
        
        // check token by form name if formName setted, else search 
        // check ajax_token post first
        
        //if (KELLY_JSON) {
        //    if (!self::checkAjaxQuery())
        //        exit();
        // } else
        if (sizeof($_POST) and !self::checkForm()) {
            header(Filter::input('SERVER_PROTOCOL', 'server') . ' 400 Bad Request'); // возможно стоит редиректить
            exit('csrf');
        }        
    }
    
    public static function set($formName) // if already setted return old? may be not 
    {
        if (!isset($_SESSION)) {
            session_start();
        } 
        
        $_SESSION['token_data'][$formName] = Tool::randString(16);
        return $_SESSION['token_data'][$formName];
    }
    
    /**
     * 
     * @return boolean
     */
    
    public static function checkForm() 
    {
        $formName = Filter::input('formName', 'post', 'stringLow', true);
        if ($formName === false) return true; 
        
        $token = Filter::input('token_data', 'post', 'string', true);
        if (!$token) return false;

        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (empty($_SESSION['token_data'][$formName]) or
            $_SESSION['token_data'][$formName] !== $token) {
            if (isset($_SESSION['token_data'][$formName]))
                unset($_SESSION['token_data'][$formName]);
            
            return false;
        }

        unset($_SESSION['token_data'][$formName]);
        return true;
    }
}
