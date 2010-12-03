<?php

	namespace aidSQL\http {

		class Url {

			private	$_url						=	array();
			private	$_variables				=	array();
			private	$_separator				=	"&";
			private	$_equalityOperator	=	"=";

			public function __construct ($url=NULL){

				$this->parse($url);

			}

			public function parse($url=NULL){
	
				if(is_array($url)){
					throw(new \Exception("Array given when String was required!"));
				}

				if(empty($url)){
					throw(new \Exception("URL cant be empty!"));
				}

				$url	= trim($url);
				$url	= rtrim($url,'/');

				$parsedUrl=array();

				if(!preg_match("#://#",$url)){

					$scheme	=	"http";
					$url		=	$scheme."://".$url;

				}else{

					$scheme	=	substr($url,0,strpos($url,":"));

				}

				$parsedUrl["fullUrl"]	=	$url;
				$parsedUrl["scheme"]		=	$scheme;

				$host	=	substr($url,strlen($scheme)+3);

				if(strpos($host,"/")!==FALSE){

					$host	=	substr($host,0,strpos($host,"/"));

				}else{

					$host	=	substr($url,strlen($scheme)+3);

				}

				if(empty($host)){
					throw(new \Exception("Invalid URL!"));
				}

				if(strpos($host,'?')){
					$host	=	substr($host,0,strpos($host,'?'));
				}

				$parsedUrl["host"]		=	$host;

				$path	=	substr($url,strlen($scheme)+3+strlen($host));

				if(strrpos($path,"/")!==FALSE){

					$path = substr($path,0,strrpos($path,"/")+1);

				}else{

					$path	=	"/";

				}

				$parsedUrl["path"]	=	$path;

				if(strrpos($path,"?")!==FALSE){
					$parsedUrl["path"]	=	substr($path,0,strpos($path,"?"));
				}

				$parsedUrl["page"]	=	basename($url);

				if(strpos($url,"?")==FALSE){

					$parsedUrl["query"]	=	"";

				}else{

					$parsedUrl["query"]	=	substr($url,strpos($url,"?")+1);

					$this->addRequestVariables($this->queryStringToArray($parsedUrl["query"]));

					$parsedUrl["page"]	=	substr($parsedUrl["page"],0,strpos($parsedUrl["page"],"?"));

				}

				if($parsedUrl["page"]==$parsedUrl["host"]){
					$parsedUrl["page"]="";
				}


				$this->_url	=	$parsedUrl;

			}

			private function queryStringToArray($queryString=NULL){

				$variables	=	array();

				if(empty($queryString)){
					return $variables;
				}

				$tmpQuery	=	explode($this->_separator,$queryString);

				foreach($tmpQuery as $tmpString){

					$tmpVarValue	=	explode($this->_equalityOperator,$tmpString);
					$variables[$tmpVarValue[0]]	=	(isset($tmpVarValue[1])) ? $tmpVarValue[1] : NULL;

				}

				return $variables;

			}

			public function addRequestVariable($var,$value=NULL){

				$this->_variables[$var]=$value;

			}

			function addRequestVariables(Array $array){

				foreach($array as $k=>$v){
					$this->addRequestVariable($k,$v);
				}

			}

			public function deleteRequestVariable($var){

				if(isset($this->requestVariables[$var])){
					unset($this->requestVariables[$var]);
					return TRUE;
				}

				return FALSE;

			}

			private function parseVariables(){

				$vars = "";

				foreach ($this->_variables as $k=>$v){

					if (is_null($v)){
						$vars .= $k . $this->_separator;
						continue;
					}

					$vars .= $k . $this->_equalityOperator . urlencode($v) . $this->_separator;

				}

				return substr($vars,0,-1);

			}

			public function getQueryAsArray(){
				return $this->_variables;
			}

			public function setSeparator($separator=NULL){

				$this->_separator = $separator;

			}

			public function setEqualityOperator($char=NULL){

				$this->_equalityOperator = $char;

			}

			public function getScheme(){
				return $this->_url["scheme"];
			}

			public function getHost(){
				return $this->_url["host"];
			}

			public function getPath(){
				return $this->_url["path"];
			}

			public function getPage(){
				return $this->_url["page"];
			}

			public function getQueryAsString(){
				return $this->parseVariables();
			}

			public function getURLAsString($parameters=TRUE){

				$full	=	$this->_url["scheme"]."://".$this->_url["host"];
				$path	=	(isset($this->_url["path"]))	?	'/'.trim($this->_url["path"],'/') : '/';
				$page	=	(isset($this->_url["page"]))	?	'/'.trim($this->_url["page"],'/') : NULL;

				if($path=='/'){
					$path=NULL;
				}

				$full	.=	$path.$page;

				if(sizeof($this->_variables)&&$parameters){

					$full	.=	'?'.$this->parseVariables();

				}

				return $full;
				
			}

			public function getURLAsArray(){
				return $this->_url;
			}

			public function getVariables(){
				return $this->_variables;
			}

		}

	}

?>
