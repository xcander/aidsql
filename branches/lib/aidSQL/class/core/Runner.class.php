<?php

	namespace aidSQL\core {

		class Runner{

			private	$_debug			=	TRUE;
			private	$_vulnerable	=	FALSE;		//boolean vulnerable TRUE or not vulnerable FALSE
			private	$_log				=	NULL;			//Log object
			private	$_httpAdapter	=	NULL;
			private	$_options		=	array();
			private	$_pLoader		=	NULL;			//Plugin Loader Instance

			public function __construct(\aidSQL\parser\CmdLine $parser,\aidSQL\http\Adapter &$adapter,\aidSQL\LogInterface &$log=NULL){

				if(!is_null($log)){
					$this->setLog($log);
				}

				$options				=	$parser->getParsedOptions();
				$this->_options	=	$options;

				$pluginsDir	=	__CLASSPATH.DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."plugin";

				$this->_pLoader	=	new PluginLoader($pluginsDir,$log);
			
				if(isset($options["plugin-disclosure-load-order"])){

					$this->_pLoader->setDisclosurePluginLoadOrder(explode(',',$options["plugin-disclosure-load-order"]));

				}

				$this->setHttpAdapter($adapter);
				$this->configureHttpAdapter($options);

			}

			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter){

				$this->_httpAdapter = $httpAdapter;

			}

			public function getHttpAdapter(){

				return $this->_httpAdapter;

			}

			public function setLog(\aidSQL\LogInterface &$log){
				$this->_log = $log;
			}

			private function log($msg=NULL){

				if(!is_null($this->_log)){
					$this->_log->setPrepend('['.__CLASS__.']');
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function isVulnerable(){

				if($this->_vulnerable){
					return TRUE;
				}

				$plugins	=	$this->_pLoader->getPlugins();

				if(!sizeof($plugins)){
					throw (new \Exception("No plugins found!"));
				}

				foreach($plugins as $plugin){

					if($plugin["type"]!="sqli"){
						continue;
					}

					$this->_pLoader->load($plugin);

					$config	=	$plugin["config"];

					$plugin	=	"aidSQL\\plugin\\sqli\\$plugin[name]";
					$plugin	=	new $plugin($this->_httpAdapter);
					$plugin->setLog($this->_log);
					$plugin->setConfig($config);

					$this->log("Testing ".get_class($plugin)." sql injection plugin...",0,"white");

					if($plugin->isVulnerable()){

						$this->_plugin			= clone($plugin);
						$this->_vulnerable	= TRUE;
						return TRUE;

					}

					$this->log("Not vulnerable to this plugin ...");

				}

				return FALSE;

			}

			/*
			*Generates a full report 
			*combines common vulnerability methods provided by the injection plugin interface
			*/

			public function generateReport(){
	
				if(is_null($this->_vulnerable)){
					$msg = "Site seems not to be vulnerable or has not been checked for being vulnerable, cannot generate report";
					throw(new Exception($msg));
				}
	
				$plugin		= $this->_plugin;
				$database	= $plugin->analyzeInjection($plugin->getDatabase());
				$database	= $database[0];

				$dbuser		= $plugin->analyzeInjection($plugin->getUser());
				$dbuser		= $dbuser[0];

				$dbtables	= $plugin->analyzeInjection($plugin->getTables());
				$dbtables	= $dbtables[0];

				$this->log("BASIC INFORMATION",0,"cyan");
				$this->log("---------------------------------",0,"white");
				$this->log("PLUGIN\t\t:\t".$plugin->getPluginName(),0,"cyan");
				$this->log("DBASE\t\t:\t$database",0,"white");
				$this->log("USER\t\t:\t$dbuser",0,"white");
				$this->log("TABLES\t\t:\t$dbtables",0,"white");

				if($plugin->isRoot($dbuser)){

					$this->log("IS ROOT\t:\tYES",0,"light_green");
					$this->log("Trying to get Shell ...",1,"light_cyan");

					//Getshell method must return FALSE on error or String path/to/shellLocation

					$g0tShell = $plugin->getShell($this->_pLoader,$this->_options);

					if($g0tShell){
						$this->log("Got Shell => $g0tShell",0,"light_green");
					}else{
						$this->log("Couldn't get shell :(",2,"yellow");
					}

				}else{
				
					$this->log("IS ROOT\t:\tNO",0,"white");

				}

				return;

			}

			/**
			*Loads, instantiates, configures http object
			*/

			private function configureHttpAdapter(Array $options){

				$this->_httpAdapter->setMethod($options["http-method"]);
				$this->_httpAdapter->setUrl($options["url"]);

				$urlVariables	= explode(",",$options["urlvars"]);
				$realUrlVars	= array();

				$value	= "";
				$var		= "";

				foreach($urlVariables as $urlVar){

					$var		= explode("=",$urlVar);
					$value	= (isset($var[1])) ? $var[1] : "";
					$this->_httpAdapter->addRequestVariable($var[0],$value);

				}				
				
				if(!empty($options["proxy-server"])){

					$this->_httpAdapter->setProxyServer($options["proxy-server"]);
					$this->_httpAdapter->setProxyPort($options["proxy-port"]);
					$this->_httpAdapter->setProxyType($options["proxy-type"]);

				}

				if(!empty($options["proxy-auth"])){

					$this->_httpAdapter->setProxyAuth($options["proxy-auth"]);
					$this->_httpAdapter->setProxyUser($options["proxy-user"]);
					$this->_httpAdapter->setProxyPassword($options["proxy-password"]);

				}

			}

			public function setAffectedParameter($parameter){

				$this->_affectedParameter = $parameter;

			}

			private function addInjectionPlugin(\aidSQL\plugin\sqli\InjectionPluginInterface $plugin){

				$this->log("Adding injection plugin $plugin");
				$this->_injectionPlugins[] = $plugin;

			}

		}

	}

?>
