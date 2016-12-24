<?php

// todo test with small images

class KellyGDColorPalete
{    
    public $image = false;
    public $imageFile = false;
    
    public $simplify = 25.5; // round founded color to 255 / $simplify , so the maximum color pallet of image may be 255 / $s * 255 / $s * 255 / $s 
    public $chunks = 32; // divide image on chunks and take pixel from each one
    
    public $centerPriority = true; // main colors in center, turn on centerM multipiller for colors founded in center of image
    public $centerM = 4;
    
    public $log = '';
    
    private $palete = array();
    private $readed = false;
	
	private $imageInfo = false;
    
    public function __construct($imageFile = false) 
    {       
        $this->setImage($imageFile);
    }
    
    public function clear() 
    {        
        if ($this->image and get_resource_type($this->image) == 'gd') imagedestroy($this->image);
        
        $this->image = false;
		$this->imageInfo = false;
		$this->palete = array();
		$this->readed = false;
        return true;
    }

    public function setImage($imageFile)
    {
        if (!$imageFile) return false;
       
        $this->readed = false;
		$this->imageInfo = false;
        
        if (sizeof($this->palete) > 0) unset($this->palete);
        $this->palete = array();
        
        if (!is_string($imageFile) and get_resource_type($imageFile) == 'gd') {
            
            $this->image = $imageFile;                       
        } else {     

            $this->imageFile = $imageFile;
        }
        
        return true;
    }
    
    private function loadImage() 
    {
        if ($this->image) return true;
        
        if (!$this->imageFile) {
            $this->log('Please select a file');
            return false;           
        }
        
        if (!is_file($this->imageFile) || !is_readable($this->imageFile)) {
            $this->log('Cannot read image file ' . $this->imageFile . ' - unexist or unreadable');
            return false;
        } 

        $size = getimagesize($this->imageFile);
        if ($size === false) {
            $this->log('Cannot read image file ' . $this->imageFile . ' - bad data');
            return false;
        }

        switch ($size[2]) {
            case 2: $img = imagecreatefromjpeg($this->imageFile);
                break;
            case 3: $img = imagecreatefrompng($this->imageFile);
					//imagealphablending($img, true);
					//imagesavealpha($img, true);
                break;
            case 1: $img = imagecreatefromgif($this->imageFile);
                break;
            default : return false;
        }
		
		$this->imageInfo = $size;
		
        if (!$img) {
            $this->log('Cannot read image file ' . $this->imageFile . ' - imagecreate function return false');
            return false;
        }
        
        if ($size[2] == 3) imagesavealpha($img, true);
        imagealphablending($img, true);
        
        $this->image = $img;       
        return true;        
    }
    
    // hsl converters https://www.sitepoint.com/community/t/converting-for-rgb-to-hsl-color-space-and-back-again/4474
    // todo remove this
    
   function rgb2hsl($rgb){
        $clrR = ($rgb[0]);
        $clrG = ($rgb[1]);
        $clrB = ($rgb[2]);
         
        $clrMin = min($clrR, $clrG, $clrB);
        $clrMax = max($clrR, $clrG, $clrB);
        $deltaMax = $clrMax - $clrMin;
         
        $L = ($clrMax + $clrMin) / 510;
         
        if (0 == $deltaMax){
            $H = 0;
            $S = 0;
        }
        else{
            if (0.5 > $L){
                $S = $deltaMax / ($clrMax + $clrMin);
            }
            else{
                $S = $deltaMax / (510 - $clrMax - $clrMin);
            }

            if ($clrMax == $clrR) {
                $H = ($clrG - $clrB) / (6.0 * $deltaMax);
            }
            else if ($clrMax == $clrG) {
                $H = 1/3 + ($clrB - $clrR) / (6.0 * $deltaMax);
            }
            else {
                $H = 2 / 3 + ($clrR - $clrG) / (6.0 * $deltaMax);
            }

            if (0 > $H) $H += 1;
            if (1 < $H) $H -= 1;
        }
        return array($H, $S,$L);
    }
    
