<?php

// dont forget about /s in expressions
// if session expired - login and get unexpected page num, maybe need reconnect or resend query

// Save rating for materials downloaded from [new] disabled
// Use updateFewMaterialsRating($limit = false, $defaultRating = 0)  or parseTagPages instead
// 25.09.16 - disabled auth require to load images, always login as guest;

// TEMPORY SETTED TO NOT LOAD GIFS UNTIL ONE FRAME DOWNLOAD METHOD WILL BE DESCRIBED

class JoyParser 
{
    // todo маршруты для каталогизации превьюшек page_route = tag/page_route - превью сейвится в подпапку page_route
    // time delay для скачивания картинок
    
    /* @const */
    public static $joyimageurl = array(
        '@img@' => 'http://img1.joyreactor.cc/pics/post/',
        '@img0@' => 'http://img0.joyreactor.cc/pics/post/',
    //    '%_img_%' => 'http://img1.joyreactor.cc/pics/post/',
    );
    
    const JOYURL = 'http://joyreactor.cc/';
    const JOYCONTENTURL = 'http://joyreactor.cc/new/'; // paging
    const JOYCONTENTURLROUTE = 'http://joyreactor.cc/tag/'; 
    const JOYLOGINURL = 'http://joyreactor.cc/login';
    const JOYMATERIALURL = 'http://joyreactor.cc/post/';
    
    const delayIteration = 0.1; // usleep(250000) - 0.25s
    const maxPagesPerJob = 100;
    const maxImagesPerJob = 10; 
    const maxExecutionTimeSec = '600'; // set to 10 minutes , выставить таймауты на чтение и подключение
        
    private $env;
    private $route;
    
    private $session = false;
    
    private $b = false;
    private $curJob = false;

    public function __construct()
    {
        $libWay = Main::getShortcut('lib');
        
        include_once($libWay . 'Browser.class.php' );
        include_once($libWay . 'Page/Joy/Session.class.php' );
        include_once($libWay . 'Page/Joy/ParsedImage.class.php' );
        include_once($libWay . 'Page/Joy/BannedSearchTags.class.php' );
        
        $this->env = Main::getInstance();
        
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", self::maxExecutionTimeSec);  

        register_shutdown_function(array($this, 'shutdown'));
    }
    
    private function getDB() {
        return $this->env->getDB();
    }
 	
    public function shutdown($unexpected = true) 
    {   
        if ($this->session and $this->session->active) {
            $this->session->setActive(false);
        }
        
        $browser = $this->getBrowser();
        $browser->close();
        
        if (!$this->curJob) return;
        
        if ($unexpected) {
            Tool::log('unexpected exit from job ' . $this->curJob['name']);
            Ajax::unexpectedShow();
        }
        
        if ($this->curJob['name'] == 'image') {
             
            if (!sizeof($this->curJob['imagesLocal']) and sizeof($browser->lastDownload)) {
                $this->curJob['imagesLocal'] = $browser->lastDownload; 
            }
            
            if (sizeof($this->curJob['failIds'])) {
            
                $sqlWhere = "`image_id` = '" . implode("' OR `image_id`='", $this->curJob['failIds']) . "'";    
                $this->insertSqlData(true, "parsed_images", array('image_loaded' => '0', 'image_load_fail' => '1'), $sqlWhere);            
            }

            if (sizeof($this->curJob['tmpUnavailable'])) {
            
                Tool::log('some image items was unavailable (503 error) try next time...' . var_export($this->curJob['tmpUnavailable'], true));
                
                $sqlWhere = "`image_id` = '" . implode("' OR `image_id`='", $this->curJob['tmpUnavailable']) . "'";    
                $this->insertSqlData(true, "parsed_images", array('image_loaded' => '0', 'image_load_fail' => '0'), $sqlWhere);            
            }
            
            foreach ($this->curJob['imagesLocal'] as $result) {
                //Tool::log(var_export($result, true));
                if ($result['error'] === 0 and file_exists($result['message'])) unlink($result['message']);
            }
        }
        
        $this->curJob = false;
    }
    
	private function insertSqlData($update = false, $table, $vars, $where = '') 
	{
		if (!$update) {
			$keys = array_keys($vars);
			$varsValues = array_values($vars);
			
            $sql = "INSERT INTO `{$table}` (`".implode("`, `", $keys)."`) "
                 . "VALUES (:".implode(", :", $keys).")";				 
		
		} else {
			
			$sqlNames = '';
			foreach ($vars as $sqlKey => &$value) {
				$value = (string) $value;                
				$sqlNames .= ($sqlNames) ? ",`$sqlKey`=:" . $sqlKey : "`$sqlKey`=:" . $sqlKey;   
			}
		
			$sql = "UPDATE `{$table}` SET {$sqlNames} WHERE " . $where;
		}
		
        $result = $this->getDB()->ask($sql, $vars); 
        
		if (!$result) {	
			return false;
		} else {
			return true;
		}		
	}
    
    public function getTotalPages($location = 'new', $value = '') 
    {
        $result = $this->getDB()->fetchRow("SELECT * FROM `parsed_locations` WHERE `location_name` = :location_name AND `location_value` = :location_value", array(
            'location_name' => $location,
            'location_value' => $value
        ));
        
        if (!$result) {
            return false;
        } else {
            return (int) $result['location_pages'];
        }    
    }
    
    public function updateTotalPages($location = 'new', $value = '', $pageInfo = false) 
    {     
		$row= $this->getDB()->fetchRow("SELECT `location_pages` FROM `parsed_locations` WHERE "
                              . "`location_name` = :location_name AND "
                              . "`location_value` = :location_value AND "
                              . "`location_last_update` + INTERVAL 2 MINUTE > NOW()", array(
                                'location_name' => $location,
                                'location_value' => $value,
                              ));
        
        if ($row and $row['location_pages']) {
            $num = (int) $row['location_pages'];
            if ($num) return $num;
        }
        
        $browser = $this->getBrowser();
                
        if ($location == 'new') {
            $url = self::JOYCONTENTURL . '1';
        } elseif ($location == 'tag') {
            $url = self::JOYCONTENTURLROUTE . urlencode($value);
        } else {
            $url = self::JOYURL;
        }
          
        if (!$pageInfo) {
            $browser->setUrl($url); 
            $result = $this->sendBrowserData(); 
            
            if ($result['code'] != '200') {
                Tool::log('getTotalPages : fail to load page info ' . $url . ' unexpected answer : ' . $result['code']);
                
                return false;
            }
            
            $pageInfo = $this->parsePageInfo($result['body']);
        }
        
        if (($location == 'new' or $location == 'best') and $pageInfo['page_num'] == 1) {
            Tool::log('getTotalPages : unexpected num of pages = 1 for location ' . $location . ' url  ' . $url);
            
            return false;
        }        
         
        $data = array(
            'location_name' => $location, 
            'location_value' => $value, 
            'location_pages' => $pageInfo['page_num'],
            'location_pages_upd' => $pageInfo['page_num']
        );

        $this->getDB()->ask("LOCK TABLES `parsed_pages` WRITE, `parsed_pages` AS `parsed_pages_read` READ");
        $this->getDB()->query("DELETE FROM `parsed_pages` WHERE `page_number`='" . ($pageInfo['page_num'] - 1) . "' AND `page_loaded` = '1'"); // previuse last page need recheck if loaded
        $this->getDB()->ask("UNLOCK TABLES");
        
        $result = $this->getDB()->ask("LOCK TABLES `parsed_locations` WRITE, `parsed_locations` AS `parsed_locations_read` READ");
        
        $sqlTpl = "INSERT INTO `parsed_locations` (`location_name`, `location_value`, `location_pages`, `location_last_update`) ". 
                  "VALUES (:location_name, :location_value, :location_pages, NOW()) ". 
                  "ON DUPLICATE KEY UPDATE `location_pages`= :location_pages_upd, `location_last_update` = NOW()";      

        $result = $this->getDB()->ask($sqlTpl, $data); 
        
        $this->getDB()->ask("UNLOCK TABLES");
        
		if (!$result) {	
			return false;
		} else {
			return $pageInfo['page_num'];
		}                  
    }
	   
