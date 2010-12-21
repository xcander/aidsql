<?php

	namespace aidSQL\plugin\disclosure {

		abstract class DisclosurePlugin implements DisclosurePluginInterface {

			protected	$_httpAdapter	=	NULL;
			private		$_log				=	NULL;
			protected	$_config			=	NULL;

			final public function __construct(\aidSQL\http\Adapter &$httpAdapter,\aidSQL\parser\CmdLine &$config, \aidSQL\core\Logger &$log=NULL){

				$this->setHttpAdapter($httpAdapter);

				if(!is_null($log)){
					$this->setLog($log);
				}

				$this->setConfig($config);

			}

			public function setConfig(\aidSQL\parser\CmdLine &$config){
				$this->_config	=	$config;
			}

			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter){
				$this->_httpAdapter	=	$httpAdapter;
			}

			public function setLog(\aidSQL\core\Logger &$log){
				
				$this->_log = $log;

			}

			protected function log($msg=NULL){
				
				if(!is_null($this->_log)){
					$this->_log->setPrepend('['.get_class($this).']');
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
