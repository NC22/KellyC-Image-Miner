<?php
/* WEB-APP : Kelly (С) 2014 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../lib/';
require $kellyRoot . 'Browser.class.php';

include_once($kellyRoot . 'Imagehash/ImageHash.php');
include_once($kellyRoot . 'Imagehash/Implementation.php');
include_once($kellyRoot . 'Imagehash/Implementations/DifferenceHash.php');
include_once($kellyRoot . 'Imagehash/Implementations/AverageHash.php');
include_once($kellyRoot . 'Imagehash/Implementations/PerceptualHash.php');


$testDir = dirname(__FILE__) . '/colorpalete/';

$images = array(
    //'1111.jpg',
    '1.gif',
   // '1.jpeg',
   // '2.jpeg',
   // 'tews.gif',
   // '2.jpg',
   // '2r.jpg',
   // '3.jpg',
    'Rally-Car-Race-Retro.11.jpg',
   // 'Rally-Car-Race-Retro.main.jpg',
   // 'Rally-Car-Race-Retro.13.jpg',
   // 'Rally-Car-Race-Retro.resized.jpg',
   // '4.jpg',
);

//$test = new phpseclib\Math\BigInteger('-920243495', 10);
//echo $test->toString();
//exit;


//bitwise_leftShift($shift)

$dec = \Jenssegers\ImageHash\ImageHash::DECIMAL;
$hex = \Jenssegers\ImageHash\ImageHash::HEXADECIMAL;
$hashObj = new \Jenssegers\ImageHash\ImageHash(null, $dec);

foreach ($images as $image)
{
    $hash = $hashObj->hash($testDir . $image, true);
    var_dump(dechex($hash));
     echo  '<br>';     
}

echo '<br> AverageHash<br>';
$hashObj = new \Jenssegers\ImageHash\ImageHash(new Jenssegers\ImageHash\Implementations\AverageHash,$hex);

foreach ($images as $image)
{
    $hash = $hashObj->hash($testDir . $image, true);
    echo $hash . '|' . sprintf("%'032b", hexdec($hash)) . '|' .  hexdec($hash) .  '<br>';     
}

echo '<br> PerceptualHash<br>';
$hashObj = new \Jenssegers\ImageHash\ImageHash(new Jenssegers\ImageHash\Implementations\PerceptualHash,$dec);

foreach ($images as $image)
{
    $hash = $hashObj->hash($testDir . $image, true);
    
    echo dechex($hash) . ' | ' . decbin($hash) . '<br>';     
}