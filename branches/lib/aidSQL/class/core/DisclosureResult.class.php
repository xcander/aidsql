<?php

	namespace aidSQL\plugin\disclosure {

			class DisclosureResult {
				
				private	$_info	=	array();

				public function __call($method,$args){	

					$method		=	strtolower($method);
					$setGet		=	substr($method,0,3);
					$property	=	substr($method,3);

					switch($method){

						case "set":
							$this->_info[$property]	=	$args;
							break;

						case "get":

							if(!isset($this->_info[$property])){
								return NULL;
							}

							return $this->_info[$property];

							break;

					}

				}

			}

	}

?>
