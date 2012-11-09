<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Registrations_API extends EspressoAPI_Registrations_API_Facade{
	var $APIqueryParamsToDbColumns=array(
		"id"=>"Attendee.id",
		"date_of_registration"=>"Attendee.date_of_registration",
		'final_price'=>'Attendee.final_price',
		'code'=>'Attendee.registration_id',
		'is_primary'=>'Attendee.is_primary',
		'is_checked_in'=>'Attendee.checked_in');
	
	var $selectFields="
		Attendee.id AS 'Registration.id',
		Attendee.id AS 'Attendee.id',
		Attendee.date AS 'Attendee.date',
		Attendee.final_price as 'Attendee.final_price',
		Attendee.orig_price as 'Attendee.orig_price',
		Attendee.registration_id as 'Attendee.registration_id',
		Attendee.is_primary as 'Attendee.is_primary',
		Attendee.quantity as 'Attendee.quantity',
		Attendee.checked_in as 'Attendee.checked',
		Attendee.price_option as 'Attendee.price_option',
		Attendee.event_time as 'Attendee.event_time',
		Attendee.end_time as 'Attendee.end_time'";
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
				
		return $sql;
	}
	
	protected function constructSQLWhereSubclause($columnName,$operator,$value){
		switch($columnName){
			case 'Registration.status':
			case 'Registration.url_link':
			case 'Attendee.is_primary':
			case 'Registration.is_going':
				return null;
			case 'Registration.is_group_registration':
				if($value=='true'){
					return "Attendee.quantity > 1";
				}else{
					return "Attendee.quantity <= 1";
				}
				
			
		}
		return parent::constructSQLWhereSubclause($columnName,$operator,$value);
	}
	/*
	 * overrides parent constructSQLWherSubclauses in order to attach an additional wherecaluse
	 * which will ensure the prices found match the ones the attendees purchased
	 */
	protected function constructSQLWhereSubclauses($keyOpVals){
		$whereSqlArray=parent::constructSQLWhereSubclauses($keyOpVals);
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
protected function processSqlResults($rows,$keyOpVals){
		global $wpdb;
		if(!function_exists('is_attendee_approved')){
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/functions/attendee_functions.php');
		}
		$attendeeStatuses=array();
		$processedRows=array();
		foreach($rows as $row){
			if(!array_key_exists($row['Attendee.id'],$attendeeStatuses)){
				$isApproved=is_attendee_approved(intval($row['Event.id']),intval($row['Attendee.id']));
				$status=$isApproved?'approved':'not_approved';
				$attendeeStatuses[$row['Attendee.id']]=$status;
			}
			$attendeeStatus=$attendeeStatuses[$row['Attendee.id']];
			$row['Registration.status.POST_PROCESSED']=$attendeeStatus;
			
			//perform filtering that couldn't be done in sql
			if(array_key_exists('Registration.status',$keyOpVals)){
				$opAndVal=$keyOpVals['Registration.status'];
				if(!$this->evaluate($row['Registration.status.POST_PROCESSED'],$opAndVal['operator'],$opAndVal['value'])){
					continue;//this condiiton failed, don't include this row in the results!!
				}
			}			
			$processedRows[]=$row;
			
		}
		return $processedRows;
	}
	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		$isGroupRegistration=$sqlResult['Attendee.quantity']>1?true:false;
		$isPrimary=$sqlResult['Attendee.is_primary']?true:false;
		$isCheckedIn=$sqlResult['Attendee.checked']?true:false;
		$transaction=array(
			'id'=>$sqlResult['Attendee.id'],
			'status'=>$sqlResult['Registration.status.POST_PROCESSED'],
			'date_of_registration'=>$sqlResult['Attendee.date'],
			'final_price'=>$sqlResult['Attendee.final_price'],
			'code'=>$sqlResult['Attendee.registration_id'],
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
		if(!EspressoAPI_Permissions_Wrapper::current_user_can('put', $this->modelNamePlural)){
			 throw new EspressoAPI_UnauthorizedException();
		}
		//get the registration
		$fetchSQL="SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$id'";
		$registration=$wpdb->get_row($fetchSQL,ARRAY_A);
		if(empty($registration))
			throw new EspressoAPI_ObjectDoesNotExist($id);
		if(!EspressoAPI_Permissions_Wrapper::espresso_is_my_event($registration['event_id']))
			throw new EspressoAPI_UnauthorizedException();
		$ignorePayment=(isset($queryParameters['ignore_payment']) && $queryParameters['ignore_payment']=='true')?true:false;
		$quantity=(isset($queryParameters['quantity']) && is_numeric($queryParameters['quantity']))?$queryParameters['quantity']:1;
		//check payment status
		if($registration['payment_status']=='Incomplete' && !$ignorePayment){
		//if its 'Incomplete' then stop
			throw new EspressoAPI_SpecialException(__("Checkin denied. Payment not complete and 'ignore_payment' flag not set.",412));
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
		if(!EspressoAPI_Permissions_Wrapper::current_user_can('put', $this->modelNamePlural)){
			 throw new EspressoAPI_UnauthorizedException();
		}
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