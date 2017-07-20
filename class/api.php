<?php
 /**
 *  Project: Edmodo Assignment
 *  Last Modified Date: 2017 July
 *  Developer: Cooltey Feng
 *  File: class/api.php
 *  Description: API Class
 */
 
class API{

	var $db;
	var $getLib;
	
	function API($get_db, $get_lib){				
		$this->getLib 				= $get_lib;
		$this->db					= $get_db;
	}

	function OutPutMessage($call_id){
		$theOutArray =  array("200", "404", "500");

		return $theOutArray[$call_id];
	}

	function init(){
		$method = $_SERVER['REQUEST_METHOD'];
		$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

		switch ($method) {

		  case 'PUT':
		  		$getId = intval($this->getLib->setFilter($_GET['id']));
		  		return $this->PutMetadata($getId);  
		    break;

		  case 'POST': 
		    	return $this->AddMetadata();  
		    break;

		  case 'GET':
		    	return $this->GetMetadata($_GET);  
		    break;

		  case 'DELETE':
		  		$getId = intval($this->getLib->setFilter($_GET['id']));
		    	return $this->DeleteMetadata($getId);  
		    break;

		  default:
		    break;
		}

		return null;
	}

	// Get Schools
	function GetMetadata($getData){

		$returnArray = array("StatusCode"	=> $this->OutPutMessage(1),
							 "Total"		=> 0,
							 "List"			=> array());

		if($this->getLib->checkVal($getData['edmodo_shool_ids']) 
			|| $this->getLib->checkVal($getData['id'])){  
			

			$getId = intval($this->getLib->setFilter($getData['id']));
			$getEdmodoSchoolId = $this->getLib->setFilter($getData['edmodo_shool_ids']);
			$whereStr = "";

			if($getId != 0){
				$whereStr 	= " AND `s_id` = :id ";

			}else if($getEdmodoSchoolId != ""){
				$whereStr	= " AND FIND_IN_SET(`edmodo_s_id`, :id) ";
				$getId 		= $getEdmodoSchoolId;

			}

			if($whereStr != ""){

				// get data from database
				try{


						$status = "1";
						$sumArray = array();

						$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

						$queryArray = array("url" => $actual_link,
											"external_school" => array(), 
											"external_school_rating" => array(),
											"external_school_subject_stats" => array());


						// get external_school
						$sql = "SELECT  
									`s_id` AS `id`,
									`edmodo_s_id` AS `edmodo_school_id`,
									`s_url` AS `external_school_url`,
									`s_name` AS `name`,
									`s_grades` AS `grades`,
									`s_students` AS `student_count`,
									`s_type` AS `school_type`,
									`s_nces_id` AS `nces_id`,
									`s_create_time` AS `created_at`,
									`s_update_time` AS `updated_at`
								FROM `schools` 
								WHERE `s_status` = :status
								{$whereStr}
								ORDER BY `s_create_time` DESC";

						$sth = $this->db->prepare($sql);
						$sth->bindValue(":status", $status);
						$sth->bindValue(":id", $getId);
						$sth->execute();

						$count = $sth->rowCount();

						// start loop
						foreach($this->getLib->fetchArray($sth) AS $rowData){

							// add school rows into array
							$queryArray['external_school'] = $rowData;

							// get school_rating
							$sql = "SELECT 
										`sr_id` AS `id`,
										`s_id` AS `external_school_id`,
										`sr_five` AS `five`,
										`sr_four` AS `four`,
										`sr_three` AS `three`,
										`sr_two` AS `two`,
										`sr_one` AS `one`,
										`sr_average` AS `average`,
										`sr_num_ratings` AS `num_ratings`,
										`sr_create_time` AS `created_at`,
										`sr_update_time` AS `updated_at` 
									FROM `school_rating` 
									WHERE `sr_status` = :status
									AND `s_id` = :s_id
									ORDER BY `sr_create_time` DESC";

							$sth = $this->db->prepare($sql);
							$sth->bindValue(":status", $status);
							$sth->bindValue(":s_id", $rowData['id']);
							$sth->execute();

							// add school rating rows into array
							$queryArray['external_school_rating'] = $this->getLib->fetchArray($sth);

							// get subject_stats 
							$sql = "SELECT 
										`st_id` AS `id`,
										`s_id` AS `external_school_id`,
										`edmodo_ss_id` AS `edmodo_subject_id`,
										`st_avg` AS `avg`,
										`st_state_avg` AS `state_avg`,
										`st_create_time` AS `created_at`,
										`st_update_time` AS `updated_at` 
									FROM `school_subject_stats` 
									WHERE `st_status` = :status
									AND `s_id` = :s_id
									ORDER BY `st_create_time` DESC";

							$sth = $this->db->prepare($sql);
							$sth->bindValue(":status", $status);
							$sth->bindValue(":s_id", $rowData['id']);
							$sth->execute();


							// add school rating rows into array
							$queryArray['external_school_subject_stats'] = $this->getLib->fetchArray($sth);

							// push into sumarray
							array_push($sumArray, $queryArray);

						}

						// output data
						$returnArray['StatusCode']	= $this->OutPutMessage(0);
						$returnArray['Total']		= $count;
						$returnArray['List']	    = $sumArray;

				}catch(Exception $e){
					$error_msg = "Database Error";
				}
			}else{
				$returnArray['StatusCode'] = $this->OutPutMessage(1);
			}
				
		}else{			
			$returnArray['StatusCode'] = $this->OutPutMessage(1);
		}

		return $returnArray;
	}	


