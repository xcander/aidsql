<?php

	interface InjectionPluginInterface {

		public function select();
		public function count();
		public function setTable();
		public function getTables();
		public function getColumns();
		public function getDatabase();
		public function getUser();
		public function getUserPrivileges();
		public function getVersion();
		public function isVulnerable();
		public function setStringEscapeCharacter();
		public function getStringEscapeCharacter();
		public function setQueryConcatenationCharacter();
		public function getQueryConcatenationCharacter();
		public function setQueryCommentOpen();
		public function getQueryCommentOpen();
		public function setQueryCommentClose();
		public function getQueryCommentClose();
		public function execute($variable,$value);
		public function analyzeInjection($injection);
		public function setAffectedVariable($var,$value);
		public function getAffectedVariable();
		public function setVerbose($boolean);
		public function getVerbose();

		
	}

?>
