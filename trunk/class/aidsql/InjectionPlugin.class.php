<?php

	abstract class InjectionPlugin implements InjectionPluginInterface {

		private $_stringEscapeCharacter			= NULL;
		private $_queryConcatenationCharacter	= NULL;
		private $_queryCommentOpen					= NULL;
		private $_queryCommentClose				= NULL;
		private $_table								= NULL;
		private $_httpAdapter						= NULL;

		public function setStringEscapeCharacter($escape=NULL){

			if(empty($escape)){
				throw(new Exception("String escape character cannot be empty!"));
			}

			$this->_stringEscapeCharacter = $escape;

		}

		public function setHttpAdapter(HttpAdapter $adapter){
			$this->_httpAdapter = $adapter;
		}

		public function getHttpAdapter(){
			return $this->_httpAdapter;
		}

		public function setTable($table=NULL){

			if(empty($table)){
				throw (new Exception("The table name cant be empty!"));
			}

			$this->_table = $table;

		}

		public function getTable(){
			return $this->_table;
		}

		public function getStringEscapeCharacter(){

			return $this->_stringEscapeCharacter;

		}

		public function setQueryConcatenationCharacter($concatChar=NULL){

			if(empty($concatChar)){
				throw(new Exception("String escape character cannot be empty!"));
			}

			$this->_queryConcatenationCharacter = $concatChar;

		}

		public function getQueryConcatenationCharacter(){
			return $this->_queryConcatenationCharacter;
		}

		public function setQueryCommentOpen($commentOpen=NULL){

			if(empty($concatChar)){
				throw(new Exception("Query comment open character cannot be empty!"));
			}

			$this->_queryCommentOpen = $commentOpen;

		}

		public function getQueryCommentOpen(){
			return $this->_queryCommentOpen;
		}

		public function setQueryCommentClose($commentClose=NULL){

			if(empty($commentClose)){
				throw(new Exception("Query comment close character cannot be empty!"));
			}

			$this->_queryCommentClose = $commentClose;

		}

		public function getQueryCommentClose(){
			return $this->_queryCommentClose;
		}

	}

?>
