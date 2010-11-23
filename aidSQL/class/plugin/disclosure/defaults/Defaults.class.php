<?php

	namespace aidSQL\plugin\disclosure {

		class Defaults extends DisclosurePlugin {

			public function getInfo(){
		
				$return	=	new \aidSQL\plugin\disclosure\DisclosureResult();
	
				$this->log("Trying to discover default directory locations ...",0,"light_cyan");
	
				$directories		=	$this->_config->getParsedOptions();
				$directories		=	explode(',',$directories["directories"]);
				$possiblyWritable	=	array();

				if(!sizeof($directories)){
					throw(new \Exception("Cant use defaults plugin with no default location list!"))	;
				}

				$url	=	new \aidSQL\http\Url($this->_httpAdapter->getUrl());
				$url	=	$url->getScheme()."://".$url->getHost();

				foreach($directories as $dir){

					$this->log("$url/$dir ...",0,"white");
					$this->_httpAdapter->setUrl($url.'/'.$dir);
					$this->_httpAdapter->fetch();

					$httpCode	=	$this->_httpAdapter->getHttpCode();

					if($httpCode==200||$httpCode==403){

						$possiblyWritable[]	=	$dir;
						$this->log("Found possible writable directory $dir, got $httpCode!",0,"light_green");

					}

				}

				$return->setDirectories($possiblyWritable);

				if(!sizeof($possiblyWritable)){
					$this->log("No possible default writable location was found :(",2,"yellow");
				}

				return $return;

			}

		}

	}

?>