    // create page item with state before start work LOCK table before for read write to prevent create same job from different scripts
    
    // get new page work for location NEW
    
    public function getNewPageWork($fail = false) 
    {
        $vars = array(
            'page_number' => 0,
            'page_site_id' => 1,
            'page_loaded' => 0, // 0 - loading in progress, -1 - load failed page process, 1 - page loaded successfull, todo dont forget add to shutdown
        );
        
        $recheck = false;
        
        if (!$fail) {
            $pages = $this->getTotalPages('new');
            
            if (!$pages) {
                Tool::log('getNewPageWork : no job :  init total pages info first');
                return false;
            }
        }
        
        $result = $this->getDB()->ask("LOCK TABLES `parsed_pages` WRITE, `parsed_pages` AS `parsed_pages_read` READ");

        if (!$fail) {
        
            $result = $this->getDB()->fetchRow("SELECT `page_id`, `page_number` FROM `parsed_pages` AS `parsed_pages_read` ORDER BY `page_number` DESC LIMIT 0,1");
            
            if (!$result) {
                $vars['page_number'] = 1;
            }
            else {
                $vars['page_number'] = (int) $result['page_number'];                
                
                if ($vars['page_number'] >= $pages) { // last page already exist, check if need to recheck
                    $result2 = $this->getDB()->fetchRow("SELECT `page_loaded` FROM `parsed_pages` AS `parsed_pages_read` WHERE `page_number` = '{$vars['page_number']}'");
                    
                    if ($result2 and (int) $result2['page_loaded'] == 0) {
                        Tool::log('getNewPageWork : last page reached : current total pages info : ' . $pages . ' last page already rechacking');
                        return false;
                    }
                    
                    Tool::log('getNewPageWork : last page reached : current total pages info : ' . $pages . ' check \ recheck last page ' . $vars['page_number']);
                    $this->insertSqlData(true, 'parsed_pages', array('page_loaded' => '0'), "`page_id` = '{$result['page_id']}'");
                    $this->getDB()->ask("UNLOCK TABLES");
                    return array('id' => (int) $result['page_id'], 'page' => $vars['page_number'], 'recheck_last' => true);
                }  

                $vars['page_number']++; 
                if ($vars['page_number'] >= $pages) {
                    $recheck = true;
                }
            }
            
            $result = $this->insertSqlData(false, 'parsed_pages', $vars);
            if (!$result) {
                $this->getDB()->ask("UNLOCK TABLES");
                return false;
            }
            
            $id = (int) $this->getDB()->lastInsertId();
        } else {
            $result = $this->getDB()->fetchRow("SELECT `page_number`, `page_id` FROM `parsed_pages` AS `parsed_pages_read` WHERE `page_loaded`='0'");
            if (!$result) {
                $this->getDB()->ask("UNLOCK TABLES");
                return false;
            }
            
            $id = (int) $result['page_id'];
            $vars['page_number'] = (int) $result['page_number'];
            
            $this->insertSqlData(true, 'parsed_pages', array('page_loaded' => '-1'), "`page_id` = '{$result['page_id']}'");
        }
        
        $this->getDB()->ask("UNLOCK TABLES");
        
        return array('id' => $id, 'page' => $vars['page_number'], 'recheck_last' => $recheck);
    }
    
    public function getBrowser()
    {
        if (!$this->b) $this->b = new Browser();
        return $this->b;
    }

    public function rehashImages($ids = false, $limit = false, $getLeft = false) // todo add to form auth param
    {   
        $return = array('error' => true, 'message' => 'fail', 'description' => '', 'images' => '', 'left' => 0);
       
        if (!$limit) $limit = self::maxImagesPerJob;
        else $limit = (int) $limit;
    
        /* load requirements */
            $libDir = Main::getShortcut('lib');
            
            include_once($libDir . 'Imagehash/ImageHash.php');
            include_once($libDir . 'Imagehash/Implementation.php');
            include_once($libDir . 'Imagehash/Implementations/' . $this->env->cfg('hash') . '.php');
            
            include_once($libDir . 'Imagehash/Math/BigInteger.php');
            include_once($libDir . 'Imagehash/Math/Hex.php');
            include_once($libDir . 'Imagehash/Math/Binary.php');
            
        //
        
        $hashClassName = "Jenssegers\\ImageHash\\Implementations\\" . $this->env->cfg('hash');
        $hashAMethod = new $hashClassName; 
        
        if ($ids and sizeof($ids)) {
            $sqlWhere = "`image_id` = '" . implode("' OR `image_id`='", $ids) . "'";            
            $sql = $this->getDB()->ask("SELECT `image_id`, `image_preview` FROM `parsed_images` WHERE " . $sqlWhere);  
            $sqlCountWhere = $sqlWhere;
        } else {
            $sql = $this->getDB()->ask("SELECT `image_id`, `image_preview` FROM `parsed_images` AS `parsed_images_read` WHERE `image_preview` != '' AND `image_dhash` = '0' LIMIT 0," . $limit);
            $sqlCountWhere = "`image_preview` != '' AND `image_dhash` = '0'";
        }
        
        if (!$sql or !$sql->rowCount()) {
             $return['message'] = 'nojob';
             $return['description'] = 'nojob';
             return $return;       
        }
        
        $bad = false;
        while($dataRow = $sql->fetch()) {
        
            $parsedImage = new JoyParsedImage($dataRow);
            $imageFile = $parsedImage->getPreview(false);
            
            if (!$imageFile or !file_exists($imageFile)) {
                Tool::log('Rehash file not exist ' . $imageFile);
                continue;
            }
            
            $size = getimagesize($imageFile);
                   
            if ($size === false) {  
                Tool::log('Rehash bad image ' . $imageFile);
                continue;
            }              
            
            $newImg = false;
            switch ($size[2]) {
                case 2: $newImg = imagecreatefromjpeg($imageFile); $ext = 'jpg'; break;
                case 3: $newImg = imagecreatefrompng($imageFile); $ext = 'png'; break; 
                case 1: $newImg = imagecreatefromgif($imageFile); $ext = 'gif'; break;
                default : 
                    Tool::log('Rehash cant detect format ' . $imageFile);
                    continue;
                break;
            }
            
            if (!$newImg) continue;
            
            $dec = \Jenssegers\ImageHash\ImageHash::DECIMAL;
            $hashObj = new Jenssegers\ImageHash\ImageHash($hashAMethod, $dec);
            $hash = $hashObj->hash($newImg, false);
            // Tool::log($dataRow['image_id'] . ' ' . $hash);
            $vars = array(
                'image_dhash' => $hash,
            );
            
            if (!$hash) {
                Tool::log('Rehash bad hash data : ' . var_export($hash, true) . ' id : ' . $dataRow['image_id']);   
                $vars['image_dhash'] = 1; 
                $bad = true;      
            } else {
                    
            }
                  
            imagedestroy($newImg);             
            
            $result = $this->insertSqlData(true, "parsed_images", $vars, "`image_id` = '{$dataRow['image_id']}'"); 
            $return['images'] .= ',' . $dataRow['image_id'];
        }  
        if (!$bad) $return['error'] = false;      
 
        if ($getLeft) {
            
            $sql = $this->getDB()->query("SELECT COUNT(*) AS `count` FROM `parsed_images` WHERE " . $sqlCountWhere);
            $row = $sql->fetch();

            $return['left'] = (int) $row['count'];
        }
        
        return $return;
    }
    
