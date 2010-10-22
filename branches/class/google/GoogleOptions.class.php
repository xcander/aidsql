<?php

	class googleOptions {


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


		public function setAPIVersion ($version=NULL){

			if(empty($version)||is_null($version)) throw (new Exception("Version number must no be NULL or empty"));

			$this->version	=	$version;

		}



		public function setStart($start=NULL){

			if(is_null($start)||!is_int($start)) throw (new Exception("Start number must NOT be NULL or empty"));

			$this->start	=	(int)$start;

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
