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
				$info["os"]	=	$this->getOperatingSystem();

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
				$content	=	$this->_httpAdapter->fetch();

				$dom	=	new \DomDocument();
				$dom->loadHTML($content);
				$server	=	$dom->getElementsByTagName("address");

				if($server->length){

					$i = 0;

					while($item = $server->item($i++)){
						$this->log ($item->nodeValue);
					}

				}else{

					return FALSE;

				}

			}

			public function generate512(){

				$url	=	$this->getBaseUrl();

				$this->log("Trying to generate 512",0,"white");

				$this->_httpAdapter->setUrl("http://".$url."/".md5(rand(0,time())));
				$content	=	$this->_httpAdapter->fetch();

				$dom	=	new \DomDocument();
				$dom->loadHTML($content);
				$server	=	$dom->getElementsByTagName("address");

				if($server->length){

					$i = 0;

					while($item = $server->item($i++)){
						$this->log ($item->nodeValue);
					}

				}else{

					return FALSE;
					
				}

			}

		}

	}

?>
