<?php
class Tool
{
	public static function getExt($file) {
		return strtolower(substr($file, 1 + strrpos($file, ".")));
	}
    
	public static function createPasswordHash($input)
	{
		if (!$input) $input = '';
		
		return md5(md5($input . '$ff$2FGeEGhWQszD'));
	}
    
    public static function getMaxUploadFileSize() 
    {
        $normalize = function($size) {
            if (preg_match('/^([\d\.]+)([KMG])$/i', $size, $match)) {
                $pos = array_search($match[2], array("K", "M", "G"));
                if ($pos !== false) {
                    $size = $match[1] * pow(1024, $pos + 1);
                }
            }
            return $size;
        };
        
        $maxUpload = $normalize(ini_get('upload_max_filesize'));
        
        if (ini_get('post_max_size') == 0) return false;
        $maxPost = $normalize(ini_get('post_max_size'));

        $memoryLimit = (ini_get('memory_limit') == -1) ?
                $maxPost : $normalize(ini_get('memory_limit'));

        if ($memoryLimit < $maxPost || $memoryLimit < $maxUpload)
            return $memoryLimit;

        if ($maxPost < $maxUpload)
            return $maxPost;

        $maxFileSize = min($maxUpload, $maxPost, $memoryLimit);
        return $maxFileSize;
    }
    
    // MIME type list http://webdesign.about.com/od/multimedia/a/mime-types-by-content-type.htm        
        
    public static function getMimeType($ext) 
    {        
        switch ($ext) {
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'png': return 'image/png';
            case 'gif': return 'image/gif';
            case 'zip': return 'application/zip';
            case 'rar': return 'application/x-rar-compressed';
            case 'exe': return 'application/octet-stream';
            case 'jar': return 'application/x-jar';
            case 'pdf': return 'application/pdf';
            case 'doc': return 'application/msword';
            case 'xls':
            case 'xlsx' : return 'application/vnd.ms-excel';
            case 'txt': return 'text/plain';
            default : return false;
        }
    }
    
    /**
     * Convert binary ip-address to string. Support IPv6 and IPv4 format
     * @param string $binIP
     * @return string Return string with full format for IPv6 and IPv4
     */
    
    public static function ipBinToStr($binIP)
    {
        $len = strlen($binIP);
        $ipv4 = ($len == 4) ? true : false;
        if ($len <= 0 and $len != 4 and $len != 16)
            return '0.0.0.0';
        $prIP = '';
        $split = ($ipv4) ? 1 : 2;

        for ($i = 0; $i < $len; $i++) {

            $prIP .= ($ipv4) ? ord($binIP[$i]) : bin2hex($binIP[$i]);
            $split--;
            if (!$split and $i < $len - 1) {

                $split = ($ipv4) ? 1 : 2;
                $prIP .= ($ipv4) ? '.' : ':';
            }
        }

        return $prIP;
    }

    /**
     * Convert Ip-address string to binary. Support IPv6 and IPv4 format
     * 
     * @param string $ip Ipv6 or Ipv4 formated string<br> Notice: <i>IPv6 string, must be full format of ip-address. <br>
     * See specification here http://www.zytrax.com/tech/protocols/ipv6.html</i>
     * @param bool $falseOnFail return <b>false</b> on fail
     * @return real|boolean Return IPv6 (16 bytes) or IPv4 (4 bytes) converted to binary string. 
     * On fail return empty (4 bites) binary data (same as <b>0.0.0.0</b>), or <b>false</b>
     */
    
    public static function ipStrToBin($ip, $falseOnFail = false)
    {
        if (strpos($ip, ':') !== false) {

            $ip = str_split(str_replace(':', '', $ip), 2);
            foreach ($ip as $key => $value)
                $ip[$key] = chr(hexdec($value));

            return implode('', $ip);
        } elseif (strpos($ip, '.') !== false) {

            $ip = explode('.', $ip);

            if (sizeof($ip) != 4) 
                return $falseOnFail ? false : 0x00000000;

            return chr($ip[0]) . chr($ip[1]) . chr($ip[2]) . chr($ip[3]);
        } else
            return $falseOnFail ? false : 0x00000000;
    }

    /**
     * Get client ip string
     * @param bool $bin return as binary data (default false)
     * @return string
     */
    
