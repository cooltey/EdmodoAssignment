<?php
 /**
 *  Project: Edmodo Assignment
 *  Last Modified Date: 2017 July
 *  Developer: Cooltey Feng
 *  File: class/api.php
 *  Description: API Class
 */
	 
	 include_once("./config/database.php");
	 include_once("./class/lib.php");
	 include_once("./class/api.php");

	 // get data
	 $getData = $_REQUEST;
	 
	 // call lib class
	 $getLib = new Lib();

	 // prevent magic quotes
	 $getLib->preventMagicQuote();
	 if(!class_exists("Lib")){
			echo "illegal";
			exit;
	 } 
	 
	 // call main class
	 $getMain = new API($db, $getLib);
	 	 
	 // return array
	 $result_array = array();
							
	 $result_array = $getMain->init();

	 // output json format
	 $getLib->outputJson($result_array);
?>