	// function auth if session with login and password
	// todo add to form auth param
    public function loadImageNum($ids = array(), $loadFail = false, $limit = false, $checkHash = true) 
    {   
        $return = array('error' => true, 'message' => 'fail', 'description' => '', 'images' => '');
       
        if (!$limit) $limit = self::maxImagesPerJob;
        else $limit = (int) $limit;
    
        /* load requirements */
            $libDir = Main::getShortcut('lib');
            
            include_once($libDir . 'Imagehash/ImageHash.php');
            include_once($libDir . 'Imagehash/Implementation.php');
            include_once($libDir . 'Imagehash/Implementations/' . $this->env->cfg('hash') . '.php');
            include_once($libDir . 'KellyGDColorPalete.class.php' );      
            include_once($libDir . 'KellyGDHelper.class.php' ); 
            include_once($libDir . 'Imagehash/Math/BigInteger.php');
            include_once($libDir . 'Imagehash/Math/Hex.php');
            include_once($libDir . 'Imagehash/Math/Binary.php');
        //
    
        //$hashAMethod = new Jenssegers\ImageHash\Implementations\AverageHash; // suck at work with gif format
        $hashClassName = "Jenssegers\\ImageHash\\Implementations\\" . $this->env->cfg('hash');
        $hashAMethod = new $hashClassName; 
        
        $previewDir = Tool::getDir(Main::getShortcut('data') . '/image/preview/');
        $tmpDir = Tool::getDir(Main::getShortcut('data') . 'tmp/image/' );
        
        if (!$tmpDir) {
             Tool::log('loadImageNum | Fail to init work dir : ' . $tmpDir);
             $return['description'] = 'init work dir fail';
             return $return;
        }
        
        if (!$previewDir) {
             Tool::log('loadImageNum | Fail to init work dir : ' . $previewDir);
             $return['description'] = 'init work dir fail';
             return $return;
        }  
        
        if (!is_array($ids)) {
            $ids = array();
        }
        
        $loadFail = $loadFail ? 1 : 0;
        
        $result = $this->getDB()->ask("LOCK TABLES `parsed_images` WRITE, `parsed_images` AS `parsed_images_read` READ");
         
        if (sizeof($ids)) {
            $sqlWhere = "`image_id` = '" . implode("' OR `image_id`='", $ids) . "'";            
            $sql = $this->getDB()->ask("SELECT `image_id`, `image_link`, `image_preview` FROM `parsed_images` WHERE " .$sqlWhere);  
            
        } else {
			$sqlNoGifs = " AND `image_link` NOT LIKE '%.gif'";
			$sqlOrder = "ORDER BY `parsed_images_read`.`image_id` DESC";  // newest first
            $sql = $this->getDB()->ask("SELECT `image_id`, `image_link`, `image_preview` FROM `parsed_images` AS `parsed_images_read` WHERE `image_loaded` = '0' AND `image_load_fail` = '{$loadFail}' {$sqlNoGifs} {$sqlOrder} LIMIT 0," . $limit);
        }
        
        if (!$sql) {
             $this->getDB()->ask("UNLOCK TABLES");
             $return['message'] = 'nojob';
             $return['description'] = 'nojob';
             return $return;       
        }
        
        $setBeasyWhere = '';
        $urlList = array();
        $ids = array();
        $data = array();
        $formats = array(
			'gif',
			'jpeg',
			'jpg',
			'png',
		);
		
        while($dataRow = $sql->fetch()) {
  
			// restore actual link
            foreach(self::$joyimageurl as $alias => $url) 
            {
                $dataRow['image_link'] = str_replace($alias, $url, $dataRow['image_link']);
            }
			
			$ext = Tool::getExt($dataRow['image_link']);
			
			if (!in_array($ext, $formats)) { 
				// currently skip bmp \ tiff files - but set marker 'beasy' - to not download in future, until download method be ready
				Tool::log('skip [' . $ext . '] file ' . $dataRow['image_link']);
				
			} else {
				$urlList[] = $dataRow['image_link'];
				$ids[] = (int) $dataRow['image_id'];
				$data[] = $dataRow;			
			}
            
            $setBeasyWhere .= $setBeasyWhere ? "OR `image_id` = '{$dataRow['image_id']}'" : "`image_id` = '{$dataRow['image_id']}'";
            $setBeasyWhere .= " ";
        }  
		
		// all selected images have unsupported format and skipped
        
		if (!sizeof($urlList) and $setBeasyWhere) {
			$this->getDB()->ask("UNLOCK TABLES");
			$result = $this->insertSqlData(true, "parsed_images", array('image_loaded' => '-1'), $setBeasyWhere); 
			$return['error'] = false;
            $return['message'] = 'ok'; 
			return $return;
		}
		
		// nothing to download
		
        if (!sizeof($urlList)) {
            $this->getDB()->ask("UNLOCK TABLES");
            $return['message'] = 'nojob';
            $return['description'] = 'nojob, url list empty';
            return $return;
        } 
		
        // set IN PROGRESS \ BEASY state
        $result = $this->insertSqlData(true, "parsed_images", array('image_loaded' => '-1'), $setBeasyWhere);     
        $this->getDB()->ask("UNLOCK TABLES");
  
        // login
		$userSession = false;
		$checkAuth = false;
		
        $loginResult = $this->freeSessionLogin($checkAuth, $userSession);
        if ($loginResult['error']) {
            $return['description'] = $loginResult['description'];
            return $return;
        }
         
        $browser = $this->getBrowser();                 
        $browser->post = array();
        $browser->get = array();
        
        $this->curJob = array(
            'name' => 'image',
            'failIds' => array(),
            'imagesLocal' => array(),    
            'tmpUnavailable' => array(),
        ); 
        
        $imagesLocal = $browser->downloadFiles($urlList, $tmpDir);
        $browser->close();
        
        if ($browser->log) {
            Tool::log('loadImageNum | Browser notices : ' . $browser->log);
            $browser->log = '';
        }
        
        if ($imagesLocal === false or !sizeof($imagesLocal)) {
            $this->insertSqlData(true, "parsed_images", array('image_loaded' => '0', 'image_load_fail' => '1'), $setBeasyWhere);
            $return['description'] = 'download fail';
            $this->session->setActive(false);
            return $return;
        }
        
        $this->curJob['imagesLocal'] = $imagesLocal;
        
        $key = 0;
        foreach ($imagesLocal as $result) {
        
            $id = $ids[$key];
            $parsedImage = new JoyParsedImage($data[$key]);
			
            // Tool::log('result get img ' . $urlList[$key]);
			
            if ($result['error'] !== 0) {
                
                Tool::log('loadImageNum ' . $id .  '| fail load image : ' . $urlList[$key] . ' result : ' . $result['error']);
                Tool::log('loadImageNum | Browser notices : ' . $browser->log . ' message :: ' . $result['message']); $browser->log = '';
                               
                if ($result['error'] == 503) {
                    $this->curJob['tmpUnavailable'][] = $id;
                    Tool::log('loadImageNum | unavailable : ' . $id);
                } else {
                    $this->curJob['failIds'][] = $id;
                }
                
                $key++;
                continue;
            } 
            
            $size = getimagesize($result['message']);
            if ($size) {
                $size[0] = (int) $size[0];
                $size[1] = (int) $size[1];
            }
            
            if ($size === false) { // or ($size[1] < 32 or $size[0] < 32)) {                
                $this->curJob['failIds'][] = $id;
                $notice = 'loadImageNum ' . $id .  '| bad image data : ' . $urlList[$key];
                // if ($size) $notice .= ' | image size too small ' . var_export($size, true);
                Tool::log($notice); 
                $key++;
                continue;
            } 

            $newImg = false;
            
            switch ($size[2]) {
                case 2: $newImg = imagecreatefromjpeg($result['message']); $ext = 'jpg'; break;
                case 3: $newImg = imagecreatefrompng($result['message']); $ext = 'png'; break; // todo check why bmps detects as png
                case 1: $newImg = imagecreatefromgif($result['message']); $ext = 'gif'; break;
                default : 
                break;
            }
            
            if ($newImg === false) {
                $this->curJob['failIds'][] = $id;
                Tool::log('loadImageNum ' . $id .  '| bad image extension or bad result while attempt to create : ' . $urlList[$key]); 
                $key++;
                continue;            
            }
            
            $palete = new KellyGDColorPalete();
            $palete->setImage($newImg);
            $paleteColors = $palete->getPalete(6);
            
            $mainColor = $palete->getRoundColor(4);
            
            $hash = 0;
            if ($checkHash) {
                Tool::log('loadImageNum ' . $id .  '| check hash '); 
                $dec = \Jenssegers\ImageHash\ImageHash::DECIMAL;
                $hashObj = new Jenssegers\ImageHash\ImageHash($hashAMethod, $dec);
                $hash = $hashObj->hash($newImg, false);
                if (!$hash) {
                    Tool::log('loadImageNum ' . $id .  '| bad image hash '); 
                    $hash = 1; // set checked state, save image for future tests
               }
            }

            
            if (!$paleteColors or !$mainColor) {
                Tool::log('loadImageNum ' . $id .  '| bad image colors : ' . var_export(array($paleteColors, $mainColor), true)); 
                $this->curJob['failIds'][] = $id;                
                $key++;
                imagedestroy($newImg);
                continue;                 
            }
            
            $resultSave = $parsedImage->savePreview($newImg, $ext);            
            $palete->clear();
            
            if (!$resultSave) {
                $this->curJob['failIds'][] = $id;
                Tool::log('loadImageNum ' . $id .  '| fail to save preview '); 
                $key++;
                continue;                 
            }               
            
            $vars = array(
                'image_dhash' => $hash,
                'image_palete' => implode(',' , array_keys($paleteColors)),
                'image_color' => $mainColor,
                'image_loaded' => '1',
                'image_load_fail' => '0',
                'image_preview' => $parsedImage->get('image_preview'),
                // 'image_rait' => '0', // for mark interesting images in future
                // 'image_ban_search' => '0', 
                'image_w' => $size[0], // size of original, use resultSave array (w, h) if you need size of saved limited image
                'image_h' => $size[1],
            );    
            
            $result = $this->insertSqlData(true, "parsed_images", $vars, "`image_id` = '{$ids[$key]}'"); 
            
            $return['images'] .= ',' . $id;
            $key++;
        }
        
        if (sizeof($this->curJob['failIds'])) {
    
            if (!$return['description']) {
                $return['description'] = 'download successfull but some images not processed correctly';
            }
            
        } else {
            $return['error'] = false;
            $return['message'] = 'ok';        
        }
        
        $this->shutdown(false);
        
        return $return;
    }

