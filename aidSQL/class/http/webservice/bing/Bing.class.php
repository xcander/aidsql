<?php

	namespace aidSQL\http\webservice {

		class Bing{

			private	$_log				=	NULL;
			private	$_httpAdapter	=	NULL;
			private	$_url				=	"http://m.bing.com/search/search.aspx";
			private	$_hosts			=	array();
			private	$_maxPages		=	10;
			
			public function setLog(\aidSQL\LogInterface &$log){
				$this->_log	=	$log;
			}

			public function setMaxPages($int=NULL){

				if(empty($int)){
					throw (new \Exception("Number of pages to be fetch cant be 0!"));
				}

				$this->_maxPages = $int;

			}

			public function getMaxPages(){
				return $this->_maxPages;
			}

			public function __construct(\aidSQL\http\Adapter &$adapter,\aidSQL\LogInterface &$log=NULL){

				if(!is_null($log)){
					$this->setLog($log);
				}

				$this->setHttpAdapter($adapter);

			}

			public function setHttpAdapter(\aidSQL\http\Adapter $adapter){
				$this->_httpAdapter	=	$adapter;
			}

			public function log($msg = NULL){

				if(!is_null($this->_log)){

					$this->_log->setPrepend("[".get_class($this)."]");
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;

				}

				return FALSE;

			}

			public function getHosts($ip=NULL){

				if(ip2long($ip)===FALSE||empty($ip)){
					throw(new \Exception("Invalid IP specified!"));
				}

				return $this->_ip2hosts($ip);

			}

			private function _ip2hosts($ip=NULL){

				$this->_httpAdapter->setUrl($this->_url);
				$this->_httpAdapter->addRequestVariable("A","webresults");
				$this->_httpAdapter->addRequestVariable("Q","ip:".$ip);
				
				$this->log("Searching hosts in ip $ip",0,"white");

				$i			=	0;	
				$x			=	0;
				$hosts	=	array();

				do{

					$this->_httpAdapter->addRequestVariable("PN",$i);
					$this->_httpAdapter->addRequestVariable("SI",$x);

					$x+=10;
					$i+=1;

				}while($this->_getHosts($this->_httpAdapter->fetch())&&$i<$this->_maxPages);

				return $this->_hosts;

			}

			private function _getHosts($content){

				if(empty($content)){
					throw(new \Exception("Empty search results!"));
				}

				$dom		=	new \DomDocument();
				$load		=	@$dom->loadHtml($content);
				$hosts	=	$dom->getElementsByTagName("span");	

				if($hosts->length==0){
					return FALSE;
				}
	

				foreach($hosts as $host){

					if($host->getAttribute("class")!="c2"){
						continue;
					}

					$host			=	$host->nodeValue;
					$slashPos	=	strpos($host,"/");

					if($slashPos!==FALSE){
						$host	=	substr($host,0,$slashPos);
					}

					if(in_array($host,$this->_hosts)){
						continue;
					}
					
					$this->_hosts[]	=	trim($host);

					$this->log("Found host $host",0,"white");

				}

				return TRUE;

			}


		}

	}

?>
