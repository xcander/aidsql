<?php

	//CopyRight: Juan Pablo Stange (11-06-2008)
	//3 Clause BSD License
	//http://code.google.com/p/uoogle

	//:set tabstop=3 #for viewing with vi or vim :)

	class GoogleSearch { 

		private	$baseUrl		=	'http://ajax.googleapis.com/ajax/services/search/web';
		private	$rawQuery	=	NULL;
		private	$pQuery		=	NULL;
		private	$result		=	NULL;
		private	$fullUrl		=	NULL;
		private	$errMsg		=	NULL;
		private	$pagerUrl	=	NULL;

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


		public function setBaseUrl($url=NULL){

			if(is_null($url)||!preg_match('#^http\:\/\/.*$#',$url)){ 
				throw (new Exception("Url must begin with HTTP:// and must not be NULL -> {$url} <- Was given"));
			}

			$this->baseUrl	=	$url;	

		}


		public function setErrorMessage($booboo=NULL){

			$this->errMsg	=	$booboo;

		}


		public function setQuery($query=NULL) {

			if(empty($query)||is_null($query)) throw (new Exception("Search query must not be null or empty"));

			$this->rawQuery	=	$query;

		}



		private function parseSearchQuery () { 

			$queryPart	=	explode(' ',$this->rawQuery);
			$final		=	NULL;

			foreach($queryPart as $part) {

				$final	.=	urlencode($part) .'+';

			}

			$final	=	substr($final,0,-1);

			$this->pQuery	=	$final;

		}



		public function getResult(){
		
			return $this->result;

		}


		private function createFullUrl() {

			$fullUrl	=	NULL;

			$fullUrl =	$this->baseUrl			.											//Base URL
							'?v='.$this->version	.											//Version
							'&q='.$this->pQuery	.											//Search Query
							(($this->language) ? "&hl={$this->language}" : '') .	//Is it language specific?
							(($this->gLKey)    ? "&key={$this->gLKey}"   : '') .	//Do we have a developer key?
							(($this->rsz)	    ? "&rsz={$this->rsz}"     : ''); 	//Whats the result size (large | small) ?


			//For allowing the pager class to do its thing

			$this->pagerUrl	 =	"{$fullUrl}&start=%s";

			$fullUrl	.= (($this->start)    ? "&start={$this->start}" : '');	//Do we have a start offset ? 
							
			return $this->fullUrl	=	$fullUrl;

		}


		public function getFullUrl(){

			return $this->fullUrl;

		}


		public function doGoogleSearch() { 

			$this->parseSearchQuery();
			$this->createFullUrl();


			$curl		=	curl_init();

			curl_setopt($curl,CURLOPT_URL           ,$this->fullUrl);
			curl_setopt($curl,CURLOPT_AUTOREFERER   ,1             );
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,1             );
			curl_setopt($curl,CURLOPT_HEADER        ,0             );

			$result = curl_exec($curl);

			curl_close($curl);

			$this->result	=	json_decode($result);

			if (!is_object($this->result)) {

				$dflErr	=	'Couldnt retrieve any result using ' . $this->fullUrl;	
				$msg	=	(!$this->errMsg) ? $dflErr : $this->errMsg;
				throw (new \Exception($msg));

			}

			if ($this->result->responseStatus !== 200) { 

				$dflErr = "[ERROR {$this->result->responseStatus} ]\n [DETAILS] {$this->result->responseDetails}\n [DATA] {$this->result->responseData}\n";
				$msg	=	(!$this->errMsg) ? $dflErr : $this->errMsg;
				throw (new \Exception($msg));

			}

			$this->result->pagerUrl = "{$this->pagerUrl}";

			return $this->result;

		}

		public function setAPIVersion ($version=NULL){

			if(empty($version)) throw (new Exception("Version number must no be empty"));

			$this->version	=	$version;

		}



		public function setStart($start=NULL){

			if(!is_int($start)){
				throw (new Exception("Start number must be an integer"));
			}

			$this->start	=	$start;

		}


		public function setLanguage ($lang=NULL){

			if(empty($lang)||is_null($lang)||strlen($lang)>2) throw (new Exception("Search language must not be null or empty and must be 2 characters long ->{$lang}<- was given"));
			$this->language	=	$lang;

		}



		public function setGoogleLicenseKey ($glKey=NULL) {

			if(empty($gLKey)||is_null($gLKey)) throw (new Exception("Google License Key  must not be null or empty"));
			$this->siteKey	=	NULL;	 

		}



		public function setResultSize($rsz=NULL)	{

			switch (strtolower(trim($rsz))) {

				case 'big':
				case 'large':
				case 'gross':
				case 'grande':
					return $this->rsz='large';
					break;

				case 'tiny':
				case 'small':
				case 'klein':
				case 'chico':
					return $this->rsz='small';
					break;


				default:
					throw (new Exception("Invalid Result size provided, valid result sizes: {big,large,gross,grande}  | {tiny,small,klein,chico}"));
					break;

			}

			return FALSE;

		}

	}

?>
