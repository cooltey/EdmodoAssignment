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

	// Get Schools
	function GetMetadata($getData){

		$returnArray = array("StatusCode"	=> $this->OutPutMessage(1),
							 "Total"		=> 0,
							 "List"			=> array());

		if($this->getLib->checkVal($getData['page']) 
			&& $this->getLib->checkVal($getData['many'])){  
			
			// call page class
			try{
					$status = "1";
					$sql = "SELECT `b_index` FROM `blocked` 
							WHERE `b_status` = :status";

					$sth = $this->db->prepare($sql);
					$sth->bindValue(":status", $status);
					$sth->execute();
					$count = $sth->rowCount();
					
					$page    	= intval($this->getLib->setFilter($getData['page']));
					$many	 	= intval($this->getLib->setFilter($getData['many']));
					$display 	= "3";
					$total	 	= $count;
					$pagename	= $this->pageName."?";
					$getPage = new Pager($page, $many, $display, $total, $pagename);
					
			}catch(Exception $e){
				$error_msg = "Database Error";
			}
			
			// get data from database
			try{
					$status = "1";
					$start  = intval($getPage->startVar);
					$many   = intval($getPage->manyVar);

					$sql = "SELECT `b_index` AS `id`, `b_url` AS `url` FROM `blocked` 
							WHERE `b_status` = :status
							ORDER BY `b_create_time` DESC
							LIMIT :start,:many";

					$sth = $this->db->prepare($sql);
					$sth->bindValue(":status", $status);
					$sth->bindValue(":start", $start, PDO::PARAM_INT);
					$sth->bindValue(":many", $many, PDO::PARAM_INT);
					$sth->execute();

					// output data
					$returnArray['StatusCode']	= $this->OutPutMessage(0);
					$returnArray['Total']		= $count;
					$returnArray['List']	    = $this->getLib->fetchArray($sth);

			}catch(Exception $e){
				$error_msg = "Database Error";
			}

				
		}else{			
			$returnArray['StatusCode'] = $this->OutPutMessage(1);
		}


		return $returnArray;
	}	


	// Add School Metadata
	function AddMetadata($getData){

		$returnArray = array("StatusCode"	=> $this->OutPutMessage(1),
							 "Message"      => "");

		if($this->getLib->checkVal($getData)){  
			
		

			$getJsonData = json_decode($getData, true);

			$getExternalSchoolData = $getJsonData['external_school'];
			$getExternalSchoolRatingData = $getJsonData['external_school_rating'];
			$getExternalSubjectStatsData = $getJsonData['external_school_subject_stats']; // array
			
			// check valid url

			try{
				$create_time = date("Y-m-d H:i:s");
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
				$create_time = date("Y-m-d H:i:s");

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
					$create_time = date("Y-m-d H:i:s");

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
				$returnArray['Message']	    	= "Database Error";
			}
		}else{			
			$returnArray['StatusCode'] = $this->OutPutMessage(1);
			$returnArray['Message']	   = "Error";
		}


		return $returnArray;
	}	

	
}