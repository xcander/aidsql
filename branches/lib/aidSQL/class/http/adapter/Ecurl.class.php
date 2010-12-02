<?php

	namespace aidSQL\http\Adapter {

		class Ecurl implements \aidSQL\http\Adapter{

			protected	$current				= NULL;
			private		$preSeparator		= '?';
			private		$separator			= '&';
			private		$equalityOperator	= '=';
			private		$cookie				= NULL;
			private		$curlOptions		= array();
			private		$url					= NULL;
			private		$handler				= NULL;
			private		$content				= NULL;
			private		$requestVariables	= array();
			private		$requestInterval	= 0;
			private		$method				= NULL;
			private		$transferInfo		= NULL;
			private		$connectRetry		=	20;	//Put in config and interfaces!!!!!

			private		$proxy				= array(
				"server"		=>NULL,
				"port"		=>80,
				"user"		=>"",
				"password"	=>"",
				"auth"		=>"BASIC",
				"type"		=>"HTTP",
				"tunnel"		=>0
			);

			private		$log					=	NULL;

			public function __construct($url=NULL,$setCurlDefaults=TRUE){

				$this->setHandler(curl_init());

				if(!is_null($url)){
					$this->setUrl($url);
				}

				if($setCurlDefaults === TRUE){

					$this->setCurlDefaults();

				}

			}

			public function setConnectRetry($int=20){

				if(!is_int($int)){
					throw(new \Exception("Connect retry should be an integer"));
				}

				$this->connectRetry	=	$int;	

			}

			public function getConnectRetry($int=20){
				return $this->connectRetry;
			}

			public function setConnectTimeout($timeout=0){

				$this->setCurlOption('CONNECTTIMEOUT',(int)$timeout);

			}

			public function getConnectTimeout(){

				return $this->getCurlOption('CONNECTTIMEOUT');

			}

			public function setCookieFile($cookie="/tmp/cookie"){

				$this->cookie=$cookie;

			}

			public function getCookieFile(){

				return $this->cookie;

			}


			public function setProxyServer($server){

				$this->proxy["server"] = $server;

			}

			public function setProxyTunnel($boolean){
				$this->proxy["tunnel"] = $boolean;
			}

			public function setProxyPort($port){

				$port = (int)$port;

				if(!$port){
					throw(new \Exception("Invalid proxy port specified"));
				}

				$this->proxy["port"] = $port;

			}


			public function setProxyUser($user){
				$this->proxy["user"]=$user;
			}

			public function setProxyPassword($password){
				$this->proxy["password"] = $password;
			}

			public function getProxyPort(){

				return (int)$this->proxy["port"];

			}

			public function getProxyServer(){

				return $this->proxy["server"];

			}

			public function setSeparator($separator=NULL){

				$this->separator = $separator;

			}

			public function setPreSeparator($char=NULL){

				$this->preSeparator=$char;

			}

			public function setEqualityOperator($char=NULL){

				$this->equalityOperator = $char;

			}

			public function setProxyAuth($auth="BASIC"){

				$auth = strtoupper($auth);

				switch($auth){
					case "NTLM":
					case "BASIC":
						$this->proxy["auth"] = $auth;
					break;
					default:
							throw(new \Exception("Invalid authentication method ->$auth<-"));
						break;
				}

			}

			public function setProxyType($type="HTTP"){

				$type = strtoupper($type);

				switch($type){

					case "HTTP":
					case "SOCKS5":
						$this->proxy["type"] = $type;
					break;

					default:
							throw(new \Exception("Invalid proxy type specified ->$type<- should be http or socks5"));
					break;

				}

			}

			public function setCurlDefaults(){

				$default = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
				$this->setBrowser($default);
				$this->setMethod('POST');
				$this->setCurlOption('FOLLOWLOCATION',TRUE);
				$this->setCurlOption('HEADER',FALSE);
				$this->setCurlOption('RETURNTRANSFER',TRUE);

				//For avoiding https verification
				$this->setCurlOption("SSL_VERIFYPEER",FALSE);
				$this->setCurlOption("SSL_VERIFYHOST",FALSE);

				//Avoid URL caching mechanisms
				$this->setCurlOption("FORBID_REUSE",TRUE);
				$this->setCurlOption("FRESH_CONNECT",TRUE);

			}


			//Setea la peticion a POST o GET

			public function setMethod ($method=NULL){

				switch ($method=strtoupper(trim($method))){

					case 'POST':
					case 'GET' :
						$this->method = $method;
						break;

					default:
						$msg = "Invalid method specified -> ". var_export ($method) ." <-, method can only be one of POST or GET";
						throw (new \Exception($msg));
						break;

				}

				return TRUE;

			}

			public function setBrowser($browser=NULL){

				if(is_null($browser)){
					$browser = "eCurl 0.1";
				}

				$this->setCurlOption('USERAGENT',$browser);

			}

			public function setUrl($url=NULL){

				if (is_null($url)||empty($url)){

					throw (new \Exception('The specified URL was NULL or empty'));

				}

				$url=trim($url);

				$ending = substr($url,-1);

				$this->url = $url;

			}

			public function getUrl(){

				return $this->url;

			}


			public function getRequestVariables(){
				return $this->requestVariables;
			}


			function getFullURL(){

				$vars = $this->parseRequestVariables();

				if($vars){
					$vars = "?$vars";
				}

				$url	=	new \aidSQL\http\Url($this->getUrl());

				return $url->getUrlAsString().$vars;

			}

			public function parseRequestVariables(){

				$vars = "";

				foreach ($this->requestVariables as $k=>$v){

					if (is_null($v)){
						$vars .= $k . $this->separator;
						continue;
					}

					$vars .= $k . $this->equalityOperator . urlencode($v) . $this->separator;

				}

				return substr($vars,0,-1);

			}


			public function setCurlOption ($option=NULL,$value=NULL){

				$value = trim($value);

				$option = strtoupper($option);

				$option = preg_replace ('#\s#','_',$option);

				$option = $this->parseCurlOption($option);

				$this->curlOptions[$option]=$value;

				if(is_null($this->handler)){

					throw (new \Exception("Could not set option $option, there's no cURL handler set!"));

				}

				return curl_setopt($this->handler,constant($option),$value);

			}

			private function parseCurlOption($option){

				$option = (!preg_match('#^CURLOPT_.*#',$option)) ? 'CURLOPT_'.$option : $option;

				return strtoupper($option);

			}

			public function getCurlOptions(){

				return $this->curlOptions;

			}

			public function getCurlOptionsAsString(){

				$options = $this->getCurlOptions();

				$strOptions = NULL;
				$strOptions.= "Curl Options:\n";
				$strOptions.= "-------------\n";

				foreach ($options as $opt=>$val){

					$strOptions.= $opt . " => ". $val."\n";

				}

				return $strOptions;

			}

			public function getProxyOptions(){
				return $this->proxy;
			}

			public function addRequestVariable($var,$value=NULL){

				$this->requestVariables[$var]=$value;

			}

			public function setRequestInterval($interval=0){
					  $this->requestInterval = $interval;
			}

			public function getRequestInterval(){
					  return $this->requestInterval;
			}

			private function setHandler($handler){

				$this->handler=$handler;

			}


			public function getHandler(){

				return $this->handler;

			}

			private function setContent($content){

				$this->content = $content;

			}

			public function getContent(){

				return $this->content;

			}

			function addRequestVariables(Array $array){

				foreach($array as $k=>$v){
					$this->addRequestVariable($k,$v);
				}

			}

			public function getProxyAuth(){

				if(is_null($this->proxy["auth"])){
					$this->proxy["auth"]="BASIC";
				}

				return $this->proxy["auth"];
			}

			private function configureProxy(){

				if(empty($this->proxy["server"])){
					return FALSE;
				}

				$this->setCurlOption("PROXY",$this->proxy["server"]);
				$this->setCurlOption("PROXYPORT",$this->proxy["port"]);

				if(!empty($this->proxy["user"])){

					$userPassword = $this->proxy["user"].":".$this->proxy["password"];
					$this->setCurlOption("PROXYUSERPWD",$userPassword);

					$authType = $this->getProxyAuth();
					$authValue = constant("CURLAUTH_".$authType);

				}

				if($this->proxy["tunnel"]){
					$this->setCurlOption("HTTPPROXYTUNNEL",TRUE);
				}

			}

			public function fetch(){

				$this->configureProxy();

				if(!is_null($this->getCookieFile())){

					$this->setCurlOption("COOKIE",$this->getCookieFile());
					$this->setCurlOption("COOKIEJAR",$this->getCookieFile());

				}

				if($this->method=="POST"){

					$this->setCurlOption("POSTFIELDS",$this->parseRequestVariables());
					$this->setCurlOption('URL',$this->getUrl());

				}else{

					$this->setCurlOption('URL',$this->getFullURL());

				}

				if((int)$this->requestInterval > 0){
					sleep($this->requestInterval);
				}

				$connect	=	0;

				do{

					$content					= curl_exec($this->handler);
					$this->transferInfo	= curl_getinfo($this->handler);

					$this->setContent($content);

					$errno	=	curl_errno($this->handler);
					$error	=	curl_error($this->handler);
					$connect++;

					if($connect>0&&$errno){
						$this->log("$error, attempting reconnect ... $connect",1,"red");
					}

				} while($connect < $this->connectRetry && $errno > 0);


				if($errno){

					throw (new \Exception($error));

				}

				return $this->getContent();

			}

			public function getCurlOption($getCurlOption){

				$getCurlOption = $this->parseCurlOption($getCurlOption);

				$options = $this->getCurlOptions();

				foreach($options as $opt=>$value){

					if($opt == $getCurlOption){

						return $value;

					}

				}

				return NULL;

			}

			public function setLog(\aidSQL\LogInterface &$log){

				$this->_log=$log;

			}

			public function log($msg = NULL){

				if(!is_null($this->_log)){

					$this->_log->setPrepend("[".get_class($this)."]");
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function getTransferInfo(){
				return $this->transferInfo;
			}

			public function getHttpCode(){
				return $this->transferInfo["http_code"];
			}

		}

	}
?>
