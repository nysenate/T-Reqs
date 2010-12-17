<div id="tabs">
<?php
/*
 * This show the 3 sections of list of request
 */
require_once('./include/3tabs.php');

$field['uuid'] = 'ID';
$field['requester']= 'Requester';
$field['reviewer'] = 'Reviewer';
$field['account_name'] = 'Account Name';
$field['message_name']= 'Message name';
$field['delivery_date'] = 'Delivery Date';
$field['district']= 'District';
$field['from_addr'] = 'From Email';
$field['from_name']= 'From Name';
$field['status'] = 'Status';
$field['request_notes']= 'Request Notes';

$dbh = open_db();
?>
<div id="tabs-1">
<?php
// only show under review, approved and rejected list
$table_name = 'request';
$key_field = 'status';
$key_value = array();
$key_value[]= 'REJECTED';
$key_value[] = 'APPROVED';
$key_value[] = 'UNDER_REVIEW';

$key_operator = 'OR';
$user = $_SESSION['username'];


$result = db_get_records_with_condition($dbh, $table_name, $key_field, $key_value, $key_operator, $sortby = 'delivery_date', $user);
if ($result != null){
  //print '<h2>My Work</h2>';	
  print '<table  border="1" style="text-align:left;font-size:95%;font-family:\'Times New Roman\', Times, serif;">';
  print '<caption><h2>My Work</h2></caption>';
  list_request_with_condition($field);
  list_request_with_condition($field, $result);
  print '</table>';
}

// only show awaiting list
?>
</div>
<div id="tabs-2">
<?php
$table_name = 'request';
$key_field = 'status';
$key_value = array();
$key_value[] = 'AWAITING_REVIEW';

$result2 = db_get_records_with_condition($dbh, $table_name, $key_field, $key_value, $key_operator, $sortby = 'delivery_date');
if ($result2 != null){
  //print '<h2>Awaiting Review</h2>';
  print '<table border="1" style="text-align:left;font-size:95%;font-family:\'Times New Roman\', Times, serif; ">';
  print '<caption><h2>Awaiting Review</h2></caption>';
  list_request_with_condition($field);
  list_request_with_condition($field, $result2);
  print '</table>';
}
?>
</div>
<div id="tabs-3">
<?php
// only show under review list
$table_name = 'request';
$key_field = 'status';
$key_value = array();

$key_value[] = 'UNDER_REVIEW';

$result = db_get_records_with_condition($dbh, $table_name, $key_field, $key_value, $key_operator, $sortby = 'delivery_date');
$display_list ="under_review";
if ($result != null){
  //print '<h2>Under Review</h2>';
  print '<table  border="1" style="text-align:left;font-size:95%;font-family:\'Times New Roman\', Times, serif;">';
  print '<caption><h2>Under Review</h2></caption>';
  list_request_with_condition($field);
  list_request_with_condition($field, $result, $display_list);
  print '</table>';
}

?>
</div> 
</div> <!-- end of <div id="tabs">-->

