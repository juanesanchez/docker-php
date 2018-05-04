<?php 

require_once("OwnMySqlI.class.php");

function getHomeMessage(){
	$qMax = "SELECT * FROM mytable";
	$rMax = OwnMySqlI::execute($qMax);
	$aMax = $rMax->fetch_assoc();

	return "<h1>" . $aMax['message'] . "</h1>";
}


function getHomeMessageCached()
{
	$description = "homeMessage";
    $key = "myKey_".$description;
    $data = "";
    
    $fnLoad = function() use ($data) {

        $qMax = "SELECT * FROM mytable";
		$rMax = OwnMySqlI::execute($qMax);
		$aMax = $rMax->fetch_assoc();

		return $aMax['message'];
    };
    return OwnMemcached::getOrLoad($key, $fnLoad, 60);
}

?>