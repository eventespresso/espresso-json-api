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
abstract class EspressoAPI_Venues_Resource_Facade extends EspressoAPI_Generic_Resource_Facade{
	var $modelName="Venue";
	var $modelNamePlural="Venues";
	/**
	 * array of requiredFields allowed for querying and which must be returned. other requiredFields may be returned, but this is the minimum set
	 * @var type 
	 */
	var $requiredFields=array(
		'id',
		'name',
		'identifier',
		'address',
		'address2',
		'city',
		'state',
		'zip',
		'country',
		'user'
	);
	
	
}