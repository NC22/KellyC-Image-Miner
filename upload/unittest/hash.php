<?php
/* WEB-APP : Kelly (С) 2016 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../lib/';
require $kellyRoot . 'Browser.class.php';

include_once($kellyRoot . 'Imagehash/ImageHash.php');
include_once($kellyRoot . 'Imagehash/Implementation.php');

include_once($kellyRoot . 'Imagehash/Implementations/DifferenceHash.php');
include_once($kellyRoot . 'Imagehash/Implementations/AverageHashBig.php');
include_once($kellyRoot . 'Imagehash/Implementations/PerceptualHashBig.php');

include_once($kellyRoot . 'Imagehash/Math/BigInteger.php');
include_once($kellyRoot . 'Imagehash/Math/Hex.php');
include_once($kellyRoot . 'Imagehash/Math/Binary.php');

$testDir = dirname(__FILE__) . '/colorpalete/';

$images = array(
    'g.jpg',
);

//$test = new phpseclib\Math\BigInteger('-920243495', 10);
//echo $test->toString();
//exit;


//bitwise_leftShift($shift)

$dec = \Jenssegers\ImageHash\ImageHash::DECIMAL;
$hex = \Jenssegers\ImageHash\ImageHash::HEXADECIMAL;
$hashObj = new \Jenssegers\ImageHash\ImageHash(null, $dec);

echo '<br> PerceptualHashBig <br>';
$hashObj = new \Jenssegers\ImageHash\ImageHash(new Jenssegers\ImageHash\Implementations\PerceptualHashBig, $dec);

foreach ($images as $image)
{
    $hash = $hashObj->hash($testDir . $image, true);
    
    echo $hash . '<br>';     
}