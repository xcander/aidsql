<?php

	namespace aidSQL\core {

		class Runner{

			private	$_debug			=	TRUE;
			private	$_vulnerable	=	FALSE;		//boolean vulnerable TRUE or not vulnerable FALSE
			private	$_logger			=	NULL;			//Log object
			private	$_httpAdapter	=	NULL;
			private	$_options		=	array();
			private	$_pLoader		=	NULL;			//Plugin Loader Instance
			private	$_crawler		=	NULL;			//This object might contain important information that 
																//may be used by the plugins.

			public function __construct(\aidSQL\parser\CmdLine $parser,\aidSQL\http\Adapter &$adapter,\aidSQL\http\crawler &$crawler,\aidSQL\core\Logger &$log=NULL){

				if(!is_null($log)){
					$this->setLog($log);
				}

				$options				=	$parser->getParsedOptions();
				$this->_options	=	$options;

				$pluginsDir	=	__CLASSPATH.DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."plugin";

				$this->_pLoader	=	new PluginLoader($pluginsDir,$log);
				$this->_pLoader->setConfig($options);
			
				$this->setHttpAdapter($adapter);
				$this->configureHttpAdapter($options);
				$this->setCrawler($crawler);

			}


			public function setCrawler(\aidSQL\http\crawler &$crawler){
				$this->_crawler	=	$crawler;
			}


			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter){

				$this->_httpAdapter = $httpAdapter;

			}


			public function getHttpAdapter(){

				return $this->_httpAdapter;

			}


			public function setLog(\aidSQL\core\Logger &$log){
				$this->_logger = $log;
			}


			private function log($msg=NULL,$color="white",$level=0,$toFile=FALSE){

				if(isset($this->_config["log-all"])){
					$toFile	=	TRUE;
				}

				if(!is_null($this->_logger)){

					$this->_logger->setPrepend('['.__CLASS__.']');
					$this->_logger->log($msg,$color,$level,$toFile);
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
					$plugin->setLog($this->_logger);
					$mergedConfig	=	array_merge($config->getParsedOptions(),$this->_options);
					$plugin->setConfig($mergedConfig);

					$this->log("Testing ".get_class($plugin)." sql injection plugin...",0,"white");

					try{

						if($plugin->isVulnerable()){

							$this->_plugin			= clone($plugin);
							$this->_vulnerable	= TRUE;
							return TRUE;

						}

						$this->log("Not vulnerable to this plugin ...");

					}catch(\Exception $e){

						$this->log($e->getMessage(),1,"red");
						return FALSE;

					}


				}


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


				try{	

					$plugin		= $this->_plugin;

					$database	= $plugin->getDatabase();
					$dbuser		= $plugin->getUser();
					$dbtables	= $plugin->getTables();

					$this->log("BASIC INFORMATION",0,"cyan",TRUE);
					$this->log("---------------------------------",0,"white",TRUE);
					$this->log("PLUGIN\t\t:\t".$plugin->getPluginName(),0,"cyan",TRUE);
					$this->log("DBASE\t\t:\t$database",0,"white",TRUE);
					$this->log("USER\t\t:\t$dbuser",0,"white",TRUE);
					$this->log("TABLES\t\t:\t$dbtables",0,"white",TRUE);

					if($plugin->isRoot($dbuser)){

						$this->log("IS ROOT\t:\tYES",0,"light_green",TRUE);
						$this->log("Trying to get Shell ...",1,"light_cyan",TRUE);

						//Getshell method must return FALSE on error or String path/to/shellLocation

						$g0tShell = $plugin->getShell($this->_pLoader,$this->_crawler,$this->_options);

						if($g0tShell){
							$this->log("Got Shell => $g0tShell",0,"light_green",TRUE);
						}else{
							$this->log("Couldn't get shell :(",2,"yellow",TRUE);
						}

					}else{
				
						$this->log("IS ROOT\t:\tNO",0,"white",TRUE);

					}

					return TRUE;

				}catch(\Exception $e){

					$this->log($e->getMessage(),1,"red",TRUE);
					return FALSE;

				}


			}

			/**
			*Loads, instantiates, configures http object
			*/

			private function configureHttpAdapter(Array $options){
				
				$this->_httpAdapter->setMethod($options["http-method"]);
				
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
