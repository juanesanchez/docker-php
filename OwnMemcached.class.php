<?php

class OwnMemcached 
{
    const POOL_SIZE = 101; // tiene que ser primo para asegurar una mejor distribucion de claves (101, 53, 73)
    private static $memcached;
    private static $lockDir;
    private static $locks = array();
    private $lock;
    private $key;
    private $lockFile;

    private function __construct($key)
    {
        $this->key = $key;
        $this->lockFile = self::getLockFile($this->key);
        if (array_key_exists($this->lockFile, self::$locks)) {
        } else {
            $this->lock = fopen($this->lockFile, "w");
            $retries = 0;
            while (!flock($this->lock, LOCK_EX | LOCK_NB)) {
                //Lock not acquired, try again in:
                usleep(round(rand(0, 900)*1000)+100); //200-1000 miliseconds
                $retries += 1;

                if ($retries == 5 || $retries == 10 || $retries == 15 || $retries == 20 || $retries == 25) {
                    // error_log("WAIT LOCK ($retries): ".$key." - ".$this->lockFile." - ".$_SERVER["SCRIPT_FILENAME"]." ".$_SERVER["QUERY_STRING"]);
                }
                if ($retries > 30) {
                    error_log("FALLO LOCK: ".$key." - ".$this->lockFile." - ".$_SERVER["SCRIPT_FILENAME"]." ".$_SERVER["QUERY_STRING"]);
                    self::enMantenimiento("lock-failed-".$key);
                }
            }
            self::$locks[$this->lockFile] = $this->lock;
            // error_log("LOCKING ".$this->key."---".$this->lockFile);
        }
    }        

    function __destruct()
    {
        if (isset($this->lock) && $this->lock) {
            error_log("Liberando Lock $this->key - $this->lockFile - ".$_SERVER["SCRIPT_FILENAME"]." ".$_SERVER["QUERY_STRING"]);
            flock($this->lock, LOCK_UN);
        }
    }

    private function invoke($loadFunction, $timeout)
    {
        // estoy adentro de la zona critica, verifico si ya esta cargado
        if (xcache_isset($this->key)) {
            $result = unserialize(xcache_get($this->key));
        } else {
            // @ ToDo esto loquea memcache y base... vale la pena dejar solo la base?
            $result = self::getOrLoadImpl($this->key, $loadFunction, $timeout);
            xcache_set($this->key, serialize($result), 300);
        }

        // liberamos el lock 
        if (isset($this->lock) && $this->lock) {
            flock($this->lock, LOCK_UN);
            unset($this->lock);
            unset(self::$locks[$this->lockFile]);
        } else {
        }
        return $result;
    }

    public static function connect() 
    {
        if (!self::$memcached) {
            self::$memcached = new Memcached();
            self::$memcached->addServer("memcached", 11211);
            self::$memcached->getStats();
            if (self::$memcached->getResultCode() != Memcached::RES_SUCCESS) {
                error_log("FALLO al conectar a MEMCACHE: ["."memcached".']');
                self::enMantenimiento("memcache-connect-failure-"."memcached"."-".self::$memcached->getResultCode());
            }
        }
        return self::$memcached;
    }   

    public static function getOrLoad($key, $loadFunction, $timeout=86400)
    {
        
        return self::getOrLoadImpl($key, $loadFunction, $timeout);

        // esto es para servers
        if (xcache_isset($key)) {
            return unserialize(xcache_get($key));
        }

        // no esta en memoria, lo buscamos en el memcache o lo cargamos
        // pero lo hacemos en un bloque sincronico
        $inst = new OwnMemcached($key);
        return $inst->invoke($loadFunction, $timeout);
    }

    private static function getOrLoadImpl($key, $loadFunction, $timeout=86400)
    {
        self::connect();
        $result = self::$memcached->get($key);
        if ($result === false) {
            try {
                $result = $loadFunction($key);
            } 
            catch(Exception $e) {
                error_log("ERROR ".$e->getMessage());
                self::enMantenimiento($e->getMessage());
            } 
            self::$memcached->set($key, $result, $timeout);
            self::$memcached->set('actualizacion_'.$key, date('Y-m-d H:i:s'), $timeout);
        }
        return $result;
    }

    private static function getLockFile($key)
    {
        if (!self::$lockDir) {
            // me aseguro que el directorio de lock exista
            self::$lockDir = Config::value("/var/www/html/xcache");
            if (!file_exists(self::$lockDir)) {
                if (!mkdir(self::$lockDir, 0777, true)) {
                    self::enMantenimiento("xcache-lock-mkdir-".self::$lockDir);
                }
            } else {
                if (!is_dir(self::$lockDir)) {
                    self::enMantenimiento("xcache-lock-file-".self::$lockDir);
                }
                if (!is_writable(self::$lockDir)) {
                    self::enMantenimiento("xcache-lock-write-".self::$lockDir);
                }
            } 
        }
        $hashKey = "SEM-".crc32($key) % self::POOL_SIZE;
        $result = join(DIRECTORY_SEPARATOR, array(self::$lockDir, $hashKey));
        if (!file_exists($result)) {
            if (!touch($result)) {
                self::enMantenimiento("xcache-lock-key-".$result);
            }
        }
        return $result;
    }

    public static function enMantenimiento($message)
    {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        error_log("MANT: ".$message." - ".$redirect." - ".$_SERVER["SCRIPT_FILENAME"]." ".$_SERVER["QUERY_STRING"]);
        ob_clean();
        header("Cache-Control: no-store, no-cache, must-revalidate"); 
        header("Location: /mantenimiento?e=".$message."&redirect=".$redirect);
        die();
    }
}

?>