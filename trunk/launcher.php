<?php

	/**
	 * @todo Implement spl_autoload
	 * @todo Fix CmdLineParser so it integrates the functionality given in the function mergeConfig located in launcher.php and implement it.
	 * @todo Analyze script performance with xdebug
	 * 
	 */

	define ("__CLASSPATH","class");
	error_reporting(E_ALL);
	function checkPHPVersion(){

		$version		= substr(PHP_VERSION,0,strpos(PHP_VERSION,"."));
		$subversion	= substr(PHP_VERSION,strpos(PHP_VERSION,".")+1);
		$subversion	= substr($subversion,0,strpos($subversion,"."));

		if($version != 5 || $subversion < 3){
			die("Sorry but you need at least version 5.3.0 in order to run aidSQL :(\n");
		}

	}

	checkPHPVersion();

	//Interfaces
	require_once "interface/HttpAdapter.interface.php";
	require_once "interface/InjectionPlugin.interface.php";
	require_once "interface/Parser.interface.php";

	//Classes
	require_once "class/aidsql/Runner.class.php";
	require_once "class/core/CmdLine.class.php";
	require_once "class/core/String.class.php";
	require_once "class/core/File.class.php";
	require_once "class/http/eCurl.class.php";
	require_once "config/config.php";
	
	//Parsers
	require_once "class/parser/Generic.parser.php";
	require_once "class/parser/TagMatcher.parser.php";
	require_once "class/parser/Dummy.parser.php";
	require_once "class/parser/MySQLError.parser.php";


	function mergeConfig($var,$file){

		if(is_null($file)||!file_exists($file)){
			return $var;
		}

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

	try {

		unset($_SERVER["argv"][0]);

		$parameters = mergeConfig($_SERVER["argv"],"config/config.ini");

		$cmdParser	= new CmdLineParser($config,$parameters);
		$aidSQL		= new aidSQL\Runner($cmdParser);

		$options = $cmdParser->getParsedOptions();
		$save		= (isset($options["save-report"])) ? $options["save-report"] : NULL;

		if($aidSQL->isVulnerable()){
			echo "Site is vulnerable to sql injection\n";
	
			$report	= $aidSQL->generateReport();

			if(!is_null($save)){

				echo "Report saved to $save\n";
				file_put_contents($save,$report);

			}

			echo $report."\n";

		}

	}catch(Exception $e){

		echo $e->getMessage()."\n";

	}
		