	// if redirects to main page - already authored
	
    private function login() 
    {
        // return trash if user already authed
        
        if (!$this->session->exist()) {
            Tool::log('bad session ' . $this->session->id);
            return false;       
        }
        
        if (!$this->session->user or !$this->session->password) {
            return true;
        }
    
    
        $browser = $this->getBrowser();  
        $browser->post = array();
        $browser->get = array();
        $browser->setUrl(self::JOYLOGINURL);
        
        $result = $this->sendBrowserData(); // get login page with actual token, also load current cookies in browser
        
		if (strpos($browser->getRequest(), 'GET / HTTP/1.1') !== false) {
			$browser->setUrl(self::JOYLOGINURL);Tool::log($this->session->user . ' - perhaps already authed');
			return true;		
		}
		
       // if ($result['code'] == '401') {
        //    $browser->setUrl(self::JOYLOGINURL);
        //    $result = $this->sendBrowserData();
        //}

        preg_match('/<input[^>]+?value=\"(.*?)\"[^>]+?signin__csrf_token[^>]+?>/', $result['body'], $match); // name="signin[_csrf_token]"
           
        if (!empty($match[1])) {
            $token = $match[1];
        } else {   
            Tool::log('login token not founded');
            Tool::log(var_export($browser->getRequest(), true));
            Tool::log(var_export($result['header'], true));
            return false;
        }
        
        $browser->post = array(
            'signin' => array(
                'username' => $this->session->user,
                'password' => $this->session->password,
                '_csrf_token' => $token,
            )
        );
        
         //exit;
        $browser->referer = self::JOYLOGINURL;
        // send post data and read result
        //Tool::log(var_export($browser->getRequest(), true));
        
        $result = $this->sendBrowserData(); 

        
        if (empty($browser->cookies['remember']) and empty($browser->cookies['remember2'])) {
            Tool::log('[remember] session cookie is empty. Check password and username for session user - ' . $this->session->user);
            // wrong login or password or protocol deprecated
            // joy always redirects on main page after successfull authorization
            Tool::log(var_export($browser->getRequest(), true));
            Tool::log(var_export($result['header'], true));
            return false;
        }
        
        $this->session->setCookies($browser->cookies);
        
        // update cookies here
        
        return $result;
    }
    
