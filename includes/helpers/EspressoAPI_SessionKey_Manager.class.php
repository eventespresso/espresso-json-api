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
			$sessionKey=EspressoAPI_SessionKey_Manager::generateSessionKey();
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
			$sessionKey=EspressoAPI_SessionKey_Manager::generateSessionKey();
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
	
	
	/**
	 * generates random string for sessino key
	 * mostly taken from http://stackoverflow.com/questions/853813/how-to-create-a-random-string-using-php
	 * @return string 
	 */
	protected static function generateSessionKey(){
		$valid_chars="qwertyuiopasdfghjklzxcvbnm1234567890";
		$length=10;
		// start with an empty random string
		$random_string = "";

		// count the number of chars in the valid chars string so we know how many choices we have
		$num_valid_chars = strlen($valid_chars);

		// repeat the steps until we've created a string of the right length
		for ($i = 0; $i < $length; $i++){
			// pick a random number from 1 up to the number of valid chars
			$random_pick = mt_rand(1, $num_valid_chars);

			// take the random character out of the string of valid chars
			// subtract 1 from $random_pick because strings are indexed starting at 0, and we started picking at 1
			$random_char = $valid_chars[$random_pick-1];

			// add the randomly-chosen char onto the end of our string so far
			$random_string .= $random_char;
		}
		// return our finished random string
		return $random_string;
	}
}