<?php

	namespace aidSQL\plugin\disclosure {

		interface DisclosurePluginInterface{

			public function __construct(\aidSQL\http\Adapter &$httpAdapter,Array $config,\aidSQL\core\Logger &$log=NULL);
			public function getInfo();
			public function setLog(\aidSQL\core\Logger &$log);
			public function setConfig(Array $config);
			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter);

		}

	}

?>
