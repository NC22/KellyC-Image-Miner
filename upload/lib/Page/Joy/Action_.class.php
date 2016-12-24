<?php

// Main controller

// работа с материалами треб авторизацию. по умолчанию не логиниться

class JoyAction 
{   
    public $user = false; // admin user
    public $sql;
    public $env;
    
    public function __construct()
    {
        $this->user = KellyUser::getCurrentUser();    
        if (!$this->user or !$this->user->exist) $this->user = false;
		
		if ($this->user and $this->user->permissions !== 777) $this->user = false;
        
        $this->env = Main::getInstance();
        $this->sql = $this->env->getDB();
        
        include_once(Main::getShortcut('lib') . 'Page/Joy/ParsedImage.class.php');
    }
    
    // ban some tags for safe search
    // search by saturation range for selected colors
    // search by main color
    // search method AND OR
    
    
    // need addition tests, perhaps returns incorrect addition colors
    
    public function getAdditionColors($palete, $rgb, $hex) {
        $hsl = $palete->rgb2hsl($rgb);
        $colors = array($hex);
        for ($i = 0; $i <= 0; $i++) {
            if ($hsl[1]-0.1 < 0) {
                $hsl[1] = 0;
            } else {
                $hsl[1] -= 0.1;
            }
           
            $colorRgb = $palete->simplifyRgb($palete->hsl2rgb($hsl));
            $colors[] = $palete->rgb2hex($colorRgb);
        }
        
        return $colors;
    }
    
	public function doSearchByPalete() 
	{	
		include_once(Main::getShortcut('lib') . 'KellyGDColorPalete.class.php');
		include_once(Main::getShortcut('lib') . 'KellyImageGrid.class.php');
        $paleteHelper = new KellyGDColorPalete();
        
        $form = Filter::input('formName', 'post', 'stringLow', true);    
        
        // search by main color    
        $paleteColors = empty($_POST['color']) ? false : $_POST['color'];
        $tags = Filter::input('tags', 'post', 'stringLow', true);
        $safe = Filter::input('safe', 'post', 'bool', true);            
        // 
        $images = '';
        
        if ($form == 'searchbycolor') {
        
            $images = 'Совпадений не найдено';
             
            $sql = "SELECT parsed_images.image_palete AS `palete`, "
                 .  "parsed_images.image_link AS `image_link`, "
                 .  "parsed_images.image_w AS `image_w`, "
                 .  "parsed_images.image_h AS `image_h`, "
                 .  "parsed_images.image_preview AS `image_preview`, "
                 .  "parsed_materials.material_rating AS `rating`, "
                 .  "parsed_materials.material_tags AS `tags` FROM `parsed_images` "
                 .  "LEFT JOIN `parsed_materials` ON parsed_images.image_material_id = parsed_materials.material_id "
                 .  "WHERE parsed_images.image_ban_search = '0' AND parsed_images.image_w > '250' AND parsed_images.image_h > '250' ";
            
            $sqlData = array();	 
            $tagsSqlStr = '';		
            $colorsSqlStr = '';
            
            $paleteHelper = new KellyGDColorPalete();
            
            $validColors = 0; $validIndex = 0; 
            foreach ($paleteColors as $key => &$color) {		
                $color = trim($color);
                if (!$color) {
                    $color = false;
                    continue;
                }
                if ($color == 3) $color = $color . $color;
                if ($color[0] == '#') $color = substr($color, 1);
                if (strlen($color) != 6) {
                    $color = false;
                    continue;
                }
                
                $validColors++;
                $validIndex = $key;
                $colorRgb = $paleteHelper->hex2rgb($color);
                $colorRgb = $paleteHelper->simplifyRgb($colorRgb);
                $color = $paleteHelper->rgb2hex($colorRgb);		
            }
            
            if ($validColors == 1) { // may be add to each mode, not only for one color
            //    $paleteColors = $this->getAdditionColors($paleteHelper, $colorRgb, $paleteColors[$validIndex]);
            }
            
            foreach ($paleteColors as $key => $color) {		
                if (!$color) continue;
                if ($colorsSqlStr) {
                
                    if ($validColors > 1) { // search by few colors
                        // $colorsSqlStr .= ' AND';
                        $colorsSqlStr .= ' OR';
                    } else { // search by range of saturation of one color
                        $colorsSqlStr .= ' OR';
                    }
                }
                
                $colorsSqlStr .= ' parsed_images.image_palete LIKE :color' . $key;
                
                if ($validColors > 1) {
                    // $sqlData['color' . $key] = '%' . $color . '%'; // select any matched
                    $sqlData['color' . $key] = '%' . $color; 
                } else {
                    $sqlData['color' . $key] = '%' . $color ; // select main color
                }                
            }
            
            if ($colorsSqlStr) {
                $sql .= 'AND (' . $colorsSqlStr . ') ';	
            }
            
            $tags = explode(',', $tags);
            
            foreach ($tags as $key => $tag) {		
                $tag = trim($tag);
                if (!$tag or strlen($tag) < 3) continue;
                
                if ($tagsSqlStr) $tagsSqlStr .= ' OR';			
                $tagsSqlStr .= ' parsed_materials.material_tags LIKE :tag' . $key;
                $sqlData['tag' . $key] = '%' . $tag . '%';
            }		
            
            if ($tagsSqlStr) {
                $sql .= 'AND (' . $tagsSqlStr . ') ';
            }
            
            if (sizeof($sqlData)) {
                $sql .= 'AND CHAR_LENGTH(parsed_images.image_palete) = 41 ';
                $sql .= 'ORDER BY parsed_materials.material_rating DESC ';
                $sql .= 'LIMIT 0, 100';
                
                Tool::log('query ' . var_export($sql, true));
                
                $result = $this->sql->ask($sql, $sqlData);
                $searchResults = '';
                
                $imageGrid = new KellyImageGrid();
                $imageGrid->width = 800;
                $imageGrid->height = 250;
                
                while($image = $result->fetch())
                {
                    $parsedImage = new JoyParsedImage($image); // add get original method
                    $imageGrid->images[] = array(
                        'link' => $parsedImage->getPreview(true),
                        'width' => $image['image_w'],
                        'height' => $image['image_h'],
                        'tags' => $image['tags'],
                        'rating' => $image['rating'],
                    );   
                } 
                
                if (sizeof($imageGrid->images)) $images = $imageGrid->show();                
                Tool::log('search images by data ' . var_export($sqlData, true));
            }
           
        }
        
        View::$content['main'] = View::show('searchbycolor', array('images' => $images));
	}
	
