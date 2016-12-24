<?php

class KellyPdoModule implements DataBaseInterface
{
    /**
     * @var PDO
     */
    
    public $link = false;
    private $lastError = '';

    public function connect($data)
    {
        if ($this->link) {
            $this->log('Already connected');
            throw new \Exception($this->lastError, 1);
        }

        try {
            
            $this->link = new \PDO("mysql:host={$data['host']};port={$data['port']};dbname={$data['db']}", $data['login'], $data['password']);

            $this->query("SET time_zone = '" . date('P') . "'");
            $this->query("SET character_set_client='utf8'");
            $this->query("SET character_set_results='utf8'");
            $this->query("SET collation_connection='utf8_general_ci'");
            
            $this->link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
        } catch (\PDOException $e) {
            $this->log($e->getMessage());
            throw new \Exception($this->lastError, 2);
        }
    }

    public function close()
    {
        $this->link = null;
    }

    public function quote($str)
    {
        if (!$this->link) {
            return false;
        }

        return $this->link->quote($str);
    }
    
    public function log($error)
    {
        $this->lastError = $error;
        Tool::log('SQLError: ' . $this->lastError);
    }

    public function query($query) 
    {
        try {                
            
            $statement = $this->link->query($query); 
            return new KellyPdoStatement($this, $statement);
            
        } catch (\PDOException $e) {

            $this->log('[' . $e->getMessage() . '] in [ ' . $query . ' ]');
            return false;
        }
    }
    
    public function ask($queryTpl, $data = null) 
    {
        // \Kelly\Tool::log($queryTpl);
        // \Kelly\Tool::log(var_export($data, true));
        $statement = $this->prepare($queryTpl);
        if (!$statement) return false;
        
        if ($data) {
            $statement->bindData($data);
        }
        if (!$statement->execute()) return false;
        
        return $statement;
    }
    
    public function prepare($queryTpl)
    {
        if (!$this->link) {
            return false;
        }

        try {             
            return new KellyPdoStatement($this, $this->link->prepare($queryTpl));            
        } catch (\PDOException $e) {
            
            $this->log('[' . $e->getMessage() . '] in [ ' . $queryTpl . ' ]');
            return false;
        }
    }

    public function fetchRow($queryTpl, $data = array(), $fetchMode = 'assoc')
    {
        $result = $this->ask($queryTpl, $data);

        if ($result === false) {
            return false;
        }
        
        $result->setFetchMode($fetchMode);
        $lines = $result->fetch();
        
        return $lines;
    }
    
    public function lastInsertId()
    {
        if (!$this->link)
            return false;

        return $this->link->lastInsertId();
    }

    public function getLastError()
    {
        return $this->lastError;
    }
    
    public function isColumnExist($table, $column)
    {
        if (!$this->link)
            return false;

        return (@$this->query("SELECT `$column` FROM `$table` LIMIT 0, 1")) ? true : false;
    }

    public function getColumnType($table, $column)
    {
        if (!$this->link) {
            return false;
        }

        $result = $this->fetchRow("SHOW FIELDS FROM `$table` WHERE Field = '$column'");

        return $result['Type'];
    }
}
