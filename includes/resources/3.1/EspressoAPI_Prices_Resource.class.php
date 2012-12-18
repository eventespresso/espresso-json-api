<?php
/**
 *this file should actually exist in the Event Espresso Core Plugin 
 */
class EspressoAPI_Prices_Resource extends EspressoAPI_Prices_Resource_Facade{
	var $APIqueryParamsToDbColumns=array(
		'id'=>'Price.id',
		'name'=>'Price.price_type',
		'amount'=>'Price.event_cost',
		'limit'=>'Event.reg_limit'
	);
	var $calculatedColumnsToFilterOn=array('Price.remaining','Price.start_date','Price.end_date','Price.remaining','Price.description');
	var $selectFields="
		Price.id AS 'Price.id',
		Price.event_id AS 'Price.event_id',
		Price.event_cost AS 'Price.event_cost',
		Price.price_type AS 'Price.price_type',
		Event.reg_limit AS 'Price.limit',
		Price.surcharge AS 'Price.surcharge',
		Price.surcharge_type AS 'Price.surcharge_type',
		Price.member_price AS 'Price.member_price',
		Price.member_price_type AS 'Price.member_price_type'
		";
	var $relatedModels=array();
    function _create($createParameters){
       return new EspressoAPI_MethodNotImplementedException();
    }
	
