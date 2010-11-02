<?php

	namespace aidSQL {

		class Runner{

			private $_debug					=	TRUE;
			private $_injectionPlugins		=	array();	//Contains all plugins
			private $_vulnerable				=	FALSE;		//boolean vulnerable TRUE or not vulnerable FALSE
			private $_plugin					=	NULL;		//Contains the plugin to be used, i.e the vulnerable plugin
			private $_log						=	NULL;		//Log object
			private $_httpAdapter			=	NULL;

			public function __construct(\CmdLineParser $parser,\HttpAdapter &$adapter,\LogInterface &$log=NULL){

				if(!is_null($log)){
					$this->setLog($log);
				}

				$this->setHttpAdapter($adapter);
				$this->configureHttpAdapter($parser);
				$this->configure($parser);	

			}

			public function setHttpAdapter(\HttpAdapter &$httpAdapter){

				$this->_httpAdapter = $httpAdapter;

			}

			public function getHttpAdapter(){

				return $this->_httpAdapter;

			}

			public function setLog(\LogInterface &$log){
				$this->_log = $log;
			}

			private function log($msg=NULL){

				if(!is_null($this->_log)){
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function isVulnerable(){

				if($this->_vulnerable){
					return TRUE;
				}

				foreach($this->_injectionPlugins as $plugin){

					$this->log("Testing ".get_class($plugin)." injection plugin...");

					if($plugin->isVulnerable()){

						$this->_plugin			= clone($plugin);
						$this->_vulnerable	= TRUE;
						unset($this->_injectionPlugins);
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
				$this->log("PLUGIN\t:\t".$plugin->getPluginName(),0,"cyan");
				$this->log("DBASE\t:\t$database",0,"white");
				$this->log("USER\t:\t$dbuser",0,"white");

				if($plugin->isRoot($dbuser)){

					$this->log("IS ROOT\t:\tYES",0,"white");
					$this->log("Trying to get Shell ...",1,"light_green");
					die();

					//Getshell method must return FALSE on error or String path/to/shellLocation
					$shellLocation = $plugin->getShell();

					if($shellLocation){
						$this->log("Got Shell!",1,"light_green");
					}

				}else{
				
					$this->log("IS ROOT\t:\tNO",0,"white");

				}

				return;

			}

			private function configure(\CmdLineParser $parser){

				$options = $parser->getParsedOptions();

				$plugins = $options["plugins"];
					
				$verbose = (array_key_exists("verbose",$options)) ? TRUE : FALSE;

				if($plugins=="all"){

					$this->loadPlugins($this->_httpAdapter,array(),$verbose);

				}else{

					$plugins = array_unique(explode(",",$plugins));
					$this->loadPlugins($this->_httpAdapter,$plugins,$verbose);

				}

			}

			/**
			*If no plugin specified, it will load all of them,
			*if given a list of plugins it will load only those specified by $wantedPlugins
			*throws an exception when a given "wanted plugin" is not found.
			*/

			public function loadPlugins(\HttpAdapter &$adapter, Array $wantedPlugins=array(),$verbose=FALSE){

				$pluginsDir		= __CLASSPATH."/plugin";
				$sizeOfWanted	= sizeof($wantedPlugins);
				$allPlugins		= $this->listPlugins();

				if(!sizeof($allPlugins)){

					throw(new \Exception("No injection plugins found!"));

				}

				$basicPlugin	= __CLASSPATH."/aidsql/InjectionPlugin.class.php";

				require_once "$basicPlugin";

				if(!$sizeOfWanted){ //Load all plugins

					while(list(,$plugin) = each($allPlugins)){

	  					require_once "$pluginsDir/$plugin.plugin.php";

						$pluginClass		= 'aidSQL\\plugin\\sqli\\'.$plugin;
						$pluginInstance	= new $pluginClass($adapter);

						if(!is_null($this->_log)){
							$pluginInstance->setLog($this->_log);
						}

						$pluginInstance->setVerbose($verbose);

						$this->addInjectionPlugin($pluginInstance);

					}

					return TRUE;

				}

				foreach($wantedPlugins as $wanted){

					$isValidPlugin = FALSE;

					$wantedToLower = strtolower($wanted);	

					foreach($allPlugins as $valid){

						$validToLower = strtolower($valid);	

						if($validToLower==$wantedToLower){ //Ok, valid plugin

							require_once "$pluginsDir/$valid.plugin.php";

							$pluginInstance	= new $plugin($adapter);
							$pluginInstance->setVerbose($verbose);

							$this->addInjectionPlugin($plugin);

							$isValidPlugin = TRUE;

						}

						if(!$isValidPlugin){
							throw(new \Exception("Unknown plugin specified, $wanted"));
						}

					}

				}			

			}

			public function listPlugins($type=NULL){

					if(is_null($type)){
						throw(new \Exception("Must give a type of plugin to list"));
					}

					$dir	= __CLASSPATH."/plugin/";

					if(!is_dir($dir)){
						throw(new \Exception("Plugins directory doesnt exists!"));	
					}

					$dir.=trim($type,"/");

					if(!is_dir($dir)){
						throw(new \Exception("Invalid plugin type specified!"));	
					}

					$dp			= opendir($dir);
					$pluginList = array();

					while($file = readdir($dp)){

						if(is_dir($file)||preg_match("/^[.]/",$file)){
							continue;
						}

						$class = substr($file,0,strpos($file,"."));
						$pluginList[] = $class;

					}

					closedir($dp);

					return $pluginList;

			}

			/**
			*Loads, instantiates, configures http object
			*/

			private function configureHttpAdapter(\CmdLineParser $parser){

				$options			= $parser->getParsedOptions();

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

			private function addInjectionPlugin(\aidSQL\plugin\InjectionPluginInterface $plugin){

				$this->_injectionPlugins[] = $plugin;

			}

		}

	}

?>
