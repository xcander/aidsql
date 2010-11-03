<?php

	$xmlFile = $_SERVER["argv"][1];

	if(empty($xmlFile)){
		die("Usage ".basename($_SERVER["argv"][0]." <nmap xml file>"));
	}

	require_once "interface/Parser.interface.php";
	require_once "class/aidsql/Nmap.class.php";

	$nmap = new aidSQL\Nmap();
	$nmap->setContent(file_get_contents($xmlFile));
	var_dump($nmap->getResult());

?>
