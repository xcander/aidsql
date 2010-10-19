<?php

	namespace aidSQL {

		class Runner{

			private $_debug					= TRUE;
			private $_injectionPlugins		= array();	//Contains all plugins
			private $_vulnerable				= FALSE;		//boolean vulnerable TRUE or not vulnerable FALSE
			private $_plugin					= NULL;		//Contains the plugin to be used, i.e the vulnerable plugin

			public function __construct(\CmdLineParser $parser){

				$this->configure($parser);	

			}

			public function isVulnerable(){

				if($this->_vulnerable){
					return TRUE;
				}

				foreach($this->_injectionPlugins as $plugin){

					$this->dEcho("Testing ".get_class($plugin)." injection plugin...");

					if($plugin->isVulnerable()){

						$this->_plugin			= clone($plugin);
						$this->_vulnerable	= TRUE;
						unset($this->_injectionPlugins);
						return TRUE;

					}

					$this->dEcho("Not vulnerable to this plugin ...");

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
	
				$plugin = $this->_plugin;

				$database	= $plugin->analyzeInjection($plugin->getDatabase());
				$database	= $database[0];

				$dbuser		= $plugin->analyzeInjection($plugin->getUser());
				$dbuser		= $dbuser[0];

				$dbtables	= $plugin->analyzeInjection($plugin->getTables());
				$dbtables	= $dbtables[0];
				
				$report	= "SITE:";
				$report  = "BASIC INFORMATION\n";
				$report .= $this->line();
				$report .= "DBASE\t:\t\t$database\n";
				$report .= "DBUSER\t:\t\t$dbuser\n";
				$report .= "TABLES\t:\t\t$dbtables\n";

				return $report;

			}

			private function line(){
				return "-----------------------------------\n";
			}

			private function configure(\CmdLineParser $parser){

				$adapter = $this->configureHttpAdapter($parser);

				$options = $parser->getParsedOptions();

				$plugins = $options["plugins"];
					
				$verbose = (array_key_exists("verbose",$options)) ? TRUE : FALSE;

				if($plugins=="all"){

					$this->loadPlugins($adapter,array(),$verbose);

				}else{

					$plugins = array_unique(explode(",",$plugins));
					$this->loadPlugins($adapter,$plugins,$verbose);

				}

			}

			/**
			*If no plugin specified, it will load all of them,
			*if given a list of plugins it will load only those specified by $wantedPlugins
			*throws an exception when a given "wanted plugin" is not found.
			*/

			public function loadPlugins(\HttpAdapter $adapter, Array $wantedPlugins=array(),$verbose=FALSE){

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

						$pluginClass		= 'aidSQL\\plugin\\'.$plugin;
						$pluginInstance	= new $pluginClass($adapter);
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

			public function listPlugins(){

					$dir	= __CLASSPATH."/plugin/";
					$dp	= opendir($dir);

					require_once __CLASSPATH."/aidsql/InjectionPlugin.class.php";

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
				$adapterName	= $options["http-adapter"];
				$httpMethod		= $options["http-method"];

				if(!file_exists(__CLASSPATH."/http/$adapterName.class.php")){
					throw(new \Exception("Cannot load adapter $adapterName. Adapter doesnt exists."));		
				}

				require_once __CLASSPATH."/http/$adapterName.class.php";

				$adapter = new $adapterName($options["url"]);

				$adapter->setMethod($httpMethod);

				$urlVariables	= explode(",",$options["urlvars"]);
				$realUrlVars	= array();

				$value	= "";
				$var		= "";

				foreach($urlVariables as $urlVar){

					$var		= explode("=",$urlVar);
					$value	= (isset($var[1])) ? $var[1] : "";
					$adapter->addRequestVariable($var[0],$value);

				}				
				
				if(!is_a($adapter,"HttpAdapter")){
					throw(new \Exception("Invalid HTTP Adapter specified, $adapterName"));
				}

				if(!empty($options["proxy-server"])){

					$adapter->setProxyServer($options["proxy-server"]);
					$adapter->setProxyPort($options["proxy-port"]);
					$adapter->setProxyType($options["proxy-type"]);
				}

				if(!empty($options["proxy-auth"])){

					$adapter->setProxyAuth($options["proxy-auth"]);
					$adapter->setProxyUser($options["proxy-user"]);
					$adapter->setProxyPassword($options["proxy-password"]);

				}

				return $adapter;

			}

			public function setAffectedParameter($parameter){

				$this->_affectedParameter = $parameter;

			}

			private function addInjectionPlugin(\aidSQL\plugin\InjectionPluginInterface $plugin){

				$this->_injectionPlugins[] = $plugin;

			}

			private function dEcho($str){

				if(!$this->_debug){
					return;
				}

				echo $str."\n";

			}


				public function setDebug($boolean=TRUE){

					$this->_debug = (boolean)$boolean;

				}

				public function getDebug(){
					return $this->_debug;
				}


		}

}
