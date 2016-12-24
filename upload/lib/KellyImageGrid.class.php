<?php

// todo move to JavaScript

class KellyImageGrid
{
	public $width = 868;
	public $height = 350;
	public $images = array();
	
	public $html = '';
    private $row = array();
    public $template = 'imagegrid_row';
	
    public function __construct($images = false)
    {   
        if (!$images) return;
        
		if (is_array($images) and sizeof($images))
			$this->images = $images;
    }
	
	// require file way if width and height is empty

	private function getImageInfo($key) 
	{
		if (empty($this->images[$key])) return false;
		if (!empty($this->images[$key]['portrait'])) {
            return $this->images[$key];
        }
        
        if (empty($this->images[$key]['width'])) {
            $size = getimagesize($this->images[$key]['file']);
            
            if (!$size) return false;
            $info = array(
                'file' => $this->images[$key]['file'],
                'link' => $this->images[$key]['link'], 
                'width' => $size[0], 
                'height' => $size[1],
                // color - default bg color for loading process
            );
		} else {
            $info = $this->images[$key];
        }
        
		if ($info['width'] < $info['height']) $info['portrait'] = true; 
		else $info['portrait'] = false; 
		
		$this->images[$key] = $info;
		return $this->images[$key];
	}

	private function getResizedInfo($v, $info, $resizeBy = 'width') 
	{		    
		if ($resizeBy == 'width') {
			$k = $info[$resizeBy] / $v;
			$info['height'] = ceil($info['height'] / $k);
		} else {
			$k = $info[$resizeBy] / $v;
			$info['width'] = ceil($info['width'] / $k);
		}
		
		$info[$resizeBy] = $v;
		return $info;
	}
    
	// todo create thumbnails
	
	private function showRow() 
	{	
		if (!sizeof($this->row)) return;
		$minWidth = 0;
		
		foreach($this->row as $image) {
			if ($image['width'] < $minWidth) $minWidth = $image['width'];
		}
		
		$width = 0;
		$height = $this->height;
		
		foreach($this->row as &$image) {
			$image = $this->getResizedInfo($height, $image, 'height');
			$width += $image['width'];
		}
		
		// get required row width by resizing common bounds width \ height
		// lose required height, if some proportions not fit
		
		$required = $this->getResizedInfo($this->width, array('width' => $width, 'height' => $height), 'width');
		
		$rowHtml = '';
		foreach($this->row as &$image) {
			$imageResized = $this->getResizedInfo($required['height'], $image, 'height');
            
            // todo think rules if images oversized
            if (sizeof($this->row) == 1 and 
                ($imageResized['width'] > $image['width'] or $imageResized['height'] > $image['height']))
            {
                $imageResized = $image;
            }
            
            $image = $imageResized;
            $image['alt'] = '';
            if (!empty($image['tags'])) {
                $image['alt'] = $image['rating'] . '|' . $image['tags'];
            }
		}  
		
        $this->html .= View::show($this->template, array('images' => $this->row));
        
        $this->portrait = 0;
        $this->landscape = 0;
        $this->row = array();
	}

	public function show() 
	{	
		$this->row = array();
        
		$this->portrait = 0;
		$this->landscape = 0;
        
		$this->html = '';

		for ($i = 0; $i < sizeof($this->images); $i++) 
        {
			$image = $this->getImageInfo($i);
			if (!$image) continue;
            
			// bad sized images
            if (max($image['width'], $image['height']) / min($image['width'], $image['height']) > 2.9) continue;
            
			if ($image['portrait']) $this->portrait++;
			else $this->landscape++;			
			
			$this->row[] = $image;
			
            if ($i + 2 >= sizeof($this->images)) continue; // dont keep last one alone
            
			if ($this->landscape == 1 and $this->portrait >= 2) {
				$this->showRow();
			} else if ($this->landscape == 2) {
				$this->showRow();
			} else if ($this->portrait == 4) {
				$this->showRow();
			}
		}

		$this->showRow();
		
		return $this->html;
	}
}
