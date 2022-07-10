<?php

// require_once "globals.php";
define('MYSQL_ASYNC', true);

class MySQLQuery implements Iterator {
    protected $_query;
    protected $_rows = 0;

    protected $_result_cache = [];
    protected $_gather = false;

    protected $_iter_current_key = 0;

    public function __construct($query)
    {
        $this->_query = $query;
    }

    public function gather() {
        if ($this->_gather) {
            foreach ($this->_result_cache as $x) yield $x;
        }
        else
        {
            $this->_rows = mysqli_num_rows($this->_query);
            while ($row = mysqli_fetch_assoc($this->_query)) {
                $this->_result_cache[] = $row;
                yield $row;
            }
            
            if (is_object($this->_query))
                mysqli_free_result($this->_query);
    
            $this->_gather = true;
        }
    }

    public function await() {
        if ($this->_gather) return;
        if (is_bool($this->_query))
        {
            $this->_gather = true;
            return;
        }
        
        
        $this->_rows = mysqli_num_rows($this->_query);
        while ($row = mysqli_fetch_assoc($this->_query)) {
            $this->_result_cache[] = $row;
        }
        
        if (is_object($this->_query))
            mysqli_free_result($this->_query);

        $this->_gather = true;
        
    }

    public function fetchAll()
    {
        return $this->gather();
    }

    public function fetchOne()
    {
        $x = $this->current();
        $this->next();
        return $x;
    }

    public function fetch($row, $key)
    {
        $this->await();
        if (!isset($this->_result_cache[$row][$key])) return null;
        return $this->_result_cache[$row][$key];
    }

    public function fetchFirst($key)
    {
        $this->await();
        if (!isset($this->_result_cache[0][$key])) return null;
        return $this->_result_cache[0][$key];
    }

    public function fetchRow($row)
    {
        $this->await();
        if (!isset($this->_result_cache[$row])) return null;
        return $this->_result_cache[$row];
    }

    public function rewind() {
        $this->_iter_current_key = 0;
        $this->await();
    }

    public function current() {
        return $this->_result_cache[$this->_iter_current_key];
    }

    public function next() {
        ++$this->_iter_current_key;
    }

    public function key() {
        return $this->_iter_current_key;
    }

    public function valid(): bool
    {
        return isset($this->_result_cache[$this->_iter_current_key]);
    }

    public function rows(): int
    {
        $this->await();
        return $this->_rows;
    }
}

class AsyncMySQLQuery extends MySQLQuery {
    public function gather() {
        if ($this->_gather) {
            foreach ($this->_result_cache as $x) yield $x;
        }
        else
        {
            $reads = [$this->_query];
            $errors = [$this->_query];
            $reject = [$this->_query];
    
            $timeout = 1;
        
            while (!mysqli_poll($reads, $errors, $reject, $timeout)) continue;
    
            if ($result = $reads[0]->reap_async_query()) {
                if (!is_bool($result))
                {
                    $this->_rows = mysqli_num_rows($result);
        
                    while ($row = mysqli_fetch_assoc($result)) {
                        $this->_result_cache[] = $row;
                        yield $row;
                    }
        
                    if (is_object($result))
                        mysqli_free_result($result);
                }
            } else die(sprintf("MySQLi Error: %s", mysqli_error($reads[0])));
    
            $this->_gather = true;
        }
    }

    public function await() {
        if ($this->_gather) return;

        $reads = [$this->_query];
        $errors = [$this->_query];
        $reject = [$this->_query];

        $timeout = 1;
    
        while (!mysqli_poll($reads, $errors, $reject, $timeout)) continue;

        if ($result = $reads[0]->reap_async_query()) {
            if (!is_bool($result))
            {
                $this->_rows = mysqli_num_rows($result);

                while ($row = mysqli_fetch_assoc($result)) {
                    $this->_result_cache[] = $row;
                }

                if (is_object($result))
                    mysqli_free_result($result);
            }
        } else die(sprintf("MySQLi Error: %s", mysqli_error($reads[0])));

        $this->_gather = true;
        
    }
}


class MySQLConn {
    private $__DBconn;
    private $__db;

    public function __construct($db) {
        $this->__db = $db;
        $this->__DBconn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, $db, MYSQL_PORT);

        if (mysqli_connect_error()) {
            throw new Exception("Could not connect to MySQL database");
        }
    }

    public function __destruct()
    {
        mysqli_close($this->__DBconn);
    }

    public function exec(String $sql, bool $async = false)
    {
        if ($async)
        {
            $link = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, $this->__db, MYSQL_PORT);

            if (!($result = $link->query($sql, MYSQLI_ASYNC|MYSQLI_STORE_RESULT))) {
                throw new Exception('Error executing MySQL query: '.$sql.'. MySQL error '.mysqli_errno($this->_DB).': '.mysqli_error($this->_DB));
            }
            return new AsyncMySQLQuery($link);
        }
        else
        {
            if (!($result = $this->__DBconn->query($sql))) {
                throw new Exception('Error executing MySQL query: '.$sql.'. MySQL error '.mysqli_errno($this->_DB).': '.mysqli_error($this->_DB));
            }
            return new MySQLQuery($result);
        }
    }

    public function execute(String $sql, bool $async = true)
    {
        return $this->exec($sql,$async);
    }

    public function identity()
    {
        return $this->exec("SELECT @@IDENTITY AS 'Identity'",false)->fetchone("Identity");
    }
};

global $MYSQL, $MYSQL_NAV;

$MYSQL = new MySQLConn(MYSQL_DB);
$MYSQL_NAV = new MySQLConn(MYSQL_NAVDB);

