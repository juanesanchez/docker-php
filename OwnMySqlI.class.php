<?php

class OwnMySqlI
{
    static $instance;
    static $instances = array();
    private $connection;
    private function __construct($preset=FALSE)
    {
        $this->connection = new mysqli("mysql", "root", "password","mydatabase");
            
        if (mysqli_connect_errno()) {
            die("Error connector");
        }
        $this->connection->set_charset("utf8");
    }        
    function closeConnection()
    {
        $connection = $this->connection;
        if ($connection) {
            // $thread = $this->connection->thread_id;
            // $this->connection->kill($thread);
            @$connection->close();
        }
        $this->connection = FALSE;
    }
    public static function connect($preset=FALSE)
    {
        if ($preset) {
            if (!array_key_exists($preset, self::$instances)) {
                self::$instances[$preset] = new OwnMySqlI($preset);
            }
            return self::$instances[$preset]->connection;
        }
        if (self::$instance) {
            // ya creamos la instancia, la reusamos
            return self::$instance->connection;
        }
        self::$instance = new OwnMySqlI();
        return self::$instance->connection;
    }
    public static function disconnect()
    {
        if (self::$instance) {
            self::$instance->closeConnection();
            self::$instance = FALSE;
        }
    }
    public static function close($mysqli)
    {
        if ($mysqli === self::$instance->connection) {
            // voy a asumir que hay una sola conexion mysqli, asi que voy a cerrar la statica 
            self::$instance->closeConnection();
            self::$instance = FALSE;
        } else {
            if ($mysqli) {
                $thread = $mysqli->thread_id;
                $mysqli->kill($thread);
                $mysqli->close();
            }
        }
    }
    public static function execute($query, $preset=FALSE)
    {
        $mysqli = self::connect($preset);
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno}\n error: {$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmtResult = $stmt->get_result();
        if ($stmtResult === FALSE) {
            error_log("MySqlI::get_result: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::get_result errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        return $stmtResult;
    }
    public static function insert($query, $preset=FALSE) 
    {
        $mysqli = self::connect($preset);
        
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        if($mysqli->insert_id){
            return $mysqli->insert_id;
        }else{
            return $mysqli->affected_rows;
        }
        
    }
    public static function insertWithParams($query, $types, $params, $preset=FALSE) 
    {
        $mysqli = self::connect($preset);
        $refs = array(); 
        $refs[] = &$types;
        foreach($params as $key => $value)
            $refs[] = &$params[$key];
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = call_user_func_array(array($stmt, 'bind_param'), $refs);
        if ($ok === FALSE) {
            error_log("MySqlI::bind: errno:{$mysqli->errno} error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::bind errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        if($mysqli->insert_id){
            return $mysqli->insert_id;
        }else{
            return $mysqli->affected_rows;
        }        
    }
    public static function delete($query) {
        $mysqli = self::connect();
        
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        return $mysqli->affected_rows;
    }
    public static function deleteWithParams($query, $types, $params, $preset=FALSE) 
    {
        $mysqli = self::connect($preset);
        $refs = array(); 
        $refs[] = &$types;
        foreach($params as $key => $value)
            $refs[] = &$params[$key];
        
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = call_user_func_array(array($stmt, 'bind_param'), $refs);
        if ($ok === FALSE) {
            error_log("MySqlI::bind: errno:{$mysqli->errno} error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::bind errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        return $mysqli->affected_rows;
    }
    public static function executeUpdate($query)
    {
        $mysqli = self::connect();
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $rows = $mysqli->affected_rows;
        if ($rows === FALSE) {
            error_log("MySqlI::get_result: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::get_result errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        return $rows;
    }
    public static function executeUpdateWithParams($query, $types, $params, $preset=FALSE)
    {
        $mysqli = self::connect($preset);
        $refs = array(); 
        $refs[] = &$types;
        foreach($params as $key => $value)
            $refs[] = &$params[$key];
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno} error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = call_user_func_array(array($stmt, 'bind_param'), $refs);
        if ($ok === FALSE) {
            error_log("MySqlI::bind: errno:{$mysqli->errno} error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::bind errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno} error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $rows = $mysqli->affected_rows;
        if ($rows === FALSE && $mysqli->errno != 0) {
            error_log("MySqlI::get_result: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::get_result errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        return $rows;
    }
    public static function execute_with_params($query, $types, $params, $preset=FALSE)
    {
        $mysqli = self::connect($preset);
        $refs = array(); 
        $refs[] = &$types;
        foreach($params as $key => $value)
            $refs[] = &$params[$key];
        $stmt = $mysqli->prepare($query);
        if(!$stmt) {
            error_log("MySqlI::prepare: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::prepare errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = call_user_func_array(array($stmt, 'bind_param'), $refs);
        if ($ok === FALSE) {
            error_log("MySqlI::bind: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::bind errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $ok = $stmt->execute();
        if ($ok === FALSE) {
            error_log("MySqlI::execute: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::execute errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmtResult = $stmt->get_result();
        if ($stmtResult === FALSE) {
            error_log("MySqlI::get_result: errno:{$mysqli->errno}\n error:{$mysqli->error}\n query:{$query}");
            throw new Exception("MySqlI::get_result errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        $stmt->close();
        return $stmtResult;
    }
    public static function loadData($query) {
        $mysqli = self::connect();
        
        $stmt = $mysqli->query($query);
        if(!$stmt) {
            error_log("MySqlI::execute load: errno:{$mysqli->errno} error:{$mysqli->error}");
            throw new Exception("MySqlI::execute load errno:{$mysqli->errno}: error:{$mysqli->error}", $mysqli->errno);
        }
        
        return $mysqli->affected_rows;
    }
    public static function realEscapeString($field) {
        $mysqli = self::connect();
        return $mysqli->real_escape_string($field);
    }
}
function OwnMySqlIShutdown()
{
    if (OwnMySqlI::$instance) {
        OwnMySqlI::$instance->closeConnection();
    }
    foreach (OwnMySqlI::$instances as $instance) {
        $instance->closeConnection();
    }
}
register_shutdown_function('OwnMySqlIShutdown');

?>
