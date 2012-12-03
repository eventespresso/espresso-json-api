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
 * Events API Facade class
 *
 * @package			Espresso REST API
 * @subpackage	includes/APIFacades/Espresso_Events_Resource_Facade.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
//require_once("EspressoAPI_Generic_Resource_Facade.class.php");
abstract class EspressoAPI_Prices_Resource_Facade extends EspressoAPI_Generic_Resource_Facade{
	var $modelName="Price";
	var $modelNamePlural="Prices";
	/**
	 * array of requiredFields allowed for querying and which must be returned. other requiredFields may be returned, but this is the minimum set
	 * @var type 
	 */
	var $requiredFields=array(
		'id',
		'name',
		'amount',
		'description',
		'limit',
		'remaining',
		'start_date',
		'end_date',
		'Pricetype'=>array(
			'id',
			'name',
			'is_member',
			'is_discount',
			'is_tax',
			'is_percent',
			'is_global',
			'order')
	);
	
	 /**
	  * creation of event facade, calls concrete child class' _creatEvent function
	  * @param array $createParameters
	  * @return array 
	  */
     function create($createParameters){
         return $this->_createDatetime($createParameters);
     }
     abstract protected function _create($createParameters);
	 
}