    // todo check material save
    
    public function savePageInitInfo($blocks, $pageId = false, $saveRating = true)
    {       
        $db = $this->getDB();
        
        if ($pageId !== false) {
            $pageId = (int) $pageId;
            if (!$pageId) return false;
        }
        
        foreach($blocks as $material)
        {
            $material['id'] = (int) $material['id']; 
            
            $oldMaterialData = $this->getDB()->fetchRow("SELECT `material_images_count` FROM `parsed_materials` WHERE `material_id`= '{$material['id']}'");
            
            $update = false;
            $where = '';
            
            if ($oldMaterialData) {
                Tool::log('material already exist...update data ' . var_export(array('page_id' => $pageId, 'material_id' =>  $material['id']), true));
                $oldMaterialData['material_images_count'] = (int) $oldMaterialData['material_images_count'];
                $update = true;
                $where = "`material_id` = '{$material['id']}'";
            }
        
            $authorId = 0;
            $imagesCount = 0;
            
            if ($material['username']) {
            
                $sql = $db->ask("SELECT `author_id` FROM `parsed_authors` WHERE `author_name`=:author", array('author' => $material['username']));
                $row = $sql->fetch();
                $authorId = (int) $row['author_id'];
                
                if (!$authorId) {
                
                    $vars = array(
                        'author_name' => $material['username'],
                        'author_loaded' => 0,
                    ); 
                    
                    $result = $this->insertSqlData(false, 'parsed_authors', $vars);
                    
                    if ($result) {
                        $authorId = (int) $db->lastInsertId();
                        
                    } else {
                    
                        Tool::log('Notice : problem with creating author default information ' . $material['username']);
                    }
                }
            }
            
            $gifs = ''; // just save, currently dont shure if they needed
            
            if (is_array($material['gifs'])) {
                foreach($material['gifs'] as $gif) 
                {
                    foreach(self::$joyimageurl as $alias => $url) {
                        $gif = str_replace($url , $alias, $gif);
                    }
                    
                    $gifs .= $gif;
                } 
            }
            
            $vars = array(
                'material_id' => $material['id'],
                'material_images_loaded' => 0,
                'material_author_id' => $authorId,
                'material_date' => $material['time'],
                'material_censored' => $material['censored'] ? 1 : 0,
                'material_auth_required' => $material['auth_required'] ? 1 : 0,
                'material_title' => $material['title'],
                'material_tags' => $material['tags'],
                'material_gifs' => $gifs,
            );


            // if material rating successfull parsed update it
            if ($saveRating and (float) $material['rating']) {
                $vars['material_rating'] = $material['rating'];
              //  Tool::log('set material rating ' . $vars['material_rating']);
            } else {
               // Tool::log('zero material rating set default \ keep the same'); // todo remove
            }
            
            if (is_array($material['images']) and (!$update or ($update and $oldMaterialData['material_images_count'] == 0))) {
            /* WARNING
                not update images at current cause that require full remove of hash and image data
                make other function if this will be needed in future
                
                may update if previouse was zero images
            */
                          
            //    if ($update) {
            //        $db->query("DELETE FROM `parsed_images` WHERE `image_material_id`='" . $material['id'] . "'");
            //        Tool::log('Notice : remove ' .  $material['id']);
            //    }
            
                if ($update) {
                    Tool::log('savePageInitInfo : add images data ' . $material['id'] . ' | images count new ' . sizeof($material['images']) . ' old ' . $oldMaterialData['material_images_count']);
                }
                
                $imagesCount = sizeof($material['images']);
                
                foreach($material['images'] as $image) 
                {
                    foreach(self::$joyimageurl as $alias => $url) {
                        $image = str_replace($url , $alias, $image);
                    }
                    
                    $this->insertSqlData(false, 'parsed_images', array(
                        'image_material_id' => $material['id'],
                        'image_link' => $image,
                        'image_loaded' => 0,
                        'image_ban_search' => BannedSearchTags::isValidTags($material['tags']) ? 0 : 1,
                    ));
                }
                
                $vars['material_images_count'] = $imagesCount;
            }
            
           // if ($this->session and !$this->session->user) {
           //     unset($vars['material_rating']); // not update rating while user loged in
           // }
            
            $result = $this->insertSqlData($update, 'parsed_materials', $vars, $where);
            
            if (!$result) {
                Tool::log('Notice : material save fail ' . var_export($material, true));
                return false;
            }
        }
        
        if ($pageId !== false) {
            $vars = array(
                'page_loaded' => 1,
            );      

            $result = $this->insertSqlData(true, 'parsed_pages', $vars, "`page_id` = '{$pageId}'");
            if (!$result) {
                Tool::log('Notice : update page info base fail ' . $pageId);
            }
            
            return $result;
        } else return true;
    }
    
    public function parsePageInfo($body) 
    {        
        $pageInfo = array(
            'location' => 'best',
            'tagName' => '',
            'page' => 1,
            'page_num' => 1,
        );
        
        preg_match('/<div[^>]+?id=\"breadCrumb[^>]+?>[^.]+?<span[^>]+?class=\"fn[^>]+?>(.*?)<\/span>/s', $body, $tag);
        if (!empty($tag) and !empty($tag[1])) {
            $pageInfo['location'] = 'tag';
            $pageInfo['tagName'] = $tag[1];
        }
        
        preg_match('/<div[^>]+?class=\"mainheader[^>]+?>Бездна<\/div>/s', $body, $header); // iU
        
        if (!empty($header) and !empty($header[0])) {
            $pageInfo['location'] = 'new';
        }
        
        preg_match('/<div[^>]+?class=\"pagination_expanded[^>]+?>(.*?)<\/div>/s', $body, $match);
        
        if (!empty($match)) {
            preg_match('/<a[^>]+?>(.*?)<\/a>/s', $match[1], $first);
            
            if (!empty($first)) {
                $pageInfo['page_num'] = (int) $first[1];
            }
            
            preg_match('/<span[^>]+?>(.*?)<\/span>/s', $match[1], $current);
            if (!empty($current)) {
                $pageInfo['page'] = (int) $current[1];
            }
            
            if ($pageInfo['page'] > $pageInfo['page_num']) $pageInfo['page_num'] = $pageInfo['page'];
            
        }
        
        
        /*preg_match('/[^>]+?href=\"\/(.*?)\/[^>]+?>(.*?)<\/div>/s', $match, $links);*/
        
        return $pageInfo;
       
    
    }
    