	// Add School Metadata
	function AddMetadata(){

		$returnArray = array("StatusCode"	=> $this->OutPutMessage(1),
							 "Message"      => "");

		$getData = file_get_contents("php://input");

		if($this->getLib->checkVal($getData)){  

			$getJsonData = json_decode($getData, true);

			$getExternalSchoolData = $getJsonData['external_school'];
			$getExternalSchoolRatingData = $getJsonData['external_school_rating'];
			$getExternalSubjectStatsData = $getJsonData['external_school_subject_stats']; // array
			
			// check valid url

			try{
				$create_time = strtotime("now");
				$status      = "1";

				// insert edmodo school 
				$sql = "INSERT INTO `edmodo_school`(
							`edmodo_s_create_time`, 
							`edmodo_s_status`) 
						VALUES(?, ?)";

				$sth = $this->db->prepare($sql);
				$sth->execute(array($create_time, 
									$status));

				// get last insert id
				$getEdmodoSchoolId = $this->db->lastInsertId();


				// insert to schools
				$sql = "INSERT INTO `schools`(`edmodo_s_id`, 
											 `s_url`, 
											 `s_name`,
											 `s_grades`,
											 `s_students`,
											 `s_type`, 
											 `s_nces_id`,
											 `s_create_time`,
											 `s_update_time`,
											 `s_status`) 
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($getEdmodoSchoolId, 
									$this->getLib->getStringValue($getExternalSchoolData['external_school_url']), 
									$this->getLib->getStringValue($getExternalSchoolData['name']), 
									$this->getLib->getStringValue($getExternalSchoolData['grades']), 
									$this->getLib->getNumberValue($getExternalSchoolData['student_count']), 
									$this->getLib->getNumberValue($getExternalSchoolData['school_type']), 
									$this->getLib->getStringValue($getExternalSchoolData['nces_id']), 
									$create_time, 
									$create_time,
									$status));

				// ======== School Rating ============

				// get last insert id
				$getSchoolId = $this->db->lastInsertId();
				$create_time = strtotime("now");

