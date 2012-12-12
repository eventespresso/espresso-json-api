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
 * Registrations API Facade class
 *
 * @package			Espresso REST API
 * @subpackage	includes/APIFacades/Espresso_Events_Resource_Facade.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
abstract class EspressoAPI_Registrations_Resource_Facade extends EspressoAPI_Generic_Resource_Facade{
	var $modelName="Registration";
	var $modelNamePlural="Registrations";
	var $requiredFields=array("id",
		"status",
		"date_of_registration",
		'final_price',
		'code',
		'url_link',
		'is_primary',
		'is_group_registration',
		'is_going',
		'is_checked_in');
	 /**
	  * creation of event facade, calls concrete child class' _creatEvent function
	  * @param array $createParameters
	  * @return array 
	  */
     function create($createParameters){
         return $this->_createAttendee($createParameters);
     }
     abstract protected function _create($createParameters);
	 
	 /**
	  * checks the registration as being checked in, and updates the registration's check-in-quanity
	  * @param string  $registrationId
	  * @param array $queryParameters may contains keys 'quantity' and 'ignorePayment' (values of 'yes' or 'no)
	  * @return array like $this->getRegistration() 
	  */
	 function checkin($registrationId,$queryParameters=array()){
		 return $this->validator->validate($this->_checkin($registrationId,$queryParameters),true);
	}
	/**
	 *implemented in child class for updating a registration as checkedIn 
	 */
	abstract protected function _checkin($registrationId,$queryParameters=array());
	
	/**
	 * checks the registration out, and updates the checked-in-quantity
	 * @param int $registrationId
	 * @param int $queryParameters, may contain keys 'quantity' 
	 * @return array like $this->getRegistration
	 */
	function checkout($registrationId,$queryParameters=array()){
		return $this->validator->validate($this->_checkout($registrationId,$queryParameters),true);	
	}
	/**
	 *implemented in child class for checking-out a registration 
	 */
	abstract protected function _checkout($registrationId,$qty=1);
}