    public function parseMaterialsInfo($body) 
    { 
        preg_match_all('/<div[^>]+?postContainer([0-9]+)[^>]+?>(.*?)class=\"post_comment_list/s', $body, $match);

        
        $key = 0;
        $contentBlocks = array();
        
        if (empty($match) or empty($match[2])) {
            Tool::log('empty page ' . $body);
            return $contentBlocks;
        }
        
        foreach($match[2] as $content)
        {
            $key = sizeof($contentBlocks);    
            
            $contentBlocks[$key] = array(
                'id' => $match[1][$key],
                'censored' => false,
                'username' => '',
                'userid' => '',
                'tags' => '',
                'timestamp' => '',
                'time' => '',
                'rating' => '',
                'title' => '',
                'auth_required' => false, // dont needed cause we always try to login
                'images' => array(),
                'gifs' => array(),
            ); 
            
            preg_match('/<h3>(.*?)<\/h3>/s', $content, $title);
            if (!empty($title)) {
                $contentBlocks[$key]['title'] = $title[1];
            }
            
            preg_match('/<div[^>]+?class=\"uhead_nick[^>]+?>(.*?)<\/div>/s', $content, $user);
            if (!empty($user)) {
                preg_match('/<a[^>]+?>(.*?)<\/a>/s', $user[1], $userName);
                
                if (!empty($userName)) {
                    $contentBlocks[$key]['username'] = $userName[1];
                }
               
            } else{
                Tool::log('parseMaterialsInfo : cant find user info '); 
            }
            
            preg_match('/<h2[^>]+?class=\"taglist[^>]+?>(.*?)<\/h2>/s', $content, $taglist);
            if (!empty($taglist)) {
                preg_match_all('/<a[^>]+?>(.*?)<\/a>/s', $taglist[1], $tags);
 
                if (!empty($tags)) {
                    $contentBlocks[$key]['tags'] = implode(',', $tags[1]);
                }
                          
            } else{
                Tool::log('parseMaterialsInfo : cant find taglist '); 
            }
            
            preg_match('/<span[^>]+?data-time=\"(.*?)\"/s', $content, $dateTime); // check localized or not localized ?
            
            /*
            preg_match('/<span[^>]+?class=\"date[^>]+?>(.*?)<\/span>/', $content, $date);
            preg_match('/<span[^>]+?class=\"time[^>]+?>(.*?)<\/span>/', $content, $time);
            */
            
            if (!empty($dateTime)) { 
                $contentBlocks[$key]['timestamp'] = (int) $dateTime[1];
                $contentBlocks[$key]['time'] = date('Y-m-d H:i:s', $contentBlocks[$key]['timestamp']);
            }
            
            if (strpos($content, 'images/unsafe_ru.gif') !== false) {
               $contentBlocks[$key]['auth_required'] = true; // r34
               $key++;
               continue;
            }
            
            if (strpos($content, 'alt="Censorship"') !== false) {
               $contentBlocks[$key]['censored'] = true; // tags - ecchi, эччи
               $key++;
               continue;
            }
            
            preg_match('/<span[^>]+?class=\"post_rating[^>]+?><span>(.*?)<div class=\"vote/s', $content, $rating);
            /*preg_match('/<span[^>]+?class=\"post_rating[^>]+?>[^.]+?(\d+(?:\.\d+))[^.]+?<\/span>/s', $content, $rating); // {1, 3}*/
            if (!empty($rating)) { 
                $contentBlocks[$key]['rating'] = sprintf("%.3f", (float) $rating[1]); 
            }            
            
            preg_match_all('/<div[^>]+?image[^>]+?>(.*?)<\/div>/s', $content, $images);
            foreach($images[1] as $image)
            {
                preg_match('/<span[^>]+?video_gif_holder[^.]+?href=\"(.*?)\"/s', $image, $gifSrc);
                if (!empty($gifSrc)) {
                    $contentBlocks[$key]['gifs'][] = $gifSrc[1];
                } 
                
                preg_match('/<img[^>]+?src=\"(.*?)\"[^>]+?>/s', $image, $imgSrc);
                
                if (empty($imgSrc[1])) continue;
                else $contentBlocks[$key]['images'][] = $imgSrc[1];
              
            }
            
            $key++;
        }
        
        return $contentBlocks;
    }
    
    // by default search in best
    // tag = new - search in DNIWE
	// download materials from tag page, update if material exist, update materials ratings if auth = false and tag != new
    
    public function parseTagPages($from, $number = 100, $tag = false, $auth = false, $all = false) 
    {
        $return = array('error' => true, 'message' => 'fail', 'description' => false, 'items' => '');
        $browser = $this->getBrowser();
        
        if ($auth) {
            $result = $this->getDB()->ask("LOCK TABLES `job_sessions` WRITE, `job_sessions` AS `job_sessions_read` READ");
            
            $this->session = JobSession::getFreeSession(true);
            if (!$this->session) {
                $return['description'] = 'session beasy';
                return $return;
            }
            
            $this->session->setActive(true);
            $this->getDB()->ask("UNLOCK TABLES");  
            $browser->cookies = $this->session->cookies;   
			
			if (!sizeof($browser->cookies)) {
				// attempt to login
				$result = $this->login(); 
				if (!$result) {
					$this->session->setActive(false);
					$browser->close();
					$return['description'] = 'login fail';
					return $return;               
				}            
			} else {              
				Tool::log('session restored without check ' . $this->session->user);
			}
        }

		$urlAdditions = '';
		
        $tag = trim($tag);
        
            if ($tag and $tag != 'new') { 
			
			$url = self::JOYCONTENTURLROUTE . urlencode($tag) . '/';
			if ($all) $urlAdditions .= '/all';
			
        } elseif (!$tag) $url = self::JOYURL;
        else $url = self::JOYCONTENTURL;
        
        Tool::log('parseTagPage : begin work parse from ' . $from . ' to : ' . ($from + $number - 1) . ' url : ' . $url);               
        
        $parsed = 0;
        for ($i = $from; $i <= $from + $number - 1; $i++)
        {
            $browser->setUrl($url . $urlAdditions . $i);
            $result = $this->sendBrowserData();
            
            if (!is_array($result)) {
                $return['description'] = 'fail to read page';
                Tool::log('parseTagPage : fail to read page ' . $browser->log . ' result code : ' . $result . ' url : ' . $url);               
                continue;
            }  

			if ($auth) {
			
				Tool::log('parseTagPages: session restored for ' . $this->session->user);
                
				$checkAuthResult = $this->checkSession($result['body']);
				
				if ($checkAuthResult['error']) {
					Tool::log('parseTagPages: check session fail ' . $checkAuthResult['description']);
					$this->session->setActive(false);
					continue; 
				}
			}
             
            $pageInfo = $this->parsePageInfo($result['body']);
            if ($i > $pageInfo['page_num']) {
                $return['description'] = 'fail to read page, unexpected page info ';
                Tool::log('parseTagPages : Bad page info : Expected page is bigger than total pages number : page info | total pages' . $pageInfo['page_num'] . ' returned page ' . $pageInfo['page'] . ' | expected page ' . $i);
                Tool::log($result['body']);
                continue;            
            } 

            
            if ($pageInfo['page'] !== $i) {
                Tool::log('parseTagPages : Unexpected page number - expected number ' . $i . ' got : ' . $pageInfo['page'] . ' url : ' . $url);
                $return['description'] = 'fail to read page';
                continue; 
            }
            
            $blocks = $this->parseMaterialsInfo($result['body']);
            
            if (sizeof($blocks)) {
                $saveRating = true;
                if ($auth or $tag == 'new') $saveRating = false;
				
				//$defaultRating = 1; // load from good
				//if ($all) $defaultRating = 0; // load from all
                
                $result = $this->savePageInitInfo($blocks, false, $saveRating);
                if (!$result){
                    $return['description'] = 'save data fail';
                    continue;
                } else {
                    $return['items'] .= ',' . $i . ' materials - ' . sizeof($blocks);
                    $parsed++;
                }
            } else {
                Tool::log('parseTagPages : page empty ' . $i . ' number : ' . $i); 
            } 

            if ($i == $pageInfo['page_num']) {
                $return['message'] = 'nojob';
                $return['description'] = 'cant get new Job';
                Tool::log('parseTagPages : Last page ' . $i . ' reached'); 
                if ($this->session) $this->session->setActive(false);
                $browser->close();                
                return $return;
            }            
        } 
        if ($parsed !== $number) {
            $return['description'] = 'some pages not loaded';       
        } else {
            $return['error'] = false;
            $return['message'] = 'ok';   
        }
        

        if ($this->session) $this->session->setActive(false);
        $browser->close();  
                
        return $return;        
    }
    
