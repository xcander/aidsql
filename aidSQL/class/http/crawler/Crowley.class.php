<?php

	namespace aidSQL\http\crawler {

		class Crowley implements \aidSQL\http\Crawler {

			private $_host				=	NULL;
			private $_links			=	array();
			private $_httpAdapter	=	NULL;
			private $_content			=	NULL;
			private $_pages			=	array();
			private $_depth			=	5;
			private $_otherSites		=	array();
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

				$url = new \aidSQL\http\URL($httpAdapter->getUrl());
				$this->_host			=	$url->getUrlAsArray();
				$this->_httpAdapter	=	$httpAdapter;

				if(!is_null($log)){
					$this->setLog($log);
				}

				$this->log("Normalized URL: ".$url->getUrlAsString());
				$this->_httpAdapter->setUrl($url->getUrlAsString());

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

			public function parseUrl($url=NULL){
	
				if(is_array($url)){
					throw(new \Exception("URL cant be empty"));
				}

				$parsedUrl=array();

				if(!preg_match("#://#",$url)){

					$scheme	=	"http";

					if(!empty($this->_host)){
						$url		=	$scheme."://".$this->_host["host"]."/".$url;
					}

				}else{

					$scheme	=	substr($url,0,strpos($url,":"));

				}

				$parsedUrl["fullUrl"]	=	$url;
				$parsedUrl["scheme"]		=	$scheme;

				$host	=	substr($url,strlen($scheme)+3);

				if(strpos($host,"/")!==FALSE){

					$host	=	substr($host,0,strpos($host,"/"));

				}else{

					$host	=	substr($url,strlen($scheme)+3);

				}

				$parsedUrl["host"]		=	$host;

				$path	=	substr($url,strlen($scheme)+3+strlen($host));

				if(strrpos($path,"/")!==FALSE){

					$path = substr($path,0,strrpos($path,"/")+1);

				}else{

					$path	=	"/";

				}

				$parsedUrl["path"]	=	$path;

				if(strrpos($path,"?")!==FALSE){
					$parsedUrl["path"]	=	substr($path,0,strpos($path,"?"));
				}

				$parsedUrl["page"]	=	basename($url);

				if($parsedUrl["page"]==$parsedUrl["host"]){
					$parsedUrl["page"]="";
				}

				if(strpos($url,"?")==FALSE){

					$parsedUrl["query"]	=	"";

				}else{

					$parsedUrl["query"]	=	substr($url,strpos($url,"?")+1);
					$parsedUrl["page"]	=	substr($parsedUrl["page"],0,strpos($parsedUrl["page"],"?"));

				}

				return $parsedUrl;

			}

			public function getOtherSites(){
				return $this->_otherSites;
			}

			public function setDepth($depth=5){
				$this->_depth = $depth;
			}

			public function setContent($content=NULL){

				if(empty($content)){
					throw (new \Exception(__CLASS__.": Content is empty!"));
				}
				
			}

			
			public function getContent(){
				return $this->_content;
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

			public function addLink($strURL=NULL){

				if(empty($strURL)){
					throw(new \Exception("Link to be added cant be empty!"));
				}
			
				$url	=	new \aidSQL\http\URL($strURL);
				$this->_links[$url->getUrlAsString(FALSE)]["parameters"]	=	$url->getQueryAsArray();

			}

			//Fetches all A elements from a given content

			private function fetchLinks($content=NULL){

				if(empty($content)){
					return array();
				}

				$return	=	array();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($content);

				$links	=	$dom->getElementsByTagName("a");

				$return = array();

				if($links->length > 0){

					foreach($links as $link){

						$href = $link->getAttribute("href");
						$return[$href] = $this->parseUrl($href);

					}


				}

				$this->log("Got ".sizeof($return)." links ...");

				return $return;

			}

			private function fetchImages($content){

				if(empty($content)){
					return array();
				}

				$return	=	array();
				$dom		=	new \DomDocument();

				@$dom->loadHTML($content);

				$images	=	$dom->getElementsByTagName("img");

				$return = array();

				if($images->length > 0){

					foreach($images as $img){

						$src = $img->getAttribute("src");
						$return[$src] = $this->parseUrl($src);

					}

				}

				$this->log("Got ".sizeof($return)." images ...");

				return $return;

			}

			private function addExternalSite($externalSite){

				if(!in_array($externalSite,$this->_otherSites)){

					$this->_otherSites[] = $externalSite;
					return TRUE;

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
			*techniques which can cause the cralwer to go into deep recursion, hopefully,
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

			public function crawl($url=NULL,$path=NULL){

				if($this->_maxLinks>0){
					if(sizeof($this->_links)>$this->_maxLinks){
						$this->log("Link limit reached!",2,"white");
						return NULL;
					}
				}

				if(empty($path)){
					$path = $this->_host["path"];
				}

				if($this->detectModRewriteFuckUp($path)){
					$this->log("Possible url rewrite Fuck up detected in $path!");
					return FALSE;
				}

				if(!is_null($url)){

					$this->_httpAdapter->setURL($url);

				}

				$this->log("Crawling ".$this->_httpAdapter->getUrl()." ...  ",0,"light_green");

				if($this->isOmittedPath($path)){

					$this->log("*$path is omitted will NOT fetch content from here!");
					return FALSE;

				}

				$this->log("Fetching content ...",0,"light_green");
				$content	=	$this->_httpAdapter->fetch();

				if(($httpCode = $this->_httpAdapter->getHttpCode()) != 200){

					$this->log("Got $httpCode",1,"red");;
					return FALSE;

				}else{

					$this->log("200 OK",0,"light_green");;

				}

				//Fetches all the links, we are through with this page, hence we have effectively
				//got all links on the given content.

				$links	=	$this->fetchLinks($content);
				$images	=	$this->fetchImages($content);

				foreach($images as $img){

					if($img["host"]!=$this->_host["host"]){

						if($this->addExternalSite($img["host"])){
							$this->log("$img[host], external site detected adding to other sites list ...",0,"purple");
						}

						continue;

					}

					$fLink	=	$this->getFullLink($img["path"].$img["page"],$path);
					$file		=	$fLink["path"].$fLink["page"];		

					if ($this->addFile($this->whatIs($file))){
						$this->log("Add file $file",0,"light_purple");
					}

				}
				
				//If links per page was specified, then we call the reduxLinks method

				if($this->_lpp>0){
					$links = $this->reduxLinks($links);
				}

				$sizeOfLinks = sizeof($links);

				if(!$sizeOfLinks){
			
					$this->log("Couldnt find any links in given URL",0,"yellow");
					return FALSE;

				}

				$this->log("Found $sizeOfLinks Links to dig in ...",0,"light_cyan");

				foreach($links as $link=>$value){

					$linkKey = $this->getLinkKey($link,$path);

					if(!$this->isValidLink($link)){
						$this->log("Invalid link found $link",0,"red");
						continue;
					}

					if($this->isEmailLink($link)){
						$this->log("Email link found $link",0,"light_green");
						$this->addEmailLink($link);
						continue;
					}

					$pLinkUrl	=	$this->parseUrl($link);

					if($pLinkUrl["host"]!=$this->_host["host"]){

						if($this->addExternalSite($pLinkUrl["host"])){
							$this->log("$pLinkUrl[host], external site detected adding to other sites list ...",0,"purple");
						}

						continue;

					}

					$fLink		=	$this->getFullLink($link,$path);
					$file			=	$this->whatIs($fLink["path"].$fLink["page"]);

					if(is_array($file)){

						if($this->addFile($file)){
							$this->log("Add file $link ...",0,"light_purple");
						}

					}

					if(!empty($fLink["page"])){

						if($this->isOmittedPage($path.$fLink["page"])){

							$page = $path.$fLink["page"];
							$this->log("*$page  was meant to be omitted",0);
							continue;

						}

						if($this->pageHasValidType($fLink["page"])===FALSE){
							
							$this->log("\"$fLink[page]\" doesnt matches given file types",0,"yellow");
							continue;

						}else{
						
							$this->log("Page \"$fLink[page]\" matches required types ".implode($this->_pageTypes,","),0,"light_green");

						}

					}

					//Check if the given Linkkey was already Crawled before, if so, check if there are any
					//Different parameters that will be usefull to us.

					if($this->wasCrawled($linkKey)){

						$this->log("Parsing previously crawled URL, looking for new parameters ...",0,"blue");

						$parameters	=	$this->parseQuery($fLink["query"]);

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

					}else{

						if(!empty($fLink["query"])){

							$parameters	=	$this->parseQuery($fLink["query"]);

							if(!empty($parameters)){

								$key = key($parameters);
								$this->_links[$linkKey]["parameters"][$key] = $parameters[$key];

							}

						}else{

							$this->_links[$linkKey]=array();
							$this->_links[$linkKey]["parameters"]=array();

						}

						if(!isset($this->_links[$linkKey]["depth"])){
							$this->_links[$linkKey]["depth"]=0;
						}


						if($this->_links[$linkKey]["depth"] < $this->_depth){
	
							$this->_links[$linkKey]["depth"]++;

							$this->log($this->drawLine($this->_links[$linkKey]["depth"]),0,"light_cyan");
							$crawlResult = $this->crawl($fLink["fullUrl"],$fLink["path"]);

							if($crawlResult === FALSE){
								unset($this->_links[$linkKey]);
							}

						}else{

							$this->log("DEPTH LIMIT FOR $linkKey REACHED!",1,"yellow");
							break;

						}

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

			private function isValidLink($link){

				if($link=="#"||preg_match("/javascript:/i",$link)){
					return FALSE;
				}
			
				return TRUE;

			}

			private function parseQuery($query=NULL,$separator="&"){

				if(empty($query)){

					$this->log("Query to be parsed was empty",1,"red");
					return NULL;

				}

				$parameters = array();

				$token = strtok($query,$separator);

				while($token!==FALSE){

					if(!strpos($token,"=")){
						continue;
					}

					$param = substr($token,0,strpos($token,"="));
					$value = substr($token,strpos($token,"=")+1);
					$parameters[$param]=$value;

					$token = strtok($separator);

				}

				return $parameters;

			}

			public function getHostURL($parse_url){

				if(!isset($parse_url["scheme"])){
					$parse_url["scheme"] = "http";
				}

				return $parse_url["scheme"]."://".$parse_url["host"];

			}

			public function isExternalSite($link){

				if(preg_match("#://#",$link)){

					$thisSite		=	$this->getHostURL(parse_url($link));
					$currentSite	=	$this->getHostURL($this->_host);

					if($thisSite!==$currentSite){
						return TRUE;
					}

				}

				return FALSE;

			}

			private function getFullLink($link,$path="/"){

				//Check if its a full link

				if(preg_match("#".$this->_host["host"]."#",$link)){	//Full link

					//$this->log("FULL LINK",0,"green");

					return $this->parseUrl($link);

				}

				//$this->log("RELATIVE!!!!!!!!!!",0,"light_green");
				return $this->getRelativePath($link,$path);

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