    public function doMainPage() 
    {
        include_once(Main::getShortcut('lib') . 'Page/Joy/Search.class.php');
        
        $uploadMax = 2;
    
        View::$content['title'] = 'Поиск по картинке'; 
             
        $form = Filter::input('formName', 'post', 'stringLow', true);
        $message = '';
        $html = '';
         
        if ($form == 'image_search') {
            
            $searchManager = new ImageSearch();            
            $imageFile = array('image' => false);
            
            if (!$searchManager->makeAction('search')) $message = 'Превышен интервал запросов';
            else {
                $imageFile = $searchManager->initSearch('imageurl', 'imagefile');
            }
            
            if ($imageFile['image']) {
                
                $matches = $searchManager->startSearch($imageFile['image']);
                
                $html = 'Совпадения не найдены';  
                
                if (sizeof($matches)) {
                               
                    $html = View::show('image_row', array('start' => true));
                    
                    foreach ($matches as $imageInfo) {
                        $html .= View::show('image_row', $imageInfo);
                    }       
                    
                    $html .= View::show('image_row', array('end' => true));
                    
                    
                } 
                
                // search here
            }
        } 
        
        $uploadForm = View::show('main', array('max_size' => $uploadMax, 'message' => $message)); 
        
        View::$content['main'] = $html;
        View::$content['upload_form'] = $uploadForm;
        
        return true;
    }
    
