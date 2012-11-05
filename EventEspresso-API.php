<?php
/*
Plugin Name: Event Espresso API Plugin
Plugin URI: http://eventespresso.com
Description: A JSON API for Event Espresso
Version: 2.0.0
Author: Mike Nelson
*/
define('EspressoAPI_DIR_PATH',plugin_dir_path(__FILE__));

//constants relating to responses
define('EspressoAPI_STATUS','status');
define('EspressoAPI_STATUS_CODE','status_code');
define('ESpressoAPI_USER_FRIENDLY_STATUS','user_friendly_status');
define('EspressoAPI_RESPONSE_BODY','body');


require (dirname(__FILE__).'/includes/EspressoAPI_URL_Rewrite.class.php');
require (dirname(__FILE__).'/includes/EspressoAPI_Router.class.php');
require (dirname(__FILE__).'/includes/EspressoAPI_Response_Formatter.class.php');
require (EspressoAPI_DIR_PATH.'/includes/helpers/EspressoAPI_Exceptions.php');
require (EspressoAPI_DIR_PATH.'/includes/helpers/EspressoAPI_ClassLoader.class.php');
require (EspressoAPI_DIR_PATH.'/includes/helpers/EspressoAPI_Permissions_Wrapper.class.php');
require (EspressoAPI_DIR_PATH.'/includes/helpers/EspressoAPI_SessionKey_Manager.class.php');
