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

			public function fetchForms(){

				$return	=	array();
				$dom		=	new \DomDocument();
				$forms	=	$this->fetchTag("form");
				die();	
				@$dom->loadHTML($this->_content);

				$forms	=	$dom->getElementsByTagName("form");

				$return = array();

				$length	=	$forms->length;

				if($length > 0){

					for($i=0;$i<$length;$i++){

						$form			=	$forms->item($i);
						$tmpResult	=	array();

						$tmpResult["method"]			=	$form->getAttribute("method");
						$tmpResult["action"]			=	$form->getAttribute("action");
						$tmpResult["enctype"]		=	$form->getAttribute("enctype");

						$postFields						=	$this->getPostFieldsFromInputs($form);
						//$postFields						=	array_merge($postFields,$this->getPostFieldsFromSelects($form));
						$tmpResult["post_fields"]	=	$postFields;

						$return[]	=	$tmpResult;

					}

				}

				return $return;

			}

			private function getPostFieldsFromInputs($form){

			}

			public function fetchTag($tagName,$attrName=NULL){

				$return	=	array();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($this->_content);

				$tags	=	$dom->getElementsByTagName($tagName);

				$return = array();

				if($tags->length > 0){

					foreach($tags as $tag){

						if(is_null($attrName)){

							$attr			=	$tag->attributes;
							$attributes	=	array();

							foreach($attr as $attribute=>$value){
								$attributes[$attribute]	=	$value;
							}

							$return[]	=	$attributes;
							var_dump($return);

						}else{

							$return[]	=	$tag->getAttribute($attrName);

						}

					}

				}

				return $return;

			}

			private function getPostFieldsFromSelects(){
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

	
			public function getInnerHTML($node){

				$doc = new \DOMDocument();

				foreach ($node->childNodes as $child){

					$doc->appendChild($doc->importNode($child, true));

				}

				return $doc->saveHTML();

			}


		}

	}
	
?>
