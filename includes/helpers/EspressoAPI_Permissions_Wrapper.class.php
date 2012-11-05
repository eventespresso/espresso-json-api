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
 * Events API Permission Wrapper class
 *
 * @package			Espresso REST API
 * @subpackage	includes/helpers/espressoAPI_Permissions_Wrapper.class.php
 * @author				Mike Nelson
 *
 * wraps functions contained in the espresso-permissions and espresso-permissions-pro plugin. 
 * If neither of those plugins is installed, handles the method call in the logically manner
 * (eg, on EspressoAPI_Permissions_Wrapper::espresso_is_admin, if neither permissions plugin is 
 * installed, it just checks if the current user is an admin)
 * ------------------------------------------------------------------------
 */
class EspressoAPI_Permissions_Wrapper{
	
	/**
	 *if espresso_is_my_event isnt defined, just returns if the user is an admin.
	 * I think 'espresso_can_manage_event' would be a better name for this function
	 * @param int $event_id
	 * @return boolean 
	 */
	static function espresso_is_my_event($event_id){
		if(function_exists('espresso_is_my_event')){
			return espresso_is_my_event($event_id);
		}else{
			return current_user_can('administrator');
		}
	}
	
	/**
	 * if espresso_is_admin isn't defined, jsut reutnrs if the user is an admin
	 * @return int 
	 */
	static function espresso_is_admin(){
		if(function_exists('espresso_is_admin')){
			return espresso_is_admin();
		}else{
			return current_user_can('administrator');
		}
	}
}