	// check session and relogin if deprecated \ expired
	
    public function checkSession($body = false) 
    {
        $return = array('error' => true, 'description' => 'body empty', 'relogin' => false);
        $browser = $this->getBrowser();
        
        if ($body === false) {
        
            $browser->clearData();
            $browser->close();
            $browser->setUrl(self::JOYURL);
            $result = $this->sendBrowserData(); 
            if (!$result['body']) {
                Tool::log('checkSession: body of main page empty');
                
                return $return;
            }
            
            $body = $result['body'];
        }
        
        preg_match('/<a[^>]+?>[^>]+?' . $this->session->user . '[^>]+?<\/a>/s', $body, $match);
        
        if (empty($match[0])) {
        
            $this->session->setCookies(array());
            // session expired
            
             Tool::log('checkSession : ' . $this->session->user . ' - session expired');
             
            $browser->clearData();
            $browser->close();
            usleep(3 * 1000 * 1000);
            
            $resultLogin = $this->login(); 
            if (!$resultLogin) {
                $return['description'] = 'relogin fail';
                return $return;                
            }
            
            $return['relogin'] = true;
        } 

        $return['error'] = false;
        return $return;
    }
    
	// user - null get any free session
	// false - get any guest session
	// true - get session with user name and password, to pass authentication
	
    public function freeSessionLogin($checkAuth = false, $user = null)
    {
        $return = array('error' => true, 'description' => '', 'guest' => false, 'sessionUnchecked' => false);
        
        $result = $this->getDB()->ask("LOCK TABLES `job_sessions` WRITE, `job_sessions` AS `job_sessions_read` READ");
        
        $this->session = JobSession::getFreeSession($user);
        if (!$this->session) {
            $return['description'] = 'all sessions beasy';
            return $return;
        }
        
        $this->session->setActive(true);
        
        $this->getDB()->ask("UNLOCK TABLES");
        
        if (!$this->session->user or !$this->session->password) {
            $return['guest'] = true;
        }
        
        $browser = $this->getBrowser();
        //$checkSessionRestore = true;
        $browser->cookies = $this->session->cookies;
        
        if (!$return['guest']) {
            
            if (!sizeof($browser->cookies)) {
                Tool::log('freeSessionLogin: attempt to login ' . $this->session->user);
                $result = $this->login(); 
                if (!$result) {
                    $this->session->setActive(false);
                    $browser->close();
                    $return['description'] = 'login fail';
                    return $return;               
                }
            } else {
            
                Tool::log('freeSessionLogin: session restored for ' . $this->session->user);
                
                if ($checkAuth == true) {
                    $checkAuthResult = $this->checkSession(false);
                    
                    if ($checkAuthResult['error']) {
                        $return['description'] = $checkAuthResult['description'];
                        $this->session->setActive(false);
                        return $return;
                    }
                } else $return['sessionUnchecked'] = true;
            }
        }   
        
        $return['error'] = false;
        return $return;
    }
    
    // load materials rating where rating is zero or some value
    
    public function updateFewMaterialsRating($limit = false, $defaultRating = 0) 
    {        
		$defaultRating = (int) $defaultRating;
        $return = array('error' => false, 'message' => 'ok', 'description' => false, 'items' => '');
        
        if (!$limit) $limit = 25;
        
        $browser = $this->getBrowser();
        // material_rating = -999 - loading process \ fail load
        
        $sql = $this->getDB()->ask("SELECT `material_id` FROM `parsed_materials` WHERE `material_rating` = '{$defaultRating}' LIMIT 0," . $limit);
        
        if (!$sql or !$sql->rowCount()) {
            $return['message'] = 'nojob';
            $return['description'] = 'nojob';
            return $return;       
        }
        
        while($dataRow = $sql->fetch()) {
        
            $url = self::JOYMATERIALURL . $dataRow['material_id'];
            $browser->setUrl($url); 
            $result = $this->sendBrowserData(true);
            $where = "`material_id` = '{$dataRow['material_id']}'";
            
            $this->insertSqlData(true, 'parsed_materials', array('material_rating' => '-999'), $where);
             
            if (!is_array($result)) {
                $return['description'] = 'fail to read material page';
                Tool::log('updateFewMaterialsRating : fail to read material page ' . $browser->log . ' result code : ' . $result . ' url : ' . $url); 
            } else {
                $blocks = $this->parseMaterialsInfo($result['body']);
                
                if (sizeof($blocks) and (int) $blocks[0]['id'] == (int) $dataRow['material_id']) {
                
                    $return['items'] .= $return['items'] ? ',' . $dataRow['material_id'] : $dataRow['material_id'];
                    
                    if (!(float) $blocks[0]['rating']) {
                    
                        Tool::log('rating is zero for material ' . $dataRow['material_id']);
                        $this->insertSqlData(true, 'parsed_materials', array('material_rating' => '-0.01'), $where); // -0.01 to detect them in future
                        $return['description'] = 'zero rating';
                        
                        $return['items'] .= '(zero)';
                        
                    } else {
                    
                        $this->insertSqlData(true, 'parsed_materials', array('material_rating' => $blocks[0]['rating']), $where);
                        Tool::log('load rating for material ' . $blocks[0]['rating'] . ' | ' . $dataRow['material_id']);
                       
                    }
                    
                } else {
                    Tool::log('updateFewMaterialsRating : page empty, couldnt find material ' . $dataRow['material_id']); 
                }           
            }
        }
        
        if (!$return['items']) {
            $return['error'] = true;
            $return['message'] = 'all items load fail';
        }
        
         $browser->close();
        
        return $return;
    }
    
