<?php 

include("OwnMySqlI.class.php");

$qMax = "SELECT * FROM mytable";
$rMax = OwnMySqlI::execute($qMax);
$aMax = $rMax->fetch_assoc();

echo "<h1>" . $aMax['message'] . "</h1>";

?>