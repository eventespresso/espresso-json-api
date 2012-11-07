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
 * @subpackage	includes/APIFacades/Espresso_Events_API_Facade.class.php
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
//require_once("EspressoAPI_Generic_API_Facade.class.php");
abstract class EspressoAPI_Categories_API_Facade extends EspressoAPI_Generic_API_Facade{
	var $modelName="Category";
	var $modelNamePlural="Categories";
	/**
	 * array of requiredFields allowed for querying and which must be returned. other requiredFields may be returned, but this is the minimum set
	 * @var type 
	 */
	var $requiredFields=array(
		'id',
		'name',
		'identifier',
		'description',
		'user'
	);

}