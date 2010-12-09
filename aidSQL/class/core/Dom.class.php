<?php

	namespace aidSQL\core {

		class Dom {

			private $_content	=	NULL;


			public function __construct($content=NULL){

				$this->setContent($content);
				
			}
			
			//Fetches all A elements from a given content

			public function fetchLinks(){

				$links	=	$this->fetchTag('a',"href");

				$javascripts	=	Array();
				$anchors			=	Array();
				$pureLinks		=	Array();
				$emails			=	Array();

				foreach($links as $link){
				
					if(preg_match("/javascript\:/i",$link)){

						if(!in_array($link,$javascripts)){

							$javascripts[]=$link;

						}

					}elseif(preg_match("/^#.*/",$link)){

							if(!in_array($link,$anchors)){

								$anchors[]=$link;

							}

					}elseif(preg_match("/^mailto\:/i",$link)){

						if(!in_array($link,$emails)){

							$emails[]=$link;

						}
						
					}else{

						if(!in_array($link,$pureLinks)){

							$pureLinks[]=$link;

						}

					}

				}

				return array(
					"javascript"	=>	$javascripts,
					"anchors"		=>	$anchors,
					"links"			=>	$pureLinks,
					"emails"			=>	$emails
				);

			}

			//Fetches all img elements from a given content

			public function fetchImages(){

				return $this->fetchTag("img","src");

			}


			public function fetchTag($tagName,$attrName){

				$return	=	array();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($this->_content);

				$tags	=	$dom->getElementsByTagName($tagName);

				$return = array();

				if($tags->length > 0){

					foreach($tags as $tag){

						$attrValue	=	$tag->getAttribute($attrName);
						$return[]	=	$attrValue;

					}

				}

				return $return;

			}

			public function setContent($content=NULL){

				if(empty($content)){
					throw (new \Exception(__CLASS__.": Content is empty!"));
				}

				$this->_content	=	$content;
				
			}

			public function getContent(){
				return $this->_content;
			}

		}

	}
	
?>