    public static function getClientIP($bin = false)
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];

        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            $tmp_xfor = trim($_SERVER['HTTP_X_FORWARDED_FOR']); // get ip from this places not pretty safe
            $tmp_xfor = explode(",", $tmp_xfor);

            $ip = $tmp_xfor[sizeof($tmp_xfor) - 1];
        } else
            $ip = $_SERVER['REMOTE_ADDR'];

        return ($bin) ? self::ipStrToBin($ip) : $ip;
    }

    public static function isFileRecieved($postName, $checkExt = false)
    {
        if (empty($_FILES[$postName]['tmp_name']) or
                $_FILES[$postName]['error'] != UPLOAD_ERR_OK or
                !is_uploaded_file($_FILES[$postName]['tmp_name']))
            return false;

        $extension = strtolower(substr($_FILES[$postName]['name'], 1 + strrpos($_FILES[$postName]['name'], ".")));

        if (is_array($checkExt) and !in_array($extension, $checkExt))
            return false;

        return true;
    }
    
    /**
     *
     * @param string $folder
     * @param string $pre
     * @param string $ext
     * @return string return unique name of file for $folder dir
     */
    
    public static function uniName($folder, $pre = '', $ext = 'tmp')
    {
        $name = $pre . time() . '_';
        
        for ($i = 0; $i < 8; $i++)
            $name .= chr(rand(97, 121));
        
        $name = $name . '.' . $ext;
        return (file_exists($folder . $name)) ? self::uniName($folder, $pre, $ext) : $name;
    }
    
    /**
     * Upload file to directory $tmpDir
     * @param string $postName
     * @param string $tmpDir temporary directory (auto create dir if unexist), by default used 'tmp' from Enviroment ways
     * @param string $prefix prefix for temporary file name
     * @param string $ext extension of temporary file
     * @return array|boolean on success return array with info about file (name, path, upload_name, size_mb, size_bytes) or <b>false</b> on fail
     */

    public static function fileSafeMove($postName, $tmpDir = false, $prefix = 'tmp', $ext = 'tmp')
    {
        if (!self::isFileRecieved($postName))
            return false;

        if (!$tmpDir)
             $tmpDir = Main::getShortcut('tmp');

        if (!self::getDir($tmpDir)) {
            self::log('Failed to create dir ' . $tmpDir);
            return false;
        }
        
        $tmpFileName = self::uniName($tmpDir, $prefix, $ext);
        $tmpFile = $tmpDir . $tmpFileName;
    
        if (!move_uploaded_file($_FILES[$postName]['tmp_name'], $tmpFile)) {
            
            if ($tmpFile) unlink($tmpFile);
            
            self::log('[fileSafeMove] --> "' . $tmpFile . '" <-- write fail');
            return false;
        }
        
        chmod($tmpFile, 0664);
        
        return array(
            'name' => $tmpFileName,  
            'path' => $tmpFile,
            'upload_name' => Filter::str($_FILES[$postName]['name'], 'stringLow'), 
            'size_mb' => round((int) $_FILES[$postName]['size'] / 1024 / 1024, 2),
            'size_bytes' => filesize($tmpFile),
       );
    }

    public static function randString($len = 50)
    {
        $allchars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $string = "";

        mt_srand((double) microtime() * 1000000);

        for ($i = 0; $i < $len; $i++)
            $string .= $allchars{ mt_rand(0, strlen($allchars) - 1) };

        return $string;
    }

	public static function generateIdWord($s) 
    {
        $s = strval($s);
        if (is_numeric($s)) {
            $s .= 'n';
        }
        
        $s = mb_strtolower($s);
        
		$r = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м', 'н','о','п','р','с','т','у','ф','х','ц','ч', 'ш', 'щ', 'ъ','ы','ь','э', 'ю', 'я',' ', '-');
		$l = array('a','b','v','g','d','e','e','g','z','i','y','k','l','m','n', 'o','p','r','s','t','u','f','h','c','ch','sh','sh','', 'y','y', 'e','yu','ya','_', '_');
		$s = str_replace( $r, $l, $s);
		$s = preg_replace("/[^\w\_]/","$1", $s);
		$s = preg_replace("/\_{2,}/", '_', $s);        
		$s = trim($s, '_'); 
        
        return $s;       
    }
    
    public static function log($string, $line = '', $route = false)
    {
        if (!defined('KELLY_LOG'))
            return;
        
        $logFile = self::getDir(Main::getShortcut('tmp')) . 'DBG_LOG.txt';
        $exist = file_exists($logFile);
        
        if ($exist and round(filesize($logFile) / 1048576) >= 50) {
            $exist = false;
            unlink($logFile);
        }
        
        if (!$fp = fopen($logFile, 'a'))
            exit('--> ' . $logFile . ' <-- write fail');
        
        if ($route) $string .= ' | Route : ' . Router::getRoute()->build();
        
        fwrite($fp, date("H:i:s d-m-Y") . ' < line : [' . $line . '] ' . $string . PHP_EOL);
        fclose($fp);
        
        if (!$exist) chmod($logFile, 0664);
    }
    
    /**
     * Create dir if unexist and return path on success
     * @param string $dir path to directory
     * @return string|boolean
     */
    
    public static function getDir($dir) 
    {
        $result = true;
        if (!is_dir($dir)) {
           $back = umask(0);
           $result = mkdir($dir, 0775, true);
           umask($back);
        }
        
        return $result ? $dir : false;
    }
    
    public static function deleteDir($dirPath, $removeDir = true)
    {
        if (!is_dir($dirPath))
            return;
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file))
                self::deleteDir($file);
            else
                unlink($file);
        }
        
        if ($removeDir and is_dir($dirPath)) rmdir($dirPath);
    }
}
