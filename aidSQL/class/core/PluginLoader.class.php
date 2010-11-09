<?php

	namespace aidSQL\core {

		class PluginLoader {
			
			private	$_log				=	NULL;
			private	$_pluginsDir	=	NULL;
			private	$_plugins		=	array();

			public function __construct($pluginsDir=NULL,\aidSQL\LogInterface &$log=NULL){

				if(!is_null($log)){
					$this->setLog($log);
				}

				if(is_null($pluginsDir)){
					$pluginsDir	=	__CLASSPATH."/plugin/";	
					return;
				}


				$this->setPluginsDir($pluginsDir);

			}

			public function setPluginsDir($dir){

				$dir	=	rtrim($dir,"/");

				if(!is_dir($dir)){
					throw(new \Exception("Invalid plugins directory specified \"$dir\"!"));
				}

				if(!is_readable($dir)){
					throw(new \Exception("Plugins directory \"$dir\", cant be read, please chek permissions!"));
				}

				$this->_pluginsDir	=	$dir;

			}

			public function getPluginsDir(){
				return $this->_pluginsDir;
			}


			public function listPluginTypes(){

				$types	=	array_values($this->_list($this->_pluginsDir,"dirsnodots"));
				return array_map("basename",$types);

			}

			public function getPlugins(){
				return $this->_plugins;
			}

			private function _normalizePluginName($plugin){
				return basename(strtolower(substr($plugin,0,strpos($plugin,"."))));
			}

			public function listPlugins(){

				$plugins	=	array();
				$types	=	$this->listPluginTypes();

				$this->log("Building plugin list ...",0,"white");

				foreach($types as $t){

					$list	=	$this->_list($this->_pluginsDir.DIRECTORY_SEPARATOR.$t,"filesnodots");

					if(!sizeof($list)){
						continue;
					}

					foreach($list as $plugin){

						$name	= $this->_normalizePluginName($plugin);

						$_plugin = array(
							"file"=>new \aidSQL\core\File($plugin),
							"name"=>$name,
							"type"=>$t
						);

						$plugins[]	=	$_plugin;

					}

				}

				return $this->_plugins	=	$plugins;

			}

			public function setLog(\aidSQL\LogInterface &$log){
				$this->_log	=	$log;
			}

			private function log($msg = NULL){

				if(!is_null($this->_log)){

					$this->_log->setPrepend("[".__CLASS__."]");
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;

				}

				return FALSE;

			}

			public function loadType($type=NULL){

				$flag	=	FALSE;

				foreach($this->_plugins as $plugin){

					if($plugin["type"]==$type){
						$flag = TRUE;
						$this->load($plugin);
					}

				}

				throw(new \Exception("There where no plugins of type \"$type\" to be loaded!"));

				return $flag;

			}

			public function load (Array $plugin){
	
				if(!isset($plugin["file"])&& !is_a($plugin["file"],"\\aidSQL\\core\\File")){

					throw(new \Exception("Couldnt load plugin because it doesnt contains a valid \\aidSQL\\core\\File instance!"));

				}

				$fileObj	=	$plugin["file"];
				$load		=	$fileObj->getFile();

				$index	=	$this->getPluginIndex($plugin["type"],$plugin["name"]);

				if($index===FALSE){
					throw(new \Exception("ERROR OBTAINING PLUGIN INDEX!"));
				}

				$pluginConstant	=	"__aidSQL_PLUGIN_".strtoupper($plugin["type"]).
				'_'.strtoupper($plugin["name"])."__";

				if(defined($pluginConstant)){

					$this->log("Load $plugin[type] => $plugin[name] ... PVL ",0,"light_green");
					return TRUE;
					
				}

				if(include $load){

					$this->log("Load $plugin[type] => $plugin[name] ... OK ",0,"light_green");
					define($pluginConstant,TRUE);
					return TRUE;

				}

				$this->log("Load $plugin[type] => $plugin[name] ... ERROR!",0,"red");
				return FALSE;

			}

			private function getPluginIndex($type,$name){

				foreach($this->_plugins as $key=>$plugin){

					if($plugin["name"]==$name && $plugin["type"]==$type){
						return $key;
					}

				}

				return FALSE;

			}

			private function isValidPlugin(Array $plugin=array()){

				$type =	key($plugin);
				$name	=	$plugin[$type];

				if(isset($this->_plugins[$type])){
					foreach($this->_plugins[$type] as $plugin){
						if($plugin["name"]==$name){
							return TRUE;
						}
					}
				}

				return FALSE;

			}

			//name must be the normalized name
			public function getInstance(Array $plugin=array()){

				if(!sizeof($this->_plugins)){
					throw(new \Exception("You must ".__CLASS__."::listPlugins() before calling this method!"));
				}

				if(!sizeof($plugin)){
					throw (new \Exception("Cannot make instance of plugin, with empty plugin name specified!"));
				}

				if(!$this->isValidPlugin($plugin)){
					throw (new \Exception("Invalid plugin specified ".key($plugin)));
				}

				$args	=	func_get_args();
				unset($args[0]);

				$type = key($plugin);
				$name = $plugin[$type];

				$pluginName	=	"aidSQL\\plugin\\$type\\$name";

				return call_user_func_array(array(new $pluginName),func_get_args());

			}

			private function _list($dir,$what="all"){

				$dir		=	rtrim($dir,"/").DIRECTORY_SEPARATOR;

				if(!is_dir($dir)){
					throw(new \Exception("Invalid directory specified $dir!"));
				}

				$dp		=	opendir($dir);
				$files	=	array();

				while ($file=readdir($dp)){

					$cFile	=	$dir.$file;	//Complete file

					switch($what){

						case "files":
							if(!is_dir($cFile)){
								$files[]	=	$cFile;
							}
						break;

						case "filesnodots":
							if(!is_dir($cFile) && !preg_match("#^\.#",$file)){
								$files[]	=	$cFile;
							}
						break;

						case "dirs":
							if(is_dir($cFile)){
								$files[]	=	$cFile;
							}
						break;

						case "dirsnodots":

							if(is_dir($cFile) && !preg_match("#^\.#",$file)){
								$files[]	=	$cFile;
							}
						break;

						case "all":
							$files[]	=	$cFile;
							break;

					}

				}

				closedir($dp);

				return $files;

			}

		}
	
	}

?>
