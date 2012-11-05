<?php
/**
 * EspressoAPI
 *
 * RESTful API for Even tEspresso
 *
 * @ package			Espresso REST API
 * @ author				Mike Nelson
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
 * @ link					{@link http://www.eventespresso.com}
 * @ since		 		3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * Events API Facade class
 *
 * @package			Espresso REST API
 * @subpackage	includes/APIFacades/Espresso_Events_API_Facade.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
//require_once("EspressoAPI_Generic_API_Facade.class.php");
abstract class EspressoAPI_Venues_API_Facade extends EspressoAPI_Generic_API_Facade{
	var $modelName="Venue";
	var $modelNamePlural="Venues";
	/**
	 * array of requiredFields allowed for querying and which must be returned. other requiredFields may be returned, but this is the minimum set
	 * @var type 
	 */
	var $requiredFields=array(
		'id',
		'name',
		'identifier',
		'address',
		'address2',
		'city',
		'state',
		'zip',
		'country',
		'user'
	);
	
	/**
	 * Gets events from database according ot query parameters by calling the concrete child classes' _getEvents function
	 * @param array $queryParameters
	 * @return array  
	 */
     function getMany($queryParameters){
		 return $this->forceResponseIntoFormat($this->_getDatetimes($queryParameters),
		     array("attendees"=>array($this->requiredFields)));
     }
	 /**
	  * implemented in concrete child class for getting events from db
	  */
     abstract protected  function _getMany($queryParameters);
     
	 function getOne($id){
		   return $this->forceResponseIntoFormat($this->_getDatetime($id),
		     array("attendee"=>$this->requiredFields));
	 }
	 abstract protected function _getOne($id);
	 /**
	  * creation of event facade, calls concrete child class' _creatEvent function
	  * @param array $createParameters
	  * @return array 
	  */
     function create($createParameters){
         return $this->_createDatetime($createParameters);
     }
     abstract protected function _create($createParameters);
	 
}