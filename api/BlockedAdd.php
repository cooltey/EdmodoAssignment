<?php
 /**
 *  Project: Securly PHP Question
 *  Last Modified Date: 2017 June
 *  Developer: Cooltey Feng
 *  File: api/BlockedAdd.php
 *  Description: API - Add Blocked URL
 */
	 
	 include_once("../config/database.php");
	 include_once("../class/lib.php");
	 include_once("../class/api.php");
	 include_once("../class/page.php");
 	 include_once("../class/auth.php");

	 // get data
	 $getData = $_REQUEST;
	 
	 // call lib class
	 $getLib = new Lib();


	 // call auth 
	 $getAuth = new Auth($db, $getLib);

	 if($getAuth->checkAuth("blocked_add", $_SESSION)){

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
								
		 $result_array = $getMain->BlockedAdd($getData);

		 // output json format
		 $getLib->outputJson($result_array);
	}
?>