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
class EspressoAPI_Permissions_Wrapper {

	/**
	 * if espresso_is_my_event isnt defined, just returns if the user is an admin.
	 * I think 'espresso_can_manage_event' would be a better name for this function
	 * @param int $event_id
	 * @return boolean 
	 */
	static function espresso_is_my_event($event_id) {
		if (function_exists('espresso_is_my_event')) {
			return espresso_is_my_event($event_id);
		} else {
			return current_user_can('administrator');
		}
	}

	/**
	 * Utilizes the permissions addon's function to determine if 
	 * the user who's trying to login should be allowed to use EE (and the API)
	 * @return boolean
	 */
	static function current_user_has_espresso_permissions(){
		if(function_exists('espresso_is_admin')){
			return espresso_is_admin() || current_user_can('espresso_event_manager');
		}else{
			return current_user_can('administrator');
		}
	}
	
	static function current_user_can_access_some($httpMethod = 'get',$resource = 'Events'){
		global $espressoAPI_public_access_query;
		//at a minumum the current user must either be using the public access session key,
		//or be an ee user.
		//ie, if they aren't authenticated
		//or if they're just a subscriber or author  (neither should happen because the router should have rejected them)
		//then they should get rejected here
		if( ! $espressoAPI_public_access_query && ! self::current_user_has_espresso_permissions() ){
			
			return false;
		}
		
		switch ($httpMethod) {
			//for VIEWING of info, make certain resources publicley-available
			//and available to any authenticated event espresso user/admin
			case'get':
			case'GET':
				switch ($resource) {
					case 'Events':
					case 'Categories':
					case 'Datetimes':
					case 'Prices':
					case 'Pricetypes':
					case 'Venues':
					case 'Questions':
					case 'Question_Groups':
						return $espressoAPI_public_access_query || self::current_user_has_espresso_permissions();
					//the following resources are NOT available publicley
					//and only to certain privileged event espresso users
					case 'Promocodes':
						return self::current_user_has_espresso_permissions();
					case 'Attendees':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Registrations':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Transactions':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Payments':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Answers':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					default:
						return self::current_user_has_espresso_permissions();
				}
				break;
			//creating, updating, or deleting the following resources generally take more specific privileges
			case'post':
			case'POST':
			case'put':
			case'PUT':
			case'delete':
			case'DELETE':
			default:
				switch ($resource) {
					case 'Events':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Categories':
						return self::current_user_has_espresso_permission('espresso_manager_categories');
					case 'Datetimes':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Prices':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Pricetypes':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Venues':
						return self::current_user_has_espresso_permission('espresso_manager_venue_manager');
					case 'Questions':
						return self::current_user_has_espresso_permission('espresso_manager_form_builder');
					case 'Question_Groups':
						return self::current_user_has_espresso_permission('espresso_manager_form_groups');
					case 'Promocodes':
						return self::current_user_has_espresso_permission('espresso_manager_discounts');
					case 'Attendees':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Registrations':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Transactions':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Payments':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Answers':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					default:
						return self::current_user_has_espresso_permissions();
				}
				break;
		}
	}
	
	/**
	 * Checks if teh current user has access to this specific resource indicated by $id, 
	 * using the http method $httpMethod.
	 * @param string $httpMethod like 'GET'
	 * @param string $resource like 'Events'
	 * @param int|float|string $id
	 * @return boolean
	 */
	static function current_user_can_access_specific($httpMethod = 'get',$resource='Events',$id = null){
		if(self::current_user_can_access_all($httpMethod,$resource)){
			return true;
		}
		//so the user dosn't have GENERAL permission to access this resource. maybe
		//does the user have SPECIFIC access to the resource with this id?
		$resource = EspressoAPI_ClassLoader::load($resource, 'Resource');
		if ( $resource->current_user_has_specific_permission_for($httpMethod,$id)){
//			echo "but they do have permission for this one!";
			return true;
		}
//		echo "well, no. they can't";
		return false;
	}

