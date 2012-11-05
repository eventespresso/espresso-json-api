<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Registrations_API extends EspressoAPI_Registrations_API_Facade{
	var $APIattributes=array(
		"id"=>"Attendee.id",
		"status"=>"Attendee.status.PROCESS",
		"date_of_registration"=>"Attendee.date_of_registration",
		'final_price'=>'Attendee.final_price',
		'code'=>'Attendee.code.PROCESS',
		'url_link'=>'Registraiton.url_link.PROCESS',
		'is_primary'=>'Registration.is_primary.PROCESS',
		'is_group_registration'=>'Registration.is_group_registration.PROCESS',
		'is_going'=>'Registration.is_going.PROCESS',
		'is_checked_in'=>'Registration.is_checked_in.PROCESS');
	
	var $selectFields="
		Attendee.id AS 'Registration.id',
		Attendee.date AS 'Registration.date_of_registration',
		Attendee.final_price as 'Registration.final_price',
		Attendee.orig_price as 'Registration.orig_price.PROCESS',
		Attendee.registratioN_id as 'Registration.code',
		Attendee.is_primary as 'Registration.is_primary',
		Attendee.quantity as 'Registration.is_group_registration.PROCESS',
		Attendee.checked_in as 'Registration.is_checked_in',
		Attendee.price_option as 'Registration.price_option.PROCESS',
		Attendee.event_time as 'Registration.event_time.PROCESS',
		Attendee.end_time as 'Registration.end_time.PROCESS'";
	var $relatedModels=array(
		"Event"=>array('modelNamePlural'=>"Events",'hasMany'=>false),
		"Attendee"=>array('modelNamePlural'=>"Attendees",'hasMany'=>false),
		"Transaction"=>array('modelNamePlural'=>"Transactions",'hasMany'=>false),
		'Datetime'=>array('modelNamePlural'=>'Datetimes','hasMany'=>false),
		'Price'=>array('modelNamePlural'=>'Prices','hasMany'=>false));

	function getManyConstructQuery($sqlSelect,$whereSql){
		global $wpdb;
		$sql = "
            SELECT
				{$this->selectFields},				
				{$sqlSelect}
            FROM
                {$wpdb->prefix}events_attendee Attendee
			LEFT JOIN
				{$wpdb->prefix}events_detail Event ON Event.id=Attendee.event_id
			LEFT JOIN
				{$wpdb->prefix}events_attendee_meta AttendeeMeta ON Attendee.id=AttendeeMeta.attendee_id
			LEFT JOIN
				{$wpdb->prefix}events_prices Price ON Attendee.event_id=Price.event_id
			LEFT JOIN
				{$wpdb->prefix}events_start_end StartEnd ON StartEnd.start_time=Attendee.event_time AND StartEnd.end_time=Attendee.end_time AND StartEnd.event_id=Attendee.event_id
			$whereSql";
				//echo "registrationsapi 60: sql:$sql";
		return $sql;
	}
	/*
	 * overrides parent constructSQLWherSubclauses in order to attach an additional wherecaluse
	 * which will ensure the prices found match the ones the attendees purchased
	 */
	protected function constructSQLWhereSubclauses($keyOpVals){
		$whereSqlArray=array();
		foreach($keyOpVals as $key=>$OpAndVal){
			$whereSqlArray[]=$this->constructSQLWhereSubclause($key,$OpAndVal['operator'],$OpAndVal['value']);
		}
		$whereSqlArray[]="
		(
			(Price.surcharge_type='flat_rate'
			AND(
				Price.member_price+Price.surcharge=Attendee.orig_price
				OR
				Price.event_cost+Price.surcharge=Attendee.orig_price
				)
			)
		
		OR
			(Price.surcharge_type='pct'
			AND(
				Price.member_price*Price.surcharge/100=Attendee.orig_price
				OR
				Price.event_cost*Price.surcharge/100=Attendee.orig_price
				)
			)
		)";
		return $whereSqlArray;
	}
	/*protected function processSqlResults($results,$keyOpVals){
		$resultsICanView = array();
		foreach ($results as $event) {
			if (EspressoAPI_Permissions_Wrapper::espresso_is_my_event($event['Event.id']))
				$resultsICanView[] = $event;
		}
		return $resultsICanView;
	}
	*/

	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		if(!function_exists('is_attendee_approved')){
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/functions/attendee_functions.php');
		}
		$isApproved=is_attendee_approved(intval($sqlResult['Event.id']),intval($sqlResult['Registration.id']));
		$isGroupRegistration=$sqlResult['Registration.is_group_registration.PROCESS']>1?true:false;
		$isPrimary=$sqlResult['Registration.is_primary']?true:false;
		$isCheckedIn=$sqlResult['Registration.is_checked_in']?true:false;
		$transaction=array(
			'id'=>$sqlResult['Registration.id'],
			'status'=>$isApproved?'approved':'not_approved',
			'date_of_registration'=>$sqlResult['Registration.date_of_registration'],
			'final_price'=>$sqlResult['Registration.final_price'],
			'code'=>$sqlResult['Registration.code'],
			'url_link'=>null,
			'is_primary'=>$isPrimary,
			'is_group_registration'=>$isGroupRegistration,
			'is_going'=>true,
			'is_checked_in'=>$isCheckedIn
			);
		return $transaction;
	}
	function _checkin($id,$queryParameters=array()){
		global $wpdb;
		//get the registration
		$fetchSQL="SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$id'";
		$registration=$wpdb->get_row($fetchSQL,ARRAY_A);
		if(empty($registration))
			throw new EspressoAPI_ObjectDoesNotExist($id);
		if(!EspressoAPI_Permissions_Wrapper::espresso_is_my_event($registration['event_id']))
			throw new EspressoAPI_UnauthorizedException();
		$ignorePayment=(isset($queryParameters['ignorePayment']) && $queryParameters['ignorePayment']=='no')?false:true;
		$quantity=(isset($queryParameters['quantity']) && is_numeric($queryParameters['quantity']))?$queryParameters['quantity']:1;
		//check payment status
		if($registration['payment_status']=='Incomplete' || !$ignorePayment){
		//if its 'Incomplete' then stop
			throw new EspressoAPI_SpecialException(__("Checkin denied. Payment not complete and 'ignorePayment' flag not set.",412));
		}
		$sql="UPDATE {$wpdb->prefix}events_attendee SET checked_in_quantity = checked_in_quantity + $quantity, checked_in=1 WHERE registration_id='{$registration['registration_id']}'";
		//update teh attendee to checked-in-quanitty and checked_in columns
		$result=$wpdb->query($sql);
		if($result){
			//refetch the registration again
			return $this->getOne($id);
		}else{
			throw new EspressoAPI_OperationFailed(__("Updating of registration as checked in failed:","event_espresso").$result);
		}
	}
	function _checkout($id,$queryParameters=array()){
		global $wpdb;
		//get the registration
		$fetchSQL="SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$id'";
		$registration=$wpdb->get_row($fetchSQL,ARRAY_A);
		if(empty($registration))
			throw new EspressoAPI_ObjectDoesNotExist($id);
		if(!EspressoAPI_Permissions_Wrapper::espresso_is_my_event($registration['event_id']))
			throw new EspressoAPI_UnauthorizedException();
		$quantity=(isset($queryParameters['quantity']) && is_numeric($queryParameters['quantity']))?$queryParameters['quantity']:1;
		//check payment status
		$sql="UPDATE {$wpdb->prefix}events_attendee SET checked_in_quantity = checked_in_quantity - $quantity, checked_in=0 WHERE registration_id='{$registration['registration_id']}'";
		//update teh attendee to checked-in-quanitty and checked_in columns
		$result=$wpdb->query($sql);
		if($result){
			//refetch the registration again
			return $this->getOne($id);
		}else{
			throw new EspressoAPI_OperationFailed(__("Updating of registration as checked out failed:","event_espresso").$result);
		}
		
	}
    function _create($createParameters){
        return array("status"=>"Not Yet Implemented","status_code"=>"500");
    }
    /**
     *for handling requests liks '/events/14'
     * @param int $id id of event
     */
	
}
//new Events_Controller();