				// insert to schools rating
				$sql = "INSERT INTO `school_rating`(`s_id`, 
											 `sr_five`, 
											 `sr_four`,
											 `sr_three`,
											 `sr_two`,
											 `sr_one`, 
											 `sr_average`,
											 `sr_num_ratings`,
											 `sr_create_time`,
											 `sr_update_time`,
											 `sr_status`) 
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($getSchoolId, 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['five']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['four']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['three']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['two']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['one']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['average']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['num_ratings']), 
									$create_time, 
									$create_time,
									$status));

				// ======== School Subject Stats ============
				foreach($getExternalSubjectStatsData AS $getSSData){
					$create_time = strtotime("now");

					// insert edmodo subject stats 
					$sql = "INSERT INTO `edmodo_school_subject_stats`(
								`edmodo_ss_create_time`, 
								`edmodo_ss_status`) 
							VALUES(?, ?)";

					$sth = $this->db->prepare($sql);
					$sth->execute(array($create_time, 
										$status));

					// get last insert id
					$getEdmodoSubjectStasId = $this->db->lastInsertId();

					// insert to schools subject stats
					$sql = "INSERT INTO `school_subject_stats`(`s_id`, 
												 `edmodo_ss_id`, 
												 `st_avg`,
												 `st_state_avg`,
												 `st_create_time`,
												 `st_update_time`,
												 `st_status`) 
							VALUES(?, ?, ?, ?, ?, ?, ?)";
					$sth = $this->db->prepare($sql);
					$sth->execute(array($getSchoolId, 
										$getEdmodoSubjectStasId,
										$this->getLib->getNumberValue($getSSData['avg']), 
										$this->getLib->getNumberValue($getSSData['state_avg']), 
										$create_time, 
										$create_time,
										$status));
				}




				$returnArray['StatusCode']	    = $this->OutPutMessage(0);
				$returnArray['Message']	    	= "Metadata Added";

			} catch (Exception $e){
				$returnArray['StatusCode']	    = $this->OutPutMessage(1);
				$returnArray['Message']	    	= "Database Error".$e;
			}
		}else{			
			$returnArray['StatusCode'] = $this->OutPutMessage(1);
			$returnArray['Message']	   = "Error";
		}


		return $returnArray;
	}	

	// Update School Metadata
	function PutMetadata($getId){

		$returnArray = array("StatusCode"	=> $this->OutPutMessage(1),
							 "Message"      => "");

		$getData = file_get_contents("php://input");

		if($this->getLib->checkVal($getData) && $this->getLib->checkVal($getId)){  

			$getJsonData = json_decode($getData, true);

			$getExternalSchoolData = $getJsonData['external_school'];
			$getExternalSchoolRatingData = $getJsonData['external_school_rating'];
			$getExternalSubjectStatsData = $getJsonData['external_school_subject_stats']; // array
			
			// check valid url

			try{
				$create_time = strtotime("now");
				$status      = "1";

				// update schools
				$sql = "UPDATE`schools` 
						SET `s_url` = ?,
							`s_name` = ?,
							`s_grades` = ?,
							`s_students` = ?,
							`s_type` = ?, 
							`s_nces_id` = ?,
							`s_update_time` = ?
						WHERE `s_status` = ?
						AND `s_id` = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($this->getLib->getStringValue($getExternalSchoolData['external_school_url']), 
									$this->getLib->getStringValue($getExternalSchoolData['name']), 
									$this->getLib->getStringValue($getExternalSchoolData['grades']), 
									$this->getLib->getNumberValue($getExternalSchoolData['student_count']), 
									$this->getLib->getNumberValue($getExternalSchoolData['school_type']), 
									$this->getLib->getStringValue($getExternalSchoolData['nces_id']), 
									$create_time, 
									$status,
									$getId));

				// ======== School Rating ============
				// get sr_id
				$sql = "SELECT  `sr_id` AS `id`
						FROM `school_rating` 
						WHERE `sr_status` = :status
						AND `s_id` = :id";

				$sth = $this->db->prepare($sql);
				$sth->bindValue(":status", $status);
				$sth->bindValue(":id", $getId);
				$sth->execute();
				$getSchoolRatingData = $this->getLib->fetchSQL($sth);
				$getSchoolRatingId   = $getSchoolRatingData['id'];

				$create_time = strtotime("now");

				// update schools rating
				$sql = "UPDATE `school_rating`
						SET  `sr_five` = ?, 
							 `sr_four` = ?,
							 `sr_three` = ?,
							 `sr_two` = ?,
							 `sr_one` = ?, 
							 `sr_average` = ?,
							 `sr_num_ratings` = ?,
							 `sr_update_time` = ?
						WHERE `sr_status` = ?
						AND `sr_id` = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($this->getLib->getNumberValue($getExternalSchoolRatingData['five']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['four']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['three']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['two']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['one']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['average']), 
									$this->getLib->getNumberValue($getExternalSchoolRatingData['num_ratings']), 
									$create_time,
									$status,
									$getSchoolRatingId));

				// ======== School Subject Stats ============
				// get st_id
				$sql = "SELECT `st_id` AS `id`
						FROM `school_subject_stats` 
						WHERE `st_status` = :status
						AND `s_id` = :id";

				$sth = $this->db->prepare($sql);
				$sth->bindValue(":status", $status);
				$sth->bindValue(":id", $getId);
				$sth->execute();
				$getSchoolSubjectStatsData = $this->getLib->fetchArray($sth);

				// get counts
				$getTotalSSData = count($getSchoolSubjectStatsData);
				$getCurrentCount = 1;

				foreach($getExternalSubjectStatsData AS $getSSData){

					$create_time = strtotime("now");

					if($getCurrentCount > $getTotalSSData){
						// new data

						// insert edmodo subject stats 
						$sql = "INSERT INTO `edmodo_school_subject_stats`(
									`edmodo_ss_create_time`, 
									`edmodo_ss_status`) 
								VALUES(?, ?)";

						$sth = $this->db->prepare($sql);
						$sth->execute(array($create_time, 
											$status));

						// get last insert id
						$getEdmodoSubjectStasId = $this->db->lastInsertId();

						// insert to schools subject stats
						$sql = "INSERT INTO `school_subject_stats`(`s_id`, 
													 `edmodo_ss_id`, 
													 `st_avg`,
													 `st_state_avg`,
													 `st_create_time`,
													 `st_update_time`,
													 `st_status`) 
								VALUES(?, ?, ?, ?, ?, ?, ?)";
						$sth = $this->db->prepare($sql);
						$sth->execute(array($getId, 
											$getEdmodoSubjectStasId,
											$this->getLib->getNumberValue($getSSData['avg']), 
											$this->getLib->getNumberValue($getSSData['state_avg']), 
											$create_time, 
											$create_time,
											$status));
					}else{

						$getSSId = $getSchoolSubjectStatsData[$getCurrentCount-1]['id'];

						// update data by sequence
						$sql = "UPDATE `school_subject_stats`
								SET  `st_avg` = ?,
									 `st_state_avg` = ?,
									 `st_update_time` = ? 
								WHERE `st_status` = ?
								AND `st_id` = ?";
						$sth = $this->db->prepare($sql);
						$sth->execute(array($this->getLib->getNumberValue($getSSData['avg']), 
											$this->getLib->getNumberValue($getSSData['state_avg']), 
											$create_time,
											$status,
											$getSSId));
					}

					$getCurrentCount++;
				}




				$returnArray['StatusCode']	    = $this->OutPutMessage(0);
				$returnArray['Message']	    	= "Metadata Updated";

			} catch (Exception $e){
				$returnArray['StatusCode']	    = $this->OutPutMessage(1);
				$returnArray['Message']	    	= "Database Error".$e;
			}
		}else{			
			$returnArray['StatusCode'] = $this->OutPutMessage(1);
			$returnArray['Message']	   = "Error";
		}


		return $returnArray;
	}

	// Delete School Data by ID 
	function DeleteMetadata($getId){

		$returnArray = array("StatusCode"	=> $this->OutPutMessage(1),
							 "Message"      => "");

		if($this->getLib->checkVal($getId)){  

			try{

				// delete subject stats
				$sql = "DELETE FROM `school_subject_stats`
						WHERE `s_id` = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($getId));


				// delete rating
				$sql = "DELETE FROM `school_rating`
						WHERE `s_id` = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($getId));

				// delete rating
				$sql = "DELETE FROM `schools`
						WHERE `s_id` = ?";
				$sth = $this->db->prepare($sql);
				$sth->execute(array($getId));


				$returnArray['StatusCode']	    = $this->OutPutMessage(0);
				$returnArray['Message']	    	= "Metadata Deleted";

			} catch (Exception $e){
				$returnArray['StatusCode']	    = $this->OutPutMessage(1);
				$returnArray['Message']	    	= "Database Error".$e;
			}
		}else{			
			$returnArray['StatusCode'] = $this->OutPutMessage(1);
			$returnArray['Message']	   = "Error";
		}


		return $returnArray;
	}	

	
}