	/**
	 * this APImodel overrides this function in order to return mutliple 'models' 
	 * from a single row, which is obviously not standard
	 * @param array $sqlResults 
	 * @param type $idKey
	 * @param type $idValue
	 * @return type 
	 */
	protected function extractMyUniqueModelsFromSqlResults($sqlResults,$idKey=null,$idValue=null){
		$filteredResults=array();
		foreach($sqlResults as $sqlResult){
			if((!empty($idKey) && !empty($idValue) && $sqlResult[$idKey]!= $idValue))
				continue;
			$prices=$this->_extractMyUniqueModelsFromSqlResults($sqlResult);
				foreach($prices as $key=>$price){
					if(isset($price['id']))
					$filteredResults[$price['id']]=$price;
				}
		}
		return $filteredResults;
	}	
	protected function processSqlResults($rows,$keyOpVals){
		global $wpdb;
		$attendeesPerEvent=array();
		$processedRows=array();
		foreach($rows as $row){
			if(empty($attendeesPerEvent[$row['Event.id']])){
				//because in 3.1 there can't be a limit per datetime, only per event, just count total attendees of an event
				$quantitiesAttendingPerRow=$wpdb->get_col( $wpdb->prepare( "SELECT quantity FROM {$wpdb->prefix}events_attendee WHERE event_id=%d;", $row['Event.id']) );
				$totalAttending=0;
				foreach($quantitiesAttendingPerRow as $quantity){
					$totalAttending+=intval($quantity);
				}
				$attendeesPerEvent[$row['Event.id']]=$totalAttending;//basically cache the result
			}
			$row['Price.limit']=intval($row['Event.reg_limit']);
			$row['Price.remaining']=intval($row['Price.limit'])-$attendeesPerEvent[$row['Event.id']];//$row['Event.reg_limit'];// just reutnr  abig number for now. Not sure how to calculate this. $row['StartEnd.reg_limit']-$attendeesPerEvent[$row['Event.id']];
			$row['Price.description']=null;
			$row['Price.start_date']=null;
			$row['Price.end_date']=null;
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
	 * @param array $sqlResults
	 * @param string/int $idKey
	 * @param string/int $idValue 
	 * @return array compatible with the required reutnr type for this model
	 */
	protected function _extractMyUniqueModelsFromSqlResults($sqlResult){
		$pricesToReturn=array();
		$priceTypeModel=  EspressoAPI_ClassLoader::load("Pricetypes",'Resource');
		$pricesToReturn['base']=array(
		'id'=>floatval($sqlResult['Price.id'].".0"),
		'amount'=>$sqlResult['Price.event_cost'],
		'name'=>$sqlResult['Price.price_type'],
		'description'=>$sqlResult['Price.description'],
		'limit'=>$sqlResult['Price.limit'],
		'remaining'=>$sqlResult['Price.remaining'],
		'start_date'=>$sqlResult['Price.start_date'],
		'end_date'=>$sqlResult['Price.end_date'],
		'Pricetype'=>$priceTypeModel->fakeDbTable[1]
		);
		if($sqlResult['Price.surcharge']!=0){
			if($sqlResult['Price.surcharge_type']=='pct')
				$priceType=$priceTypeModel->fakeDbTable[2];
			else
				$priceType=$priceTypeModel->fakeDbTable[3];
			
			$pricesToReturn['surcharge']=array(
			'id'=>floatval($sqlResult['Price.id'].".1"),
			'amount'=>$sqlResult['Price.surcharge'],
			'name'=>"Surcharge for ".$sqlResult['Price.price_type'],
			'description'=>null,
			'limit'=>$sqlResult['Price.limit'],
			'remaining'=>$sqlResult['Price.remaining'],
			'start_date'=>null,
			'end_date'=>null,
			"Pricetype"=>$priceType
			);
		}
		$pricesToReturn['member']=array(
		'id'=>floatval($sqlResult['Price.id'].".2"),
		'amount'=>$sqlResult['Price.member_price'],
		'name'=>$sqlResult['Price.member_price_type'],
		'description'=>null,
		'limit'=>$sqlResult['Price.limit'],
		'remaining'=>$sqlResult['Price.remaining'],
		'start_date'=>null,
		'end_date'=>null,
		"Pricetype"=>$priceTypeModel->fakeDbTable[4]
		);
		return $pricesToReturn;
	}
	
	/**
	 * Overrides parent extractMyUniqueModelFromSqlResults because when finding a single price, in the case we
	 * want a single price for a registration, each db table actually contains 2 or 3 (normal rate, optional surcharge, and memebr rate)
	 * so this function should grab the appropriate one (based on the Price's amount compared with what the
	 * registration's orig_price was.) If it doesn't find an exact price match,
	 * invents one with some filled-in info, but with the orig_price set to
	 * be teh amount
	 * @param array $sqlResults
	 * @param string $idKey like 'Attendee.id'
	 * @param string $idValue
	 * @return type 
	 */
	protected function extractMyUniqueModelFromSqlResults($sqlResults,$idKey=null,$idValue=null){
		$foundOrigPrice=false;
		foreach($sqlResults as $sqlResult){
			if($sqlResult[$idKey]==$idValue){
				$origPrice=$sqlResult['Attendee.orig_price'];
				$rowWithOrigPrice=$sqlResult;
				$foundOrigPrice=true;
				break;
			}
		}
		if(!$foundOrigPrice)
			$origPrice=0;
		
		$foundOrigPrice=false;
		$modelRepresentations=$this->extractMyUniqueModelsFromSqlResults($sqlResults, $idKey, $idValue);
		foreach($modelRepresentations as $modelRepresentation){
			if($modelRepresentation['amount']==$origPrice){
				$priceWhichMatchesOrigPrice=$modelRepresentation;
				$foundOrigPrice=true;
				break;
			}
		}
		if($foundOrigPrice && isset($priceWhichMatchesOrigPrice))
			return $priceWhichMatchesOrigPrice;
		else{
			$priceTypeModel=  EspressoAPI_ClassLoader::load("Pricetypes",'Resource');
			return array(
			'id'=>"0",
			'amount'=>$origPrice,
			'name'=>isset($rowWithOrigPrice['$Attendee.price_option'])?$rowWithOrigPrice['$Attendee.price_option']:'Unknown',
			'description'=>null,
			'limit'=>9999999,
			'remaining'=>999999,//$sqlResult['Event.remaining'],
			'start_date'=>null,
			'end_date'=>null,
			'Pricetype'=>$priceTypeModel->fakeDbTable[1]);

		}
	}
}
//new Events_Controller();