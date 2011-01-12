<?php

	namespace aidSQL\core {

		class DatabaseSchema {

				private $_tables	=	array();

				public function addTable($table,Array $fields){

					$this->_tables[$table]	=	$fields;

				}

				public function getSchema(){
					return $this->_tables;
				}

		}

	}

?>
