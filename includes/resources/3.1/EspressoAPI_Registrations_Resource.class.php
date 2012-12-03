<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Registrations_Resource extends EspressoAPI_Registrations_Resource_Facade{
	var $APIqueryParamsToDbColumns=array(
		//"id"=>"Attendee.id",
		"date_of_registration"=>"Attendee.date_of_registration",
		'final_price'=>'Attendee.final_price',
		'code'=>'Attendee.registration_id',
		//'is_primary'=>'Attendee.is_primary',
		'is_checked_in'=>'Attendee.checked_in');
	var $calculatedColumnsToFilterOn=array('Registration.id', 'Registration.status','Registration.url_link','Registration.is_going','Attendee.is_primary');
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
/**
 * an array for caching  registration ids taht related to group registrations
 * it coudl look like array('2etf2w24rtw'=>true, '54tgsdsf'=>false), meaning
 * '2etf2w24rtw' is a known group registration, but '54tgsdsf' is known to NOT 
 * be a gruop registration. All other registartion ids are not yet known andshould eb cached.
 * @var type 
 */
	private $knownGroupRegistrationRegIds=array();
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
			/*case 'Registration.status':
			case 'Registration.url_link':
			case 'Registration.is_going':
				return null;*/
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
			$row['Registration.status']=$attendeeStatus;
			$row['Registration.is_going']=true;
			$row['Registration.url_link']=null;
			$row['Registration.is_group_registration']=$this->determineIfGroupRegistration($row);
			$row['Registration.is_primary']=$row['Attendee.is_primary']?true:false;
			$row['Registration.is_checked_in']=$row['Attendee.checked']?true:false;
			
			//in 3.2, every single row in registrationtable relates to a ticket for somebody
			//to get into the event. In 3.1 it sometimes does and sometimes doesn't. Which is somewhat 
			//confusing. So it really should,instead, 
			$baseRegId=$row['Registration.id'];
			for($i=1;$row['Attendee.quantity']>=$i;$i++){
				$row['Registration.id']="$baseRegId.$i";
				 if($i>1){  
					$row['Registration.is_primary']=false;  
				}  
				if(!$this->rowPassesFilterByCalculatedColumns($row,$keyOpVals))
					continue;		
			
				$processedRows[]=$row;
			}	
		}
		return $processedRows;
	}
	
	private function determineIfGroupRegistration($sqlResult){
		//if it hasa quantity over 1
		//or there are other registrations with teh same Attendee.registration_id
		if(!array_key_exists($sqlResult['Attendee.registration_id'],$this->knownGroupRegistrationRegIds)){
			if($sqlResult['Attendee.quantity']>1){
				$this->knownGroupRegistrationRegIds[$sqlResult['Attendee.registration_id']]=true;
			}else{
				//check for other attendee rows with teh same registration id
				global $wpdb;
				$count=$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}events_attendee Attendee
					WHERE Attendee.registration_id='{$sqlResult['Attendee.registration_id']}'");
				if($count>1){
					$this->knownGroupRegistrationRegIds[$sqlResult['Attendee.registration_id']]=true;
				}else{
					$this->knownGroupRegistrationRegIds[$sqlResult['Attendee.registration_id']]=false;
				}

			}
		}
		return $this->knownGroupRegistrationRegIds[$sqlResult['Attendee.registration_id']];
			

//return in_array($sqlResult['Attendee.registration_id'],$this->knowGroupRegistrationRegIds);
	}
	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		
		$transaction=array(
			'id'=>$sqlResult['Registration.id'],
			'status'=>$sqlResult['Registration.status'],
			'date_of_registration'=>$sqlResult['Attendee.date'],
			'final_price'=>$sqlResult['Attendee.final_price'],
			'code'=>$sqlResult['Attendee.registration_id'],
			'url_link'=>$sqlResult['Registration.url_link'],
			'is_primary'=>$sqlResult['Registration.is_primary'],
			'is_group_registration'=>$sqlResult['Registration.is_group_registration'],
			'is_going'=>$sqlResult['Registration.is_going'],
			'is_checked_in'=>$sqlResult['Registration.is_checked_in']
			);
		return $transaction;
	}
	function _checkin($id,$queryParameters=array()){
		global $wpdb;
		if(!EspressoAPI_Permissions_Wrapper::current_user_can('put', $this->modelNamePlural)){
			 throw new EspressoAPI_UnauthorizedException();
		}
		//note: they might be checking in a registrant with an id like 1.1 or 343.4, (this happens in group registrations
		//where all tickets use the same attendee info
		//if that's the case, we row we want to update is 1 or 343, respectively.
		//soo just strip everything out after the "."
		$idParts=explode(".",$id,2);
		$rowId=$idParts[0];
		//get the registration
		$fetchSQL="SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$rowId'";
		$registration=$wpdb->get_row($fetchSQL,ARRAY_A);
		if(empty($registration))
			throw new EspressoAPI_ObjectDoesNotExist($id);
		if(!EspressoAPI_Permissions_Wrapper::espresso_is_my_event($registration['event_id']))
			throw new EspressoAPI_UnauthorizedException();
		$ignorePayment=(isset($queryParameters['ignore_payment']) && $queryParameters['ignore_payment']=='true')?true:false;
		$quantity=(isset($queryParameters['quantity']) && is_numeric($queryParameters['quantity']))?$queryParameters['quantity']:1;
		if(intval($registration['checked_in_quantity'])+$quantity>$registration['quantity']){
			throw new EspressoAPI_SpecialException(__("Checkins Exceeded! Checkins permitted on this registration: ","event_espresso").$registration['quantity']);
		}
		
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
		//note: they might be checking in a registrant with an id like 1.1 or 343.4, (this happens in group registrations
		//where all tickets use the same attendee info
		//if that's the case, we row we want to update is 1 or 343, respectively.
		//soo just strip everything out after the "."
		$idParts=explode(".",$id,2);
		$rowId=$idParts[0];
		
		//get the registration
		$fetchSQL="SELECT * FROM {$wpdb->prefix}events_attendee WHERE id='$rowId'";
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