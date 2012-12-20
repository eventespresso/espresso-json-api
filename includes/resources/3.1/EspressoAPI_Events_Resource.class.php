<?php

/**
 * this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Events_Resource extends EspressoAPI_Events_Resource_Facade {
	var $APIqueryParamsToDbColumns = array(
		'id'=>'Event.id',
		'name'=>'Event.event_name',
		'code'=>'Event.event_code',
		'description'=>'Event.event_desc',
		'status'=>'Event.event_status',
		'limit'=>'Event.reg_limit',
		'group_registrations_allowed'=>'Event.group_registration_allowed',
		'group_registrations_max'=>'Event.group_registration_max',
		'active'=>'Event.is_active',
		'member_only'=>'Event.member_only',
		'virtual_url'=>'Event.virtual_url',
		'call_in_number'=>'Event.virtual_phone',
		'phone'=>'Event.phone');
	var $calculatedColumnsToFilterOn=array('status');
	

	
	var $selectFields="
		Event.id AS 'Event.id',
		Event.event_code AS 'Event.event_code',
		Event.event_name AS 'Event.event_name',
		Event.event_desc AS 'Event.event_desc',
		Event.event_meta AS 'Event.event_meta',
		Event.event_status as 'Event.event_status',
		Event.reg_limit AS 'Event.reg_limit',
		Event.allow_multiple AS 'Event.allow_multiple',
		Event.additional_limit AS 'Event.additional_limit',
		Event.is_active AS 'Event.is_active',
		Event.member_only AS 'Event.member_only',
		Event.virtual_url AS 'Event.virtual_url',
		Event.virtual_phone AS 'Event.virtual_phone',
		Event.phone AS 'Event.phone'";
	var $relatedModels=array(
		"Datetime"=>array('modelName'=>'Datetime','modelNamePlural'=>"Datetimes",'hasMany'=>true),
		"Venue"=>array('modelName'=>'Venue','modelNamePlural'=>"Venues",'hasMany'=>true),
		"Category"=>array('modelName'=>'Category','modelNamePlural'=>"Categories",'hasMany'=>true),
		'Promocode'=>array('modelName'=>'Promocode','modelNamePlural'=>'Promocodes','hasMany'=>true),
		'Price'=>array('modelName'=>'Price','modelNamePlural'=>'Prices','hasMany'=>true));
	var $statusConversions=array(
				'S'=>'secondary/waitlist',
				'X'=>'expired',
				'A'=>'active',
				'D'=>'denied',
				'IA'=>'inactive',
				'O'=>'ongoing',
				'P'=>'pending',
				'R'=>'draft');
	/*
	 * overrides parent constructSQLWherSubclauses in order to attach an additional wherecaluse
	 * which will ensure the prices found match the ones the attendees purchased
	 */
	protected function constructSQLWhereSubclauses($keyOpVals){
		$whereSqlArray=parent::constructSQLWhereSubclauses($keyOpVals);
		global $current_user;
		if($current_user->ID==0){//public users can only see active events
			$whereSqlArray[]="Event.event_status IN ('A','O','S') AND Event.is_active='Y'";
		}
		return $whereSqlArray;
	}
	

	
	
	function getManyConstructQuery($sqlSelect,$whereSql){
		global $wpdb;
		$sql = "
            SELECT
				{$this->selectFields},
				{$sqlSelect}
            FROM
                {$wpdb->prefix}events_detail Event
			LEFT JOIN
				{$wpdb->prefix}events_start_end StartEnd ON Event.id=StartEnd.event_id
			LEFT JOIN
				{$wpdb->prefix}events_venue_rel VenueRel ON Event.id=VenueRel.event_id
			LEFT JOIN
				{$wpdb->prefix}events_venue Venue ON VenueRel.venue_id=Venue.id
			LEFT JOIN
				{$wpdb->prefix}events_category_rel CategoryRel ON Event.id=CategoryRel.event_id
			LEFT JOIN
				{$wpdb->prefix}events_category_detail Category ON CategoryRel.cat_id=Category.id
			LEFT JOIN
				{$wpdb->prefix}events_discount_rel PromocodeRel ON PromocodeRel.event_id=Event.id
			LEFT JOIN
				{$wpdb->prefix}events_discount_codes Promocode ON Promocode.id=PromocodeRel.discount_id
			LEFT JOIN
				{$wpdb->prefix}events_prices Price ON Price.event_id=Event.id
			
			$whereSql";
		return $sql;
	}
	protected function processSqlResults($results,$keyOpVals){
		$resultsICanView = array();
		foreach ($results as $event) {
			//if (EspressoAPI_Permissions_Wrapper::espresso_is_my_event($event['Event.id']))//allow all users to at least 'see' an event, but probably not moredetails
				$resultsICanView[] = $event;
				
		}
		return $resultsICanView;
	}
	
	/**
	 * important to remember: paramname is the API param, but this constructs SQL
	 * @param string $paramName eg 'Event.status'
	 * @param string $operator representing SQL operator, eg <, LIKE, etc.
	 * @param string $value api value, eg true instead of 'Y'
	 * @return string of sql 
	 */
	protected function constructSQLWhereSubclause($paramName,$operator,$value){
		
		switch($paramName){
			case 'Event.status':
				$apiParamToDbStatus=array_flip($this->statusConversions);
				$column=$this->convertApiParamToDBColumn($paramName);
				$value=$this->constructValueInWhereClause($operator,$value,$apiParamToDbStatus,'Event.status');
				/*if($operator=="IN"){
					$valuesSeperated=explode(",",$value);
					$valuesConverted=array();
					foreach($valuesSeperated as $singleValueInIn){
						$valuesConverted[]=$apiParamToDbStatus[$singleValueInIn];
					}
					$value=implode(",",$valuesConverted);
				}else{
					$value=$apiParamToDbStatus[$value];
				}*/
				//now we've converted the status from something like 'Active' to 'A', handle the value as usual
				return "$column $operator $value";
			case 'Event.active':
			case 'Event.member_only':
			case 'Event.group_registrations_allowed':
				$column=$this->convertApiParamToDBColumn($paramName);
				$value=$this->constructValueInWhereClause($operator,$value,array('true'=>'Y','false'=>'N'));
				return "$column $operator $value";
		}		
		return parent::constructSQLWhereSubclause($paramName, $operator, $value);		
	}
	

	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
			if(empty($sqlResult['Event.id'])){
				return null;
			}
			$metaDatas=unserialize($sqlResult['Event.event_meta']);
			$statusUnconverted=$sqlResult['Event.event_status'];
			
			$eventActive=($sqlResult['Event.is_active']=='Y')?true:false;
			$memberOnly=($sqlResult['Event.member_only']=='Y')?true:false;
			$groupRegistrationsAllowed=$sqlResult['Event.allow_multiple']=='Y'?true:false;
			$event=array(
				'id'=>$sqlResult['Event.id'],
				'code'=>$sqlResult['Event.event_code'],
				'name'=>$sqlResult['Event.event_name'],
				'description'=>$sqlResult['Event.event_desc'],
				'metadata'=>$metaDatas,	
				'status'=>$this->statusConversions[$statusUnconverted],
				'limit'=>$sqlResult['Event.reg_limit'],
				'group_registrations_allowed'=>$groupRegistrationsAllowed,
				'group_registrations_max'=>$sqlResult['Event.additional_limit'],
				'active'=>$eventActive,
				//'thumbnail_url'=>@$metaDatas['event_thumbnail_url'],
				'member_only'=>$memberOnly,
				'virtual_url'=>$sqlResult['Event.virtual_url'],
				'call_in_number'=>$sqlResult['Event.virtual_phone'],
				'phone'=>$sqlResult['Event.phone']
				);
			return $event;
	}

	/**
	 * gets all the database column values from api input
	 * @param array $apiInput either like array('events'=>array(array('id'=>... 
	 * //OR like array('event'=>array('id'=>...
	 * @return array like array('wp_events_attendee'=>array(12=>array('id'=>12,name=>'bob'... 
	 */
	function extractMyColumnsFromApiInput($apiInput,$dbEntries,$options=array()){
		$models=$this->extractModelsFromApiInput($apiInput);
		//$dbEntries=array(EVENTS_DETAIL_TABLE=>array());
		$options=shortcode_atts(array('correspondingAttendeeId'=>null),$options);
		
		foreach($models as $thisModel){
			//$dbEntries[EVENTS_DETAIL_TABLE][$thisModel['id']]=array();
			foreach($thisModel as $apiField=>$apiValue){
				switch($apiField){
					case 'id':
					
						$dbCol=$apiField;
						$dbValue=$apiValue;
						if(isset($options['correspondingAttendeeId'])){
							$dbEntries[EVENTS_ATTENDEE_TABLE][$options['correspondingAttendeeId']]['event_id']=$apiValue;
						}
						$thisModelId=$dbValue;
						break;
					case 'virtual_url':
					case 'phone':
						$dbCol=$apiField;
						$dbValue=$apiValue;
						break;
					case 'name':
						$dbCol='event_name';
						$dbValue=$apiValue;
						break;
					case 'code':
						$dbCol='event_code';
						$dbValue=$apiValue;
						break;
					case 'description':
						$dbCol='event_desc';
						$dbValue=$apiValue;
						break;
					case 'metadata':
						$dbCol='event_meta';
						$dbValue=serialize($apiValue);
						break;
					case 'status':
						$dbCol='event_status';
						$flippedStatusConversions=array_flip($this->statusConversions);
						$dbValue=$flippedStatusConversions[$apiValue];
						break;
					case 'limit':
						$dbCol='reg_limit';
						$dbValue=$apiValue;
						break;
					case 'group_registrations_allowed':
						$dbCol='allow_multiple';
						$dbValue=($apiValue?'Y':'N');
						break;
					case 'group_registrations_max':
						$dbCol='additional_limit';
						$dbValue=$apiValue;
						break;
					case 'active':
						$dbCol='is_active';
						$dbValue=($apiValue?'Y':'N');
						break;
					/*case 'thumbnail_url'://ignore this input. This needs to be changed
						if(!array_key_exists('metadata',$dbEntries[$dbTable][$thisModel['id']])){
							$dbEntries[$dbTable][$thisModel['id']]['metadata']=array();
						}
						$dbEntries[$dbTable][$thisModel['id']]['metadata']['thumbnail_url']=$dbValue;
						continue;*/
					case 'member_only':
						$dbCol='member_only';
						$dbValue=($apiValue?'Y':'N');
						break;
					case 'call_in_number':
						$dbCol='virtual_phone';
						$dbValue=$apiValue;
						break;
				
				}
				$dbEntries[EVENTS_DETAIL_TABLE][$thisModelId][$dbCol]=$dbValue;
			}
		}
		return $dbEntries;
	}

}