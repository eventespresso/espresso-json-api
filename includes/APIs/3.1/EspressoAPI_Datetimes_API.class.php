<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Datetimes_API extends EspressoAPI_Datetimes_API_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'StartEnd.id',
		'limit'=>'StartEnd.reg_limit',
		'tickets_left'=>'Datetime.tickets_left'
	);
	var $selectFields="
		StartEnd.id AS 'Datetime.id',
		StartEnd.start_time AS 'Datetime.start_time.PROCESS',
		StartEnd.end_time AS 'Datetime.end_time.PROCESS',
		Event.start_date AS 'Datetime.start_date.PROCESS',
		Event.end_date AS 'Datetime.end_date.PROCESS',
		Event.registration_start AS 'Datetime.registration_start.PROCESS',
		Event.registration_end AS 'Datetime.registration_end.PROCESS',
		StartEnd.reg_limit AS 'Datetime.limit',
		Event.registration_startT AS 'Datetime.registration_startT.PROCESS',
		Event.registration_endT AS 'Datetime.registration_endT.PROCESS'
	";
	
	/**
	 * used to construct SQL for special cases when comparing dates. 
	 * This extra logic exists because we accept times like '2012-11-23 23:40:59',
	 * but in 3.1 the time columsn are stored in seperate tables (event_details and event_start_end)
	 * and in different columns (usually one to represent the date, the other the time).
	 * So if we want to all date models which have, for example, whose registration begins
	 * before '2012-11-23 23:40:59', then what we we REALLY want is:
	 * -all dates whose registration date is before 2012-11-23
	 * AND
	 * -all dates whose registration date is ON 2012-11-23 AND whose registration time is BEFORE 
	 * @param type $operator
	 * @param type $dateColumn
	 * @param type $dateValue
	 * @param type $timeColumn
	 * @param type $timeValue
	 * @return type 
	 */
	private function constructSqlDateTimeWhereSubclause($operator,$dateColumn,$dateValue,$timeColumn,$timeValue){
		switch($operator){
			case '<':
			case '<=':
			case '>':
			case '>=':
				return "($dateColumn $operator $dateValue || ($dateColumn=$dateValue && $timeColumn $operator $timeValue))";					
			default:
				return "$dateColumn $operator $dateValue && $timeColumn $operator $timeValue";
		}
	}
	/**
	 *overrides parent 'constructSQLWhereSubclause', because we need to handle 'Datetime.event_start', 'Datetime.event_end', and maybe some 
	 * other columns differently
	 * see parent's comment for more details
	 * @param string $columnName
	 * @param string $operator
	 * @param string $value
	 * @return string 
	 */
	protected function constructSQLWhereSubclause($columnName,$operator,$value){
		$matches=array();
		switch($columnName){
			case 'event_start':
				//break value into parts
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.start_date',$date,'StartEnd.start_time',$hourAndMinute);
			case 'event_end':
				//break value into parts
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.end_date',$date,'StartEnd.end_time',$hourAndMinute);
			case 'registration_start':
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.registration_start',$date,'Event.registration_startT',$hourAndMinute);
			case 'registration_end':
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.registration_end',$date,'Event.registration_endT',$hourAndMinute);
			case 'limit':
				$filteredValue=$this->constructValueInWhereClause($operator,$value);
				return "Event.reg_limit $operator $filteredValue";
			case 'is_primary'://ignore, doesn't apply to 3.1
				return '';
		}
		return parent::constructSQLWhereSubclause($columnName, $operator, $value);		
	}
	/**
     * gets all events in the database, according to query parmeters
     * @global type $wpdb
     * @param array $queryParameters of key=>values. eg: "array("start_date"=>"2012-04-23","name"=>"Mike Party").
     * @return type 
     */
    function _getMany($queryParameters){
		return new EspressoAPI_MethodNotImplementedException();
    }
    function _create($createParameters){
       return new EspressoAPI_MethodNotImplementedException();
    }
    /**
     *for handling requests liks '/events/14'
     * @param int $id id of event
     */
	protected function _getOne($id) {
		return new EspressoAPI_MethodNotImplementedException();
	}
	
	protected function processSqlResults($rows,$keyOpVals){
		global $wpdb;
		$attendeePerEvent=array();
		$processedRows=array();
		foreach($rows as $row){
			if(empty($attendeePerEvent[$row['Event.id']])){
				$count=$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}events_detail WHERE id={$row['Event.id']};" ) );
				$attendeePerEvent[$row['Event.id']]=$count;//basically cache the result
			}
			$row['Datetime.tickets_left']=$row['Event.limit'];// just reutnr  abig number for now. Not sure how to calculate this. $row['Datetime.limit']-$attendeePerEvent[$row['Event.id']];
			if(array_key_exists('Datetime.tickets_left',$keyOpVals)){
				$opAndVal=$keyOpvals['Datetime.tickets_left'];
			
				if(!$this->evaluate($row['Datetime.tickets_left'],$opAndVal['operator'],$opAndVal['value'])){
					continue;//this condiiton failed, don't include this row in the results!!
				}
			}
			$processedRows[]=$row;
		}
		return $processedRows;
	}
	/**
	 * takes the results acquired from a DB selection, and extracts
	 * each instance of this model, and compiles into a nice array like
	 * array(12=>("id"=>12,"name"=>"mike party","description"=>"all your base"...)
	 * Also, if we're going to just be finding models that relate
	 * to a specific foreign_key on any table in the query, we can specify
	 * to only return those models using the $idKey and $idValue,
	 * for example if you have a bunch of results from a query like 
	 * "select * FROM events INNER JOIn attendees", and you just want
	 * all the attendees for event with id 13, then you'd call this as follows:
	 * $attendeesForEvent13=parseSQLREsultsForMyDate($results,'Event.id',13);
	 * @param array $sqlResults single row from a big inner-joined query, such as constructed in EventEspressoAPI_Events_API->getManyConstructQuery or EventEspressoAPI_Registrations_API->getManyConstructQuery
	 * @param string/int $idKey
	 * @param string/int $idValue 
	 * @return array compatible with the required reutnr type for this model
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		// if the user signs up for a time, and then the time changes,  Datetime.start_time won't be set! So 
		// insteadof returning a blank, we'll return the time the attendee originally registered for)
		if(empty($sqlResult['Datetime.start_time.PROCESS']) || empty($sqlResult['Datetime.end_time.PROCESS'])){
			$sqlResult['Datetime.id']="0";
			$myTimeToStart=$sqlResult['Registration.event_time.PROCESS'];
			$myTimeToEnd=$sqlResult['Registration.end_time.PROCESS'];
		}else{
			$myTimeToStart=$sqlResult['Datetime.start_time.PROCESS'];
			$myTimeToEnd=$sqlResult['Datetime.end_time.PROCESS'];
		}
		//if we can't get teh time from either, just default to midnight. or we could just return null
		if(empty($myTimeToEnd) || empty($myTimeToStart)){
			$myTimeToEnd="00:00";
			$myTimeToStart="00:00";
		}
		
		$eventStart=$sqlResult['Datetime.start_date.PROCESS']." $myTimeToStart:00";
		$eventEnd=$sqlResult['Datetime.end_date.PROCESS']." $myTimeToEnd:00";
		$registrationStart=$sqlResult['Datetime.registration_start.PROCESS']." ".$sqlResult['Datetime.registration_startT.PROCESS'].":00";
		$registrationEnd=$sqlResult['Datetime.registration_end.PROCESS']." ".$sqlResult['Datetime.registration_endT.PROCESS'].":00";

			
		$datetime=array(
			'id'=>$sqlResult['Datetime.id'],
			'is_primary'=>true,
			'event_start'=>$eventStart,
			'event_end'=>$eventEnd,
			'registration_start'=>$registrationStart,
			'registration_end'=>$registrationEnd,
			'limit'=>$sqlResult['Datetime.limit'],
			'tickets_left'=>$sqlResult['Datetime.tickets_left']
			);
		return $datetime; 
	}
}
//new Events_Controller();