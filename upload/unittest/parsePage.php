<?php
/* WEB-APP : Kelly (С) 2014 NC22 | License : GPLv3 */

error_reporting(E_ALL | E_STRICT); 

$kellyRoot = '../';
include $kellyRoot . 'lib/Main.class.php';

$c = Main::getInstance();
$c->init($kellyRoot);

require $kellyRoot . '/lib/Page/Joy/Parser.class.php';

$material = './pages/test.html';

$p = new JoyParser();

ob_start();
include ($material);
$materialBody = ob_get_clean();

var_dump($p->parsePageInfo($materialBody));