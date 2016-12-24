<?php

class VisitorsMain {

    public function __construct()
    {
    
    }

    public function exec() {
        
        $sqlTpl = "INSERT INTO `site_visitors` (`visitor_ip`) ". 
                  "VALUES (:ip) ". 
                  "ON DUPLICATE KEY UPDATE `visitor_visits`= visitor_visits + 1";
        
        $result = Main::getInstance()->getDB()->ask($sqlTpl, array('ip' => Tool::getClientIP()));       
    }
}