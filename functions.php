<?php 

require_once("OwnMySqlI.class.php");
require_once("OwnMemcached.class.php");

function getHomeMessageCached()
{
	$description = "homeWelcomeMessage";
    $key = "myKey_".$description;
    $data = "";

    $fnLoad = function() use ($data) {

        $qMax = "SELECT * FROM mytable";
		$rMax = OwnMySqlI::execute($qMax);
		$aMax = $rMax->fetch_assoc();
		echo "Paso por la DB<br>";
		return $aMax['message'];
    };
    return OwnMemcached::getOrLoad($key, $fnLoad, 5);
}

?>