<?php

class KellyGDHelper
{
    public static function reduseSize($img, $limit = 2024, $alpha = true) 
    {
        $wImg = imagesx($img); 
        $hImg = imagesy($img);
        
        if($wImg <= $limit and $hImg <= $limit) return $img;
        if ($wImg > $hImg) $by = 'width';
        else $by = 'height';
        
        if ($by == 'width') {	
			$dW = $limit;
			$k = $wImg / $limit;
			$dH = ceil($hImg / $k);	
		} else {
			$dH = $limit;
			$k = $hImg / $limit;
			$dW = ceil($wImg / $k);
		}
              
        $preview = imagecreatetruecolor($dW, $dH); 
        
        if ($alpha) {
            imagesavealpha($img, true);
            imagealphablending($img, false);    
        }
        $transparent = imagecolorallocatealpha($preview, 255, 255, 255, 127);

        imagefill($preview, 0, 0, $transparent);
   
        imagecopyresampled($preview, $img, 0, 0, 0, 0, $dW, $dH, $wImg, $hImg); 
        imagedestroy($img);
        
        return $preview;
    }
    
    public static function cutQuad($img, $w) 
    {
        $preview = imagecreatetruecolor($w, $w); 
        $transparent = imagecolorallocatealpha($preview, 255, 255, 255, 127);
        imagefill($preview, 0, 0, $transparent);
        
        $wImg = imagesx($img); 
        $hImg = imagesy($img);

        // cut quad center of main image if its horizontal
        
        if ($wImg > $hImg) 
        imagecopyresampled($preview, $img, 0, 0,
                         round((max($wImg, $hImg)-min($wImg, $hImg))/2),
                         0, $w, $w, min($wImg, $hImg), min($wImg, $hImg)); 

        // cut quad top by y if main image is vertical
        
        if ($wImg < $hImg) 
        imagecopyresampled($preview, $img, 0, 0, 0, 0, $w, $w,
                         min($wImg, $hImg), min($wImg, $hImg)); 

        // image already quad - resize it only
        if ($wImg == $hImg) 
        imagecopyresampled($preview, $img, 0, 0, 0, 0, $w, $w, $wImg, $wImg); 

        return $preview;
    }
    
    /**
     * same as imagecopyresampled but with opacity for $src_im 
     * @param resource $dst_im
     * @param resource $src_im
     * @param int $dst_x
     * @param int $dst_y
     * @param int $src_x
     * @param int $src_y
     * @param int $dst_w
     * @param int $dst_h
     * @param int $src_w
     * @param int $src_h
     * @param int $pct уровень прозрачности при наложении 0 .. 100
     * @return boolean
     */
    
    public static function imagecopymergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $pct) 
    {
        if (!isset($pct)) {
            return false;
        }
        $pct /= 100;
        $w = imagesx($src_im);
        $h = imagesy($src_im);
        imagealphablending($src_im, false);
        $minalpha = 127;
        for ($x = 0; $x < $w; $x++)
            for ($y = 0; $y < $h; $y++) {
                $alpha = ( imagecolorat($src_im, $x, $y) >> 24 ) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $colorxy = imagecolorat($src_im, $x, $y);
                $alpha = ( $colorxy >> 24 ) & 0xFF;
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $pct * ( $alpha - 127 ) / ( 127 - $minalpha );
                } else {
                    $alpha += 127 * $pct;
                }
                $alphacolorxy = imagecolorallocatealpha($src_im, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha
                );
                if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }
        
        return imagecopyresampled($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    }
}
