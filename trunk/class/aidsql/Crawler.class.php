<?php

	namespace aidsql {

		class Crawler {

			private $_host				=	NULL;
			private $_links			=	array();
			private $_httpAdapter	=	NULL;
			private $_content			=	NULL;
			private $_pages			=	array();
			private $_depth			=	5;

			public function __construct(\HttpAdapter $httpAdapter){

				if(is_null($httpAdapter->getUrl())){

					throw(new \Exception("URL Must be set in the adapter before passing it to the crawler"));

				}

				$this->_httpAdapter = $httpAdapter;

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

			public function getLinks(){
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

				return $return;

			}



			public function crawl($page=NULL){

				if(!empty($page)){

					$this->_httpAdapter->setUrl($page);

				}else{

					$url		=	$this->_httpAdapter->getUrl();
					$parsed	=	parse_url($url);

					if(!isset($parsed["scheme"])){

						$url = "http://".$url;
						$parsed = parse_url($url);

					}

					if(!isset($parsed["host"])){
						$parsed["host"] = substr($url,strpos($url,"/")+1);
					}

					$baseName = NULL;

					if(isset($parsed["path"])){

						$baseName = basename($parsed["path"]);

						if(preg_match("/\./",$baseName)){

							$parsed["path"] = substr($parsed["path"],0,strlen($baseName)*-1);

						}else{

							$baseName = NULL;

						}

						if(!is_null($baseName)){
							$page = $baseName;
						}

					} else {

						$parsed["path"] = "/";

					}

					$this->_host	= $parsed["scheme"]."://".$parsed["host"];

					$this->_httpAdapter->setUrl($this->_host.$parsed["path"].$page);

				}

				echo "Crawling ".$this->_httpAdapter->getUrl()." ...\n";

				$content	=	$this->_httpAdapter->fetch();

				//Fetches all the links, we are through with this page, hence we have effectively
				//got all links on the given content.

				$links	=	$this->fetchLinks($content);
				$depth	=	0;

				while($depth++<$this->_depth){

					foreach($links as $link=>$value){

						if($link=="#"||preg_match("/javascript/",$link)){
							continue;
						}

						if(!isset($value["path"])){
							$value["path"] = "/";
						}

						if(!isset($value["query"])){
							$value["query"] = "";
						}

						$site = parse_url($link);

						if(preg_match("#://#",$link)){

							$isAnotherSite = $site["scheme"]."://".$site["host"];

							if($isAnotherSite!==$this->_host){

								echo $this->_host."!== $isAnotherSite is not on the same host, skipping\n";
								continue;

							}

						}else{

							$currentUrl = parse_url($page);

							$path = NULL;

							if(isset($currentUrl["path"])){

								if(preg_match("#/.*/#",$currentUrl["path"])){
									$path = $currentUrl["path"];
								}

							}

							$link = $this->_host."/".$path.trim($link,"/");

						}

						if(!isset($this->_links[$value["path"]]["parameters"])){

							$this->_links[$value["path"]]["parameters"][] = $value["query"];
							$this->crawl($link);	

						}else{
							$this->_links[$value["path"]]["parameters"][] = $value["query"];
						}

					}

				}

			}

		}


	}

?>
