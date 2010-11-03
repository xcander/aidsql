<?php
	
	namespace aidSQL {

		class Nmap {

			private $_content	=	NULL;

			public function setContent($content){
				$this->_content	=	$content;
			}

			public function getContent(){
				return $this->_content;
			}

			public function getResult(){
				
				$dom	=	new \DomDocument();

				if(@$dom->loadXML($this->_content)===FALSE){
					throw(new \Exception("Invalid Nmap xml file provided!"));
				}

				$hosts	=	$dom->getElementsByTagName("host");

				$return	=	array();

				foreach($hosts as $host){

					$childNodes	=	$this->_getChildNodes($host);
					var_dump($childNodes);
					die();

				}

				return $return;
				
			}	


			private function _getChildNodes(\DomNode $curNode){

				$tmpArray	=	array();

				if($curNode->hasChildNodes()){

					$childNodes	=	$curNode->childNodes;

					for($i=0;$i < $childNodes->length;$i++){

						$curNode						=	$childNodes->item($i);
						$curNodeName				=	$curNode->nodeName;

						$attributes					=	$this->_getNodeAttributes($curNode);

						$tmpArray[$curNodeName]["attributes"]	=	$attributes;

						if($curNode->hasChildNodes()){
							$tmpArray[$curNodeName]["childs"] = $this->_getChildNodes($curNode);
						}

					}

				}else{

					$curNodeName				=	$curNode->nodeName;
					$tmpArray[$curNodeName]	=	$this->_getNodeAttributes($curNode);

				}

				return $tmpArray;

			}

			private function _getNodeAttributes(\DomNode $node){

				if(!sizeof($node->attributes)){
					return NULL;
				}

				$attributes		=	$node->attributes;
				$attrArray		=	array();

				foreach($attributes as $index=>$attr){
					$attrArray[$attr->name]	=	$attr->value;
				}

				return $attrArray;

			}
			
		}

	}

?>
