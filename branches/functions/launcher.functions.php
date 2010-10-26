<?php

	function mergeConfig($var,$file){

		if(is_null($file)||!file_exists($file)){
			return $var;
		}

		//parse_ini_file if an option in the ini file is set to yes is automatically translated into a 1 ...
		//PHP 5.3.2

		$config	= parse_ini_file($file);
		$cmdLine	= array();

		foreach($config as $configParam=>$configValue){
			$cfgFile[] = "--".$configParam."=".$configValue;
		}

		if(!sizeof($var)){
			return $cfgFile;
		}

		$cmdLineArgs	= array();

		for($i=1;isset($var[$i]);$i++){

			$found = FALSE;

			$temp1 = substr($var[$i],0,strpos($var[$i],"="));

			if(empty($temp1)){
				$temp1 = $var[$i];
			}

			for($x=0;isset($cfgFile[$x]);$x++){

				$temp2  = substr($cfgFile[$x],0,strpos($cfgFile[$x],"="));

				if($temp1==$temp2){
					$found = TRUE;
					$cfgFile[$x]=$var[$i];
				}

			}

			if(!$found){
				$cfgFile[] = $var[$i];
			}

		}

		return $cfgFile;

	}

	function isVulnerable(cmdLineParser $cmdParser,\HttpAdapter &$httpAdapter,\LogInterface &$log=NULL){

			$aidSQL		= new aidSQL\Runner($cmdParser,$httpAdapter,$log);

			try {

				if($aidSQL->isVulnerable()){

					$log->log("Site is vulnerable to sql injection!!",0,"light_cyan");
					$aidSQL->generateReport();

					return TRUE;

				}

			}catch(\Exception $e){
		
				$log->log($e->getMessage(),1,"light_red");
				return FALSE;

			}

	}

	function googleSearch(\GoogleSearch &$google,$offset=0,$userTotal=200){

		try{

			$sites	= array();

			$total = 1;

			for($i=$offset;$i<$total&&$i<$userTotal;$i+=8){

				$google->setStart($i);
				$result = $google->doGoogleSearch();

				if($result->responseData->cursor->estimatedResultCount){
					$total = $result->responseData->cursor->estimatedResultCount;
				}

				foreach($result->responseData->results as $searchResult){

					$url = $searchResult->visibleUrl;

					if(!in_array($url,$sites)){
						$sites[] = $url;
					}

				}
			}

		}catch(Exception $e){

			echo $e->getMessage()."\n";
			return $sites;

		}

		return $sites;

	}


?>