    public function hsl2rgb($hsl){
        $h = $hsl[0];
        $s = $hsl[1];
        $l = $hsl[2];
        $r; 
        $g; 
        $b;
        $c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
        $x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
        $m = $l - ( $c / 2 );
        if ( $h < 60 ) {
            $r = $c;
            $g = $x;
            $b = 0;
        } else if ( $h < 120 ) {
            $r = $x;
            $g = $c;
            $b = 0;			
        } else if ( $h < 180 ) {
            $r = 0;
            $g = $c;
            $b = $x;					
        } else if ( $h < 240 ) {
            $r = 0;
            $g = $x;
            $b = $c;
        } else if ( $h < 300 ) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }
        $r = ( $r + $m ) * 255;
        $g = ( $g + $m ) * 255;
        $b = ( $b + $m  ) * 255;
        return array( floor( $r ), floor( $g ), floor( $b ) );
    }
   
    public function hex2rgb($hex) 
    {
        $hex = trim($hex);
        
        if (empty($hex)) return false;
        if ($hex[0] == '#') $hex = substr($hex, 1);
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return array($r, $g, $b);
    }
    
    public function rgb2hex($rgb) 
    {
       $hex = str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

       return (string) $hex;
    }

	public function simplifyRgb($rgb) {
		return array(
			round($rgb[0] / $this->simplify, 0, PHP_ROUND_HALF_DOWN) * $this->simplify,
			round($rgb[1] / $this->simplify, 0, PHP_ROUND_HALF_DOWN) * $this->simplify,
			round($rgb[2] / $this->simplify, 0, PHP_ROUND_HALF_DOWN) * $this->simplify
		);
	}
	
    public function rgb2cmyk($rgb) 
    {
        if (!is_array($rgb) or !sizeof($rgb)) return false;

        $c = (255 - $rgb[0]) / 255.0 * 100;
        $m = (255 - $rgb[1]) / 255.0 * 100;
        $y = (255 - $rgb[2]) / 255.0 * 100;

        $b = min(array($c,$m,$y));
        $c = $c - $b; $m = $m - $b; $y = $y - $b;

        return array($c, $m, $y, $b);
    }

    public function cmyk2rgb($cmyk) {
        if (!is_array($cmyk) or ! sizeof($cmyk))
            return false;
 
        $c = $cmyk[0] / 100;
        $m = $cmyk[1] / 100;
        $y = $cmyk[2] / 100;
        $k = $cmyk[3] / 100;

        $r = 1 - ($c * (1 - $k)) - $k;
        $g = 1 - ($m * (1 - $k)) - $k;
        $b = 1 - ($y * (1 - $k)) - $k;

        $r = round($r * 255);
        $g = round($g * 255);
        $b = round($b * 255);

        return array($r, $g, $b);
    }

    private function mixColors($colors) {
        
        $cmyk = array(0, 0, 0, 0);
        $num = 0;
        
        
        foreach ($colors as $hex) {
            $rgb = false;
            $hex = (string) $hex;
            $rgb = $this->hex2rgb($hex);
            if (!$rgb) continue;
            
            $ccymyk = $this->rgb2cmyk($rgb);
            if (!$ccymyk) continue;
            
            $cmyk[0] += $ccymyk[0];
            $cmyk[1] += $ccymyk[1];
            $cmyk[2] += $ccymyk[2];
            $cmyk[3] += $ccymyk[3];
            
            $num++;
        }
        
        if ($num) {
            $cmyk[0] = $cmyk[0] / $num;
            $cmyk[1] = $cmyk[1] / $num;
            $cmyk[2] = $cmyk[2] / $num;
            $cmyk[3] = $cmyk[3] / $num;
        }        
        return (string) $this->rgb2hex($this->cmyk2rgb($cmyk));
    }
    
    /* get middle color frome color pallete of image */
    
    public function getRoundColor($mixNum = 2) {
        $palete = $this->getPalete();
        if (!$palete or !sizeof($palete)) return false;
        
        $paleteColors = array_keys($palete);
        if (sizeof($paleteColors) < 2) {
            return $paleteColors[0];  
        }
        
        if ($mixNum > sizeof($paleteColors)) $mixNum = sizeof($paleteColors);

        return $this->mixColors(array_slice($paleteColors, $mixNum * -1, null, true));
    }
    
    public function getPalete($cutPalete = false, $limitPalete = false) 
    {
        if ($this->readed) return $this->palete;
        if (!$this->loadImage()) return false;
        
        $baseW = imagesx($this->image);
        $baseH = imagesy($this->image); 
        $step = 1;
        
        $centerX = $baseW / 2;
        $centerY = $baseH / 2;
                      
        $max = $baseH;
        if ($baseW > $baseH) $max = $baseW;
        
        if ($max > $this->chunks * 2) {
            $step += floor($max / $this->chunks);
        }
        
        $ix = 0;
        $cycles = 0;
        while($ix < $baseW) {
            
            $iy = 0;
            
            if ($this->centerPriority) {
                // calc raiting of color by calculating way from center by x and y
                $mX = (($ix > $centerX) ? $ix - $centerX : $centerX - $ix) / $centerX;
                $mX = 1 - $mX;
                
                // multiplay raiting if near to center
                if ($mX > 0.8) $mX = $mX * $this->centerM;
            } else $mX = 1;
            
            while($iy < $baseH) {
                
                if ($this->centerPriority) {
                    // same for y
                    $mY = (($iy > $centerY) ? $iy - $centerY : $centerY - $iy) / $centerY;
                    $mY = 1 - $mY;
                    
                    if ($mY > 0.8) $mY = $mY * $this->centerM;
                } else $mY = 1;
                
                // finall rating of founded color
                $m = ($mY + $mX) / 2;
				
                $color = imagecolorat($this->image, $ix, $iy); 
				$colors = imagecolorsforindex($this->image, $color);
				$rgb  = array(
					$colors['red'],
					$colors['green'],
					$colors['blue'],
					$colors['alpha']
				); 
				
				// echo $ix . ' ' . $iy . ' ' . $rgba[0] . $rgba[1] . $rgba[2] . '<br>';
				
				$rgb = $this->simplifyRgb($rgb);
                $hex = (string) $this->rgb2hex($rgb);
                
                if (empty($this->palete[$hex])) $this->palete[$hex] = $m;
                else $this->palete[$hex] += $m;
                
                if ($limitPalete and sizeof($this->palete) >= $limitPalete) {
                    break 2;
                }
                
                $iy = $iy + $step; 
            }                
            
            $ix += $step;
        }
        
        if (sizeof($this->palete)) {
            asort($this->palete);
        }  
        
         if ($cutPalete and sizeof($this->palete) > $cutPalete) {
            $this->palete = array_slice($this->palete, $cutPalete * -1, null, true);
        }         
        
        $this->readed = true;
        return $this->palete;
    }
    
    private function log($text) 
    {
        $this->log .= ' Notice : ' . $text;
    }
    
    private function isSimilarColor($color, $color2, $diff = 5) 
    {
        if ($color >= $color2 - $diff and $color <= $color2 + $diff) return true;
        else return false;
    }
    
    public function comparePaletes($paletes, $referenceKey = 0, $fineDif = 5)
    {
        if (sizeof($paletes) < 2) return false;
        if (!sizeof($paletes[$referenceKey])) return false;
        
        $reference = array_keys($paletes[$referenceKey]);
        // may be better search similar color on the whole color palete?
        // may be add compare middle color
        
        $matchColorsTotal = 0;
        
        foreach($reference as $key => $color) {
            $rColor = $this->hex2rgb($color);
            $matchColors = 0;
            
            foreach ($paletes as $pKey => $colors) {
                if ($referenceKey == $pKey) continue;
                
                if ($colors === false) continue;
                $colors = array_keys($colors);
                if (empty($colors[$key])) continue;
                
                $compareColor = $this->hex2rgb($colors[$key]);
                // search similar in whole color palete
                /*foreach ($colors as $compareColor) {
                    $compareColor = $this->hex2rgb($compareColor);
                    
                    // echo '<br>' . implode(' ', $rColor) . ' vs ' . implode(' ', $compareColor) . '<br>';
                    
                    $similar = true;
                    for ($i = 0; $i <= 2; $i++) {
                        if (!$this->isSimilarColor($rColor[$i], $compareColor[$i], $fineDif)) {
                            $similar = false;
                        }
                    }
                    
                    if ($similar) {
                        $matchColors++;
                        break;
                    }
                }   */  
                
                // echo '<br>' . implode(' ', $rColor) . ' vs ' . implode(' ', $compareColor) . '<br>';
                
                $similar = true;
                for ($i = 0; $i <= 2; $i++) {
                    if (!$this->isSimilarColor($rColor[$i], $compareColor[$i], $fineDif)) {
                        $similar = false;
                    }
                }
                
                if ($similar) {
                    $matchColors++;
                }
            }
            
            $matchColorsTotal += $matchColors / (sizeof($paletes) - 1);
        }
        
        return $matchColorsTotal / sizeof($reference);        
    }
    
    public function testCYMK() {
        $cmyk = $this->rgb2cmyk($this->hex2rgb('1985e5'));
        $rgb = $this->cmyk2rgb($cmyk);
            

        $html = '<div style="'
               . 'background-color : #' . $this->rgb2hex($rgb) . '; '
               . 'display : inline-block; '
               . 'width : 50px; '
               . 'height : 50px; margin-right : 12px; '
               . 'text-aling : center;"></div>';            

        return $html;          
    }
    
    // todo смешивать с прозрачностью
    
    public function drawRoundColor($mixNum = 2) 
    {        
        $color = $this->getRoundColor($mixNum);        
        if(!$color) return false;

        $html = '<div style="'
               . 'background-color : #' . $color . '; '
               . 'display : inline-block; '
               . 'width : 50px; '
               . 'height : 50px; margin-right : 12px; '
               . 'text-aling : center;"></div>';            

        return $html;       
        
    }
    
    public function drawPalete() 
    {
        if (!$this->readed) {
            if (!$this->getPalete()) return false;
        }
        
        $html = '';
        
        foreach ($this->palete as $hex => $count) {
			// echo $hex . '<br>';
            $html .= '<div style="'
                   . 'background-color : #' . $hex . '; '
                   . 'display : inline-block; '
                   . 'width : 25px; '
                   . 'height : 25px; margin-right : 12px; '
                   . 'text-aling : center;">' . round($count) . '</div>';            
        }
        
        return $html;
    }
}
