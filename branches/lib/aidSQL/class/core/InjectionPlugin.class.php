<?php

	namespace aidSQL\plugin\sqli {

		abstract class InjectionPlugin implements InjectionPluginInterface {

			private $_stringEscapeCharacter			=	NULL;
			private $_queryConcatenationCharacter	=	NULL;
			private $_queryCommentOpen					=	NULL;
			private $_queryCommentClose				=	NULL;
			private $_table								=	NULL;
			private $_verbose								=	FALSE;
			private $_log									=	NULL;

			protected $_httpAdapter						=	NULL;
			protected $_httpCode							=	NULL;
			protected $_affectedVariable				=	Array();
			protected $_injectionAttempts				=	40;
			protected $_parser							=	NULL;
			protected $_dbUser							=	NULL;


			public final function __construct(\aidSQL\http\Adapter $adapter,aidSQL\LogInterface &$log=NULL){

				if(!$adapter->getRequestVariables()){
					throw(new \Exception("Unable to perform injection without any request variables set in the http adapter!"));
				}

				$this->_httpAdapter = $adapter;

				if(!is_null($log)){
					$this->setLog($log);
				}

			}

			/* Wrapper */

			public function setLog(\aidSQL\LogInterface &$log){
				$this->_log = $log;
			}

			public function log($msg = NULL){

				if(!is_null($this->_log)){

					$this->_log->setPrepend("[".get_class($this)."]");
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			/**
			*Good for decoupling execution with injection string generation
			*/

			public function execute($variable,$value){

				$this->_httpAdapter->addRequestVariable($variable,$value);

				$this->log("Fetching ".$this->_httpAdapter->getFullUrl());

				$content				=	$this->_httpAdapter->fetch();
				$this->_httpCode	=	$this->_httpAdapter->getHttpCode();

				if($this->_httpCode!==200){

					return FALSE;

				}

				if($this->_verbose){
					$this->log($content);
				}

				return $content;

			}

			public function setStringEscapeCharacter($escape=NULL){

				if(empty($escape)){
					throw(new Exception("String escape character cannot be empty!"));
				}

				$this->_stringEscapeCharacter = $escape;

			}

			public function setHttpAdapter(aidSQL\http\Adapter $adapter){
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

				if(empty($commentOpen)){
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

			public function setAffectedVariable($var,$value){

				$this->_affectedVariable = array("variable"=>$var,"value"=>$value);

			}

			public function getAffectedVariable(){
				return $this->affectedVariable;
			}


			/**
			*This for now, only stands for outputting the results from URL execution to stdout
			*/

			public function setVerbose($boolean=TRUE){
				$this->_verbose = $boolean;
			}

			public function getVerbose(){
				return $this->_verbose;
			}

			public function setParser(\aidSQL\parser\ParserInterface $parser){
				$this->_parser = $parser;
			}

			public function getParser($requiredParser=NULL){

				if(!is_null($requiredParser)){

					$allParsers	= $this->listParsers();

					$flag = FALSE;

					foreach($allParsers as $theParser){

						if(strtolower($theParser) == strtolower($requiredParser)){

							$parserClassName	= "\\aidSQL\\parser\\$theParser";
							$parserInstance	= new $parserClassName();
							$this->setParser($parserInstance);
							$flag = TRUE;
							break;

						}

					}

					if(!$flag){
						throw (new \Exception("$requiredParser parser was not found in class/parser/TagMatcher.parser.php!"));
					}

				}

				return $this->_parser;

			}


			/**
			 *	Performs directory listing on the parser directory to find out which parsers
			 * are available.
			 * @return Array Parsers as string
			 */
			public function listParsers(){

					$dir	= __CLASSPATH."/parser/";
					$dp	= opendir($dir);

					$baseParser = "Generic.parser.php";
					$parserList = array();

					while($file = readdir($dp)){

						if(is_dir($file)||$file==$baseParser||preg_match("/^[.]/",$file)){
							continue;
						}

						$class = substr($file,0,strpos($file,"."));
						$parserList[] = $class;

					}

					closedir($dp);

					return $parserList;

			}

		}

	}

?>
