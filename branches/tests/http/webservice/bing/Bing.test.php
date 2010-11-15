<?php

	if(empty($_SERVER["argv"][1])){
		die("Usage: ".basename($_SERVER["argv"][0])." <ip>\n");
	}

	require_once "interface/Log.interface.php";
	require_once "interface/HttpAdapter.interface.php";
	require_once "class/log/Logger.class.php";
	require_once "class/http/eCurl.class.php";
	require_once "class/webservice/bing/Bing.class.php";

	$log	=	new Logger();
	$log->setEcho(TRUE);

	try{

		$http	=	new \eCurl();
		$bing	=	new aidsql\webservice\Bing($http,$log);
		$bing->setLog($log);
		var_dump($bing->getHosts($_SERVER["argv"][1]));

	}catch (Exception $e){

		$log->log($e->getMessage(),1,"red");

	}

?>