    // todo check is frontpage -- done
    
    // fail - parse failded pages
    
    public function parseFewPages($num = false, $fail = false) 
    {
        $return = array('error' => true, 'message' => 'fail', 'description' => false, 'pages' => '');
        
        if (!$num) $num = self::maxPagesPerJob;
        $loginResult = $this->freeSessionLogin();
        
        if ($loginResult['error']) {
            $return['description'] = $loginResult['description'];
            return $return;
        }
        
        $browser = $this->getBrowser();
        $notLoaded = array();
        
        for ($i = 1; $i <= $num; $i++) {
            $page = $this->getNewPageWork($fail);
            if ($page['recheck_last']) {
                $num = -1;                
            }
               
            if (!$page or !$page['page']) {
                $return['message'] = 'nojob';
                $return['description'] = 'cant get new Job';
                Tool::log('parseFewPages : cant get new Job');
                break;
            }
            
            Tool::log('parseFewPages : begin job ' . $page['page'] . ' | ' . ($page['recheck_last'] ? ' recheck last' : ''));
            $url = self::JOYCONTENTURL . $page['page'];
            $browser->setUrl($url); // if '/' at the end joy throw 404 page
            $result = $this->sendBrowserData();
            
            // check session and relogin if needed only once per job
            
            if (is_array($result) and $loginResult['sessionUnchecked']) {
                $loginResult['sessionUnchecked'] = false;
                
                $checkSessionResult = $this->checkSession($result['body']);

                if ($checkSessionResult['error']) {
                    $return['description'] = $checkSessionResult['description'];
                    $notLoaded[] = $page['id'];
                    break;
                }
                
                if ($checkSessionResult['relogin']) {
                    $browser->setUrl($url); 
                    $result = $this->sendBrowserData();
                } 
            } 
            
            if (!is_array($result)) {
                $return['description'] = 'fail to read page';
                Tool::log('parseFewPages : fail to read page ' . $browser->log . ' result code : ' . $result . ' url : ' . $url);
                $notLoaded[] = $page['id'];                
                break;
            } 
            
            if ($loginResult['guest'] and !sizeof($this->session->cookies)) {
                $this->session->setCookies($browser->cookies); // for guests without login \ pass save just something
            }
            
            $pageInfo = $this->parsePageInfo($result['body']);
            if ($page['page'] > $pageInfo['page_num']) {
                $return['description'] = 'fail to read page, unexpected page info ';
                $notLoaded[] = $page['id'];
                Tool::log('parseFewPages : Bad page info : Expected page is bigger than total pages number : page info | total pages' . $pageInfo['page_num'] . ' returned page ' . $pageInfo['page'] . ' | expected page ' . $page['page']);
                Tool::log($result['body']);
                break;            
            }
            
            if ($page['page'] == $pageInfo['page_num']) {
             
                Tool::log('parseFewPages : Last page ' . $page['page'] . ' reached'); // may be some custom parser will be needed to catch updates fast, last page always rechecks                
                
            }
            
            if ($pageInfo['page'] !== $page['page']) {
                Tool::log('parseFewPages : Unexpected page number - expected number ' . $page['page'] . ' got : ' . $pageInfo['page'] . ' url : ' . $url);
                $return['description'] = 'fail to read page';
                $notLoaded[] = $page['id'];                
                break;
            }
            
            $blocks = $this->parseMaterialsInfo($result['body']);
            
            if (sizeof($blocks)) {
            
                $result = $this->savePageInitInfo($blocks, $page['id'], false); // dont save rating - in new it always hidden
                if (!$result){
                    $return['description'] = 'save data fail';
                    $notLoaded[] = $page['id'];
                    break;
                }
            } else {
                Tool::log('parseFewPages : page empty ' . $page['id'] . ' number : ' . $page['page']); 
            }
            
            $return['pages'] .= ',' . $page['page'];
            
            if (self::delayIteration) {
                // Tool::log('parseFewPages : go sleep '); 
                usleep(self::delayIteration * 1000 * 1000);
                // Tool::log('parseFewPages : go sleep restore'); 
            }      
        }
        
        if (sizeof($notLoaded) and $fail) {
            $sqlNotLoaded = '';
            foreach($notLoaded as $id) {
                $sqlNotLoaded = $sqlNotLoaded ? "OR `page_id` = '{$id}' " : "`page_id` = '{$id}' ";
            }
            
            $this->insertSqlData(true, 'parsed_pages', array('page_loaded' => '0'), $sqlNotLoaded);
        }     
            
        //var_dump($result['body']);
        //var_dump($browser->log);
        $this->session->setActive(false);
        $browser->close();
        
        if (!$return['description']) {
            $return['error'] = false;
            $return['message'] = 'ok';
        }
        
        return $return;
    }
    
    // 3 - too many redirects
    // if disconnect try to reconnect
    
    private function sendBrowserData($reconnectNewLoc = false) {
    
        $b = $this->getBrowser();
        if (!$b->isConnected()) $b->connect();  
        
        $b->sendRequest(true, true);
        
        $result = $b->readData();
        
        if (is_array($result) and $result['location']) {
            
            $vurl = $b->validateUrl($result['location']);
            $ourl = $b->url;
            Tool::log('redirect to ' . $result['location']); // . ' | ' . var_export($vurl, true)
            $b->setUrl($vurl);

            if ($reconnectNewLoc or $ourl['port'] != $vurl['port']) {
                $b->close();
                $b->connect();
                
                // echo "close old port<br>";
            }
            
            $b->sendRequest();
            // echo "redirecting to ". $result['location'] ."...<br>";
            $result = $b->readData();	
            
            // Tool::log(var_export($result, true));
            if (is_array($result) and $result['location']) {
                return 3;
            }
        }
        
        return $result;
    }
    
    private function getLastPageId($jcontent) 
    {
        preg_match('/<div[^>]+?pagination_expanded[^>]+?>(.*?)<\/div>/s', $jcontent, $match);
        if (!empty($match[1])) {
            preg_match('/<a[^>]+?[^>]+?>(.*?)<\/a>/s', $match[1], $match);
            if (!empty($match[1])) {
                return (int) $match[1];
            }
        } 

        return false;
    }
}