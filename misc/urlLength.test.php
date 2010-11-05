<?php

	ini_set("memory_limit",-1);

	require_once "interface/HttpAdapter.interface.php";
	require_once "class/http/eCurl.class.php";
	

	$url	= $_SERVER["argv"][1];
	$length	= $_SERVER["argv"][2];

	$http = new eCurl();
	$http->setMethod("GET");

	$value="";

	for($i=0;$i<$length;$i++){
		$value.="%00";
	}

	//$url = rtrim($url,"/")."/".$value;

	$http->setUrl($url);
	$http->addRequestVariable($_SERVER["argv"][3],$_SERVER["argv"][4].$value);
	$http->addRequestVariable("test","penis");

	echo $url."\n";

	echo "Making request\n";
	var_dump($http->fetch());

?>
