<?php
/* WEB-APP : Kelly (С) 2016 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../lib/';
require $kellyRoot . 'Browser.class.php';


$b = new Browser();

//$b->setUrl('http://www.catface.ru');

//$b->connect();
//$b->closeAfterRead = true;
//$b->sendRequest(); - will be unexpected content type if uncomment couse headers already sent with other url inside

$b->additionHeaders .= "Pragma: no-cache\r\n"; // not required actually much

//$b->additionHeaders .= "Cache-Control: no-cache\r\n"; - fire fox uses that header if image not chached
// This two if already downloaded
// If-Modified-Since: Wed, 25 Jun 2014 00:02:14 GMT
// If-None-Match: "29fc078-25d6-4fc9dc9e2ed80"

// if not modifed server answer HTTP/1.1 304 Not Modified

@ini_set("max_execution_time", '120');  

$result = $b->downloadFiles(
array('https://raw.githubusercontent.com/NC22/ImageMiner/master/example.png'),
dirname(__FILE__) . '/downloadtest/');

$b->close();

var_dump($b->getRequest());

echo "<br>result<br><br><br>";

var_dump($result);

echo "<br>log<br><br><br>";

var_dump($b->log);

var_dump($b->lastHeaders);