<?php
/*
 * This shows the reassign submit/cancel page first.
 * If reassign is being submitted, email is sent to the new reviewer (if not back on awaiting list), then goes back the list of request page.
 */
require_once 'include/common.inc.php';

$dbh = open_db();

if (isset($_POST['submitted'])){
  
  if (!empty($_POST['status'])){
    db_update_request_status_user($dbh, $fm_requestid, $_POST['status'], $fm_notes, $_POST['username']);
  
    $table_name = 'request';
    $key_field = 'uuid';
    $key_value = $fm_requestid;
    $result_message = db_get_records($dbh, $table_name, $key_field, $key_value, $sortby = null);
  
    $table_name = 'user';
    $key_field = 'username';
    $key_value = $_POST['username'];
    if ($key_value != 'reviewer1'){
      $result = db_get_records($dbh, $table_name, $key_field, $key_value, $sortby = null);
        if ($result != null){ 
          $to_addr = $result[0]['email'];
        $to_name = $result[0]['firstname'].' '.$result[0]['lastname'];
    
        $key_value = $_POST['fm_username'];
        $result = db_get_records($dbh, $table_name, $key_field, $key_value, $sortby = null);
        $from_addr =$result[0]['email'];
        $from_name = $result[0]['firstname'].' '.$result[0]['lastname'];
    
        $cc_addrs = array();
        $subject = 'Message with request ID# '.$fm_requestid. ' is reassigned to you.';
        $url_str = get_review_url($fm_requestid);
        $body = "Hello ".$to_name."!\n\n".
          "$from_name has assigned a T-Reqs email approval request to you, for the email titled ".
           $result_message[0]['message_name'].
          ".\n\n".
          "Please use the link below to sign into the T-Reqs website, review the message, and either approve or reject it based on Senate guidelines.".
           ".\n\n".
          "    $url_str\n\n\n".
          "Please reference the information provided below ".
          "with technical questions.\n\n".
          "Request Details:\n".
          "    Request ID: $fm_requestid\n".
          "    Message Name: ".$result_message[0]['message_name']."\n".
          "    Site Name: ".$result_message[0]['account_name']."\n\n".
      
          
          "\n\n\n".
          "Sincerely,\n\n".
          "$from_name\n".
          "(via ".APP_NAME.")\n\n";
        
  
        send_email_message($from_addr, $from_name, $to_addr, $to_name, $cc_addrs, $subject, $body);
      }
    }
  }
  else {
   	$result = db_get_records($dbh, "request", "uuid", $fm_requestid, $sortby = null);
    db_update_request_status_user($dbh, $fm_requestid, $result[0]['status'], $result[0]['request_notes'], $result[0]['reviewer']);
  }
  include ('display_listrequest.php'); //AB //show request list
  print_requestid_form();
  
}
else {
  print '<h2>T-Reqs blast e-mail message ID# '.$fm_requestid.' to be either reassigned to a different reviewer or to a different status.</h2>';
  print '<h2>To put it back to the queue, just choose Default Reviewer and Awaiting Review otherwise choose a reviewer and Under Review to reassign.</h2>';
  review_re_assign_form($dbh, 'user','role', 'REVIEWER', $fm_requestid); //build the reassign <form>
}

?>