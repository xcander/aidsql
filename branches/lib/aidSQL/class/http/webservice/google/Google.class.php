<?php

	//CopyRight: Juan Pablo Stange (11-06-2008)
	//Modified: 04-11-2010	(just added namespaces and changed some other stuff)
	//3 Clause BSD License
	//http://code.google.com/p/uoogle

	//:set tabstop=3 #for viewing with vi or vim :)

	namespace aidsql\http\webservice {

		class Google { 

			private	$baseUrl				=	'http://ajax.googleapis.com/ajax/services/search/web';
			private	$searchQuery		=	NULL;
			private	$result				=	NULL;
			private	$url					=	NULL;
			private	$httpAdapter		=	NULL;
			private	$log					=	NULL;

			//protected $gLKey:	Google license key. This is a valid license. Get your own license, by going to www.google.com/api

			//protected $rsz			:	This optional argument supplies the number of results that the application would like to recieve. 
			//									A value of small indicates a small result set size or 4 results. A value of large indicates a large 
			//									result set or 8 results. If this argument is not supplied, a value of small is assumed.

			//protected $language	:	This optional argument supplies the host language of the application making the request. 
			//									If this argument is not present then the system will choose a value based on the value of the Accept-Language http header.
			//									If this header is not present, a value of en is assumed.

			//protected $start		:	This optional argument supplies the start index of the first search result. 
			//									Each successful response contains a cursor object (see below) which includes an array of pages.
			//									The start property for a page may be used as a valid value for this argument.
	

			protected $gLKey		=	NULL;
			protected $rsz			=	'large';
			protected $version	=	'1.0';
			protected $language	=	NULL;
			protected $start		=	NULL;

			public function __construct(\aidSQL\http\Adapter &$adapter=NULL,\aidSQL\LogInterface &$log=NULL){

				$this->url	=	new \aidSQL\http\URL($this->baseUrl);

				if(!is_null($log)){

					$this->setLog($log);
					$this->log("Google search engine started");

				}

				if(!is_null($adapter)){
					$this->setHttpAdapter($adapter);
				}
	
			}

			public function log($msg=NULL){

				if(!is_null($this->log)){
					call_user_func_array(array($this->log, "log"),func_get_args());
					return TRUE;
				}

				return FALSE;

			}

			public function setLog(\aidSQL\LogInterface &$log){

				$this->log = $log;
				$log->setPrepend('['.__CLASS__.']');

			}

			public function setHttpAdapter(\aidSQL\http\Adapter &$adapter){

				$this->httpAdapter = $adapter;

			}

			public function setBaseUrl($url=NULL){

				$this->url	=	new \aidSQL\http\Url($url);	

			}

			public function setQuery($query=NULL) {

				if(empty($query)){
					throw (new Exception("Search query must not be empty"));
				}

				$this->log("Setting search query to \"$query\"",0,"white");
				$this->searchQuery	=	$query;

			}

			public function getUrl(){

				return $this->url;

			}

			public function search() { 

				$this->log("Getting results ...",0,"white");

				$searchQuery	=	preg_replace("/ /","%20",$this->searchQuery);
				$this->url->addRequestVariable('v',$this->version);
				$this->url->addRequestVariable('q',$searchQuery,FALSE);

				($this->language) ?	$this->url->addRequestVariable("hl",$this->language)	:	NULL;
				($this->gLKey)	 	?	$this->url->addRequestVariable("key",$this->gLKey)		:	NULL;
				($this->rsz)		?	$this->url->addRequestVariable("rsz",$this->rsz)		:	NULL;
				($this->start)		?	$this->url->addRequestVariable("start",$this->start)	:	NULL;


				$this->httpAdapter->setUrl($this->url);
				$this->httpAdapter->setMethod("GET");

				$result = $this->httpAdapter->fetch();
				$this->result	=	json_decode($result);

				if (!is_object($this->result)) {

					$msg	=	"Query {$this->searchQuery}  didnt retrieved any results";
					throw (new \Exception($msg));

				}

				if ($this->result->responseStatus !== 200) { 

					$msg = "[ERROR {$this->result->responseStatus} ] [DETAILS] {$this->result->responseDetails} [DATA] {$this->result->responseData}";
					throw (new \Exception($msg));

				}

				return $this->result;

			}

			public function setAPIVersion ($version=NULL){

				if(empty($version)){
					throw (new Exception("Version number must no be empty"));
				}

				$this->version	=	$version;

			}



			public function setStart($start=NULL){

				$this->log("Setting start offset to $start",0,"white");
				$this->start	=	(int)$start;

			}


			public function setLanguage ($lang=NULL){

				if(empty($lang)||is_null($lang)){
					throw (new Exception("Search language must not be empty"));
				}

				$this->log("Setting language to $lang",0,"light_cyan");

				$this->language	=	$lang;

			}

			public function setGoogleLicenseKey ($glKey=NULL) {

				if(empty($gLKey)||is_null($gLKey)){
					throw (new Exception("Google License Key must not be null or empty"));
				}

				$this->log("Setting license key to $glKey",0,"white");

				$this->siteKey	=	NULL;	 

			}


			public function setResultSize($rsz=NULL)	{

				switch (strtolower(trim($rsz))) {

					case 'big':
					case 'large':
						$this->log("Setting result size to $rsz",0,"white");
						return $this->rsz='large';
					break;

					case 'tiny':
					case 'small':
						$this->log("Setting result size to $rsz",0,"white");
						return $this->rsz='small';
					break;


					default:
						throw (new Exception("Invalid Result size provided, valid result sizes: {big,large}  | {tiny,small}"));
					break;

				}

				return FALSE;

			}

		}

	}

?>
