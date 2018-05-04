<?php 

require_once("OwnMySqlI.class.php");
require_once("OwnMemcached.class.php");
require_once("functions.php");

echo "<h1>" . getHomeMessageCached() . "</h1>";

?>