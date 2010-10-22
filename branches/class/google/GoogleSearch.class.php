<?php

	//CopyRight: Juan Pablo Stange (11-06-2008)
	//3 Clause BSD License
	//http://code.google.com/p/uoogle

	//:set tabstop=3 #for viewing with vi or vim :)


	class googleSearch extends googleOptions { 

		private	$baseUrl		=	'http://ajax.googleapis.com/ajax/services/search/web';
		private	$rawQuery	=	NULL;
		private	$pQuery		=	NULL;
		private	$result		=	NULL;
		private	$fullUrl		=	NULL;
		private	$errMsg		=	NULL;
		private	$pagerUrl	=	NULL;


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


	}

/*

//Example

	$search	=	new googleSearch();							//An instance of our search proxy
	$search->setQuery('fruta litchi');						//Search term, usually, something like user input

//Following options, are NOT required

//	$search->setResultSize('small');							//Results per page 4 small | 8 large
//	$search->setLanguage('fr');								//This defaults to english if you dont set it
//	$search->setStart(1);										//Paging, start page (see the pager class)
//	$search->setErrorMessage('Tiro verdura y fruta');	//This is a custom error message for you to set in case of an error
//	$search->setGoogleLicenseKey('abcedf');				//Google license key here get one at www.google.com/api

	try { 

		$search->doGoogleSearch();								//Do the search!

	} catch (\Exception $e) {

		var_dump( $e->getMessage());							//dump the error, if any

	}

//	searchPager($search);

*/

?>
