<?php
/**
 * Description of EspressoAPI_SessionKey_Manager
 *
 * @author mnelson4
 */
define('EspressoAPI_SessionKey_MetaKey','EspressoAPI_SessionKey');
class EspressoAPI_SessionKey_Manager {
	//fetch session key for user
	static function getSessionKeyForUser($userId){
		$sessionKey=get_user_meta($userId,EspressoAPI_SessionKey_MetaKey,true);
		if(empty($sessionKey)){
			$sessionKey=EspressoAPI_Functions::generateRandomString();
			update_user_meta($userId,EspressoAPI_SessionKey_MetaKey,$sessionKey);
		}
		return $sessionKey;
	}
	//flush all sessionkeys
	static function regeneratAllSessionKeys(){
		global $wpdb;
		$query="SELECT * FROM {$wpdb->users} INNER JOIN {$wpdb->usermeta} 
			ON {$wpdb->users}.ID={$wpdb->usermeta}.user_id 
			WHERE meta_key='".EspressoAPI_SessionKey_MetaKey."'";
		$users=$wpdb->get_results($query,ARRAY_A );
		var_dump($users);
		foreach($users as $user){
			$sessionKey=EspressoAPI_Functions::generateRandomString();
			update_user_meta($user['ID'],EspressoAPI_SessionKey_MetaKey,$sessionKey);
		}
	}
	//get user from sessionKey
	static function getUserFromSessionKey($sessionKey){
		global $wpdb;
		$query=$wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} 
			WHERE meta_key=%s 
				AND
				meta_value=%s",EspressoAPI_SessionKey_MetaKey,$sessionKey);
		$userId=$wpdb->get_var($query);
		if(empty($userId)){//we couldn't find a user to match that session key, so they must not be authorized
			throw new EspressoAPI_UnauthorizedException();
		}
		$user=get_user_by('id',$userId);
		return $user;
	}
	//expire session key if older than 
	
	
	
}