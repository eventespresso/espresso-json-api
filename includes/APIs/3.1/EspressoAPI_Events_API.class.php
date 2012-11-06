<?php

/**
 * this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Events_API extends EspressoAPI_Events_API_Facade {
	var $APIqueryParamsToDbColumns = array(
		'id'=>'Event.id',
		'name'=>'Event.event_name',
		'description'=>'Event.description',
		'status'=>'Event.event_status',
		'limit'=>'Event.reg_limit',
		'group_registrations_allowed'=>'Event.group_registration_allowed',
		'group_registrations_max'=>'Event.group_registration_max',
		'active'=>'Event.is_active',
		'member_only'=>'Event.member_only',
		'virtual_url'=>'Event.virtual_url',
		'call_in_number'=>'Event.virtual_phone',
		'phone'=>'Event.phone');
	var $selectFields="
		Event.id AS 'Event.id',
		Event.event_name AS 'Event.name',
		Event.event_desc AS 'Event.description',
		Event.event_meta AS 'Event.meta',
		Event.event_status as 'Event.status',
		Event.reg_limit AS 'Event.limit',
		Event.allow_multiple AS 'Event.group_registrations_allowed',
		Event.additional_limit AS 'Event.group_registrations_max',
		Event.is_active AS 'Event.active',
		Event.member_only AS 'Event.member_only',
		Event.virtual_url AS 'Event.virtual_url',
		Event.virtual_phone AS 'Event.call_in_number',
		Event.phone AS 'Event.phone'";
	var $relatedModels=array(
		"Datetime"=>array('modelNamePlural'=>"Datetimes",'hasMany'=>true),
		"Venue"=>array('modelNamePlural'=>"Venues",'hasMany'=>true),
		"Category"=>array('modelNamePlural'=>"Categories",'hasMany'=>true),
		'Promocode'=>array('modelNamePlural'=>'Promocodes','hasMany'=>true),
		'Price'=>array('modelNamePlural'=>'Prices','hasMany'=>true));
	var $statusConversions=array(
				'S'=>'seconary/waitlist',
				'X'=>'expired',
				'A'=>'active',
				'D'=>'denied',
				'IA'=>'inactive',
				'O'=>'ongoing',
				'P'=>'pending',
				'R'=>'draft');
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
			if (EspressoAPI_Permissions_Wrapper::espresso_is_my_event($event['Event.id']))
				$resultsICanView[] = $event;
		}
		return $resultsICanView;
	}
	
	protected function constructSQLWhereSubclause($columnName,$operator,$value){
		
		switch($columnName){
			case 'status':
				$apiParamToDbStatus=array_flip($this->statusConversions);
				if($operator=="IN"){
					$valuesSeperated=explode(",",$value);
					$valuesConverted=array();
					foreach($valuesSeperated as $singleValueInIn){
						$valuesConverted[]=$apiParamToDbStatus[$singleValueInIn];
					}
					$value=implode(",",$valuesConverted);
				}else{
					$value=$apiParamToDbStatus[$value];
				}
				//now we've converted the status from something like 'Active' to 'A', handle the value as usual
				break;
			case 'active':
			case 'member_only':
				if($value=='true'){
					$value='Y';
				}else{
					$value='N';
				}
		}
				
		return parent::constructSQLWhereSubclause($columnName, $operator, $value);		
	}
	

	/**
	 *for taking the info in the $sql row and formatting it according
	 * to the model
	 * @param $sqlRow a row from wpdb->get_results
	 * @return array formatted for API, but only toplevel stuff usually (usually no nesting)
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
			$metaDatas=unserialize($sqlResult['Event.meta']);
			$statusUnconverted=$sqlResult['Event.status'];
			
			$eventActive=($sqlResult['Event.active']=='Y')?true:false;
			$memberOnly=($sqlResult['Event.member_only']=='Y')?true:false;
			$groupRegistrationsAllowed=$sqlResult['Event.group_registrations_allowed']=='Y'?true:false;
			$event=array(
				'id'=>$sqlResult['Event.id'],
				'name'=>$sqlResult['Event.name'],
				'description'=>$sqlResult['Event.description'],
				'metadata'=>$metaDatas,
				'status'=>$this->statusConversions[$statusUnconverted],
				'limit'=>$sqlResult['Event.limit'],
				'group_registrations_allowed'=>$groupRegistrationsAllowed,
				'group_registrations_max'=>$sqlResult['Event.group_registrations_max'],
				'active'=>$eventActive,
				'thumbnail_url'=>@$metaDatas['event_thumbnail_url'],
				'member_only'=>$memberOnly,
				'virtual_url'=>$sqlResult['Event.virtual_url'],
				'call_in_number'=>$sqlResult['Event.call_in_number'],
				'phone'=>$sqlResult['Event.phone']
				);
			return $event;
	}

	function _create($createParameters) {
		if (EspressoAPI_Permissions_Wrapper::espresso_is_admin())
			throw new EspressoAPI_MethodNotImplementedException();
		else
			throw new EspressoAPI_UnauthorizedException();
	}


}