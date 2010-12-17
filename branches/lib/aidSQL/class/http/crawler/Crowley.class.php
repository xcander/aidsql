<?php

	namespace aidSQL\http\crawler {

		class Crowley implements \aidSQL\http\Crawler {

			private $_host				=	NULL;
			private $_links			=	array();
			private $_httpAdapter	=	NULL;
			private $_content			=	NULL;
			private $_pages			=	array();
			private $_depth			=	0;
			private $_depthCount		=	0;
			private $_externalUrls	=	array();
			private $_scheme			=	NULL;
			private $_emails			=	array();
			private $_files			=	array();		//PHP, HTM,PDF, TXT other extensions
			private $_omitPaths		=	array();
			private $_omitPages		=	array();
			private $_pageTypes		=	array();
			private $_lpp				=	0;				//Links per page
			private $_log				=	NULL;
			private $_maxLinks		=	0;				//Amount of links desired to crawl

			public function __construct(\aidSQL\http\Adapter $httpAdapter,\aidSQL\LogInterface $log=NULL){

				if(is_null($httpAdapter->getUrl())){

					throw(new \Exception("URL Must be set in the adapter before passing it to the crawler"));

				}

				$this->_host			=	$httpAdapter->getUrl();
				$this->_httpAdapter	=	$httpAdapter;

				if(!is_null($log)){
					$this->setLog($log);
				}

				$this->log("Normalized URL: ".$this->_host->getUrlAsString());

			}

			public function setLog(\aidSQL\LogInterface &$log){

				$this->_log = $log;

			}

			/* Wrapper */

			private function log(){

				if(!is_null($this->_log)){

					$this->_log->setPrepend("[".__CLASS__."]");

					call_user_func_array(array($this->_log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function addPageType($type){

				if(empty($type)){
					throw(new \Exception("Given page type was empty!"));
				}

				if(!in_array($type,$this->_pageTypes)){

					$this->_pageTypes[] = $type;
					return TRUE;

				}

				return FALSE;

			}

			public function addPageTypes(Array $types){

				foreach($types as $type){
					$this->addPageType($type);
				}

			}

			public function pageHasValidType($page){

				if(!sizeof($this->_pageTypes)){
					return NULL;
				}

				$pageType = substr($page,strrpos($page,".")+1);

				if(in_array($pageType,$this->_pageTypes)){
					return TRUE;
				}

				return FALSE;

			}

			public function addOmitPath($path){

				if(empty($path)){
					throw(new \Exception("Given path was empty!"));
				}

				if(!in_array($path,$this->_omitPaths)){

					$this->_omitPaths[] = $path;
					return TRUE;

				}

				return FALSE;

			}

			public function addOmitPaths (Array $paths){

				foreach ($paths as $path){
					$this->addOmitPath($path);
				}

			}

			public function addOmitPage($page=NULL){

				if(empty($page)){
					throw(new \Exception("Given page was empty!"));
				}

				if(!in_array($page,$this->_omitPages)){

					$this->_omitPages[] = $page;
					return TRUE;

				}

				return FALSE;

			}

			public function addOmitPages(Array $pages){

				foreach ($pages as $page){
					$this->addOmitPage($page);
				}

			}

			public function isOmittedPath($path=NULL){

				if(empty($path)){
					throw(new \Exception("Path to be tested cant be empty!"));
				}

				$path = trim($path,"/");

				if(in_array($path,$this->_omitPaths)){
					return TRUE;
				}
	
				return FALSE;

			}


			public function isOmittedPage($page=NULL){

				if(empty($page)){
					throw(new \Exception("Page to be tested cant be empty!"));
				}

				if(in_array($page,$this->_omitPages)){
					return TRUE;
				}
	
				return FALSE;

			}


			public function isEmailLink($link){

				if(!preg_match("#mailto:.*#",$link)){
					return FALSE;	
				}

				return TRUE;

			}

			/**
			*Adds an email link, if the link is an email link returns TRUE, else it returns false
			*/

			public function addEmailLink($link){

				$mail	= substr($link,strpos($link,":"));

				if(!in_array($mail,$this->_emails)){
					$this->_emails[] = $mail;
				}

				return TRUE;

			}


			public function setMaxLinks($amount=0){
				$this->_maxLinks=(int)$amount;
			}

			public function setLinksPerPage($amount=0){

				$amount = (int)$amount;

				if($amount==0){
					throw(new \Exception("Amount of links per page can't be 0"));	
				}

				$this->_lpp = $amount;

			}


			public function getEmailLinks($link){

				return $this->_emails;

			}


			private function reduxLinks(Array $links){

				$sizeOfLinks = sizeof($links);

				if(!$sizeOfLinks){
					$this->log("No links to reduce");	
					return $links;
				}


				if($sizeOfLinks < $this->_lpp){

					$this->log("Amount of links not enough to perform redux!");
					return $links;

				}

				$this->log("Shuffling Links ...");

				$shuffled = array_keys($links);
				shuffle($shuffled);

				for($i=0;$i<$this->_lpp;$i++){
					unset($links[$shuffled[$i]]);
				}

				return $links;

			}


			public function getOtherSites(){
				return $this->_otherSites;
			}

			public function setDepth($depth=5){
				$this->_depth = $depth;
			}

			public function getLinks($onlyWithParameters=FALSE){

				if(!sizeof($this->_links)){
					return $this->_links;
				}

				if($onlyWithParameters){

					$links = array();

					foreach($this->_links as $link=>$params){

						if(isset($params["parameters"])){
							$links[$link] = $params["parameters"];
						}

					}

					return $links;

				}

				return $this->_links;

			}

			private function addExternalSite(\aidSQL\http\Url $extUrl){

				foreach($this->_externalUrls as $ext){

					if($ext->getHost()!=$this->_host->getHost()){

						$this->log("External URL detected ".$ext->getFullUrl($parameters=TRUE),0,"green");
						$this->_externalUrls[$ext->getHost()][] = $ext;
						return TRUE;

					}

				}

				return FALSE;

			}

			public function wasCrawled($linkKey){

				if(isset($this->_links[$linkKey])){
					return TRUE;
				}

				return FALSE;
				
			}

			public function getLinkKey($link,$path){

				$fLink = $this->getFullLink($link,$path);

				if(trim($fLink["path"],"/")==trim($fLink["page"],"/")){

					$linkKey	=	trim($fLink["scheme"]."://".$fLink["host"].$fLink["path"],"/");

				}else{

					$linkKey	=	trim($fLink["scheme"]."://".$fLink["host"].$fLink["path"].$fLink["page"],"/");

				}

				return $linkKey;

			}

			/**
			*Some sites make bad use of mod_rewrite and other server side URL rewriting
			*techniques which can cause the crawler to go into recursion mayhem, hopefully,
			*this function will avoid that kind of recursion.
			*@param String $path only the path, not the hostname
			*@param Int    $fuckLimit count until fuckLimit is reached
			*@return boolean TRUE  The URL is fucked up
			*@return boolean FALSE The URL is not fucked up
			*/

			public function detectModRewriteFuckUp($path,$fuckLimit=2){

				if($fuckLimit==0){
					throw(new \Exception("Fuck limit cant be 0!"));
				}

				$token	=	strtok($path,"/");
				$paths	=	array();
				$fucked	=	0;
				$i			=	0;
				$fuckLimit--;

				for($i=0;($fucked<$fuckLimit)&&($token!==FALSE);$i++){

					$paths[$i]=$token;

					if($i!=0){

						for($x=0;$x<$i;$x++){
							if($paths[$x]==$paths[$i]){
								$fucked++;
							}
						}

					}
			
					$token = strtok("/");

				}

				if($fucked>=$fuckLimit){
					return TRUE;
				}

				return FALSE;

			}

			public function addFile(Array $file){

				$key		= key($file);
				$files	=	array_keys($this->_files);

				if(in_array($key,$files)){
					return FALSE;
				}

				$this->_files[$key]	=	$file[$key];
				return TRUE;

			}

			public function getFiles(){
				return $this->_files;
			}


			private function makeUrls(Array &$uris,$path=NULL){

				foreach($uris as $key=>$uri){

					if(!preg_match("#://#",$uri)){	

						//Means that the uri is relative to the path
						//We *have* to normalize the url passing also the host 

						$path	=	(dirname($uri)=='.')	? $path.$this->_host->getPathSeparator() : NULL;

						$uris[$key]	=	new \aidSQL\http\URL($this->_host->getScheme()."://"				.
																		$this->_host->getHost()							.
																		$this->_host->getPathSeparator()				.
																		$path													.
																		$uri
											);

					}else{

						$uris[$key]	=	new \aidSQL\http\URL($uri);

					}

				}

			}

			public function crawl(\aidSQL\http\URL $url=NULL){

				$this->log($this->drawLine($this->_depthCount++,0,"light_cyan"));

				if($this->_depth>0){
					if($this->_depthCount>$this->_depth){
						return NULL;
					}
				}

				if(!is_null($url)){

					$this->_httpAdapter->setURL($url);

				}else{

					$url	=	$this->_httpAdapter->getUrl();

				}


				if($this->isOmittedPath($url->getPath())){

					$this->log('*'.$url->getPath()." is omitted will NOT fetch content from here!");
					return FALSE;

				}

				$this->log("Fetching content from ".$url->getUrlAsString($parameters=TRUE),0,"light_green");


				try{

					$this->_content	=	new \aidSQL\core\Dom($this->_httpAdapter->fetch());

				}catch(\Exception $e){

					$this->log($e->getMessage(),1,"red");

				}


				if($this->_maxLinks>0){

					if(sizeof($this->_links)>$this->_maxLinks){

						$this->log("Link limit reached!",2,"white");
						return NULL;

					}

				}


				if($this->detectModRewriteFuckUp($url->getPath())){

					$this->log("Possible url rewrite Fuck up detected in $path!");
					return FALSE;

				}


				if(($httpCode = $this->_httpAdapter->getHttpCode()) != 200){

					$this->log("Got $httpCode",1,"red");;
					return FALSE;

				}else{

					$this->log("200 OK",0,"light_green");;

				}


				//Fetches all the links, we are through with this page, hence we have effectively
				//got all links on the given content.

				$images	=	$this->_content->fetchImages();		//Get all the images, image location is important to know
																				//certain DocumentRoot locations in order to get a shell.
																				//This is the case of the mysql5 plugin


				$this->makeUrls($images,$url->getPath());
				$this->filterExternalSites($images);

	
				if(sizeof($images)){

					$this->log("Found ".sizeof($images)." images",0,"light_cyan");

					foreach($images as $img){

						$file		=	$img->getPath().$img->getPathSeparator().$img->getPage();

						if ($this->addFile($this->whatIs($file))){
							$this->log("Add file $file",0,"light_purple");
						}

					}

				}else{
	
					$this->log("No images found",2,"yellow");

				}
			
				//This also returns javascript links and anchors
				//might want to use them in the future.
	
				$links	=	$this->_content->fetchLinks();
				$links	=	$links["links"];	

				$this->makeUrls($links,$url->getPath());	//Foreach URI returned by the content makes a URL Object
				$this->filterExternalSites($links);	//Foreach made URL object takes away the external sites

				$sizeOfLinks = sizeof($links);

				if(!$sizeOfLinks){

					$this->log("No links found",2,"yellow");
					return FALSE;

				}else{

					$this->log("TOTAL Links found: $sizeOfLinks",0,"light_cyan");

				}


				if($this->_lpp>0&&$sizeOfLinks > $this->_lpp){

					$this->log("Reducing links amount to ".$this->_lpp,0,"yellow");
					$links = $this->reduxLinks($links);

				}


				foreach($links as $link=>$value){

					$linkKey	=	$value->getUrlAsString($parameters=FALSE);

					$file		=	trim($value->getPath().$value->getPathSeparator().$value->getPage(),'/');
					$file		=	$this->whatIs($file);

					if(is_array($file)){

						if($this->addFile($file)){

							$this->log("Add file ".$value->getPage()." ...",0,"light_purple");

						}

					}

					$_empty	=	$value->getPage();

					if(!empty($_empty)){

						$page	=	$value->getPath().$value->getPathSeparator().$value->getPage();

						if($this->isOmittedPage($page)){

							$this->log("*$page  was meant to be omitted",0);
							continue;

						}

						if($this->pageHasValidType($value->getPage())===FALSE){
							
							$this->log($value->getPage()." doesnt matches given file types",0,"yellow");
							continue;

						}else{
						
							$this->log("Page \"".$value->getPage()."\" matches required types ".implode($this->_pageTypes,","),0,"light_green");

						}

					}

					//Check if the given Linkkey was already Crawled before, if so, check if there are any
					//different parameters that will be usefull to us.

					if($this->wasCrawled($linkKey)){

						$this->log("Parsing previously crawled URL, looking for new parameters ...",0,"blue");

						$parameters	=	$value->getQueryAsArray();

						if(sizeof($parameters)){

							$storedParameters		=	array_keys($this->_links[$linkKey]["parameters"]);
							$sizeOfStoredParams	=	sizeof($storedParameters);

							foreach($parameters as $parameter=>$value){

								if($sizeOfStoredParams){

									if(in_array($parameter,$storedParameters)){

										$this->log("Parameter $parameter was already inside",0,"yellow");
										continue;

									}

								}

								$this->log("Detected new parameter \"$parameter\"!",0,"cyan");
								$this->_links[$linkKey]["parameters"][$parameter] = $value;

							}

						}else{

							$this->log("No parameters found");

						}

					}else{	//if($this->wasCrawled($linkKey))
					
						$this->_links[$linkKey]=array();

						$parameters	=	$value->getQueryAsArray();

						if(sizeof($parameters)){

							if(!empty($parameters)){

								$key = key($parameters);
								$this->_links[$linkKey]["parameters"][$key] = $parameters[$key];

							}

						}else{

							$this->_links[$linkKey]["parameters"]=array();

						}

						if($this->_depth > 0){

							$crawlResult = $this->crawl($value);
							$this->depthCount	=	0;

							if($crawlResult === FALSE){
								unset($this->_links[$linkKey]);
							}

							if(is_null($crawlResult)){

								$this->log("DEPTH LIMIT FOR $linkKey REACHED!",1,"yellow");

							}

						}

					}

				}

			}

			private function filterExternalSites(Array &$links){

				foreach($links as $key=>$url){

					if($this->isExternalSite($url)){

						if($this->addExternalSite($url)){

							$this->log($url->getHost().", external site detected adding to other sites list ...",0,"purple");

						}

						unset($links[$key]);

					}

				}
			
			}

			private function drawLine($depth){

				$depth = ($depth == 0) ? 1 : $depth;

				$line = "";

				for($i=0;$i<$depth;$i++){
					$line.="-";
				}

				$line.=">";

				return $line;

			}

			private function whatIs($link){

				$bName				=	basename($link);
				$dotPos				=	strrpos($bName,".");
				$return				=	array();

				if(!$dotPos){
					$return[$link] = array("type"=>"path");
					return $return;
				}

				$docExt						=	strtolower(substr($bName,$dotPos+1));
				$return[$link]["type"]	=	$docExt;
				$argPos						=	strpos($docExt,"?");

				if($argPos){

					$return[$link]["arguments"]	=	substr($docExt,$argPos+1);

				}

				return $return;

			}


			public function getHostURL($parse_url){

				if(!isset($parse_url["scheme"])){
					$parse_url["scheme"] = "http";
				}

				return $parse_url["scheme"]."://".$parse_url["host"];

			}

			public function isExternalSite(\aidSQL\http\Url $url){

				$currentHost	=	$this->_host->getHost();
				$givenHost		=	$url->getHost();

				if($currentHost!==$givenHost){
						return TRUE;
				}

				return FALSE;

			}


			private function getRelativePath($link=NULL,$path="/"){

				$link				=	trim($link,"/");
				$path				=	"/".trim($path,"/");
				$token			=	strtok($link,"/");
				$ascendCount	=	0;

				while($token!==FALSE){

					if($token==".."){
						$ascendCount++;
					}

					$token = strtok("/");

				}

				//$this->log("Levels: $ascendCount",0,"green");

				while($ascendCount--){

					$path	=	substr($path,0,strrpos($path,'/'));
					$link	=	substr($link,strpos($link,'/')+1);

				}

				$link = trim($link,".");
				$link = trim($link,"/");

				if(empty($path)){
					$path="/";
				}

				if($path=="/"){
					return $this->parseUrl($this->_host["scheme"]."://".$this->_host["host"].$path.$link);
				}

				return $this->parseUrl($this->_host["scheme"]."://".$this->_host["host"].$path."/".$link);

			}

		}

	}
?>
