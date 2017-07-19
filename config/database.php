<?php
 /**
 *  Project: Securly PHP Question
 *  Last Modified Date: 2017 June
 *  Developer: Cooltey Feng
 *  File: config/database.php
 *  Description: Database Settings
 */
 ini_set('session.cookie_httponly', 1);
 ini_set("magic_quotes_gpc", "on");
 ini_set("display_errors", "on");
 error_reporting(E_ALL & ~E_NOTICE);

 // cookie domain name
 $GLOBALS['cookie_folder_name'] = "/securly";

 // // session setting
 session_set_cookie_params(0, $GLOBALS['cookie_folder_name'], "", FALSE, TRUE);
 session_start();

 // use PDO to make the connection
 $db_host 		= "127.0.0.1";
 $db_name 		= "securly";
 $db_username 	= "root";
 $db_password 	= "";

 try {
	$db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_username, $db_password);
	$db->exec("SET CHARACTER SET utf8");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 }
 catch( PDOException $Exception ) {
 }


 header('X-Frame-Options: DENY');

 
?>