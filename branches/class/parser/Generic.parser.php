<?php

	namespace aidSQL\parser {

		abstract class GenericParser implements ParserInterface {

			protected $_content = NULL;

			final public function __construct($content=NULL){

				if(!is_null($content)){
					$this->setContent($content);
				}

			}

			public function getContent(){
				return $this->_content;
			}

			public function setContent($content=NULL){

				if(empty($content)){
					throw(new \Exception("Invalid content provided"));
				}

				$this->_content=$content;

			}

		}

	}

?>
