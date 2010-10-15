<?php

	namespace aidsql {

		class Crawler {

			private $_host				=	NULL;
			private $_links			=	array();
			private $_httpAdapter	=	NULL;
			private $_content			=	NULL;
			private $_pages			=	array();
			private $_depth			=	5;
			private $_otherSites		=	array();
			private $_curPath			=	"/";
			private $_scheme			=	NULL;
			private $_emails			=	array();

			public function __construct(\HttpAdapter $httpAdapter){

				if(is_null($httpAdapter->getUrl())){

					throw(new \Exception("URL Must be set in the adapter before passing it to the crawler"));

				}

				$host = $this->parseUrl($httpAdapter->getUrl());
				$this->_host			=	$host;
				$this->_httpAdapter	=	$httpAdapter;

			}


			/**
			*Adds an email link, if the link is an email link returns TRUE, else it returns false
			*/

			public function addEmailLink($link){

				if(!preg_match("#mailto:.*#",$link)){
					return FALSE;	
				}

				$mail	= substr($link,strpos($link,":"));

				if(!in_array($mail,$this->_emails)){
					$this->_emails[] = $mail;
				}

				return TRUE;

			}

			public function getEmailLinks($link){

				return $this->_emails;

			}

			public function parseUrl($url){

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

				$parsedUrl["host"]		=	$host;

				$path	=	substr($url,strlen($scheme)+3+strlen($host));

				if(strrpos($path,"/")!==FALSE){

					$path = substr($path,0,strrpos($path,"/")+1);

				}else{

					$path	=	"/";

				}

				$parsedUrl["path"]		=	$path;
				$parsedUrl["page"]		=	basename($url);

				if($parsedUrl["page"]==$parsedUrl["host"]){
					$parsedUrl["page"]="";
				}

				if(strpos($url,"?")==FALSE){

					$parsedUrl["query"]	=	"";

				}else{

					$parsedUrl["query"]	=	substr($url,strpos($url,"?")+1);
					$parsedUrl["page"]	=	substr($parsedUrl["page"],0,strpos($parsedUrl["page"],"?"));

				}

				return $parsedUrl;

			}

			public function getOtherSites(){
				return $this->_otherSites;
			}

			public function setDepth($depth=5){
				$this->_depth = $depth;
			}

			public function setContent($content=NULL){

				if(empty($content)){
					throw (new \Exception(__CLASS__.": Content is empty!"));
				}
				
			}

			
			public function getContent(){
				return $this->_content;
			}

			public function getLinks($onlyWithParameters=FALSE){

				if(!sizeof($this->_links)){
					return $this->_links;
				}


				if($onlyWithParameters){

					$links = array();

					foreach($this->_links as $link=>$params){

						if(isset($params["parameters"])){
							$links[$link] = $params["parameters"];
						}

					}

					return $links;

				}

				return $this->_links;

			}


			//Fetches all A elements from a given content

			private function fetchLinks($content=NULL){

				if(empty($content)){
					return FALSE;
				}

				$return	=	array();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($content);

				$links	=	$dom->getElementsByTagName("a");

				foreach($links as $link){

					$href = $link->getAttribute("href");
					$return[$href] = parse_url($href);

				}

				echo "Got ".sizeof($return)." links ...\n";

				return $return;

			}

			private function addExternalSite($externalSite){

				if(!in_array($externalSite,$this->_otherSites)){

					echo "$externalSite, external site detected adding to other sites list ...\n";
					$this->_otherSites[] = $externalSite;
					return TRUE;

				}

				return FALSE;

			}

			public function crawl($url=NULL,$path="/"){

				$this->_curPath = $path;

				if(!is_null($url)){

					$this->_httpAdapter->setURL($url);

				}

				echo "Crawling ".$this->_httpAdapter->getUrl()." ...  ";

				$content	=	$this->_httpAdapter->fetch();

				if(($httpCode = $this->_httpAdapter->getHttpCode()) != 200){
					echo "Got $httpCode\n";
					return;
				}else{
					echo "200 OK\n";	
				}

				//Fetches all the links, we are through with this page, hence we have effectively
				//got all links on the given content.

				$links	=	$this->fetchLinks($content);
				$depth	=	0;

				while($depth++<$this->_depth){

					echo ">DEPTH: $depth\n";

					foreach($links as $link=>$value){

						if(!$this->isValidLink($link)){
							echo "Invalid link found $link\n";
							continue;
						}

						if($this->addEmailLink($link)){
							echo "Email link found $link\n";
							continue;
						}

						if($this->isExternalSite($link)){

							$this->addExternalSite($link);
							continue;

						}

						$fLink = $this->getFullLink($link,$path);

						if($fLink===FALSE){
							continue;
						}

						$fLink = $this->parseUrl($fLink);

						if($fLink["path"]==$fLink["page"]){

							$linkKey	=	trim($fLink["path"],"/");

						}else{

							$linkKey	=	trim($fLink["path"].$fLink["page"],"/");

						}

						$linkKey = empty($linkKey) ? "/" : $linkKey;

						if(!isset($this->_links[$linkKey])){

							if(!empty($fLink["query"])){

								$parameters				=	$this->parseQuery($fLink["query"]);

								if(!is_null($parameters)){

									$key = key($parameters);
									$this->_links[$linkKey]["parameters"][$key] = $parameters[$key];

								}

							}else{
								$this->_links[$linkKey]="";
							}

							$this->crawl($fLink["fullUrl"],$fLink["path"]);

						}else{

							try{

								$parameters				=	$this->parseQuery($fLink["query"]);
								$storedParameters		=	array_keys($this->_links[$linkKey]["parameters"]);
								$sizeOfStoredParams	=	sizeof($storedParameters);

								foreach($parameters as $parameter=>$value){

									if($sizeOfStoredParams){

										if(in_array($parameter,$storedParameters)){

											continue;

										}

									}

									$this->_links[$linkKey]["parameters"][$parameter] = $value;

								}

							}catch(\Exception $e){

								echo $e->getMessage()."\n";

							}

						}

					}

				}

			}

			public function isValidLink($link){

				if($link=="#"||preg_match("/javascript:/",$link)){
					return FALSE;
				}
			
				return TRUE;

			}

			public function parseQuery($query=NULL,$separator="&"){

				if(empty($query)){
					throw(new \Exception("Query to be parsed was empty"));
				}

				$params = explode("&",$query);

				if($params!==FALSE&&sizeof($params)){

					if(preg_match("#=#",$query)){

						$param	=	substr($query,0,strpos($query,"="));
						$value	=	substr($query,strpos($query,"=")+1);

						return array($param=>$value);

					}

					return NULL; //No parameters, we are not interested in parameters that probably act as flags or whatever

				}

				foreach($params as $param){

					if(in_array($param,array_keys($parameters))){
						continue;
					}

					$param = substr($param,0,strpos($param,"="));
					$value = substr($param,strpos($param,"=")+1);

					$parameters[$param] = $value;

				}

				return $parameters;

			}

			public function getHostURL($parse_url){

				if(!isset($parse_url["scheme"])){
					$parse_url["scheme"] = "http";
				}

				return $parse_url["scheme"]."://".$parse_url["host"];

			}

			public function isExternalSite($link){

				if(preg_match("#://#",$link)){

					$thisSite		=	$this->getHostURL(parse_url($link));
					$currentSite	=	$this->getHostURL($this->_host);

					if($thisSite!==$currentSite){
						return TRUE;
					}

				}

				return FALSE;

			}


			public function getFullLink($link,$path="/"){

				if(substr($link,0,3) == "../"){

					//Be able to determine how many levels deep does the link go

					echo "UHhhh FIX ME!!!!!!!! $link\n";
					return FALSE;

				}

				$host		=	$this->_host["host"];
				$regex	=	"#".$host."#";

				//Check if its a full link

				if(preg_match($regex,$link)){	//Full link

					if(!preg_match("#^".$this->_host["scheme"]."#",$link)){
						return $this->_host["scheme"]."://".$link;
					}

					return $link;

				}

				//Relative link

				$link = trim($link,"/");

				if(stristr($link, $path) === FALSE) {
					return $this->_host["scheme"]."://".$host.$path.$link;
				}

				return $this->_host["scheme"]."://".$host.$path.$link;

			}

		}

	}

?>
