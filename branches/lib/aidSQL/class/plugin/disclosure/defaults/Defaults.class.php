<?php

	namespace aidSQL\plugin\disclosure {

		class Defaults implements \aidSQL\plugin\Disclosure {

			private	$_httpAdapter	=	NULL;
			private	$_httpFuzzer	=	NULL;
			private	$_log				=	NULL;
			private	$_url				=	NULL;

			public function __construct(\aidSQL\http\Adapter &$httpAdapter, \aidSQL\LogInterface &$log=NULL){

				$this->setHttpAdapter($httpAdapter);

				if(!is_null($log)){
					$this->setLog($log);
				}

				if(!class_exists("\\aidSQL\\http\\Fuzzer")){	//This shouldnt be here, its just a temporary fix

					$class	=	 __CLASSPATH."class".DIRECTORY_SEPARATOR."http".DIRECTORY_SEPARATOR."Fuzzer.class.php";
					require $class;

				}

				$this->_httpFuzzer	=	new \aidSQL\http\Fuzzer($httpAdapter,$log);

			}

			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter){
				$this->_httpAdapter	=	$httpAdapter;
			}

			public function setLog(\aidSQL\LogInterface &$log){
				
				$this->_log = $log;

			}

			private function log($msg=NULL){
				
				if(!is_null($this->_log)){
					$this->_log->setPrepend('['.__CLASS__.']');
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function getInfo(){

				die($config."\n");

			}

		}

	}

?>
