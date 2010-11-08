<?php
	
	namespace aidSQL\parser {

		class Nmap {

			public function parseXMLFile(\aidSQL\core\File $file){
			
				$content	=	$file->getFile();

				$file->isUsable();
	
				$dom	=	new \DomDocument();

				if(@$dom->loadXML(file_get_contents($content))===FALSE){
					throw(new \Exception("Invalid Nmap xml file provided!"));
				}

				$hosts	=	$dom->getElementsByTagName("host");

				$return	=	array();

				foreach($hosts as $host){

					$return[]	=	$this->_getChildNodes($host);

				}

				return $return;
				
			}	


			private function _getChildNodes(\DomNode $curNode){

				$tmpArray	=	array();

				if($curNode->hasChildNodes()){

					$childNodes	=	$curNode->childNodes;

					for($i=0;$i < $childNodes->length;$i++){

						$curNode			=	$childNodes->item($i);
						$curNodeName	=	$curNode->nodeName;
						$nodeValue		=	"";

						if($curNodeName == "#text"){
							continue;
						}

						$attributes					=	$this->_getNodeAttributes($curNode);

						if(!empty($nodeValue)){
							$tmpArray[$curNodeName]["value"]	=	$nodeValue;
						}

						if(sizeof($attributes)){
							foreach($attributes as $key=>$value){
								$tmpArray[$curNodeName][$key]=$value;
							}
						}

						if($curNode->hasChildNodes()){

							$childs	=	$this->_getChildNodes($curNode);

							foreach($childs as $key=>$value){
								$tmpArray[$curNodeName][$key]=$value;
							}

						}

					}

				}else{

					if($curNodeName == "#text"){
						return $tmpArray;
					}

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
