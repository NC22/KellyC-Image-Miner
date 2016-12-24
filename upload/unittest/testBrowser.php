<?php
/* WEB-APP : Kelly (С) 2016 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../lib/';
require $kellyRoot . 'Browser.class.php';

$b = new Browser();

$b->setUrl('http://catface.ru');

$b->connect();
$b->closeAfterRead = false;
$b->sendRequest();

$result = $b->readData();

if (is_array($result) and $result['location']) {
    echo "<br>log<br><br><br>";

    var_dump($b->log);
    $b->log = '';

    echo "answer<br>";
    var_dump($result);
    
    $vurl = $b->validateUrl($result['location']);
    $ourl = $b->url;
    
    $b->setUrl($vurl);

    if ($ourl['port'] != $vurl['port']) {
        $b->close();
        $b->connect();
        
        echo "close old port<br>";
    }
    
    $b->sendRequest();
     echo "redirecting to ". $result['location'] ."...<br>";
    $result = $b->readData();	
    
    if (is_array($result) and $result['location']) {
        echo "too many redirects <br>";
    }
}

$b->close();


echo "<br>result<br><br><br>";

var_dump($result);

echo "<br>log<br><br><br>";

var_dump($b->log);