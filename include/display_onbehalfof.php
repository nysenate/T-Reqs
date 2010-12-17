<?php
/*
 * This finds the disctrict number in the request and match the district senator's name and display On behalf of Senator (firstname + lastname)
 */
$dbh = open_db();

if 	(isset($_GET['fm_requestid'])){
	$fm_requestid = $_GET['fm_requestid'];
}
else {
	$fm_requestid = $_POST['fm_requestid'];
}


$table_name = 'request';
$key_field = 'uuid';
$key_value = $fm_requestid;

$result = db_get_records($dbh, $table_name, $key_field, $key_value, $sortby = null);
if ($result != null){
  
  $table_name = 'senator';
  $key_field = 'district';
  $key_value = $result[0]['district'];

  $result = db_get_records($dbh, $table_name, $key_field, $key_value, $sortby = null);
  if ($result != null){
	print 'On behalf of Senator ';
	print $result[0]['firstname']." ". $result[0]['lastname'];
  }
}
print '<br /><br />';
?>