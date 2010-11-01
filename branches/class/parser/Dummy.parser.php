<?php

	namespace aidSQL\parser {

		class DummyParser extends GenericParser {

			public function getResult(){
				echo "Im dumb and I'll say that everything is FALSE!";
				return FALSE;
			}

		}

	}

?>
