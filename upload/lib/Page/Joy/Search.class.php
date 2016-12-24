<?php

class ImageSearch 
{
    public $db;
    public $env;
    
    // last data from client session
    
    public $id = 0;
    public $ip = '127.0.0.1';
    public $actions = 0;
    
    public function __construct($ip = false)
    {
        $this->db = Main::getInstance()->getDB();
        $this->env = Main::getInstance();     
    }
    
    public function makeAction($name = '', $limit = 6, $ip = false)
    {
        $ip = !$ip ? Tool::getClientIP() : $ip;
        
        $this->ip = $ip;
        
        $sql = "DELETE FROM `search_sessions` WHERE `ssession_last_active` + INTERVAL 1 MINUTE < NOW()"; 
        $this->db->ask($sql); 
        
        $sql = "SELECT * FROM `search_sessions` WHERE `ssession_ip`=:ip AND `ssession_action` = :name";
        $line = $this->db->fetchRow($sql, array('ip' => $ip, 'name' => $name)); 

        if (!$line) { 
            
            $sql = "INSERT INTO `search_sessions` (`ssession_ip`, `ssession_actions`, `ssession_action`) VALUES (:ip, :actions, :action)";
            $result = $this->db->ask($sql, array('ip' => $ip, 'actions' => 0, 'action' => $name));
            
            if (!$result) return false;
            else $this->id = (int) $this->db->lastInsertId();  
            
            $this->actions = 1;
            
        } else {
            
            $this->actions = (int) $line['ssession_actions'] + 1;
            $this->id = (int) $line['ssession_id'];
        }

        if ($this->actions > $limit) return false;
       
        $result = $this->db->ask("UPDATE `search_sessions` SET `ssession_actions` = `ssession_actions` + 1, `ssession_last_active` = NOW() WHERE `ssession_id`='{$this->id}'");  
        if ($result)  {
            return true;
        }
    
        return false;
    }
    
	private function searchSort($varA, $varB) {	
		if ($varA['diff'] > $varB['diff']) return -1;
		else return 1;
	}
	
	private function comparePalete($colorsMain, $colorsCompare) 
	{
		if (!$colorsCompare or !$colorsMain) return 0;
		
		$colors = explode(',', $colorsCompare);
		$matches  = 0;
		
		foreach($colors as $color) {
			$color = trim(strtolower($color));
			if (strpos($colorsMain,$color) !== false) $matches++;
		}
		
		return $matches;
	}
	
    public function startSearch($image) 
    {    
        $libDir = Main::getShortcut('lib');
        
        include_once($libDir . 'Imagehash/ImageHash.php');
        include_once($libDir . 'Imagehash/Implementation.php');
        include_once($libDir . 'Imagehash/Implementations/' . $this->env->cfg('hash') . '.php');
        include_once($libDir . 'Imagehash/Math/BigInteger.php');
        include_once($libDir . 'Imagehash/Math/Hex.php');
        include_once($libDir . 'Imagehash/Math/Binary.php');    
		
		include_once($libDir . 'KellyGDColorPalete.class.php');
        
        $dec = \Jenssegers\ImageHash\ImageHash::DECIMAL;
        $hashClassName = "Jenssegers\\ImageHash\\Implementations\\" . $this->env->cfg('hash');
        $hashAMethod = new $hashClassName;
        $hashObj = new Jenssegers\ImageHash\ImageHash($hashAMethod, $dec);
        $hash = $hashObj->hash($image, false);  
		
		$palete = new KellyGDColorPalete();
		$palete->setImage($image);				
		$paleteColors = $palete->getPalete(6);
		$paleteColors = implode(',' , array_keys($paleteColors));	
		
		$compareByPalete = false;		
		if (substr($paleteColors, -6) == 'ffffff') $compareByPalete = true; // hash function get bad results with absolute colors due to no brigtness gradations awailable
		
        // $hash = sprintf("%'032b", $hash);
        
        imagedestroy($image);
		$palete->clear();
        
        $sql = $this->db->ask("SELECT `search_result` FROM `search_cache` WHERE `search_dhash` = '{$hash}'");
        $sqlWhere = "`image_dhash` > '1' AND BIT_COUNT(" . $hash . " ^ image_dhash) <= 8";
        
        if ($sql and $sql->rowCount()) {
            $result = $sql->fetch();
            $ids = explode(',', $result['search_result']);
            
            $sqlWhere = "`image_id` = '" . implode("' OR `image_id`='", $ids) . "'";
            Tool::log('restore from cashe');
        }
        
        $sql = $this->db->ask("SELECT " 
                               . "BIT_COUNT(" . $hash . " ^ image_dhash) AS `diff`, " 
                               . "`image_material_id`, "  
                               . "`image_id`, " 
                               . "`image_color`, " 
                               . "`image_palete`, " 
                               . "`image_preview`, "
							   . "`image_w`, "
							   . "`image_h` "
                               . "FROM `parsed_images` WHERE "
                               . $sqlWhere . " ORDER BY `image_id` LIMIT 0,5");

        // order by DIFF ? 
		
        $results = array();
        $ids = '';
        
        if ($sql and $sql->rowCount()) {
            
            while($dataRow = $sql->fetch()) {
                $dataRowMaterial = $this->db->fetchRow("SELECT * FROM `parsed_materials` WHERE `material_id` = '{$dataRow['image_material_id']}'");
                
                $parsedImage = new JoyParsedImage($dataRow);                
                $similarIndex = round(((64 - (int) $dataRow['diff']) / 64) * 100);
				$matches = $this->comparePalete($paleteColors, $parsedImage->get('image_palete'));
				// echo $matches. '<br>';
                if ($compareByPalete and  $matches < 3) continue;
				
				//echo $parsedImage->get('image_palete') . '<br>';
				
                $results[] = array('image' => $parsedImage, 'material' => $dataRowMaterial, 'diff' => $similarIndex);
                $ids .= $ids ? ',' . $dataRow['image_id'] : $dataRow['image_id'];
            }
        } 
        
		usort($results, array($this, 'searchSort'));
		
        $sqlTpl = "INSERT INTO `search_cache` (`search_dhash`, `search_result`) ". 
                  "VALUES ('{$hash}', '{$ids}') ". 
                  "ON DUPLICATE KEY UPDATE `search_result`= '{$ids}', `search_rating`= `search_rating` + 1";   

        $this->db->ask($sqlTpl); // todo delete oldest if > 1000   
        
        $sql= $this->db->query("SELECT COUNT(*) AS `count` FROM `search_cache` WHERE `search_rating` < '80'");
		$row = $sql->fetch();

		$num = (int) $row['count'];
        if ($num > 1000) {
            $this->db->ask("DELETE FROM `search_cache` WHERE `search_rating` < '80' ORDER BY `search_rating` ASC LIMIT 200");   
        }
		
        
        return $results;
    }
    
