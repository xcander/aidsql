<?php

	namespace aidsql {

		class Crawler {

			private $_host				=	NULL;
			private $_links			=	array();
			private $_httpAdapter	=	NULL;
			private $_content			=	NULL;
			private $_pages			=	array();
			private $_depth			=	5;
			private $_otherSites		=	array();
			private $_scheme			=	NULL;
			private $_emails			=	array();
			private $_omitPaths		=	array();
			private $_omitPages		=	array();
			private $_pageTypes		=	array();
			private $_lpp				=	0;				//Links per page

			public function __construct(\HttpAdapter $httpAdapter){

				if(is_null($httpAdapter->getUrl())){

					throw(new \Exception("URL Must be set in the adapter before passing it to the crawler"));

				}

				$host = $this->parseUrl($httpAdapter->getUrl());
				$this->_host			=	$host;
				$this->_httpAdapter	=	$httpAdapter;
				echo $host["scheme"]."://".$host["host"].$host["path"].$host["page"]."\n";
				$this->_httpAdapter->setUrl($host["scheme"]."://".$host["host"].$host["path"].$host["page"]);

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

				echo $path."\n";

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


			/**
			*Adds an email link, if the link is an email link returns TRUE, else it returns false
			*/

			public function addEmailLink($link){

				if(!preg_match("#mailto:.*#",$link)){
					return FALSE;	
				}

				$mail	= substr($link,strpos($link,":"));

				if(!in_array($mail,$this->_emails)){
					$this->_emails[] = $mail;
				}

				return TRUE;

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
					echo "No links to reduce!\n";
					return $links;
				}

				if($sizeOfLinks < $this->_lpp){
					echo "Amount of links not enough to perform redux!\n";
					return $links;
				}

				echo "Shuffling Links ...\n";

				$shuffled = array_keys($links);
				shuffle($shuffled);

				for($i=0;$i<$this->_lpp;$i++){
					unset($links[$shuffled[$i]]);
				}

				return $links;

			}

			public function parseUrl($url){

				$parsedUrl=array();

				if(!preg_match("#://#",$url)){

					$scheme	=	"http";
					$url		=	$scheme."://".$url;

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

				echo "Got ".sizeof($return)." links ...\n";

				return $return;

			}

			private function addExternalSite($externalSite){

				if(!in_array($externalSite,$this->_otherSites)){

					echo "$externalSite, external site detected adding to other sites list ...\n";
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



			public function crawl($url=NULL,$path=NULL){

				if(empty($path)){
					$path = $this->_host["path"];
				}

				if($this->detectModRewriteFuckUp($path)){
					echo "Mod Rewrite Fuck up detected in $path!\n";
					return FALSE;
				}

				if(!is_null($url)){

					$this->_httpAdapter->setURL($url);

				}

				echo "Crawling ".$this->_httpAdapter->getUrl()." ...  ";

				if($this->isOmittedPath($path)){

					echo "*$path is omitted will NOT fetch content from here!\n";
					return FALSE;

				}

				echo "Fetching content ...\n";
				$content	=	$this->_httpAdapter->fetch();

				if(($httpCode = $this->_httpAdapter->getHttpCode()) != 200){

					echo "Got $httpCode\n";
					return FALSE;

				}else{

					echo "200 OK\n";	

				}

				//Fetches all the links, we are through with this page, hence we have effectively
				//got all links on the given content.

				$links	=	$this->fetchLinks($content);

				//If links per page was specified, then we call the reduxLinks method

				if($this->_lpp>0){
					$links = $this->reduxLinks($links);
				}

				$sizeOfLinks = sizeof($links);

				if(!$sizeOfLinks){
			
					echo "Couldnt find any links in given URL\n";
					return FALSE;

				}

				echo "Found $sizeOfLinks Links to dig in ...\n";

				foreach($links as $link=>$value){

					$linkKey = $this->getLinkKey($link,$path);

					if(isset($this->_links[$linkKey]) && $this->_links[$linkKey]["depth"]++>$this->_depth){
						echo "!!!!!!!!!!!!!DEPTH LIMIT REACHED!!!!!!!!!!!!!!!!!!\n";
						break;
					}

					if(!$this->isValidLink($link)){
						echo "Invalid link found $link\n";
						continue;
					}

					if($this->addEmailLink($link)){
						echo "Email link found $link\n";
						continue;
					}

					if($this->isExternalSite($link)){

						$this->addExternalSite($link);
						continue;

					}

					$fLink = $this->getFullLink($link,$path);

					if(!empty($fLink["page"])){

						if($this->isOmittedPage($path.$fLink["page"])){

							$page = $path.$fLink["page"];
							echo "*$page  was meant to be omitted\n";
							continue;

						}

						if($this->pageHasValidType($fLink["page"])===FALSE){
							echo "Page doesnt matches with given page types\n";
							continue;
						}else{
							echo "Page matches required types ".implode($this->_pageTypes)."\n";
						}

					}

					//Check if the given Linkkey was already Crawled before, if so, check if there are any
					//Different parameters that will be usefull to us.

					if($this->wasCrawled($linkKey)){

						echo "Parsing previously crawled URL, looking for new parameters ...\n";

						try{

							$parameters				=	$this->parseQuery($fLink["query"]);
							$storedParameters		=	array_keys($this->_links[$linkKey]["parameters"]);
							$sizeOfStoredParams	=	sizeof($storedParameters);

							foreach($parameters as $parameter=>$value){

								if($sizeOfStoredParams){

									if(in_array($parameter,$storedParameters)){

										echo "This parameter was already inside\n";
										continue;

									}

								}

								echo "Detected new parameter \"$parameter\"!\n";

								$this->_links[$linkKey]["parameters"][$parameter] = $value;

							}

						}catch(\Exception $e){

							echo $e->getMessage()."\n";

						}

					}else{

						if(!empty($fLink["query"])){

							$parameters	=	$this->parseQuery($fLink["query"]);

							if(!empty($parameters)){

								$key = key($parameters);
								$this->_links[$linkKey]["parameters"][$key] = $parameters[$key];

							}

						}else{

							$this->_links[$linkKey]="";
							$this->_links[$linkKey]["parameters"]="";

						}

						if(!isset($this->_links[$linkKey]["depth"])){
							$this->_links[$linkKey]["depth"]=0;
						}


						if($this->_links[$linkKey]["depth"] < $this->_depth){

							$crawlResult = $this->crawl($fLink["fullUrl"],$fLink["path"]);

							if($crawlResult === FALSE){
								unset($this->_links[$linkKey]);
							}

						}

					}

				}

			}

			public function isValidLink($link){

				if($link=="#"||preg_match("/javascript:/i",$link)){
					return FALSE;
				}
			
				return TRUE;

			}

			public function parseQuery($query=NULL,$separator="&"){

				if(empty($query)){
					throw(new \Exception("Query to be parsed was empty"));
				}

				$params = explode("&",$query);

				if($params!==FALSE&&sizeof($params)){

					if(preg_match("#=#",$query)){

						$param	=	substr($query,0,strpos($query,"="));
						$value	=	substr($query,strpos($query,"=")+1);

						return array($param=>$value);

					}

					return NULL; //No parameters, we are not interested in parameters that probably act as flags or whatever

				}

				foreach($params as $param){

					if(in_array($param,array_keys($parameters))){
						continue;
					}

					$param = substr($param,0,strpos($param,"="));
					$value = substr($param,strpos($param,"=")+1);

					$parameters[$param] = $value;

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

			public function getFullLink($link,$path="/"){

				//Check if its a full link

				if(preg_match("#".$this->_host["host"]."#",$link)){	//Full link

					echo "FULL LINK \n";

					return $this->parseUrl($link);

				}

				echo "RELATIVE!!!!!!!!!!\n";
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

				echo "Levels: $ascendCount\n";

				while($ascendCount--){

					$path	=	substr($path,0,strrpos($path,'/'));
					$link	=	substr($link,strpos($link,'/')+1);
					echo "$ascendCount:$link\n";

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
