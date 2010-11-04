<?php

	namespace aidSQL {

		class PluginLoader {
			
			private	$_log				=	NULL;
			private	$_pluginsDir	=	NULL;
			private	$_plugins		=	array();

			public function __construct($pluginsDir=NULL,\LogInterface &$log=NULL){

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
							"path"=>dirname($plugin),
							"file"=>basename($plugin),
							"name"=>$name
						);

						$plugins[$t][]	=	$_plugin;

					}

				}

				$this->log("Done!",0,"white");

				return $this->_plugins	=	$plugins;

			}

			public function setLog(\LogInterface &$log){
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

			public function load ($load=array()){

				$plugins		=	$this->_plugins;

				if(!sizeof($plugins)){
					throw(new \Exception("Cant load plugins because no plugins where specified!"));
				}

				$pluginsDir	=	$this->_pluginsDir;

				foreach($plugins as $type=>$plugin){

					$dir	=	$pluginsDir.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR;

					foreach($plugin as $p){

						$this->log("Loading plugin $type => $p[name] ...",0,"white");

						require_once $p["path"].DIRECTORY_SEPARATOR.$p["file"];

					}

				}

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
