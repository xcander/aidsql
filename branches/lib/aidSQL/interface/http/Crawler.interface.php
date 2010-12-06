<?php

	namespace aidSQL\http {
		interface Crawler {
			public function crawl();
			public function getLinks($withParameters=TRUE);
		}
	}
?>
