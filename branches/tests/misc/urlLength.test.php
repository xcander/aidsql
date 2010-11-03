<?php

	require_once "interface/HttpAdapter.interface.php";
	require_once "class/http/eCurl.class.php";

	$url  = $_SERVER["argv"][1];

	$http = new eCurl();
	$http->setMethod("GET");

	$value="";

	for($i=0;$i<5000;$i++){
		$value.="%00";
	}

	$url = rtrim($url,"/")."/".$value;

	$http->setUrl($url);

	echo $url."\n";

	echo "Making request\n";
	var_dump($http->fetch());

?>
