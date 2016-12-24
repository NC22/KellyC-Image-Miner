<?php

class LoginMain 
{   
    private $env;
    public $route;

    public function __construct()
    { 
        $this->env = Main::getInstance();
        $vars = Router::getRoute();     
        if ($vars['default']) {
            $vars['get']['do'] = $vars['default'];
        } 
        $this->route = empty($vars['get']['do']) ? 'login' : $vars['get']['do'];
    }
    
    public function doRegister() 
    {
        View::$content['title'] = 'Регистрация в системе'; 
        $result = '';
                
        $form = Filter::input('formName', 'post', 'stringLow', true);
        if ($form == 'user_reg') {
        
            $vars = array(
				'user_login' => Filter::input('login', 'post', 'stringLow', true), 
				'user_password' => Filter::input('password', 'post', 'stringLow', true), 
			);
            
            if (!$vars['user_login']) {
                $result = 'Укажите логин ';
            } elseif (!$vars['user_password']) {
                $result = 'Укажите пароль ';
            } else
                $user = new KellyUser();
                $cResult = $user->update($vars);
                
                if ($cResult !== true) {
                    if ($cResult == 1) {
                        
                        $result = 'Пользователь с таким логином уже существует';
                    } else {
                        $result = 'Ошибка добавления пользователя';
                    }
                } else {
                    $result = 'Успешно добавлен новый пользователь';
                }
        }
        View::$content['main'] = View::show('register', array('result' => $result));       
    }
    
    public function doLogout() 
    {
        $user = KellyUser::getCurrentUser();
        if (!$user) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL);  
        
        $user->logout();
        Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL);       
    }
    
    public function doLogin() 
    {
        View::$content['title'] = 'Авторизация в системе'; 
        $result = '';
                
        $form = Filter::input('formName', 'post', 'stringLow', true);
        if ($form == 'user_login') {
            $login = Filter::input('login', 'post', 'stringLow', true);
            $password = Filter::input('password', 'post', 'stringLow', true);
            
            if ($login and $password) {
            
                $user = new KellyUser($login, 'user_login');
                
                if (!$user->exist) $result = 'Пользователя не существует';
                elseif (!$user->login($password)) $result = 'Пароль не подходит';
                else Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL);  
            }
        }
        
        View::$content['main'] = View::show('login', array('result' => $result));       
    }
    
    public function exec() 
    {
        View::$content['mode'] = $this->route;
        
        switch($this->route) {
            case 'logout' :
                $this->doLogout();
            break;
            case 'register' :
                $this->doRegister();
            break;
            case 'login' :
            default : 
                View::$content['mode'] = 'login';
                $this->doLogin();
            break;
        }
        
        echo View::show('index', View::$content);  
    }
}