<?php
class View {

    public static $themeWay = false;
    public static $themeUrl = false;
    
    public static $content = array(
        'main' => '', 
        'side' => '',
        'scripts' => '',
        'footer' => '',
        'title' => 'Без названия',
    );
    
    public static function show($way, $vars = false)
    {
        ob_start();        
        include self::$themeWay . $way . '.php'; 
        
        return ob_get_clean();
    }
    
    public static function arrowsGenerator($link, $curpage, $itemsnum, $per_page)
    {
		if (strpos ($link, '?') === false) {
			$link .= '?';
		} else {
			$link .= '&';
		}
		
        $numoflists = ceil($itemsnum / $per_page);
        $arrows = '';

        if ($numoflists > 10 and $curpage > 4) {

            $showliststart = $curpage - 4;
            $showlistend = $curpage + 5;

            if ($showliststart < 1)
                $showliststart = 1;

            if ($showlistend > $numoflists)
                $showlistend = $numoflists;
        } else {

            $showliststart = 1;

            if ($numoflists < 10)
                $showlistend = $numoflists;
            else
                $showlistend = 10;
        }

		$style = Main::getShortcut('style') . 'default/paging.php';
		
        ob_start();
		
		$arrows = false;
		$selected = false;
		
        if ($numoflists > 1) {

            if ($curpage > 1) {

                if ($curpage - 4 > 1) {
                    $var = 1;
                    $text = '<<';
                    include ($style);
                }

                $var = $curpage - 1;
                $text = '<';
                include ($style);
            }

            for ($i = $showliststart; $i <= $showlistend; $i++) {

                $var = $i;
                $text = $i;
				$selected = ($i == $curpage) ? true : false;
				
				include ($style);
            }
			
			$selected = false;
			
            if ($curpage < $numoflists) {

                $var = $curpage + 1;
                $text = '>';
                include ($style);

                if ($curpage + 5 < $numoflists) {
                    $var = $numoflists;
                    $text = '>>';
                    include ($style);
                }
            }
        }

        $arrows = ob_get_clean();
        if ($arrows) {

            ob_start();
            include ($style);
            return ob_get_clean();
        }

        return '';
    }
}