<?php 

require_once("OwnMySqlI.class.php");
require_once("functions.php");


// phpinfo();

$memca = $memcached = new Memcached();
$memca->addServer("memcached", 11211);
$memca->getStats();

echo "Status Result: "; print_r($memca->getResultCode());

echo "<br>";

$memca->add('foo', 'bar');
if ($memca->getResultCode() == Memcached::RES_NOTSTORED) {
    echo "OK";
}else{
	echo "ERROR";
}


?>