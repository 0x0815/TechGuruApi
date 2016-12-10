<?php
require_once('mysql.class.php');
define('DEBUG', true);
	
function csv_to_array($filename='', $delimiter=',') {
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;
	
	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE) {
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {	
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

function addToDatabase($array) {
	
	$DB = new MySQL('repairdata', 'repairdata', 'm#42Vqc7');
	$DB->executeSQL('SELECT servicenumber, status from repaircases');	
	$lastDBCases = $DB->arrayResultsWithKey('servicenumber');
		
	foreach($array as &$csvArray){
		if($csvArray["Konto"] != "100010"){			
			$rowToAdd = array(
				'servicenumber' => $csvArray["Servicenr"],
				'status' => str_replace('(','',explode(')', $csvArray["S"])[0]),
				'dayin' => strtotime(substr($csvArray["Neuanlage"], 0,10)),
				'customernumber' => $csvArray["Konto"],
				'customername' => $csvArray["Name"],
				'customermail' => $csvArray["E-Mail Kunde"],
				'customertel' => $csvArray["Tel. Kunde"],
				'customermobil' => $csvArray["Mobiltel. Kunde"],
				'customerfax' => $csvArray["Fax Kunde"],
				'articlenumber' => $csvArray["Artikelnummer"],
				'articledescription' => str_replace("¶", "",$csvArray["Bezeichnung 1"]),
				'serialnumber' => $csvArray["Seriennummer"],
				'tasknumber' => $csvArray["AuftragsNr"],
				'errordescription' => $csvArray["FehlerTextExtern"],
				'manufacturernumber' => $csvArray["HerstellerArtNr."],
				'user' => $csvArray["Benutz N"],
				'lastchangedate' => time()
			);
			
			$servicenumber = $rowToAdd['servicenumber'];
			$newStatus = $rowToAdd['status'];
			
			$rowToUpdate = array(
				'status' => $newStatus,
				'lastchangedate' => time()
			);
			
			if(!$lastDBCases[$servicenumber]){
				if(DEBUG) { echo "$servicenumber - not in DB - ADD<br>"; }
				if($DB->insertArray('repaircases',$rowToAdd)){
					if(DEBUG) { echo "$servicenumber - Added<br>"; }
				}else{
					if(DEBUG) { echo "$servicenumber - something went wrong<br>"; }
				}
			}else{
				if($newStatus > $lastDBCases[$servicenumber]['status'] or $lastDBCases[$servicenumber]['status'] == 60){
					if(DEBUG) { echo "$servicenumber - need update: time and to status $newStatus<br>"; }
					if($DB->update('repaircases',$rowToUpdate,array('servicenumber'=>$servicenumber))){
						if(DEBUG) { echo "$servicenumber - updated to $newStatus<br>"; }
					}else{
						if(DEBUG) { echo "$servicenumber - something went wrong<br>"; }
					}
				}else{
					if(DEBUG) { echo "$servicenumber - no update<br>"; }
				}
			}
		}
	}
}

function backupDB(){
	$mysqli = new mysqli("localhost", "repairdata", "m#42Vqc7", "repairdata");
	
	if (mysqli_connect_errno()) {
    	printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}
	
	if ($result = $mysqli->query($query)) {
	
	    /* fetch object array */
	    while ($obj = $result->fetch_object()) {
	        printf ("%s (%s)\n", $obj->Name, $obj->CountryCode);
	    }
	    
	    
	    /* free result set */
	    $result->close();
	}
	
	$headers = 
	$query = "SELECT * FROM `repaircases`";

}

switch($_GET['q1']){
	case 'add':
		if(strlen($_POST['csv']) > 1){
			$content = $_POST['csv'];
			$file = "cases.tmp";
			$Saved_File = fopen($file, 'w');
			fwrite($Saved_File, $content);
			fclose($Saved_File);
			$data = csv_to_array($file, ";");
			addToDatabase($data);
			unlink($file);
		}
	break;
	case 'update':
		if(isset($_GET['q2']) and isset($_GET['q3'])){
			$newServiceNumber = $_GET['q2'];
			$newStatus = $_GET['q3'];
		}
	break;
	case 'get':
		$limit = $_GET['q2'];
	default:
		echo "nüscht";
	break;
}
?>