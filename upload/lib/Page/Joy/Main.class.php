<?php

class JoyMain 
{   
    private $env;
    private $action;
    public $route;

    public function __construct()
    {
        $libWay = Main::getShortcut('lib');
        
        include_once($libWay . '/Page/Joy/Parser.class.php' );
        include_once($libWay . '/Page/Joy/Action.class.php' );
        include_once($libWay . 'Browser.class.php' );
        
        $this->env = Main::getInstance();
        $this->action = new JoyAction();
        $vars = Router::getRoute();        
        
        if ($vars['default']) {
            $vars['get']['do'] = $vars['default'];
        } 
        
        $this->route = empty($vars['get']['do']) ? 'main' : $vars['get']['do'];
    }
    
    //$jcontent = loadJPage();
    //if (!$jcontent) exit('load error');

    //getAllContentBlocks($jcontent);
    
    public function exec() 
    {
        $html = '';
        
        View::$content['mode'] = $this->route;
        View::$content['user'] = KellyUser::getCurrentUser();
                        
        switch($this->route) {
            case 'jobselect' :
                $this->action->doSelectJob();
                echo View::show('index', View::$content);  
            break; 
            case 'useradd' :
            
            break;  
            case 'authors' :
            
            break; 
            case 'jobpages' :
            
            break;    
            case 'jobrating' :
            
            break;  
            case 'jobauthor' :
            
            break;      
            case 'jobimg' :
            
            break; 
            case 'jobunfailpages' : // - page_loaded - 0
            
            break; 
            case 'jobfailimages' : // image_load_fail - 1
            
            break;  
            case 'searchbycolor' :
                $this->action->doSearchByPalete();                
                echo View::show('index', View::$content);  		               
            break;
            case 'imagemove' :
                 $this->action->doMoveImages();     
            break;  
            case 'rehash' :
                $this->action->doRehash();                
                echo View::show('index', View::$content);  			
			break;
            case 'sessiondelete' :
                $this->action->doSessiondelete();                
                echo View::show('index', View::$content);  			
			break;
            case 'sessionedit' :
                $this->action->doSessionedit();                
                echo View::show('index', View::$content);  			
			break;
			case 'sessionlist' :
                $this->action->doSessionlist();                
                echo View::show('index', View::$content);  			
			break;
            case 'materiallist' :
                $this->action->doMateriallist();                
                echo View::show('index', View::$content);  
            break;
            case 'downloadtag' :  // DOWNLOAD Directly from tag
                echo $this->action->doParseTag();
            break;
            case 'updatenewpages' : // refresh \ init num of pages on host
                echo $this->action->doUpdatePagesNum();
            break;
            case 'downloadimg' :
                echo $this->action->doDownloadImage();
            break;
			case 'deletefromsearch' :
                echo $this->action->doDeleteFromSearch();
            break;
			case 'deletefromtag' :
                echo $this->action->doDeleteFromTag();
            break;
            case 'downloadmaterial' :
                echo $this->action->doParseMaterial();
            break;
            case 'downloadrating' :
            case 'downloadratings' :
                echo $this->action->doDownloadRatings();
            break;
            case 'getimagessize' :
                echo $this->action->doReadSize();
            break;
            case 'loadpalete' :
                echo $this->action->doLoadPalete();
            break;			
            case 'main' : 
            default : 
                View::$content['mode'] = 'main';
                
                $this->action->doMainPage();                
                echo View::show('index', View::$content);  
            break;
        }
    }
}