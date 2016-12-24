<?php
/* WEB-APP : Kelly (С) 2016 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../lib/';
require $kellyRoot . 'KellyGDColorPalete.class.php';

$testDir = dirname(__FILE__) . '/colorpalete/';


function renderPage($center = true) {
global $images, $imagePalete, $testDir;
    echo "<div></div>";
    
    $compare = array();

    foreach($images as $image)
    {
        $file = $testDir . $image;
        
        echo '<div style="display : inline-block; max-width : 320px;"><img width="300" src="colorpalete/'.$image.'">';
        echo '<br>' . $file;
        
        $imagePalete->centerPriority = $center;
        $imagePalete->setImage($file);
       // $imagePalete->getPalete(6);
        $compare[] = $imagePalete->getPalete(6);
        $palete = $imagePalete->drawPalete();
        //var_dump(array_slice($imagePalete->getPalete(), 6 * -1, null, true));
        //var_dump($imagePalete->getPalete());
        if (!$palete) echo $imagePalete->log;
        echo '<br> ' . $palete;
        echo '<br> ' . $imagePalete->drawRoundColor(2);
        echo '</div>';
        
        $imagePalete->clear();
    }
    
    echo 'Different : ' . $imagePalete->comparePaletes($compare, 0, 25);
}

$imagePalete = new KellyGDColorPalete();

$images = array(
    'g.jpg',
);


renderPage(true);
