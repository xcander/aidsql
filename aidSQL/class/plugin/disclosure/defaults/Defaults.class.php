<?php

	namespace aidSQL\plugin\disclosure {

		class Defaults extends DisclosurePlugin {

			public function getInfo(){

				$return	=	new \aidSQL\plugin\disclosure\DisclosureResult();
	
				$this->log("Trying to discover default directory locations ...",0,"light_cyan");

				$webDirectories		=	explode(',',$this->_config["web-directories"]);

				$possiblyWritable		=	array();

				if(!sizeof($webDirectories)){
					throw(new \Exception("Cant use defaults plugin with no web directories default location list!"))	;
				}

				$url	=	$this->_httpAdapter->getUrl();
				$url	=	$url->getScheme()."://".$url->getHost();

				foreach($webDirectories as $dir){

					$this->log("$url/$dir ...",0,"white");
					$this->_httpAdapter->setUrl(new \aidSQL\http\Url($url.'/'.$dir));
					$this->_httpAdapter->fetch();

					$httpCode	=	$this->_httpAdapter->getHttpCode();

					if($httpCode==200||$httpCode==403){

						$possiblyWritable[]	=	$dir;
						$this->log("Found possible web writable directory $dir, got $httpCode!",0,"light_green");

					}

				}

				$return->setWebDirectories($possiblyWritable);
				$return->setUnixDirectories(explode(',',$this->_config["unix-directories"]));
				$return->setWindowsDirectories(explode(',',$this->_config["win-directories"]));

				if(!sizeof($possiblyWritable)){

					$this->log("No possible default writable web path was found :(",2,"yellow");

				}

				return $return;

			}

		}

	}

?>