    public function initSearch($searchLink = 'imageurl', $searchFile = 'imagefile') 
    {        
        $imageFile = array('image' => false, 'message' => '');
        $imageUrl = Filter::input($searchLink, 'post', 'stringLow', true);
                
        if (!$imageUrl) {
        
            $imageFile = self::getInputImage('imagefile');
        } else {
            
            $tmpDir = Tool::getDir(Main::getShortcut('data') . 'tmp/image/' );
    
            if (!$tmpDir) {
                 $imageFile['message'] = 'Проблема инициализации директории';
            } else {
                $browser = new Browser();
                $browser->setUrl($imageUrl);
                $imageFileInfo = $browser->downloadFiles(array($imageUrl), $tmpDir);
                
                if (!$imageFileInfo) {
                    $imageFile['message'] = 'Ошибка доступа к файлу. Удаленный ресурс недоступен';  
                } else if ($imageFileInfo[0]['error'] === 1) {
                     $imageFile['message'] = 'Ошибка поиска по файлу. Формат файла не поддерживается';  
                } else if ($imageFileInfo[0]['error'] === 3) {
                     $imageFile['message'] = 'Ошибка доступа к файлу. Ожидаемый формат файла не соответствует полученому от удаленного сервера';  
                } else if ($imageFileInfo[0]['error']) {
                     $imageFile['message'] = 'Ошибка доступа к файлу';  
                } else {
                    $imageFile = self::getInputImage(false, array('path' => $imageFileInfo[0]['message']));
                }
            }
        }
        return $imageFile;
    }
    
    public static function getInputImage($name = 'imagefile', $fileInfo = false) 
    {
        $return = array('image' => false, 'message' => '', 'code' => 1);
        
        if ($fileInfo == false) {
            if (!Tool::isFileRecieved($name)) return $return;
                    
            $fileInfo = Tool::fileSafeMove($name);
            
            if (!$fileInfo) {
                $return['message'] = 'Ошибка загрузки файла, возможно превышен допустимый размер';
                $return['code'] = 2;
                return $return;
            }
        }
        
        $tmpWay = $fileInfo['path']; 
        $size = getimagesize($fileInfo['path']);
            
        if ($size === false) {
            unlink($tmpWay);
            $return['message'] = 'Не определен формат и размеры изображения';
            $return['code'] = 3;  
            return $return;
        }   
            
        switch ($size[2]) {
            case 2: $newImg = imagecreatefromjpeg($tmpWay); $ext = 'jpg'; break;
            case 3: $newImg = imagecreatefrompng($tmpWay); $ext = 'png'; break;
            case 1: $newImg = imagecreatefromgif($tmpWay); $ext = 'gif'; break;
            default : 
                unlink($tmpWay);
                $return['message'] = 'Не определен формат изображения';
                $return['code'] = 4;    
                return $return;
            break;
        }
        
        unlink($tmpWay);
        return array('image' => $newImg);
    }
    
}
