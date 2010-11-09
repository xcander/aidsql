<?php

	ini_set("memory_limit",-1);

	require_once "lib/aidSQL/interface/http/Adapter.interface.php";
	require_once "lib/aidSQL/class/http/adapter/Ecurl.class.php";
	

	$url	= $_SERVER["argv"][1];
	$length	= $_SERVER["argv"][2];

	$http = new \aidSQL\http\adapter\Ecurl();
	$http->setMethod("GET");

	$value="";

	for($i=0;$i<$length;$i++){
		$value.="%00";
	}

	$url = rtrim($url,"/")."/".$value;

	echo "URL:".$url."\n";
	$http->setUrl($url.$value);


	echo "Making request\n";
	var_dump($http->fetch());

?>
