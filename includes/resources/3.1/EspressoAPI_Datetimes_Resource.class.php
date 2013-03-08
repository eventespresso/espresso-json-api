<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Datetimes_Resource extends EspressoAPI_Datetimes_Resource_Facade{
	/**
	 * primary ID column for SELECT query when selecting ONLY the primary id
	 */
	protected $primaryIdColumn='StartEnd.id';
	var $APIqueryParamsToDbColumns=array(
		'id'=>'StartEnd.id',
		'limit'=>'StartEnd.reg_limit'
	);
	var $calculatedColumnsToFilterOn=array('Datetime.is_primary','Datetime.tickets_left');
	var $selectFields="
		StartEnd.id AS 'Datetime.id',
		StartEnd.id AS 'StartEnd.id',
		StartEnd.start_time AS 'StartEnd.start_time',
		StartEnd.end_time AS 'Startend.end_time',
		Event.start_date AS 'Event.start_date',
		Event.end_date AS 'Event.end_date',
		Event.registration_start AS 'Event.registration_start',
		Event.registration_end AS 'Event.registration_end',
		StartEnd.reg_limit AS 'StartEnd.reg_limit',
		Event.registration_startT AS 'Event.registration_startT',
		Event.registration_endT AS 'Event.registration_endT'
	";
	var $relatedModels=array();
	
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
			case 'Datetime.event_start':
				//break value into parts
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.start_date',$date,'StartEnd.start_time',$hourAndMinute);
			case 'Datetime.event_end':
				//break value into parts
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.end_date',$date,'StartEnd.end_time',$hourAndMinute);
			case 'Datetime.registration_start':
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.registration_start',$date,'Event.registration_startT',$hourAndMinute);
			case 'Datetime.registration_end':
				preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$value,$matches);
				$date=$this->constructValueInWhereClause($operator,$matches[1]);
				$hourAndMinute=$this->constructValueInWhereClause($operator,$matches[2].":".$matches[3]);
				return $this->constructSqlDateTimeWhereSubclause($operator,'Event.registration_end',$date,'Event.registration_endT',$hourAndMinute);
			case 'Datetime.reg_limit':
				$filteredValue=$this->constructValueInWhereClause($operator,$value);
				return "Event.reg_limit $operator $filteredValue";
		}
		return parent::constructSQLWhereSubclause($columnName, $operator, $value);		
	}
	protected function processSqlResults($rows,$keyOpVals){
		global $wpdb;
		$attendeesPerEvent=array();
		$processedRows=array();
		foreach($rows as $row){
			if(empty($attendeesPerEvent[$row['Event.id']])){
				//because in 3.1 there can't be a limit per datetime, only per event, just count total attendees of an event
				/*$quantitiesAttendingPerRow=$wpdb->get_col( $wpdb->prepare( "SELECT quantity FROM {$wpdb->prefix}events_attendee WHERE event_id=%d AND ;", $row['Event.id']) );
				$totalAttending=0;
				foreach($quantitiesAttendingPerRow as $quantity){
					$totalAttending+=intval($quantity);
				}*/
				$attendeesPerEvent[$row['Event.id']]=get_number_of_attendees_reg_limit($row['Event.id'],'num_attendees');//basically cache the result
			}
			$row['StartEnd.reg_limit']=intval($row['Event.reg_limit']);
			$row['Datetime.tickets_left']=intval($row['Event.reg_limit'])-$attendeesPerEvent[$row['Event.id']];//$row['Event.reg_limit'];// just reutnr  abig number for now. Not sure how to calculate this. $row['StartEnd.reg_limit']-$attendeesPerEvent[$row['Event.id']];
			$row['Datetime.is_primary']=true;
//now that 'tickets_left' has been set, we can filter by it, if the query parameter has been set, of course
			if(!$this->rowPassesFilterByCalculatedColumns($row,$keyOpVals))
				continue;
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
	 * @param array $sqlResults single row from a big inner-joined query, such as constructed in EventEspressoAPI_Events_Resource->getManyConstructQuery or EventEspressoAPI_Registrations_Resource->getManyConstructQuery
	 * @param string/int $idKey
	 * @param string/int $idValue 
	 * @return array compatible with the required reutnr type for this model
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		// if the user signs up for a time, and then the time changes,  StartEnd.start_time won't be set! So 
		// insteadof returning a blank, we'll return the time the attendee originally registered for)
		if(empty($sqlResult['StartEnd.start_time']) || empty($sqlResult['Startend.end_time'])){
			$sqlResult['StartEnd.id']="0";
			if(array_key_exists('Attendee.event_time',$sqlResult)){
				$myTimeToStart=$sqlResult['Attendee.event_time'];
			}
			if(array_key_exists('Attendee.end_time',$sqlResult)){
				$myTimeToEnd=$sqlResult['Attendee.end_time'];
			}
		}else{
			$myTimeToStart=$sqlResult['StartEnd.start_time'];
			$myTimeToEnd=$sqlResult['Startend.end_time'];
		}
		//if we can't get teh time from either, just default to midnight. or we could just return null
		if(empty($myTimeToEnd) || empty($myTimeToStart)){
			$myTimeToEnd="00:00";
			$myTimeToStart="00:00";
		}
		$registrationStartTime=(empty($sqlResult['Event.registration_startT']))?"00:00":$sqlResult['Event.registration_startT'];
		$registrationEndTime=(empty($sqlResult['Event.registration_endT']))?"00:00":$sqlResult['Event.registration_endT'];
		$eventStart=$sqlResult['Event.start_date']." $myTimeToStart:00";
		$eventEnd=$sqlResult['Event.end_date']." $myTimeToEnd:00";
		$registrationStart=$sqlResult['Event.registration_start']." ".$registrationStartTime.":00";
		$registrationEnd=$sqlResult['Event.registration_end']." ".$registrationEndTime.":00";

			
		$datetime=array(
			'id'=>$sqlResult['StartEnd.id'],
			'is_primary'=>$sqlResult['Datetime.is_primary'],
			'event_start'=>$eventStart,
			'event_end'=>$eventEnd,
			'registration_start'=>$registrationStart,
			'registration_end'=>$registrationEnd,
			'limit'=>$sqlResult['StartEnd.reg_limit'],
			'tickets_left'=>$sqlResult['Datetime.tickets_left']
			);
		return $datetime; 
	}
	/**
	 * gets the date and time contained in the $dateSTring
	 * @param string $dateString in mysql datetime format, eg "YYYY-MM-DD HH:MM:SS'
	 * @return array with keys 'date' (YYYY-MM-DD) and 'time' (HH:MM, seconds are ignored), 
	 */
	private function parseDate($dateString){
		preg_match("~^(\\d*-\\d*-\\d*) (\\d*):(\\d*):(\\d*)$~",$dateString,$matches);
		return array('date'=>$matches[1],'time'=>$matches[2].":".$matches[3]);
	}
	/**
	 * gets all the database column values from api input. also, if in the $options array, 
	 * the setting for 'correspondingAttendeeId' is set, then we will also try to update
	 * the events_attendee row with the datetime information contained in $apiInput
	 * @param array $apiInput either like array('events'=>array(array('id'=>... 
	 * //OR like array('event'=>array('id'=>...
	 * @return array like array('wp_events_attendee'=>array(12=>array('id'=>12,name=>'bob'... 
	 */
	function extractMyColumnsFromApiInput($apiInput,$dbEntries,$options=array()){
		global $wpdb;
		$options=shortcode_atts(array('correspondingAttendeeId'=>null,'correspondingEvent'=>null),$options);
		
		$models=$this->extractModelsFromApiInput($apiInput);
		/*$dbEntries=array(EVENTS_DETAIL_TABLE=>array(),EVENTS_START_END_TABLE=>array());
		if(!empty($options['corresondingAttendeeId'])){
			$dbEntries[EVENTS_ATTENDEE_TABLE]=array();
		}*/
		foreach($models as $thisModel){
			$correspondingEventId=$options['correspondingEvent']['id'];
			/*
			$sql='SELECT * FROM '.EVENTS_START_END_TABLE.' WHERE id='.$thisModel['id'];
			$correspondingEventRow=$wpdb->get_row($sql,ARRAY_A );
			if(empty($correspondingEventRow)){
				throw new EspressoAPI_SpecialException(__("The Datetime you provided is missing from our system. If you are storing your Datetimes, please update them. Otherwise, contact Event Espresso support with a database dump and the current request information.","event_espresso"));
			}
			$correspondingEventId=$correspondingEventRow['event_id'];
			//$dbEntries[EVENTS_START_END_TABLE][$thisModel['id']]=array();
			$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['id']=$correspondingEventId;
			 
			 */
			/*if(isset($options['correspondingAttendeeId'])){
				$dbEntries[EVENTS_ATTENDEE_TABLE][$options['correspondingAttendeeId']]=array();
			}*/
			foreach($thisModel as $apiField=>$apiValue){
				switch($apiField){
					case 'id':
						$dbCol=$apiField;
						$dbTable=EVENTS_START_END_TABLE;
						$dbValue=$apiValue;
						$skipInsertionInArray=false;
						$thisModelId=$dbValue;
						break;
					//case 'is_primary': notion doesn't exist in 3.1 DB
					case 'event_start':
						$dateTimeInfo=$this->parseDate($apiValue);
						$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['start_date']=$dateTimeInfo['date'];
						$dbEntries[EVENTS_START_END_TABLE][$thisModel['id']]['start_time']=$dateTimeInfo['time'];
						if(isset($options['correspondingAttendeeId'])){
							$dbEntries[EVENTS_ATTENDEE_TABLE][$options['correspondingAttendeeId']]['start_date']=$dateTimeInfo['date'];
							$dbEntries[EVENTS_ATTENDEE_TABLE][$options['correspondingAttendeeId']]['event_time']=$dateTimeInfo['time'];
						}
						$skipInsertionInArray=true;
						break;
					case 'event_end':
						$dateTimeInfo=$this->parseDate($apiValue);
						$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['end_date']=$dateTimeInfo['date'];
						$dbEntries[EVENTS_START_END_TABLE][$thisModel['id']]['end_time']=$dateTimeInfo['time'];
						if(isset($options['correspondingAttendeeId'])){
							$dbEntries[EVENTS_ATTENDEE_TABLE][$options['correspondingAttendeeId']]['end_date']=$dateTimeInfo['date'];
							$dbEntries[EVENTS_ATTENDEE_TABLE][$options['correspondingAttendeeId']]['end_time']=$dateTimeInfo['time'];
						}
						$skipInsertionInArray=true;
						break;
					case 'registration_start':
						$dateTimeInfo=$this->parseDate($apiValue);
						$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['registration_start']=$dateTimeInfo['date'];
						$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['registration_startT']=$dateTimeInfo['time'];
						$skipInsertionInArray=true;
						break;
					case 'registration_start':
						$dateTimeInfo=$this->parseDate($apiValue);
						$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['registration_end']=$dateTimeInfo['date'];
						$dbEntries[EVENTS_DETAIL_TABLE][$correspondingEventId]['registration_endT']=$dateTimeInfo['time'];
						$skipInsertionInArray=true;
						break;
					case 'limit':
						$dbCol='reg_limit';
						$dbTable=EVENTS_START_END_TABLE;
						$dbValue=$apiValue;
						$skipInsertionInArray=false;
						break;
					case 'tickets_left':
					default:
						$skipInsertionInArray=true;
				}
				if(!$skipInsertionInArray){
					$dbEntries[$dbTable][$thisModelId][$dbCol]=$dbValue;
				}
			}
		}
		return $dbEntries;
	}
}
//new Events_Controller();