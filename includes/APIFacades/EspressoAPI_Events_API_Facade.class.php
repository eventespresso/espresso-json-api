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
abstract class EspressoAPI_Events_API_Facade extends EspressoAPI_Generic_API_Facade{
	var $modelName="Event";
	var $modelNamePlural="Events";
	
	var $requiredFields = array(
		'id',
		'name',
		'description',
		'status',
		'limit',
		'group_registrations_allowed',
		'group_registrations_max',
		'active',
		'member_only',
		'virtual_url',
		'call_in_number',
		'phone',
		'metadata');
}