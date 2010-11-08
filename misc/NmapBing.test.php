<?php
	namespace aidSQL;

	set_include_path("./lib/aidSQL/".PATH_SEPARATOR.get_include_path());

	$xmlFile = $_SERVER["argv"][1];

	if(empty($xmlFile)){
		die("Usage ".basename($_SERVER["argv"][0]." <nmap xml file>"));
	}

	require "interface/Log.interface.php";
	require "interface/http/Adapter.interface.php";

	require	"class/core/File.class.php";
	require "class/http/adapter/Ecurl.class.php";
	require "class/parser/Nmap.class.php";
	require "class/log/StdLog.class.php";
	require "class/http/webservice/bing/Bing.class.php";


	$log	= new StdLog();
	$log->setEcho(TRUE);

	try{

		$nmap		=	new parser\Nmap();
		$nmapRes	=	$nmap->parseXmlFile(new core\File($xmlFile));

		$http		=	new http\adapter\ECurl();
		$bing		=	new http\webservice\Bing($http,$log);
		$bing->setLog($log);

		foreach($nmapRes as $host){

			foreach($host["address"] as $key=>$addr){

				if($key!=="addr"){	
					continue;
				}

				$bing->getHosts($addr);

			}

		}

	}catch (\Exception $e){

		$log->log($e->getMessage(),1,"red");

	}


?>
