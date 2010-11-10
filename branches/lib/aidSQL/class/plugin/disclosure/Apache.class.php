<?php

	namespace aidSQL\plugin\disclosure {

		class Apache implements \aidSQL\plugin\Disclosure {

			private	$_log				=	NULL;
			private	$_httpAdapter	=	NULL;
			private	$_url				=	NULL;

			public function __construct(\aidSQL\http\Adapter &$httpAdapter, \aidSQL\LogInterface &$log=NULL){

				$this->setHttpAdapter($httpAdapter);

				if(!is_null($log)){
					$this->setLog($log);
				}

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

				$this->getBaseURL();

				//Attempt to generate a 404 request

				$_404 = $this->generate404();

				if(sizeof($_404)){

					$info	=	$_404;

				}else{

					//Attempt to generate a 512 request
					$_512 = $this->generate512();
					$info	=	$_512;

				}

				if(sizeof($info)){

					$isApache	=	FALSE;

					foreach($info as $inf){

						$this->log("Got information $inf!",0,"light_green");

						if(preg_match("#apache#i",$inf)){

							$this->log("Server *seems* to be Apache",0,"light_green");
							$isApache	=	TRUE;

						}

					}

					if(!$isApache){

						$this->log("Unfortunately this server seems not to be Apache :(",2,"yellow");
						return FALSE;

					}

				}

			}

			public function getOperatingSystem(){

			}

			private function getBaseURL(){

				if(isset($this->_url)){
					return $this->_url;
				}

				$url	=	$this->_httpAdapter->getUrl();

				return $this->_url	=	substr($url,strpos($url,"/")+2);

			}

			public function generate404(){

				$url	=	$this->getBaseUrl();

				$this->log("Trying to generate 404 (NOT FOUND)",0,"white");
				$this->_httpAdapter->setUrl("http://".$url."/".md5(rand(0,time())));

				$result	=	array();

				if($this->_httpAdapter->getHttpCode()=="404"){	//Who knows!
					
					$result = $this->parseError($this->_httpAdapter->fetch());

				}else{

					$this->log("You've just won the lottery or something! ".$this->_httpAdapter->getUrl(),2,"yellow");

				}

				return $result;

			}

			public function generate512(){

				$url		=	$this->getBaseUrl();
				$result	=	array();

				$this->log("Trying to generate 512",0,"white");

				try{

					$start	=	1000;

					while($start < 5000){

						$this->log("Request length $start ...",0,"white");

						$crap		=	\str_repeat("%00", $start);
						$start	+=	100;

						$this->_httpAdapter->setUrl("http://".$url."/".md5(rand(0,time())));

						if($this->_httpAdapter->getHttpCode()=="512"){
							$result = $this->parseError($this->_httpAdapter->fetch());
							break;
						}

					}


				}catch(\Exception $e){

					$this->log($e->getMessage(),0,"rojo");

				}

				return $result;

			}

			private function parseError($errorHTML){

				$items	=	array();

				$dom		=	new \DomDocument();
				$dom->loadHTML($errorHTML);
				
				$server	=	$dom->getElementsByTagName("address");

				if($server->length){

					$i = 0;

					while($item	=	$server->item($i++)){

						$items[]	=	$item->nodeValue;

					}

				}

				return $items;

			}

		}

	}

?>
