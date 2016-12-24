<?php

// todo check referer

// supports gzip / chuncked data
// add log function
// replaceable browser info

class Browser 
{    
    public $blocking = false; // проверки на чтение нет
    public $readMaxSize = false; 
    
    public $timeout = 60; // connect timeout sec
    // read / write timeout setted to 5 sec
    
    public $host;
    public $server;
    public $target;
    
    public $currentPage = false;
    public $referer;
    
    public $url = false;

    public $post = array();
    public $get = array();
    public $additionHeaders = '';
    
    public $port = 80;
    
    private $lastData = '';
    
    public $cookies = array();
	
    
    public $closeAfterRead = false;
    private $connection = false;
    
    public $log = '';
	
    public $readTimeLimit = -1;
    public $readStartTime = -1;
    
    public $lastDownload = array();
    
	// headers info
	
	public $lastHeaders = array(
		'Set-Cookie' => false,
		'Location' => false,
		'Content-Encoding' => false,
		'Content-Length' => false,
		'Transfer-Encoding' => false,
		'Code' => false,
	);
	
	public $lastHeadersStr = '';
	
	//public $transfer = false;
	//public $contentEncoding = false;
	//public $bodyLength = false;
    
    public function __construct() 
    {
        
    }
    
    public function clearData()
    {
        $this->cookies = array();
        $this->post = array();
        $this->get = array();   
    }
	
    public function setReadMaxSize($size) {
        $this->readMaxSize = (int) $size;
    }
    
