<?php

	namespace aidSQL\core {

		class Dom {

			private $_content	=	NULL;


			public function __construct($content=NULL){

				$this->setContent($content);

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


			public function fetchImages(){

				return $this->getAttributeFromElements("img","src");

			}			


			public function fetchLinks(){

				$allLinks	=	array();
				$links		=	$this->getAttributeFromElements("a","href");

				if(!sizeof($links)){

					return $allLinks;

				}

				foreach($links as $link){

					if(preg_match("#mailto:#",$link)){

						$mail					=	substr($link,7);

						if(!in_array($mail,$allLinks["mail"])){

							$allLinks["mail"][]	=	$mail;

						}

					}else{

						if(!in_array($link,$allLinks)){

							$allLinks["links"][]	=	$link;

						}

					}

				}

				return $allLinks;

			}


			public function getAttributeFromElements($element,$attribute){

				$return	=	array();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($this->_content);

				$elements	=	$dom->getElementsByTagName($element);

				$return = array();

				if($elements->length > 0){

					foreach($elements as $element){

						$return[]	=	$element->getAttribute($attribute);

					}


				}

				return $return;

			}

		}

	}

?>
