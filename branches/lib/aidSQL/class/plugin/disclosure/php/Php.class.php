<?php

	namespace aidSQL\plugin\disclosure{

	//Perhaps good for checking http headers ??!?!?!
	//We could also check if there are PHP sessions
	//etc.
	//Could also check for a phpinfo file laying around the server :D
	//This way we could parse it, now where the application is hosted, precisely! :))
	

		class Php extends Disclosure {

			public function getInfo(){
				$this->log("Should get PHP info and stuff",0,"yellow");
			}

		}

	}

?>
