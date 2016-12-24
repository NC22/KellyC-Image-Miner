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
    
	// limits :
	// tags - length - 1024
	// tags num - 5
	// colors num - 6
	
	public function doSearchByPalete() 
	{	
		include_once(Main::getShortcut('lib') . 'KellyGDColorPalete.class.php');
		include_once(Main::getShortcut('lib') . 'KellyImageGrid.class.php');
        
        $form = Filter::input('formName', 'post', 'stringLow', true);    
        
        // search by main color    
        $paleteColors = empty($_POST['color']) ? false : $_POST['color'];
        $tags = Filter::input('tags', 'post', 'stringLow', true);
		$tags = mb_substr($tags, 0, 1024, Main::ENCODE);
		
        $safe = Filter::input('safe', 'post', 'bool', true);   
		$from = Filter::input('from', 'post', 'int', false); 
		
        $censored = Filter::input('censored', 'post', 'bool', true); 	
        if (!$censored) $censored = Filter::input('censored', 'get', 'bool', true); 
        
		if ($from <= 0) $from = 0;
        // 
        $images = '';
        
        $censoreTags = array('Эротика', 'Ero', 'NSFW', 'секретные разделы');
        
        if ($form == 'searchbycolor') {
        
            $images = 'Совпадений не найдено';
             
            $sqlData = array();	 
            $tagsSqlStr = '';		
            $colorsSqlStr = '';
            
            $paleteHelper = new KellyGDColorPalete();
            
            $validColors = 0; $validIndex = 0; 
			
			// read POST data
			
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

				if ($validColors >= 6) break;
            }
            
            if ($validColors == 1) { // may be add to each mode, not only for one color
            //    $paleteColors = $this->getAdditionColors($paleteHelper, $colorRgb, $paleteColors[$validIndex]);
            }
            
            foreach ($paleteColors as $key => $color) {		
                if (!$color) continue;
                if ($colorsSqlStr) {                
                    // $colorsSqlStr .= ' ';
                    $colorsSqlStr .= ' OR';
                }      
                
                // $sqlData['color' . $key] .= '+'; // must contain all colors
                $sqlData['color' . $key] = '%' . $color ; // select main color
                // $sqlData['color' . $key] = $color;
                
                $colorsSqlStr .= " parsed_images.image_palete LIKE '" . $sqlData['color' . $key] . "'";
                // $colorsSqlStr .= $sqlData['color' . $key];
            }
               

               
            $tags = explode(',', $tags);
            
			$validTags = 0;
            foreach ($tags as $key => $tag) {		
                $tag = trim($tag);
                if (!$tag or strlen($tag) < 3) continue;
                if ($censored and array_search($tag, $censoreTags) !== false) continue;
                if ($tagsSqlStr) $tagsSqlStr .= ' ';
                
               // if (strpos($tag, ' ') !== false or strpos($tag, '-') !== false)
                $tagsSqlStr .= '+"' . $tag . '"* ';
               // else
               // $tagsSqlStr .= '+' . $tag . '*';
                
				$validTags++;
				if ($validTags > 5) break;
            }
            
            if ($censored) {
                
                if (!$tagsSqlStr) $tagsSqlStr = '"красивые картинки" Anime фэндомы разное';
                
                foreach ($censoreTags as $key => $tag) {		
                    $tag = trim($tag);
                    if (!$tag or strlen($tag) < 3) continue;
                        if ($tagsSqlStr) $tagsSqlStr .= ' ';
                        if (strpos($tag, ' ') !== false)
                        $tagsSqlStr .= '-"' . $tag . '" ';
                        else
                        $tagsSqlStr .= '-' . $tag . ' ';     
                }
                
                // NOT MATCH (parsed_materials.material_tags) AGAINST ('{$tagsSqlStr}' IN BOOLEAN MODE)
            }
            
            if ($colorsSqlStr) {
                // $colorsSqlStr = "MATCH (parsed_images.image_palete) AGAINST ('{$colorsSqlStr}' IN BOOLEAN MODE)";
                $colorsSqlStr = '(' . $colorsSqlStr . ') ';	
            }               
            $sqlSelect = "SELECT parsed_images.image_palete AS `palete`, "
                 .  "parsed_images.image_link AS `image_link`, "
				 .  "parsed_images.image_id AS `image_id`, "
                 .  "parsed_images.image_w AS `image_w`, "
                 .  "parsed_images.image_h AS `image_h`, "
				 .  "parsed_images.image_color AS `image_color`, "
                 .  "parsed_images.image_preview AS `image_preview`, "
                 .  "parsed_materials.material_rating AS `rating`, "
                 .  "parsed_materials.material_tags AS `tags` ";
                            
             
             $main = $tagsSqlStr ? 'parsed_materials' : 'parsed_images';
			 $join = $tagsSqlStr ? 'parsed_images' : 'parsed_materials';
			 
             $sql = '';
             if ($tagsSqlStr) {
			 		 			 
				// sub select from materials table
			 
                // if ($validTags)
                $sql .= "SELECT * FROM `parsed_materials` WHERE parsed_materials.material_images_count > '0' AND MATCH (parsed_materials.material_tags) AGAINST ('{$tagsSqlStr}' IN BOOLEAN MODE)";
                // else { // only censored data - slow
                //   $tagsSqlStr = str_replace('-', '', $tagsSqlStr); 
                //   // may be use NOT IN
                //   $sql .= "SELECT * FROM `parsed_materials` WHERE MATCH (parsed_materials.material_tags) AGAINST ('{$tagsSqlStr}' IN BOOLEAN MODE)";
                // }
             } else               
                $sql .= "SELECT * FROM `parsed_images` WHERE " . $colorsSqlStr; 
             
             $sql = $sqlSelect . ' FROM (' . $sql . ') ' . $main . ' ';             
             $sql .= "INNER JOIN `{$join}` ON  parsed_materials.material_id=parsed_images.image_material_id ";
			 
			 $where = '';		 
             $where = "parsed_images.image_ban_search = '0' AND parsed_images.image_w > '250' AND parsed_images.image_h > '250' ";
			 
             if ($tagsSqlStr and $colorsSqlStr) $where .= 'AND ' . $colorsSqlStr;
                
             if ($tagsSqlStr or $colorsSqlStr) {
                // $where .= 'AND CHAR_LENGTH(parsed_images.image_palete) = 41 ';				
				
				if ($where) $sql .= 'WHERE ' . $where . ' ';				
				
                $sql .= 'ORDER BY parsed_materials.material_rating DESC ';
                $sql .= 'LIMIT ' . $from . ', 100';
                
                //echo $sql;
                Tool::log('query ' . var_export($sql, true));
               // exit;
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
						'color' => $image['image_color'],
						'id' => $image['image_id'],
                    );   
                } 
                
                if (sizeof($imageGrid->images)) $images = $imageGrid->show();                
                Tool::log('search images by data ' . var_export($sqlData, true));
            }
           
        }
        
		$continueButton = '';
		if (sizeof($imageGrid->images) >= 100) {
			$continueButton = $from + 100;
		}
		
        View::$content['main'] = View::show('searchbycolor', array('images' => $images, 'continue' => $continueButton, 'from' => $from));
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
	
	// fix or reload palete (if was some bug, or on case if algorithm changes)
	// Drop colors to empty value first
	//
	// UPDATE `parsed_images` SET `image_palete` = '', `image_color` = '' WHERE `image_palete` LIKE '%0000e5%'
	// UPDATE `parsed_images` SET `image_palete` = '', `image_color` = '' WHERE `image_palete` = '0'
	//
	
    public function doLoadPalete() 
    { 
         if (!$this->user) return 'fail|auth required';
        $result = array('error' => false, 'message' => 'nothing to do', 'description' => '');
		
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", JoyParser::maxExecutionTimeSec);  
		
		include_once(Main::getShortcut('lib') . 'KellyGDColorPalete.class.php');
        
        $sql = $this->sql->query("SELECT * FROM  `parsed_images` WHERE `image_loaded` = '1' AND `image_palete` = '' LIMIT 0,500");
		$nojob = true;
		
        if (!$sql or !$sql->rowCount()) {}
		else {
			$result['message'] = 'update palete :: rows ' . $sql->rowCount();
			$palete = new KellyGDColorPalete(); 
            $count = 0;
            $lastDir = ''; $parsedImage = false;
			$nojob = false;
			
			while($dataRow = $sql->fetch())
			{           
				$parsedImage = new JoyParsedImage($dataRow);
				$imageFile = $parsedImage->getPreview(false);
				
				$palete->setImage($imageFile);				
				$paleteColors = $palete->getPalete(6);
				$paleteColors = implode(',' , array_keys($paleteColors));
				
				$mainColor = $palete->getRoundColor(4);	
				$palete->clear();
				
				$this->sql->query("UPDATE `parsed_images` SET `image_palete` = '{$paleteColors}', `image_color` = '{$mainColor}' WHERE `image_id` = '{$dataRow['image_id']}'");
				// $result['message'] .= $paleteColors;
			}			
		}               

        $answer = array(
            'code' => 0, 
            'output' => 'none', 
            'action' => 'rewrite',
            'nojob' => $nojob,
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

    // loads rating for materials with zero rating, sets -999 rating on fail load material
    // not adopted for multisession TODO
    public function doDownloadRatings() 
    { 
         if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
        $count = Filter::input('count', 'post', 'int', false);
        $from = Filter::input('from', 'post', 'int', false);  
        $tag = Filter::input('tag', 'get', 'stringLow', true); 
        
        if ($count > 25) $count = 25;
        if (!$count or $count < 0) $count = 1;
        
        $parser = new JoyParser();
        
        // without auth in current, for get rating
        $result = $parser->updateFewMaterialsRating($count);
   
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

    // for custom work site.ru/downloadtag?tag=new
    // http://www.site.ru/jparse/joy/downloadtag
    // continue only if all pages loaded correctly - start from 1 
    // site.ru/downloadtag - download from best
    
    public function doParseTag() // mostly for get ratings \ preload some new from tag without authorization
    {        
        if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
        $count = Filter::input('count', 'post', 'int', false);
        $from = Filter::input('from', 'post', 'int', false);  
        $tag = Filter::input('tag', 'get', 'stringLow', true); 
        $auth = Filter::input('auth', 'get', 'bool', true); 
		$all = Filter::input('all', 'get', 'bool', true); 
		
		if (!$auth) $auth = false;
		if (!$all) $all = false;
		
        if ($count > 500) $count = 500;
        if (!$count or $count < 0) $count = 1;
        
        $parser = new JoyParser();
        
        $result = $parser->parseTagPages($from, $count, $tag, $auth, $all);
   
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
	
    // remove material from tag / color search
    
    public function doDeleteFromSearch() 
    {        
        if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
        $id = Filter::input('id', 'post', 'int', true);
		if (!$id or !$ajax) return '';
		
		$sql = "UPDATE `parsed_images` SET `image_ban_search` = 1 WHERE `image_id` = :id";		
        $result = $this->sql->ask($sql, array('id' => $id)); 
			
        $answer = array(
            'code' => $result ? 0 : 1, 
            'output' => 'none', 
            'action' => 'rewrite',
            'message' => '',
        );        
		
        $sql = "INSERT INTO `site_actions` (`action_type`, `action_value`) VALUES ('delete_fromsearch', :id)";
		$this->sql->ask($sql, array('id' => $id)); 
		
        Ajax::$message = $answer;
        Ajax::show();
    } 
	
    /* moderation - delete material from selected tag TODO need to correction */
    
    public function doDeleteFromTag() 
    {        
        if (!$this->user) return 'fail|auth required';
        
        $ajax = Ajax::isAjaxRequest();
        $id = Filter::input('id', 'post', 'int', true);
		$removeTags = trim(Filter::input('tag', 'post', 'stringLow', false));
		if (!$id or !$ajax or !$removeTags) return '';
		
		$sqlOld = "SELECT parsed_materials.material_tags AS `tags`, parsed_materials.material_id AS `id` FROM `parsed_images` LEFT JOIN `parsed_materials` ON parsed_materials.material_id = parsed_images.image_material_id WHERE parsed_images.image_id = :id";
		$result = $this->sql->ask($sqlOld, array('id' => $id));
		$row = $result->fetch();
		
		if (!$row) return '';
		
		$tags = trim($row['tags']);
		$tags = str_replace($removeTags, '', $tags);
		if ($tags == ',') $tags = '';
		
		$sql = "UPDATE `parsed_materials` SET `material_tags` = :mtags WHERE `material_id` = :mid";		
        $result = $this->sql->ask($sql, array('mid' => $row['id'], 'mtags' => $tags)); 
			
        $answer = array(
            'code' => $result ? 0 : 1, 
            'output' => 'none', 
            'action' => 'rewrite',
            'message' => '',
        );        
		
        $sql = "INSERT INTO `site_actions` (`action_type`, `action_value`) VALUES ('delete_fromtags', :text)";
		$this->sql->ask($sql, array('text' =>'m : ' . $row['id'] . ' told : ' . $row['tags'] . ' tnew : ' . $tags)); 
		
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