	/**
	 * wrapper for checking if the current user has the necessary permission to
	 * access/edit this resource.
	 * initially though, we've just hard-coded the permissions
	 * @param $httpMethod like get,post,put,delete
	 * @param $resource name of API Model pluralized which user is trying to access,eg 'Events','Categories', etc.
	 * @global boolean $espressoAPI_public_access_query indicates that the current request is using the public-access session key
	 * @return booelean whether the current user can perform the given $httpMethod on the specified $resource
	 */
	static function current_user_can_access_all($httpMethod = 'get', $resource = 'Events') {
		global $espressoAPI_public_access_query;
		//at a minumum the current user must either be using the public access session key,
		//or be an ee user.
		//ie, if they aren't authenticated
		//or if they're just a subscriber or author  (neither should happen because the router should have rejected them)
		//then they should get rejected here
		if( ! $espressoAPI_public_access_query && ! self::current_user_has_espresso_permissions() ){
			return false;
		}
		
		
		//ok, so now we know they're either a public user or an ee user
		switch ($httpMethod) {
			//for VIEWING of info, make certain resources publicley-available
			//and available to any authenticated event espresso user/admin
			case'get':
			case'GET':
				switch ($resource) {
					case 'Events':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Categories':
					case 'Datetimes':
					case 'Prices':
					case 'Pricetypes':
					case 'Venues':
					case 'Questions':
					case 'Question_Groups':
						return $espressoAPI_public_access_query || self::current_user_has_espresso_permissions();
					//the following resources are NOT available publicley
					//and only to certain privileged event espresso users
					case 'Promocodes':
						return self::current_user_has_espresso_permissions();
					case 'Attendees':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Registrations':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Transactions':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Payments':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					case 'Answers':
						return self::current_user_has_espresso_permission('espresso_manager_events');
					default:
						return current_user_can('administrator');
				}
			//creating, updating, or deleting the following resources generally take more specific privileges
			case'post':
			case'POST':
			case'put':
			case'PUT':
			case'delete':
			case'DELETE':
			default:
				switch ($resource) {
					case 'Events':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Categories':
						return self::current_user_has_espresso_permission('espresso_manager_categories');
						
					case 'Datetimes':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Prices':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Pricetypes':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Venues':
						return self::current_user_has_espresso_permission('espresso_manager_venue_manager');
						
					case 'Questions':
						return self::current_user_has_espresso_permission('espresso_manager_form_builder');
						
					case 'Question_Groups':
						return self::current_user_has_espresso_permission('espresso_manager_form_groups');
						
					case 'Promocodes':
						return self::current_user_has_espresso_permission('espresso_manager_discounts');
						
					case 'Attendees':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Registrations':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Transactions':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Payments':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					case 'Answers':
						return self::current_user_has_espresso_permission('espresso_manager_events');
						
					default:
						return current_user_can('administrator');
				}
				
		}
	}

	/**
	 * 
	 * @global array $espresso_manager set in the permissions addons
	 * @global WP_User $current_user
	 * @global boolean $espressoAPI_public_access_query whether the current query was done via the public access session key or not
	 * @param string $permission like espresso_manager_events (one of the array keys in
	 * $espresso_manager) 
	 * @return boolean
	 */
	private static function current_user_has_espresso_permission($permission) {
		global $espresso_manager;
		//if user isn't logged in, only grant them access to particular stuff
//		echo "current user is :";
//				var_dump($current_user);
//		if( ! $current_user->ID){
////			echo "override epsresso_manager";
//			$espresso_manager = array(
//				'espresso_manager_events'=>true,
//				'espresso_manager_venue_manager'=>true,
//				//espresso_manager_form_builder
//				//espresso_manager_form_groups
//				//espresso_manager_categories
//				//espresso_manager_discounts
//			);
//			if(isset($espresso_manager[$permission]) && $espresso_manager[$permission] ){
//				return true;
//			}
//		}else{
		$can = current_user_can(isset($espresso_manager[$permission]) ? $espresso_manager[$permission] : 'administrator');
		return $can;
//		}
	}

}
