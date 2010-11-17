<?php

	namespace aidSQL\plugin\disclosure {

		interface DisclosurePluginInterface{

			public function __construct(\aidSQL\http\Adapter &$httpAdapter,\aidSQL\parser\CmdLine &$config,\aidSQL\LogInterface &$log=NULL);
			public function getInfo();
			public function setLog(\aidSQL\LogInterface &$log);
			public function setConfig(\aidSQL\parser\CmdLine &$config);
			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter);

		}

	}

?>
