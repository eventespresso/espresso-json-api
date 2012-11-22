<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Attendees_Resource extends EspressoAPI_Attendees_Resource_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'Attendee.id',
		'firstname'=>'Attendee.fname',
		'lastname'=>'Attendee.lname',
		'address'=>'Attendee.address',
		'address2'=>'Attendee.address2',
		'city'=>'Attendee.city',
		'state'=>'Attendee.state',
		'country'=>'Attendee.country_id',
		'zip'=>'Attendee.zip',
		'email'=>'Attendee.email',
		'phone'=>'Attendee.phone'
		);
    
	var $calculatedColumnsToFilterOn=array();
	var $selectFields="
		Attendee.id AS 'Attendee.id',
		Attendee.fname as 'Attendee.fname',
		Attendee.lname as 'Attendee.lname',
		Attendee.address as 'Attendee.address',
		Attendee.address2 as 'Attendee.address2',
		Attendee.city as 'Attendee.city',
		Attendee.state as 'Attendee.state',
		Attendee.country_id as 'Attendee.country',
		Attendee.zip as 'Attendee.zip',
		Attendee.email as 'Attendee.email',
		Attendee.phone as 'Attendee.phone'";
	var $relatedModels=array();
	
	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
			$attendee=array(
				'id'=>$sqlResult['Attendee.id'],
				'firstname'=>$sqlResult['Attendee.fname'],
				'lastname'=>$sqlResult['Attendee.lname'],
				'address'=>$sqlResult['Attendee.address'],
				'address2'=>$sqlResult['Attendee.address2'],
				'city'=>$sqlResult['Attendee.city'],
				'state'=>$sqlResult['Attendee.state'],
				'country'=>$sqlResult['Attendee.country'],
				'zip'=>$sqlResult['Attendee.zip'],
				'email'=>$sqlResult['Attendee.zip'],
				'phone'=>$sqlResult['Attendee.phone'],
				'comments'=>null,
				'notes'=>null
				);
			return $attendee;
	}
	
    function _create($createParameters){
        return array("status"=>"Not Yet Implemented","status_code"=>"500");
    }
    /**
     *for handling requests liks '/events/14'
     * @param int $id id of event
     */
	protected function _getOne($id) {
		global $wpdb;
		$result=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$id'",ARRAY_A);
		if(empty($result))
			throw new EspressoAPI_ObjectDoesNotExist($id);
		if(EspressoAPI_Permissions_Wrapper::espresso_is_my_event($result['event_id']))
			return array("attendee"=>$result);
		else
			throw new EspressoAPI_UnauthorizedException();
	
	}
}
//new Events_Controller();