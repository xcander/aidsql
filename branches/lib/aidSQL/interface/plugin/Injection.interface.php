<?php

	namespace aidSQL\plugin\sqli {
		
		interface InjectionPluginInterface {

			public function getPluginName();
			public function setLog(\aidSQL\LogInterface &$log);	//Log setter
			public function log($msg=NULL);				//Log wrapper
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
			public function isRoot($dbUser=NULL);
			public function getShell(\aidSQL\core\PluginLoader &$ploader,Array &$parsedOptions);
			public function setStringEscapeCharacter($escape);
			public function getStringEscapeCharacter();
			public function setQueryConcatenationCharacter($concatChar);
			public function getQueryConcatenationCharacter();
			public function setQueryCommentOpen($commentOpen);
			public function getQueryCommentOpen();
			public function setQueryCommentClose($commentClose);
			public function getQueryCommentClose();
			public function execute($variable,$value);
			public function analyzeInjection($injection);
			public function setAffectedVariable($var,$value);
			public function getAffectedVariable();
			public function setVerbose($boolean);
			public function getVerbose();
			public function setParser(\aidSQL\parser\ParserInterface $parser);
			public function getParser();
			public function getAffectedDatabases();

		}

	}

?>
