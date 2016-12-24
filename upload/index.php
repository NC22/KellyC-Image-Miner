<?php
error_reporting(E_ALL | E_STRICT); 
//error_reporting(0); 

$kellyRoot = dirname(__FILE__) . '/';
include $kellyRoot . '/lib/Main.class.php';

$c = Main::getInstance();
$c->exec($kellyRoot);

