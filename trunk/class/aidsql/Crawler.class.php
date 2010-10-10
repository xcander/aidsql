<?php

	namespace aidsql {

		class Crawler {

			private $_url				=	NULL;
			private $_links			=	array();
			private $_httpAdapter	=	NULL;
			private $_content			=	NULL;

			public function __construct(\HttpAdapter $httpAdapter){

				if(is_null($httpAdapter->getUrl())){

					throw(new \Exception("URL Must be set in the adapter before passing it to the crawler"));

				}

				$this->_httpAdapter = $httpAdapter;

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

			private function fetchLinks(){

				$content	=	$this->_httpAdapter->fetch();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($content);

				$links = $dom->getElementsByTagName("a");

				$return = array();

				foreach($links as $link){

					$href = $link->getAttribute("href");
						
					if(!in_array($href,$return)){

						$return[] = parse_url($href);

					}

				}

				return $return;

			}

			public function crawl(){

				$links = $this->fetchLinks();

				foreach($links as $link){

					if(!isset($link["query"])){
						continue;
					}
	
					if(!in_array($link["query"],$this->_links)){

						$params = explode("&",$link["query"]);

						//Cuidado aca porque evitamos todos los otros parametros que puedan haber!
						$this->_links[$link["path"]]	=	implode(",",$params);

					}

				}

			}

		}

	}

?>
