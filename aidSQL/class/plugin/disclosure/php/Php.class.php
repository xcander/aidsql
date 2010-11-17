<?php

	//Perhaps good for checking http headers ??!?!?!
	//We could also check if there are PHP sessions
	//etc.
	//Could also check for a phpinfo file laying around the server :D
	//This way we could parse it, now where the application is hosted, precisely! :))
	

	namespace aidSQL\plugin\disclosure{

		class Php implements \aidSQL\plugin\Disclosure {

			private	$_log				=	NULL;
			private	$_httpAdapter	=	NULL;

			public function __construct(\aidSQL\http\Adapter &$httpAdapter, \aidSQL\LogInterface &$log=NULL){

				$this->setHttpAdapter($httpAdapter);

				if(!is_null($log)){
					$this->setLog($log);
				}

			}

			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter){
				$this->_httpAdapter	=	$httpAdapter;
			}

			private function log($msg=NULL){

				if(!is_null($this->_log)){
					$this->_log->setPrepend('['.__CLASS__.']');
					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function setLog(\aidSQL\LogInterface &$log){
				$this->_log = $log;
			}

			public function getInfo(){
			}

		}

	}

?>
