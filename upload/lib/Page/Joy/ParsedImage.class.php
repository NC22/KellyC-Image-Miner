<?php
// read only class to easy work with selected data

class JoyParsedImage
{   
    public static $previewFolder = 'joydata/';
    private $data;
    public static $lastPreviewSubdir = '';
    
    public function __construct($data)
    {    
        $this->data = $data;
    }    
    
    public function isSubDirLimited($saveTo)
    {
        if (!file_exists($saveTo)) return false;
        
        if ($handle = opendir($saveTo)) {
            $i = 0;
            while (($file = readdir($handle)) !== false){
                if (!in_array($file, array('.', '..')) && !is_dir($saveTo.$file)) 
                    $i++;
                    
                if ($i > 2000) {
                    return true;
                }
            }
        } 
        
        return false;
    }
    
    // todo replace to mysql storage about count of files
    
    public function createNewSubDirIfLimit() {
        $saveTo = Main::getShortcut('root') . self::$previewFolder;
        
        $subDir = substr($this->getPreviewSubDir(false), 0, -1);
        
        $count = 1;
        while (file_exists($saveTo . $subDir . $count . '/')) {
            $count++;
        }
        
        $curCount = $count;
        $curCount--;
        
        if ($curCount == 0) $curCount = '';
         
        if ($this->isSubDirLimited($saveTo . $subDir . $curCount . '/')) {
  
            return Tool::getDir($saveTo . $subDir . $count);
        }   
    }
    
    public function getPreviewSubDir($dcount = true)
    {
        $subDir = $this->data['image_link'];
        
        if (!$subDir or strlen($subDir) < 2) return 'i/m/';
        
        if ($subDir[0] == '@') {
            foreach(JoyParser::$joyimageurl as $alias => $url) 
            {
                $subDir = str_replace($alias, '', $subDir);
            }
        } else {
            foreach(JoyParser::$joyimageurl as $alias => $url) 
            {
                $subDir = str_replace($url, '', $subDir);
            }        
        }
        
        $subDir = urldecode($subDir);
        $subDir = Tool::generateIdWord($subDir);
        $subDir = str_replace('_', 'n', $subDir);  
        
        if (strlen($subDir) < 2) $subDir = 'i/m';
        else {
            $subDir = substr ($subDir, 0, 1) . '/' . substr ($subDir, 1, 1);
        }     
        
        $count = '';
        if ($dcount) {
            $saveTo = Main::getShortcut('root') . self::$previewFolder . $subDir; 
            
            $count = 1;
            while (file_exists($saveTo . $count . '/')) {
                $count++;
            }
            
            $count--;
            if ($count == 0) $count = '';
        }
        
        
        $subDir .= $count . '/';

        return $subDir;
    }
    
    public function savePreview($newImg, $ext) 
    {            
        include_once(Main::getShortcut('lib') . 'KellyGDHelper.class.php' ); 

        $previewSubFolder = $this->getPreviewSubDir();
        if (self::$lastPreviewSubdir !== $previewSubFolder) {
            $this->createNewSubDirIfLimit();
        }        
        
        $saveTo = Tool::getDir(Main::getShortcut('root') . self::$previewFolder . $previewSubFolder);
        if (!$saveTo) return false;  
        
        $alpha = false;
        if ($ext == 'png') $alpha = true;
        
        $preview = KellyGDHelper::reduseSize($newImg, 1960, $alpha);
        $previewName = Tool::uniName($saveTo, 'pr_', $ext);
                   
         switch ($ext) {
            case 'jpg':
            case 'jpeg': $result = imagejpeg($preview, $saveTo . $previewName, 80); break;
            case 'png': $result = imagepng($preview, $saveTo . $previewName, 9); break;
            case 'gif': $result = imagegif($preview, $saveTo . $previewName); break;
            default : $result = false;
        }              
        
        if ($result) {
            chmod($saveTo . $previewName, 0664);
            
            $oldPreview = $this->getPreview(false);
            if ($oldPreview and file_exists($oldPreview)) {
                Tool::log('new preview ' . $previewName . ' | remove old preview ' . $oldPreview);
                unlink($oldPreview);
            }  
            
            $this->data['image_preview'] = $previewSubFolder . $previewName;
            $result = array(imagesx($preview), imagesy($preview));
        }
        
        self::$lastPreviewSubdir = $previewSubFolder;
        
        imagedestroy($preview);
        return $result;
    }
    
    public function getPreview($asUrl = true) 
    {
        if (empty($this->data['image_preview'])) return false;
        
        $imagePreview = $asUrl ? KELLY_ROOT_URL : Main::getShortcut('root');
        $preview = $this->data['image_preview'];
        
        if (substr($preview, 0, 3) == 'pr_') {
            $imagePreview .= 'data/image/preview/' . $preview;
        } else {
            $imagePreview .= self::$previewFolder . $preview;
        } 
        
        return $imagePreview;
    } 
    
    public function getPalete() {
        if (empty($this->data['image_palete'])) return array();
        
        return explode(',', $this->data['image_palete']);
    }
    
    public function get($key) {
        if (!isset($this->data[$key])) return false;
        else return $this->data[$key];    
    }
}