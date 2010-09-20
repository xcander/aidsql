<?php

	/**
	 * This is a basic parser that matches whatever is between self::_closeTag
	 * and self::_openTag
	 *
	 * @see setOpenTag
	 * @see setCloseTag
	 */

	namespace aidSQL\parser {

		class TagMatcher extends GenericParser {

			private $_openTag			= NULL;
			private $_closeTag		= NULL;

			public function setOpenTag($string=NULL){

				if(empty($string)){
					throw(new Exception("Open tag String cannot be empty!"));
				}

				$this->_openTag=$string;
			}

			public function setCloseTag($string=NULL){

				if(empty($string)){
					throw(new Exception("Close tag String cannot be empty!"));
				}

				$this->_closeTag=$string;

			}

			public function getResult(){

				$regex = '/'.$this->_openTag."+.*".$this->_closeTag.'/';

				$matches = NULL;
				//var_dump($this->_content);
				preg_match_all($regex, $this->_content,$matches,PREG_SET_ORDER);

				if(sizeof($matches)){

					$matching = array();

					foreach($matches as $key=>$match){

						$match=$match[0];
						$match = preg_replace("/^($this->_openTag)/",'',$match);
						$match = preg_replace("/($this->_closeTag)/",'',$match);
						$matching[$key]=$match;
					}

					return $matching;

				}

				return FALSE;

			}


		}

	}