    public function getSessionSelect($selected = false) 
    {
        $sql = $this->sql->query("SELECT `session_id`, `session_user` FROM `job_sessions` ORDER BY `session_id` DESC LIMIT 0,90");
        
		$options = '<option value="0" ' . ($selected == false ? 'selected="selected"' : '') . '>Любая свободная</option>';
        
		while($row = $sql->fetch())
		{
			$selected = '';
			if ($selected == (int) $row['session_id']) $selected = 'selected="selected"';
			
			$options .= '<option value="' . $row['session_id'] . '" '. $selected .'>' . ($row['session_user'] ? $row['session_user']  : 'Гость ID ' . $row['session_id']) . ' </option>';
		}	
		
		return $options;
    }
    
    public function doSessiondelete() 
    {
        if (!$this->user) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'login');
        include_once( Main::getShortcut('lib') . 'Page/Joy/Session.class.php');      

        $id = Filter::input('sessionid', 'get', 'int', true);
        if (!$id) $id = Filter::input('itemid', 'post', 'int', true);
        
        $form = Filter::input('formName', 'post', 'stringLow', true);
        
        $session = new JobSession($id);
        if (!$session->exist()) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'joy/sessionlist'); 
            
        if ($form == 'session_delete') {
            if (!$id) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'joy/sessionlist');
            
            $session->delete();
            Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'joy/sessionlist');
        }
        
        $html = View::show('delete_confirm', array(
            'title' => $session->getTitle(), 
            'formName' => 'session_delete', 
            'cancel' => 'joy/sessionedit?sessionid' . $session->id, 
            'id' => $session->id
        ));
        
        View::$content['main'] = $html;        
    }   
    
    public function doSessionedit() 
    {
        if (!$this->user) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'login');
        include_once( Main::getShortcut('lib') . 'Page/Joy/Session.class.php');
        
        $id = Filter::input('sessionid', 'get', 'int', true);
        if (!$id) $id = Filter::input('sessionid', 'post', 'int', true);
        $form = Filter::input('formName', 'post', 'stringLow', true);
        $session = new JobSession($id);
        $message = '';
        
        if ($form == 'session_edit') {			
			$user = Filter::input('user', 'post', 'stringLow', true);
			$password = Filter::input('password', 'post', 'stringLow', true);
            $active = Filter::input('active', 'post', 'bool', true);            
			
			$message = '';
			
			if ($id and !$session->exist()) $message = 'Сессия не найдена';
			else {
                $vars = array('user' => $user);
                if ($password) {
                    $vars['password'] = $password;
                }
                $result = $session->update($vars);
                         
                $message = $id ? 'Сессия обновлена' : 'Создана новая сессия';
                
                if ($result === 1) $message = 'Сессия с таким логином уже существует';
                elseif ($result == false) $message = 'Данные не заданы';
                elseif ($result !== true) $message = 'Ошибка ' . $result;
                
                $session->setActive($active);       
            }
		}
        
        $sessionEditForm = View::show('session_edit', array('session' => $session, 'message' => $message));
        
        $html = $sessionEditForm;
        View::$content['main'] = $html;
    }	
    
    public function doSessionlist() 
    {
        if (!$this->user) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'login');
		
		include_once( Main::getShortcut('lib') . 'Page/Joy/Session.class.php');
		
 		$list = Filter::input('l', 'get', 'int');
		$list = (int) $list;
		if ($list <= 0)
			$list = 1;

		$sql= $this->sql->query("SELECT COUNT(*) AS `count` FROM `job_sessions`");
		$row = $sql->fetch();

		$num = (int) $row['count'];
		
		$html = '';
        $pagination = false;
		
		if (!$num) $html .= 'Сессий нет';
		else {
            $sql = $this->sql->query("SELECT * FROM `job_sessions` ORDER BY `session_user` DESC LIMIT " . (10 * ($list - 1)) . ",10");
			
			$html .= View::show('session_row', array('start' => true));
			
			while($row = $sql->fetch())
			{
				$html .= View::show('session_row', array('session' => $row));
			}	
			
			$html .= View::show('session_row', array('end' => true));
			
			$pagination = View::arrowsGenerator(KELLY_ROOT_URL . 'joy/sessionlist/', $list, $num, 10);
        }   
		
        View::$content['main'] = $html;
        View::$content['pagination'] = $pagination;         
    } 
	
    public function doMateriallist() 
    {
        if (!$this->user) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'login');  
        
 		$list = Filter::input('l', 'get', 'int');
		$list = (int) $list;
		if ($list <= 0)
			$list = 1;

		$sql= $this->sql->query("SELECT COUNT(*) AS `count` FROM `parsed_materials`");
		$row = $sql->fetch();

		$num = (int) $row['count'];
		$html = '';
        $pagination = false;
		
		if (!$num) $html = 'Материалов нет';
		else {
            $sql = $this->sql->query("SELECT * FROM `parsed_materials` ORDER BY `material_id` DESC LIMIT " . (20 * ($list - 1)) . ",20");
			
			$html .= View::show('material_row', array('start' => true));
			
			while($row = $sql->fetch())
			{
				$html .= View::show('material_row', array('material' => $row));
			}	
			
			$html .= View::show('material_row', array('end' => true));
			
			$pagination = View::arrowsGenerator(KELLY_ROOT_URL . 'joy/materiallist/', $list, $num, 20);
        }   

        View::$content['main'] = $html;
        View::$content['pagination'] = $pagination;         
    } 
    
    public function doUseradd() 
    {
    
    }    
    
    public function doSelectJob() 
    {
        if (!$this->user) Main::getInstance()->exitWithRedirect(KELLY_ROOT_URL . 'login'); 
        
        View::$content['title'] = 'Выбор работы'; 
        View::$content['main'] = View::show('jobselect', array('session_select' => $this->getSessionSelect(false)));  
    }   
    
    public function doDownloadImage()
    {
        if (!$this->user) return 'fail|auth required';
        // loadImageNum($ids = array(), $loadFail = false, $limit = false)
        
        $ajax = Ajax::isAjaxRequest();
         if (!$ajax) { 
            $loadFail = Filter::input('fail', 'get', 'bool', true);
            $images = Filter::input('count', 'get', 'int', false);
            $checkHash = Filter::input('checkHash', 'post', 'bool', true);
        } else {
            $loadFail = Filter::input('fail', 'post', 'bool', true);  
            $images = Filter::input('count', 'post', 'int', false);
            $checkHash = Filter::input('checkHash', 'post', 'bool', true);
        }  
        
        $mode = $loadFail ? 'failreload' : 'normal'; 
        
        if ($images > 100) $images = 100;
        if (!$images or $images < 0) $images = 1;        
        
        $parser = new JoyParser();
        // $result = $parser->loadImageNum(array(33), false, 1);
        $result = $parser->loadImageNum(false, $loadFail, $images, $checkHash);
                
        if (!$ajax) {    
            return $result['message'] . '|' . $result['description'] . '|' . $mode;
        } else {
        
            $answer = array(
                'code' => 0, 
                'output' => 'none', 
                'action' => 'rewrite',
                'nojob' => $result['message'] == 'nojob' ? true : false,
                'message' => $result['description'],
                'short' => $result['message'],
                'token' => Token::set('download_images'),
                'images' => $result['images'],
            );
            
            if ($result['error'] === false) {
            
            } else {
                $answer['code'] = 1;
                if ($result['message'] == 'nojob') $answer['code'] = -1;
            }
            
            Ajax::$message = $answer;
            Ajax::show();
        }
    }    
    
    // fix 1 - move images from common dirrectory
    
    public function doMoveImages() 
    { 
        if (!$this->user) return 'fail|auth required';
         
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", JoyParser::maxExecutionTimeSec);  
        
        $sql = $this->sql->query("SELECT * FROM  `parsed_images` WHERE `image_preview` LIKE 'pr_%' LIMIT 0,1000");

        if (!$sql or !$sql->rowCount()) echo 'nojob';
		else {
            $count = 0;
            $lastDir = ''; $parsedImage = false;
			while($dataRow = $sql->fetch())
			{
                $count++;
                $parsedImage = new JoyParsedImage($dataRow);
                
                $subDir = $parsedImage->getPreviewSubDir();
                if ($lastDir != $subDir) $parsedImage->createNewSubDirIfLimit();
                
                $store = Tool::getDir(Main::getShortcut('root') . JoyParsedImage::$previewFolder . $subDir);
                $newPreview = $subDir . $dataRow['image_preview'];
                if (!$store) exit('bad direcroty');
 
                if (!rename(Main::getShortcut('data') . 'image/preview/' . $dataRow['image_preview'], $store . $dataRow['image_preview'])) {
                    echo 'fail to move ' . $store . $dataRow['image_preview'] . '<br>';
                   continue;
                }
                
                $sqlQuery = "UPDATE `parsed_images` SET `image_preview`='{$newPreview}' WHERE `image_id` = '{$dataRow['image_id']}'";
                $this->sql->query($sqlQuery);	
                //echo   urldecode($dataRow['image_link']) . '|' . $store . $dataRow['image_preview'] . '<br>';
                $lastDir = $subDir;
			}
            echo 'moved ' . $count;
			if ($parsedImage) $parsedImage->createNewSubDirIfLimit(); 
        }  
    }

    // init number of pages
    
    public function doUpdatePagesNum() 
    { 
         if (!$this->user) return 'fail|auth required';
        $result = array('error' => false, 'message' => 'fail to load pages num', 'description' => '');
        
        $parser = new JoyParser();
        $num = $parser->updateTotalPages('new');
        
        $result['message'] = 'update pages num : new : ' . $num;

        $answer = array(
            'code' => 0, 
            'output' => 'none', 
            'action' => 'rewrite',
            'nojob' => true,
            'message' => $result['description'],
            'short' => $result['message'],
            'token' => Token::set('custom_work'),
        );
        
        if ($result['error'] === false) {
        
        } else {
            $answer['code'] = -1;
        }
        
        Ajax::$message = $answer;
        Ajax::show();        
    }
       
    public function doReadSize() 
    { 
        if (!$this->user) return 'fail|auth required';
        
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", JoyParser::maxExecutionTimeSec);  
        
        $images = Filter::input('count', 'post', 'int', false);
        
            if ($images < 1) $images = 1;
        elseif ($images > 3000) $images = 3000;
        
        $sql = $this->sql->query("SELECT `image_id`, `image_preview` FROM  `parsed_images` WHERE `image_preview` != '' AND `image_w` = '0' LIMIT 0,{$images}");

        $result = array('error' => false, 'message' => '', 'description' => '');
        
        if (!$sql or !$sql->rowCount()) $result['message'] = 'nojob';
		else {
            $count = 0;
            while($dataRow = $sql->fetch())
			{
                $width = 0;
                $height = 0;
                $preview = Main::getShortcut('root') . JoyParsedImage::$previewFolder . $dataRow['image_preview'];
                if (!file_exists($preview)) {
                    Tool::log('Image ID : '. $dataRow['image_id'] .' | file not exist ' . $preview);
                    continue;
                }
                
                $size = getimagesize($preview);
                if ($size) {
                    $width = (int) $size[0];
                    $height = (int) $size[1];
                } else {
                    Tool::log('Image ID : '. $dataRow['image_id'] .' | get size from image fail : file ' . $dataRow['image_preview']);
                    continue;
                }
                               
                $sqlQuery = "UPDATE `parsed_images` SET `image_w`='{$width}', `image_h`='{$height}' WHERE `image_id` = '{$dataRow['image_id']}'";
                $this->sql->query($sqlQuery);	
                $count++;
			}
            
            $result['message'] = 'load size from : ' . $count;
        }

        $answer = array(
            'code' => 0, 
            'output' => 'none', 
            'action' => 'rewrite',
            'nojob' => $result['message'] == 'nojob' ? true : false,
            'message' => $result['description'],
            'short' => $result['message'],
            'token' => Token::set('custom_work'),
        );
        
        if ($result['error'] === false) {
        
        } else {
            $answer['code'] = 1;
            if ($result['message'] == 'nojob') $answer['code'] = -1;
        }
        
        Ajax::$message = $answer;
        Ajax::show();        
    }

    public function doParseTag() // mostly for get ratings \ preload some new from tag without authorization
    {        
        if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
        $count = Filter::input('count', 'post', 'int', false);
        $from = Filter::input('from', 'post', 'int', false);  
        $tag = Filter::input('tag', 'get', 'stringLow', true); 
        
        if ($count > 500) $count = 500;
        if (!$count or $count < 0) $count = 1;
        
        $parser = new JoyParser();
        
        // without auth in current, for get rating
        $result = $parser->parseTagPages($from, $count, $tag, false);
   
        $answer = array(
            'code' => 0, 
            'output' => 'none', 
            'action' => 'rewrite',
            'nojob' => $result['message'] == 'nojob' ? true : false,
            'message' => $result['description'],
            'short' => $result['message'],
            'token' => Token::set('custom_work'),
            'items' => $result['items'],
        );
        
        if ($result['error'] === false) {
        
        } else {
            $answer['code'] = 1;
            if ($result['message'] == 'nojob') $answer['code'] = -1;
        }
        
        Ajax::$message = $answer;
        Ajax::show();
    }    
    
    public function doRehash()
    {        
        if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
         if (!$ajax) { 
            $images = Filter::input('count', 'get', 'int', false);
            $getLeft= false;
        } else {
            $images = Filter::input('count', 'post', 'int', false);
            $getLeft = Filter::input('getLeft', 'post', 'int', false);
        }  
        
        if ($images > 500) $images = 500;
        if (!$images or $images < 0) $images = 1;
        
        $parser = new JoyParser();
        $result = $parser->rehashImages(false, $images, $getLeft);
        
        if (!$ajax) {    
            return $result['message'] . '|' . $result['description'];    
        } else {
        
            $answer = array(
                'code' => 0, 
                'output' => 'none', 
                'action' => 'rewrite',
                'nojob' => $result['message'] == 'nojob' ? true : false,
                'message' => $result['description'],
                'short' => $result['message'],
                'token' => Token::set('re_hash'),
                'images' => $result['images'],
                'left' => $result['left'],
            );
            
            if ($result['error'] === false) {
            
            } else {
                $answer['code'] = 1;
                if ($result['message'] == 'nojob') $answer['code'] = -1;
            }
            
            Ajax::$message = $answer;
            Ajax::show();
        }
    }
    
    public function doParseMaterial()
    {        
        if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
         if (!$ajax) { 
            $fail = Filter::input('fail', 'get', 'bool', true);
            $pages = Filter::input('count', 'get', 'int', false);
        } else {
            $fail = Filter::input('fail', 'post', 'bool', true);  
            $pages = Filter::input('count', 'post', 'int', false);
        }  
        
        $mode = $fail ? 'failreload' : 'normal';     
        
        if ($pages > 500) $pages = 500;
        if (!$pages or $pages < 0) $pages = 1;
        
        $parser = new JoyParser();
        $result = $parser->parseFewPages($pages, $fail);
        
        if (!$ajax) {    
            return $result['message'] . '|' . $result['description'] . '|' . $mode;    
        } else {
        
            $answer = array(
                'code' => 0, 
                'output' => 'none', 
                'action' => 'rewrite',
                'nojob' => $result['message'] == 'nojob' ? true : false,
                'message' => $result['description'],
                'short' => $result['message'],
                'token' => Token::set('parse_pages'),
                'pages' => $result['pages'],
            );
            
            if ($result['error'] === false) {
            
            } else {
                $answer['code'] = 1;
                if ($result['message'] == 'nojob') $answer['code'] = -1;
            }
            
            Ajax::$message = $answer;
            Ajax::show();
        }
    }
    
    public function doTestDownloadImages() 
    {
        $b = new Browser();
        $b->setReadMaxSize(10 * 1024 * 1024);
        
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", 0);
        
        ob_start(); include_once(Main::getShortcut('lib') . 'Page/Joy/test/test.html' );
        $body = ob_get_clean();
        
        $parser = new JoyParser();
        $blocks = $parser->parseMaterialsInfo($body);  

        foreach ($blocks as $block)
        {
            if (!sizeof($block['images'])) continue;
            $result = $b->downloadFiles($block['images'], Main::getShortcut('tmp') . 'images/', true);
            
            var_dump($result);
        }
        
        $b->close();
        
        echo $b->log;    
    }
}