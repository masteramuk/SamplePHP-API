<?php
/**
	This API program will accept input from various sources and do the following
	1. Insert into database if the data is new
	2. Update the database if the data is old
	3. Return list of data information based on query parameter
	
	Created by		:	Nurul Haszeli
	Created Date	:	31/10/2016
	Function Name	:	Sample
	Modified By		:	Nurul Haszeli
	Modified Date	:	20/09/2018
*/
 	require_once("Rest.inc.php");
	require_once("conf.ini.php");
	
	class VehicleRtd extends REST {
	
		public $data = "";
		
		private $db = NULL;
		private $mysqli = NULL;
		private $dbserver = NULL;
		private $dbuser = NULL;
		private $dbpassword = NULL;
		private $dbschema = NULL;
		
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		/*
		 *  Connect to Database
		*/
		private function dbConnect(){
			$this->initConnection();
			if ($this->dbserver != null) $this->mysqli = new mysqli($this->dbserver, $this->dbuser, $this->dbpassword, $this->dbschema);
		}
		
		/*
		 *  Close connection to DB
		 */
		 private function dbClose(){
			if ( $this->mysqli != null ){
				mysqli_close( $this->mysqli );
				//echo "Success close connection to " . $this->$DB ." at ". $this->DB_SERVER . ".";			
			}		
		 }
		 
		/*
		 * Dynmically call the method based on the query string
         * This will be the central focus of all. All request will be managed/diverted from this
		 */
		public function processSample(){
			$func = strtolower(trim(str_replace("/","",$_REQUEST['x'])));
			if((int)method_exists($this,$func) > 0)
				$this->$func();
			else
				$this->response('',404); // If the method not exist with in this class "Page not found".
		}
				
		 /*
		 * Function to get all records in database
         * The API name to be called
		 */
		 private function listAllSample(){ 
			 if($this->get_request_method() != "GET"){
				$this->response('',406); //invalid request
			 }
			 //form the query statement
			 $query="SELECT * " .
					"FROM SAMPLE_TABLE v ORDER BY v.modified_date ASC";
			
			 //query database
			 $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
			 
			 if($r->num_rows > 0){
				$result = array();
				while($row = $r->fetch_assoc()){
					$result[] = $row;
				}
				$this->response($this->json($result), 200); // send user details
				//print_r ($result);
			 }
			 $this->response('',204); // If no records "No Content" status
		}
		
		/*
		 * Function to get all records in database based on parameter through normal query
		 * Parameter used:
		 * sample_id
		 * sample_number
		 * report_datetime in format YYYYMMDD
         * The parameter is passed through normal http parsing: listSampleByQuery?sample_id='data'&sample_number='data'....
		 */
		 private function listVehicleRtd(){ 
			 if($this->get_request_method() != "GET"){
				$this->response('',406); //invalid request
			 }
			 
			 $sample_id = (string) $this->returnValue($this->_request['sample_id']);
			 $sample_number = (string) $this->returnValue($this->_request['sample_number']);
			 $report_datetime_from = (string) $this->returnValue($this->_request['reportfrom']);
			 $report_datetime_to = (string) $this->returnValue($this->_request['reportto']);
			 
			 //form the query statement
			 $query_select = "SELECT *";
			 $query_from = "FROM sample_table v ";
			 $query_where = " WHERE ";
			 $query_order = " ORDER BY v.modified_date ASC";
			 
			 $add_and = false;
			 if ( !empty($sample_id) ){
				 $add_and = true;
				 $query_where = $query_where . " sample_id = '" . $sample_id . "'";
			 }
			 if ( !empty($sample_number) ){
				 $query_where = $query_where . ( $add_and ? " AND ": "" ) . " sample_number = '" . $sample_number . "'";
				 $add_and = true;
			 }
			 if ( !empty($report_datetime_from) && !empty($report_datetime_to) ){
				 $query_where = $query_where . ( $add_and ? " AND ": "" ) . 
						" (DATE_FORMAT(report_datetime, '%Y/%m/%d') between DATE_FORMAT('" . $report_datetime_from . "'," .
						"'%Y/%m/%d') and DATE_FORMAT('" . $report_datetime_to . "', '%Y/%m/%d'))";
				 $add_and = true;
			 }
			 
			 $query_str = $query_select . $query_from . ( $add_and ? $query_where: "" ) . $query_order;
			
			 //query database
			 $r = $this->mysqli->query($query_str) or die($this->mysqli->error.__LINE__);
			 
			 if($r->num_rows > 0){
				$result = array();
				while($row = $r->fetch_assoc()){
					$result[] = $row;
				}
				$this->response($this->json($result), 200); // send user details
				//print_r ($result);
			 }
			 $this->response('',204); // If no records "No Content" status
		}
		
		/*
		 * Function to get all records in database for RTD cases based on parameter through json submission
		 * Parameter used:
		 * sample_id
		 * sample_number
		 * report_datetime in format YYYYMMDD
		 */
		 private function getVehicleRtd(){ 
			 if($this->get_request_method() != "POST"){
				$this->response('',406); //invalid request
			 }
			 
			//get data from caller
			$arrContextOptions=array(
			  "ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);  
			$input_array = json_decode(file_get_contents("php://input",false, stream_context_create($arrContextOptions)),true);
		
			$input_array = json_decode(file_get_contents("php://input"),true);
			$column_names = array('sampleid', 'samplenumber', 'reportfrom', 'reportto');
			
			//print_r ($input_array['vehiclertd']);
			 //print_r ($token_info);
			 $rtd_info = explode (",", implode(",",$input_array));
			
			 $sample_id = (string) $rtd_info[0];
			 $sample_number = (string) $rtd_info[1];
			 $report_datetime_from = (string) $rtd_info[2];
			 $report_datetime_to = (string) $rtd_info[3];
			 
			 //form the query statement
			 //print_r ( "-form the query statement" );
			 $query_select = "SELECT * ";
			 $query_from = "FROM sample_table v ";
			 $query_where = " WHERE ";
			 $query_order = " ORDER BY v.modified_date ASC";
			 
			 $add_and = false;
			 if ( !empty($sample_id) ){
				 $add_and = true;
				 $query_where = $query_where . " sample_id = '" . $sample_id . "'";
			 }
			 if ( !empty($sample_number) ){
				 $query_where = $query_where . ( $add_and ? " AND ": "" ) . " sample_number = '" . $sample_number . "'";
				 $add_and = true;
			 }
			 if ( !empty($report_datetime_from) && !empty($report_datetime_to) ){
				 $query_where = $query_where . ( $add_and ? " AND ": "" ) . 
						" (DATE_FORMAT(report_datetime, '%Y/%m/%d') between DATE_FORMAT('" . $report_datetime_from . "'," .
						"'%Y/%m/%d') and DATE_FORMAT('" . $report_datetime_to . "', '%Y/%m/%d'))";
				 $add_and = true;
			 }
			 
			 $query_str = $query_select . $query_from . ( $add_and ? $query_where: "" ) . $query_order;
			 //print_r ($query_str);
			 //query database
			 $r = $this->mysqli->query($query_str) or die($this->mysqli->error.__LINE__);
			 
			 if($r->num_rows > 0){
				$result = array();
				while($row = $r->fetch_assoc()){
					$result[] = $row;
				}
				$this->response($this->json($result), 200); // send user details
				//print_r ($result);
			 }
			 $this->response('',204); // If no records "No Content" status
		}
		
		/*
		* Purpose of the functions is to get the database configuration and set it
		*/
		private function initConnection(){
			$config_array  = parse_ini_file('conf.ini.php', TRUE);
			//print_r( $config_array );
			$db_info = $config_array['db_info'];
			//print_r( $db_info );
			$this->dbserver = $db_info['server_url'];
			$this->dbuser = $db_info['user'];
			$this->dbpassword = $db_info['password'];
			$this->dbschema = $db_info['db_name'];
		}
		
		/*
		* return empty string or value
		*/
		private function returnValue($str){
			if ( $str == null ){
				return "";
			}
			return $str;
		}
		
		/*
		 *	Encode array into JSON
		*/
		private function json($data){
			if(is_array($data)){
				return json_encode($data);
			}
		}
	}
	
	// Initiiate Library
	$sample = new Sample;
	$sample->processSample();
?>
