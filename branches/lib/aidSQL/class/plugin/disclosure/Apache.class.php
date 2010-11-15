<?php

	namespace aidSQL\plugin\disclosure {

		class Apache implements \aidSQL\plugin\Disclosure {

			private	$_httpAdapter	=	NULL;
			private	$_httpFuzzer	=	NULL;
			private	$_log				=	NULL;
			private	$_url				=	NULL;

			public function __construct(\aidSQL\http\Adapter &$httpAdapter, \aidSQL\LogInterface &$log=NULL){

				$this->setHttpAdapter($httpAdapter);

				if(!is_null($log)){
					$this->setLog($log);
				}

				if(!class_exists("\aidSQL\http\Fuzzer")){	//This shouldnt be here, its just a temporary fix

					$class	=	 __CLASSPATH."class".DIRECTORY_SEPARATOR."http".DIRECTORY_SEPARATOR."Fuzzer.class.php";
					require $class;

				}

				$this->setHttpFuzzer(new \aidSQL\http\Fuzzer($httpAdapter),$log);

			}

			public function setHttpFuzzer(\aidSQL\http\Fuzzer &$fuzzer){
				$this->_httpFuzzer	=	$fuzzer;
			}

			public function getHttpFuzzer(){
				return $this->_httpFuzzer;
			}

			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter){
				$this->_httpAdapter	=	$httpAdapter;
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

			public function getInfo(){

				$info	=	array("error"=>NULL,"http_code"=>NULL);

				$info = $this->_httpFuzzer->generate404(); //Attempt to generate a 404 request

				if(empty($info["error"])&&$info["http_code"]==200){

					$info["mod_rewrite"] = TRUE;

					$extensions	=	array("jpeg","html","php","phtml","cgi");

					foreach($extensions as $ext){

						$info	=	$this->_httpFuzzer->generate404(".".$ext);	//Try to generate 404 (URI Length exceeded)

						if($info["error"]){
							break;
						}

					}

				}

				if(!$info["error"]){
					$info	=	$this->_httpFuzzer->generate414();	//Try to generate 414 (URI Length exceeded)
				}

				$apacheInfo	=	array();

				if($info["error"]){

					$this->log("Got banner $info[error]",0,"light_green");

					if(preg_match("#apache#i",$info["error"])){

						$this->log("Server *seems* to be Apache",0,"light_green");
						$apacheInfo["version"]	=	$this->getApacheVersion($info["error"]);
						$apacheInfo["os"]			=	$this->getOperatingSystem($info["error"]);

					}else{

						$this->log("Unfortunately this server seems not to be Apache :(",2,"yellow");

					}

				}else{

					$this->log("Couldnt disclose any information regarding to Apache :/",2,"yellow");

				}

				return $apacheInfo;

			}

			public function getApacheVersion($info){

				$token	=	strtok($info," ");
				$version	=	FALSE;

				while($token!==FALSE){

					$pos	=	strpos($token,"/");

					if($pos!==FALSE){

						$version	=	substr($token,$pos+1);
						break;

					}

					$token = strtok(" ");

				}
	
				return $version;

			}

			public function getOperatingSystem($info){

				$token	=	strtok($info," ");
				$OS		=	FALSE;

				while($token!==FALSE){

					$pos	=	strpos($token,"(");
					$pos2	=	strpos($token,")");

					if($pos!==FALSE&&$pos2!==FALSE){

						$OS	=	substr($token,$pos+1,$pos2-1);
						break;

					}

					$token = strtok(" ");

				}
	
				return $OS;

			}


			private function parseError($errorHTML){

				$item		=	FALSE;
				$dom		=	new \DomDocument();

				$dom->loadHTML($errorHTML);
				
				$server	=	$dom->getElementsByTagName("address");

				if($server->length){

					$item	=	$server->item(0)->nodeValue;

				}

				return $item;

			}

		}

	}

?>
