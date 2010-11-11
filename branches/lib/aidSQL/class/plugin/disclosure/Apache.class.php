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

				$info	=	array("error"=>NULL,"http_code"=>NULL);

				$info = $this->generate404(); //Attempt to generate a 404 request

				if(empty($info["error"])&&$info["http_code"]==200){

					$info["mod_rewrite"] = TRUE;

					$extensions	=	array("jpeg","html","php","phtml","cgi");

					foreach($extensions as $ext){

						$info	=	$this->generate404(".".$ext);	//Try to generate 404 (URI Length exceeded)

						if($info["error"]){
							break;
						}

					}

				}


				if(!$info["error"]){
					$info	=	$this->generate414();	//Try to generate 414 (URI Length exceeded)
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

			private function getBaseURL(){

				if(isset($this->_url)){
					return $this->_url;
				}

				$url	=	$this->_httpAdapter->getUrl();

				if(empty($url)){
					throw(new \Exception("Cannot disclose Apache information with an adapter that has no URL set!"));
				}

				return $this->_url	=	substr($url,strpos($url,"/")+2);

			}

			public function generate404($extension=NULL){

				$url	=	$this->getBaseUrl();

				$this->log("Trying to generate 404 (Not Found)",0,"white");
				$fakeUrl	=	"http://".$url."/".substr(md5(rand(1,time())),0,rand(0,32));

				if(!is_null($extension)){
					$fakeUrl.=$extension;
				}

				$this->log("Setting URL to $fakeUrl");

				$this->_httpAdapter->setUrl($fakeUrl);

				$result	=	FALSE;

				$this->_httpAdapter->fetch();

				$httpCode	=	$this->_httpAdapter->getHttpCode();

				if($httpCode == 404){

					$this->log("Got 404 :)",0,"light_cyan");
					$result = $this->parseError($this->_httpAdapter->fetch());
	
					if($result==FALSE){
						$this->log("Couldnt get any banner :(",2,"yellow");
					}

				}else{

					$this->log("Got $httpCode instead of 404 :/",2,"yellow");

				}
		
				return array(
							"error"=>$result,
							"http_code"=>$httpCode
				);

			}

			public function generate414(){

				$url		=	$this->getBaseUrl();
				$result	=	FALSE;

				$this->log("Trying to generate 414 (URI Length Exceeded)",0,"white");

				$start	=	mt_rand(1,1000);

				while($start < 5000){

					$crap		=	\str_repeat("%00", $start);
					$start	+=	mt_rand(1,100);

					$fakeUrl	=	"http://".$url."/".$crap;
					$this->_httpAdapter->setUrl($fakeUrl);
					$this->log("Request length $start ...",0,"white");
					$this->_httpAdapter->fetch();

					$httpCode	=	$this->_httpAdapter->getHttpCode();
					$this->log("Got $httpCode instead of 414 :(",2,"yellow");
					if($httpCode==414){

						$this->log("Got 414 :)!",0,"light_cyan");
						$result = $this->parseError($this->_httpAdapter->fetch());
						break;

					}

				}

				return array(
							"error"=>$result,
							"http_code"=>$httpCode
				);

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
