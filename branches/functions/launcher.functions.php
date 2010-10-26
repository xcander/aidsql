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

			$total = 0;
	
			do{

				$result = $google->doGoogleSearch();
				$google->setStart($offset);

				if($result->responseData->cursor->estimatedResultCount){

					$total = $result->responseData->cursor->estimatedResultCount - $offset;

					if($userTotal==0){
						$userTotal = $total;
					}

				}

				foreach($result->responseData->results as $searchResult){

					$url = $searchResult->visibleUrl;

					if(!in_array($url,$sites)){
						$sites[] = $url;
					}

				}

				$offset+=8;

			}while($offset<$total && $offset<$userTotal);

		}catch(Exception $e){

			$google->log($e->getMessage(),1,"red");
			return $sites;

		}

		return $sites;

	}

	function banner(\LogInterface &$log){

		$log->setX11Info(FALSE);

		$banner="               _     _           _ ";
		$log->log($banner,0,"red");
		$banner="   _          (_)   | |         | |";
		$log->log($banner,0,"red");
		$banner=" _| |_    __ _ _  __| |___  __ _| |";
		$log->log($banner,0,"red");
		$banner="|_   _|  / _` | |/ _` / __|/ _` | |";
		$log->log($banner,0,"red");
		$banner="  |_|   | (_| | | (_| \__ \ (_| | |";
		$log->log($banner,0,"red");
		$banner="         \__,_|_|\__,_|___/\__, |_|";
		$log->log($banner,0,"red");
		$banner="                              | |  ";
		$log->log($banner,0,"red");
		$banner="                              |_|  ";
		$log->log($banner,0,"red");
		$banner="\n\tSQL INJECTION DETECTION TOOL\n";
		$log->log($banner,0,"white");
		$banner="\t\tBy Juan Stange <jpfstange@gmail.com>\n\n\n";
		$log->log($banner,0,"white");

		$log->setX11Info(TRUE);

	}

	function filterLinksWithoutParameters(Array &$links){

		$tmpLinks = array();

		foreach($links as $page=>$variables){

			if(sizeof($variables)){

				foreach($variables as $param=>$value){

					if(!isset($tmpLinks[$page])){
						$tmpLinks[$page]="";
					}

					$tmpLinks[$page].="$param=$value,";

				}

				$tmpLinks[$page] = substr($tmpLinks[$page],0,-1);

			}

		}

		$links = $tmpLinks;

	}

	function filterSites (Array &$sites,\Logger &$log,$regex=NULL){

		$regex	=	trim($regex,"/");
		$doRegex	=	!empty($regex);

		foreach($sites as $key=>$site){

			if($doRegex){

				if(preg_match("/$regex/",$site)){

					$log->log("Not adding ".$site,2,"yellow");
					unset($sites[$key]);
					continue;

				}

			}

			$log->log("Site added ".$site,0,"green");

		}

	}

	function testLinks(Array $links,\HttpAdapter &$httpAdapter,\CmdLineParser &$cmdParser,\Logger &$log){

		$log->log("Amount of links to be tested for injection:".sizeof($links),0,"light_cyan");
		$parsedOptions	=	$cmdParser->getParsedOptions();

		$tmpLinks = array_keys($links);

		foreach($tmpLinks as $lnk){
			$log->log($lnk,0,"light_cyan");
		}

		foreach($links as $path=>$query){

			if($path===0){
				$cmdParser->setOption("url",$parsedOptions["url"]);
			} else {
				$cmdParser->setOption("url",$path);
			}

			$cmdParser->setOption("urlvars",$query);

			if(isVulnerable($cmdParser,$httpAdapter,$log)&&(bool)$parsedOptions["immediate-mode"]){
				break;
			}

		}

	}

?>
