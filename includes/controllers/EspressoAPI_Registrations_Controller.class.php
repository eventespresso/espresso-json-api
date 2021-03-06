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
 * Registrations Controller class
 * hanldes requests like "espresso-api/attendees/*
 * if you want to create another controller, to hanlde, say, "venues", name the class "Venues_Controller" and place it in "Venues_Controller.class.php" in this same folder.
 * 
 * @package			Espresso REST API
 * @subpackage	includes/controllers/EspressoAPI_Attendees_Controller.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
//require_once("EspressoAPI_Generic_Controller.class.php");
class EspressoAPI_Registrations_Controller extends EspressoAPI_Generic_Controller{
	
	/**
	 *request like GET events/13/attendees, for getting all attendees at an event 
	 * @param $id id of the object
	 * @param $attribute 3rd part of the URI, in teh above example it would 'attendees'
	 * @return array list of objects (if in a has-many,belongs-to-many,or has-and-bleongs-to-many relationship) or single object (if in a has-one relationship)
	 */
	 protected function specificAttributeRequestGet($id,$attribute){
		 switch($attribute){
			 case 'checkin':
				return $this->apiFacade->checkin($id,$_REQUEST);
			 case 'checkout':
				return $this->apiFacade->checkout($id,$_REQUEST);
			 default:
				  throw new EspressoAPI_MethodNotImplementedException();
		 }
	 }
	/**
	 *request like POST events/13/venue, for creating a venue for this event
	 * @param $id id of the object
	 * @param $attribute 3rd part of the URI, in teh above example it would 'attendees'
	 * @return boolean success of object-creation
	 */
	 protected function specificAttributeRequestPost($id,$attribute){
		 switch($attribute){
			 case 'checkin':
				return $this->apiFacade->checkin($id,$_REQUEST);
			 case 'checkout':
				return $this->apiFacade->checkout($id,$_REQUEST);
			 default:
				  throw new EspressoAPI_MethodNotImplementedException();
		 }
	}
	
	/**
	 *request like PUT events/13/venue, for updating the one-and-only venu for this event
	 * @param $id id of the object
	 * @param $attribute 3rd part of the URI, in teh above example it would 'attendees'
	 * @return boolean success fo object update
	 */
	 protected function specificAttributeRequestPut($id,$attribute){
		  switch($attribute){
			 case 'checkin':
				return $this->apiFacade->checkin($id,$_PUT);
			 case 'checkout':
				return $this->apiFacade->checkout($id,$_PUT);
			 default:
				  throw new EspressoAPI_MethodNotImplementedException();
		 }
	 }
	/**
	 * requests like DELETE events/13/venue, for deleting the venue of ane vent 
	 * @param $id id of the object
	 * @param $attribute 3rd part of the URI, in teh above example it would 'attendees'
	 * @return boolean success of deletion
	 */
	 protected function specificAttributeRequestDelete($id,$attribute){throw new EspressoAPI_MethodNotImplementedException();}
	 
	 /**
	  * For handling non-standard attributes like 'checkin' and 'checkout'.
	  * @param string $id
	  * @param string $attribute like 'registrations', or 'checkin'
	  * @return boolean
	  */
	 protected function canAccessAttribute($id, $attribute){
		 switch($attribute){
				case 'checkin':
				case 'checkout':
					return EspressoAPI_Permissions_Wrapper::current_user_can_access_specific('put', 'Registrations', $id);
		 }
		 //the attribute isn't one of registrations' exceptions
		 //so treat it as normal
		 return parent::canAccessAttribute($id, $attribute);
	 }
}
