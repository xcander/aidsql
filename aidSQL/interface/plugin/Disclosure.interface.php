<?php

	namespace aidSQL\plugin {

		interface Disclosure{

			public function __construct(\aidSQL\http\Adapter &$httpAdapter, \aidSQL\LogInterface &$log=NULL);
			public function getInfo();
			public function setLog(\aidSQL\LogInterface &$log);
			public function setHttpAdapter(\aidSQL\http\Adapter &$httpAdapter);

		}

	}

?>
