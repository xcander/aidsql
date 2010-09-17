<?php

	class aidSQLParser{

		private $_content			= NULL;
		private $_matchString	= NULL;
		private $_openTag			= NULL;
		private $_closeTag		= NULL;

		public function __construct($content=NULL,$openTag=NULL,$closeTag=NULL){

			if(!is_null($content)){
				$this->setContent($content);
			}

		}

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


		public function getMatchString(){
			return $this->_matchString;
		}

		public function setContent($content=NULL){

			if(empty($content)){
				throw(new Exception("Invalid content provided"));
			}

			$this->_content = $content;

		}

		public function getContent(){

			return $this->_content;

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