	// taken from http://php.net/gzdecode#82930
	public function gzdecode($data, &$error='', &$filename='', $maxlength=null)
	{
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
			$error = "Not in GZIP format.";
			return null;  // Not GZIP format (See RFC 1952)
		}
		$method = ord(substr($data,2,1));  // Compression method
		$flags  = ord(substr($data,3,1));  // Flags
		if ($flags & 31 != $flags) {
			$error = "Reserved bits not allowed.";
			return null;
		}
		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack("V", substr($data,4,4));
		$mtime = $mtime[1];
		$xfl   = substr($data,8,1);
		$os    = substr($data,8,1);
		$headerlen = 10;
		$extralen  = 0;
		$extra     = "";
		if ($flags & 4) {
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8) {
				return false;  // invalid
			}
			$extralen = unpack("v",substr($data,8,2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8) {
				return false;  // invalid
			}
			$extra = substr($data,10,$extralen);
			$headerlen += 2 + $extralen;
		}
		$filenamelen = 0;
		$filename = "";
		if ($flags & 8) {
			// C-style string
			if ($len - $headerlen - 1 < 8) {
				return false; // invalid
			}
			$filenamelen = strpos(substr($data,$headerlen),chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
				return false; // invalid
			}
			$filename = substr($data,$headerlen,$filenamelen);
			$headerlen += $filenamelen + 1;
		}
		$commentlen = 0;
		$comment = "";
		if ($flags & 16) {
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8) {
				return false;    // invalid
			}
			$commentlen = strpos(substr($data,$headerlen),chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
				return false;    // Invalid header format
			}
			$comment = substr($data,$headerlen,$commentlen);
			$headerlen += $commentlen + 1;
		}
		$headercrc = "";
		if ($flags & 2) {
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8) {
				return false;    // invalid
			}
			$calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
			$headercrc = unpack("v", substr($data,$headerlen,2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc) {
				$error = "Header checksum failed.";
				return false;    // Bad header CRC
			}
			$headerlen += 2;
		}
		// GZIP FOOTER
		$datacrc = unpack("V",substr($data,-8,4));
		$datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
		$isize = unpack("V",substr($data,-4));
		$isize = $isize[1];
		// decompression:
		$bodylen = $len-$headerlen-8;
		if ($bodylen < 1) {
			// IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data,$headerlen,$bodylen);
		$data = "";
		if ($bodylen > 0) {
			switch ($method) {
			case 8:
				// Currently the only supported compression method:
				$data = gzinflate($body,$maxlength);
				break;
			default:
				$error = "Unknown compression method.";
				return false;
			}
		}  // zero-byte body content is allowed
		// Verifiy CRC32
		$crc   = sprintf("%u",crc32($data));
		$crcOK = $crc == $datacrc;
		$lenOK = $isize == strlen($data);
		if (!$lenOK || !$crcOK) {
			$error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
			return false;
		}
		return $data;
	}
	
	public function validateUrl($url) 
	{
        $url = trim($url);
        //$url = strtolower($url);
        
        if (!$url) return false;
        
        $url = parse_url($url);
        if (!$url) return false;
        
        if (empty($url['scheme']) or ($url['scheme'] != 'http' and $url['scheme'] != 'https')) {
            
            $url['scheme'] = 'http';
        }
        
        if ($url['scheme'] == 'https') {            
            $url['port'] = 443;
        } else {
			$url['port'] = 80;
		}
        
		$url['get'] = array();
		
        if (!empty($url['query'])) {
            
            $get = explode('&', $url['query']);
            
            foreach($get as $params) {
                if (!$params) continue;
                
                $param = explode('=', $params);
                if (sizeof($param) != 2) continue;
                $param[0] = trim($param[0]);
				
                $url['get'][$param[0]] = trim($param[1]);
                $param = null;                
            }  
        }
        
        if (empty($url['host'])) {
            
            $param = explode('/', $url['path']);
            $url['host'] = $param[0];
            
            unset($param[0]);
            
            $url['path'] = '/' . implode("/", $param);
        }
        
        if (empty($url['path'])) $url['path'] = '/';

        $url['link'] = $url['scheme'] . '://' . $url['host'];
        return $url;		
	}
	
    public function isTimeout() {
        if ($this->connection) {
            $info = stream_get_meta_data($this->connection); 
            if ($info['timed_out']) return true;
            else return false;
        } else return true;
    }
    
    public function isConnected() {  

        //if ($this->readTimeLimit and $this->readStartTime == -1) {
        //    $this->readStartTime = time();
        //}
        //
        //if ($this->readTimeLimit) {
        //    if (time() - $this->readStartTime > $this->readTimeLimit) {
        //        $this->readTimeLimit = -1;
        //        $this->readStartTime = -1;
        //        $this->log .= 'Time limit reached';
        //        return false;
        //    }
        //}
    
        if ($this->connection and !feof($this->connection) and !$this->isTimeout())  return true;
        else return false;
    }
    
    /* return false if url incorrect */
    
    public function setUrl($url) 
    {	
		if (is_array($url)) {
			$this->url = $url;		
		} else {   
			$this->url = $this->validateUrl($url);
		}
		
		if (!$this->url) return false;
        
        //if ($encodeUrl) {
        //    $this->url['path'] = urlencode($this->url['path']);
        //}	
        
		$this->port = $this->url['port'];
		$this->get = $this->url['get']; 
        $this->host = $this->url['host'];
        $this->server = $this->host;
        $this->target =  $this->url['path'];
        
        if ($this->currentPage) $this->referer = $this->currentPage;
        else $this->referer = $this->url['link'];
        
        $this->currentPage = $this->url['link']; 
    }
 
    public function getRequest() 
    {   
        if (!$this->url) return false;
        
        $method = "GET"; 
        $getValues = '';
        $postValues = '';
        
        if ( $this->get and sizeof($this->get) > 0) { 
            $getValues = '?'; 
            foreach( $this->get as $name => $value )
            { 
                
                $getValues .= urlencode( $name ) . "=" . urlencode( $value ) . '&'; 
            } 
            
            $getValues = substr($getValues, 0, -1); 
            
        } 

        if ( $this->post and sizeof($this->post) > 0 ) 
        { 
            foreach( $this->post as $name => $value )
            { 
                if (is_array($value)) {
                    foreach($value as $arrayKey => $subValue ){
                        $postValues .= urlencode($name  . '[' . $arrayKey . ']') . '=' . urlencode($subValue) . '&';
                    }
                } else {                
                    $postValues .= urlencode( $name ) . "=" . urlencode( $value ) . '&'; 
                }
            } 
            
            $postValues = substr( $postValues, 0, -1 ); 
            $method = "POST";             
        }        

        $request  = "$method " . $this->target . "$getValues HTTP/1.1\r\n"; 
        $request .= "Host: " . $this->host . "\r\n"; 
        
		if (!empty($this->url['user']) and !empty($this->url['pass'])) {
			$request .= "Authorization: basic ".base64_encode($this->url['user'] . ":" . $this->url['pass']) . "\r\n";
		}        
        
        $request .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0\r\n";  // todo generate on new session
        $request .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"; 
        $request .= "Accept-Language: en-us, en;q=0.50\r\n"; 
        $request .= "Accept-Encoding: gzip, deflate\r\n"; 
        $request .= "Accept-Charset: ISO-8859-1, utf-8;q=0.66, *;q=0.66\r\n";   


        
        if(sizeof($this->cookies) > 0) {
                        
           $request .= "Cookie: "; 
           $cookies = '';
           foreach ($this->cookies as $key => $value) {
               if (!$cookies) $cookies .= $key . '=' . $value;
               else $cookies .= '; ' . $key . '=' . $value;
           }       
           
           $request .= $cookies . "\r\n"; 
        }
        
        if (!$this->closeAfterRead) {
            $request .= "Connection: keep-alive\r\n";
        } else {
            $request .= "Connection: close\r\n"; 
        }
        $request .= "Referer: " . $this->referer . "\r\n"; 
        $request .= "Cache-Control: max-age=0\r\n"; // Cookie: yandexuid=2006058111469974985; yp=1472566985.ygu.1; yandex_gid=76; _ym_uid=1470142322395036015; _ym_isad=2
        
        if ($this->additionHeaders) $request .= $this->additionHeaders;  
        
        if ($method == "POST" ) { 
            $lenght = strlen( $postValues ); 
            $request .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
            $request .= "Content-Length: $lenght\r\n"; 
            $request .= "\r\n"; 
            $request .= $postValues; 
        } else {
            $request .= "\r\n"; 
        }
        
        return "\r\n" . $request;
    }
    
    private function addCookie() 
    {
        if (strpos($this->lastData, 'Set-Cookie') === false) return;
        
        $cookie = explode(':', $this->lastData, 2);
        if (sizeof($cookie) < 2) return;
        
        unset($cookie[0]);
        $cookie = implode('', $cookie);
        $cookie = explode(';', $cookie);
        if (sizeof($cookie) < 1) return;
        
        // get coockie key = value
        $cookie = explode('=', $cookie[0]);
        if (sizeof($cookie) != 2) return;
        
        $cookie[0] = trim($cookie[0]);
        $cookie[1] = trim($cookie[1]);
        
        $this->cookies[$cookie[0]] = $cookie[1];
        // yandex_gid=76; Expires=Thu, 01-Sep-2016 12:50:33 GMT; Domain=.ya.ru; Path=/
    }


    private function getBodyLength() 
    {
        if (is_string($this->lastData) and strpos($this->lastData, 'Content-Length') !== false) {
            $bodyLength = explode(':', $this->lastData);
            if (sizeof($bodyLength) == 2) {
                return (int) trim($bodyLength[1]);
            } else return false;
        }
        
        return false;       
    }
	
	private function getAnswerCode() 
	{
        if (is_string($this->lastData) and strpos($this->lastData, 'HTTP/1') !== false) {
            $code = explode(' ', $this->lastData);
            if (sizeof($code) >= 2) {
                return (int) trim($code[1]);
            } else return false;
        }		
	}
	
	private function getLocation() 
	{
        if (is_string($this->lastData) and strpos($this->lastData, 'Location') !== false) {
            $url = explode(':', $this->lastData, 2);
            if (sizeof($url) >= 2) {
                return trim($url[1]);
            } else return false;
        } else return false;		
	}  
	
	private function getContentEncoding() 
	{
        if (is_string($this->lastData) and strpos($this->lastData, 'Content-Encoding') !== false) {
            $encoding = explode(':', $this->lastData, 2);
            if (sizeof($encoding) >= 2) {
                return trim($encoding[1]);
            } else return false;
        } else return false;		
	}  	
	
	public function connect()
	{
		if ($this->connection !== false) $this->close();
		
        if (!$this->url) return false;
        
        $protocol = $this->url['scheme'] == 'https' ? 'ssl://' : 'tcp://';

        $this->connection  = @fsockopen( $protocol . $this->server, $this->port, $errno, $errstr, $this->timeout ); 
        if (!$this->connection) return false;
        
        socket_set_blocking($this->connection, $this->blocking);  
        socket_set_timeout($this->connection, 5); // read / write timeout	
		
		return true;
	}
	
	public function close() {
		if (!$this->connection) return;

        fclose($this->connection);
        $this->connection = false;		
	}
    
    public function getContentType($ext) 
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
			case 'bmp': return 'image/bmp';
			case 'tiff': return 'image/tiff';
            default : return false;
        }
    }
    
    private function uniName($folder, $pre = '', $ext = 'tmp')
    {
        $name = $pre . time() . '_';
        
        for ($i = 0; $i < 8; $i++)
            $name .= chr(rand(97, 121));
        
        $name = $name . '.' . $ext;
        return (file_exists($folder . $name)) ? $this->uniName($folder, $pre, $ext) : $name;
    }
    
    // return false on connect fail
    // return array of result data for all files
    // dont forget close connection after
    
    public function downloadFiles($urlList, $saveTo, $ignoreContentMis = false, $limitTime = 1)
    {
        if (!sizeof($urlList)) return array();
        
        $result = true;
        if (!is_dir($saveTo)) {
        
           $back = umask(0);
           $result = mkdir($saveTo, 0775, true);
           umask($back);
           
           $this->log .= ' | cant init folder ' . $saveTo;
           if (!$result) return false;
           
        }
        
        $cleaner = array();
        $this->lastDownload = array();
        
        foreach ($urlList as $url) 
        {
            $key = sizeof($this->lastDownload);
            $this->lastDownload[$key] = false;
            
            $ext = strtolower(substr($url, 1 + strrpos($url, ".")));
            
            if (!$this->getContentType($ext))  {
                $this->lastDownload[$key] = array('error' => 1, 'message' => 'unknown mime type for item ' . $url . ' ext ' . $ext);
                continue;
            }
            
            $this->setUrl($url);
            
            if (!$this->isConnected()) {
                 $this->connect();    
            }  
            
            $this->readTimeLimit = $limitTime;
            
            $result = $this->sendRequest();   
            if (!$result) {
                $this->log .= ' | downloadFiles fail to send request : url : ' . $url;
                foreach($cleaner as $file) {
                    unlink($file);
                }
                return false;
            }
            
            $bresult = $this->readData();
            
            if (!$ignoreContentMis and trim($this->lastHeaders['Content-Type']) != trim($this->getContentType($ext))) {
            
                $output = $this->lastHeaders['Content-Type'];
                if (!$output) $output = '';
                
                $code = $this->lastHeaders['Code'] == 503 ? 503 : 3;
                $this->lastDownload[$key] = array('error' => $code, 'message' => 'Content type mismatch output : ' . $output . ' expect : ' . $this->getContentType($ext));
    
                Tool::log($this->lastHeaders['Code'] . ' server code | ' . $code . ' | '. $this->lastDownload[$key]['message']);
                continue;
            }
            
            $saveFile = $saveTo . $this->uniName($saveTo, 'get', $ext);
            
            if (file_put_contents($saveFile, $bresult['body'])) {
                $this->lastDownload[$key] = array('error' => 0, 'message' => $saveFile);
                $cleaner[] = $saveFile;
            } else {
                $this->lastDownload[$key] = array('error' => 2, 'message' => 'Fail save to file ' . $saveFile);
            }
        }
        
        return $this->lastDownload;
    }
	
    /* send request to server . Clear post data after successfull send request */
    
	public function sendRequest($clearPost = false, $clearGet = false) 
	{        
		if (!$this->connection) return false;
		
        $request = $this->getRequest();        
        if (!$request) return false;
        
        if (fwrite($this->connection, $request) === false) {
			return false;
		} else {
            if ($clearPost) {
                $this->post = array();
            }
            if ($clearGet) {
                $this->get = array();
            }
            return true;
        }
	}
	
	private function readBodyWithLength($bodyLength)
	{
		if (!$this->connection) return false;
        
		$body = '';
        while ($this->isConnected()) 
        {    
			$this->lastData = fgets( $this->connection, $bodyLength ); // todo how to timeout
            
			if ($this->lastData === false) continue;
            
            if (!$body and $this->lastData === "\r\n") break; // empty body
            // body not containt \r\n while fgets only end \r\n possible
            
			$body .= $this->lastData;
            
			if (strlen($body) >= $bodyLength) break;	

            if ($this->readMaxSize and strlen($body) > $this->readMaxSize) {
                $this->log .= ' | readBodyWithLength max size limit reached';
                return false;
            }
		}	
        
        // var_dump(strlen($body));
        // var_dump($bodyLength);	
        
		if ($this->lastHeaders['Content-Encoding'] == 'gzip') {
            $error = '';
			$body = $this->gzdecode($body, $error);
            
            if ($body == false) { 
                $this->log .= '| gzdecode fail [body with length] : ' . $error . ' | ';
            }
		}
		
		return $body;		
	}
	
	private function hexToStr($hex)
    {
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
	}
	
	private function readBodyChunked()
	{
		if (!$this->connection) return false;
		$chunks = "";
		
        while ($this->isConnected()) 
        {    
			$this->lastData = fgets($this->connection);
			
			if ($this->lastData === false) continue;
			if ($this->lastData === "\r\n") break; // after end chunk or empty body
			
			$chunks .= $this->lastData;	
            if ($this->readMaxSize and strlen($chunks) > $this->readMaxSize) {
                $this->log .= ' | readBodyChunked read max size limit reached';
                return false;
            }            
		}
		
		for ($res = ''; !empty($chunks); $chunks = trim($chunks)) 
		{
			$pos = strpos($chunks, "\r\n");
			$len = hexdec(substr($chunks, 0, $pos));
			$res .= substr($chunks, $pos + 2, $len);
			$chunks = substr($chunks, $pos + 2 + $len);
		}
		
		if (sizeof($res)) {
						
			if ($this->lastHeaders['Content-Encoding'] == 'gzip') {
                $error = '';
				$chunks = $this->gzdecode($res, $error);
                            
                if ($chunks == false) {
                    $this->log .= '| gzdecode fail [body with chunks] : ' . $error . ' | ';
                }
			}
			
		} else {
			
			$chunks = false;
		}
		
		return $chunks;		
	}
	
	public function readHeaders() 
	{
		if (!$this->connection) return -1;
		
		$this->lastHeaders = array(
			'Set-Cookie' => false,
			'Location' => false,
			'Content-Encoding' => false,
			'Content-Length' => false,
			'Transfer-Encoding' => false,
			'Code' => false,
            'Content-Type' => false,
		);
	
		$headers = '';
		$this->lastHeadersStr = '';
        
        while ($this->isConnected()) // read headers
        {   
			$this->lastData = fgets( $this->connection ); 
			if ($this->lastData === false) continue;
			if (strpos($this->lastData, 'Set-Cookie') !== false) {
				
				$this->addCookie(); 
				$this->lastHeaders['Set-Cookie'] = $this->cookies;
				
			} elseif (strpos($this->lastData, 'Location') !== false) {
				
				$this->lastHeaders['Location'] = $this->getLocation();
				if ($this->lastHeaders['Location'] === false) return 3;
				
			} elseif (strpos($this->lastData, 'Content-Type') !== false) {
            
                $type = explode(':', $this->lastData, 2);
                
				if (sizeof($type) >= 2) {
                    $this->lastHeaders['Content-Type'] = $type[1];
				}
				
			} elseif (strpos($this->lastData, 'Content-Encoding') !== false) {
				
				$this->lastHeaders['Content-Encoding'] = $this->getContentEncoding();
				if ($this->lastHeaders['Content-Encoding'] === false) return 2;					
				
			} elseif (strpos($this->lastData, 'Content-Length') !== false) {
				
				$this->lastHeaders['Content-Length'] = $this->getBodyLength();
				if ($this->lastHeaders['Content-Length'] === false) return 4;					
				
			} elseif (strpos($this->lastData, 'Transfer-Encoding') !== false) {
				
				$encoding = explode(':', $this->lastData, 2);
				if (sizeof($encoding) >= 2) {
					$this->lastHeaders['Transfer-Encoding'] = trim($encoding[1]);
				} 					
				
			} elseif (strpos($this->lastData, 'HTTP/1') !== false) {
				
				$this->lastHeaders['Code'] = $this->getAnswerCode();
				if ($this->lastHeaders['Code'] === false) return 1;
				
			} elseif ($this->lastData === "\r\n") { // empty string - end of headers, read message
				break;
			} // elseif ($this->isEndOfAnswer()) break;
            
            if (strpos($this->lastData, ':') !== false or strpos($this->lastData, 'HTTP') !== false) 
			$headers .= $this->lastData;   
        } 

		$this->lastHeadersStr = $headers;
        if (!$this->lastHeadersStr) return 8;
        
		return true;
	}
	
    public function readData()
    {      
        if (!$this->connection) {
            $this->log .= 'connection closed | ';
            return -1;
        }
		
        $bodyLength = 0;
        $bodyRead = false;
		
        $body = '';    
		
        $rHeaders = $this->readHeaders();
		if (is_int($rHeaders)) {
            $this->log .= 'bad headers  ' . $rHeaders . ' | ';
            $this->close();
            return $rHeaders;
        }

		$redirect = false;
		$newCookie = false;

		if ($this->lastHeaders['Code'] != '302') {
            if ($this->lastHeaders['Transfer-Encoding'] === 'chunked') {
                $body = $this->readBodyChunked();
            } elseif ($this->lastHeaders['Content-Length']) {
                $body = $this->readBodyWithLength($this->lastHeaders['Content-Length']);
            }
		}
        
        return array(
			'header' => $this->lastHeadersStr, 
			'body' => $body, 
			'cookie' => $this->cookies, 
			'new-cookie' => ($this->lastHeaders['Set-Cookie']) ? true : false, 
			'code' => $this->lastHeaders['Code'], 
			'location' => $this->lastHeaders['Location']
		);
    }
}
