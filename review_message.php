<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Main entry point for the Reviewer interface
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2010-03-09
*************************************************************************/

session_start();
require_once 'include/common.inc.php';
require_once 'include/review_forms.php';
require_once 'include/header.php';
require_once 'include/jquery.php';

$post_vars = array("stage", "substage", "username", "password", "requestid", "sessionid", "notes", "initials");

foreach ($post_vars as $post_var) {
  $post_var = "fm_".$post_var;
  $$post_var = isset($_POST[$post_var]) ? $_POST[$post_var] : "";
}

// Use the Bronto SessionID to establish a binding to the active session.
if ($fm_sessionid) {
  $bapi = connect_bronto_session($fm_sessionid);
  if (!is_session_active($bapi)) {
    display_errorbox("Your ".APP_NAME." session has expired; please log in again.<br/>[id=".$fm_sessionid."]");
    $fm_stage = "start";
  }
}
else {
  $bapi = null;
}

if (empty($fm_stage) || $fm_stage == "start") {
  $request_id = null;
  if (isset($_GET['fm_requestid'])) {
    $request_id = $_GET['fm_requestid'];
    if (!empty($_SESSION['username'])){
      $dbh = open_db(); //ab
      prepare_message_review($dbh, $request_id); //ab
    }
    else {//ab
      print_review_login_form("", "", $request_id);
    }//ab
  }
  else {//ab
  	if (isset($_SESSION['requestid'])) {
  		$dbh = open_db();
  		db_update_request_status_user($dbh, $_SESSION['requestid'],"UNDER_REVIEW" , "", $_SESSION['username']);
  	}
  	print_review_login_form("", "", $request_id);
  }//ab
}
else if ($fm_stage == "auth") {
  if (empty($fm_username) || empty($fm_password)) {
    display_errorbox("Must specify both a username and a password.");
    print_review_login_form($fm_username, $fm_password, $fm_requestid);
  }
  else {
      $dbh = open_db();
      // attempt local (DB) authentication, or LDAP authentication
      $userinfo = authenticate_reviewer($dbh, $fm_username, $fm_password);
      if ($userinfo !== null) {
        if (is_reviewer($userinfo)) {
          $_SESSION['auth'] = true;
          $_SESSION['username'] = $fm_username;
          if (db_update_user_last_login($dbh, $fm_username) == false) {
            echo "Unable to record login date/time.";
          }
         
          if (empty($fm_requestid)) {
          	require_once('./include/display_listrequest.php'); //AB //show request list
            print_requestid_form();
          }
          else {
            prepare_message_review($dbh, $fm_requestid);
          }
        }
        else {
          display_errorbox("Only authorized Senate reviewers can use this site.");
          print_review_login_form($fm_username, $fm_password, $fm_requestid);
        }
      }
      else {
        display_errorbox("Invalid username or password.");
        print_review_login_form($fm_username, $fm_password, $fm_requestid);
      }
  } 
} //if ($fm_stage == "auth")
else if ($fm_stage == "reqid") {
  if (!is_authenticated()) {
    display_errorbox("You are not authenticated; please log in.");
    print_review_login_form();
  }
  else if (empty($fm_requestid)) {
    display_errorbox("Expecting a request ID, but none was provided.");
    print_requestid_form();
  }
  else {
    $dbh = open_db();
    prepare_message_review($dbh, $fm_requestid);
  }
}
else if ($fm_stage == "reviewed") {
  if (!is_authenticated()) {
    display_errorbox("You are not authenticated; please log in.");
    print_review_login_form();
  }
  else if (empty($fm_sessionid) || empty($fm_requestid) || empty($fm_substage)) {
    display_errorbox("Expecting sessionID, requestID, and substage.");
    print_requestid_form($fm_requestid);
  }
  else {
    // $fm_substage is either VERIFY_TYPE_APPROVE or VERIFY_TYPE_REJECT
    print_verify_form($fm_substage, $fm_sessionid, $fm_requestid);
  }
}
else if ($fm_stage == "send") {
  if (!is_authenticated()) {
    display_errorbox("You are not authenticated; please log in.");
    print_review_login_form();
  }
  else if (empty($fm_requestid) || empty($fm_sessionid)) {
    display_errorbox("Expecting both a requestID and sessionID.");
    print_review_login_form();
  }
  else if (strlen($fm_initials) < 2) {
    display_errorbox("You must enter your initials in order to confirm authorization to send the e-mail.");
    print_verify_form(VERIFY_TYPE_APPROVE, $fm_sessionid, $fm_requestid, $fm_notes, $fm_initials);
  }
  else {
    $dbh = open_db();
    //db_update_request_status($dbh, $fm_requestid, "APPROVED", $fm_notes);
    db_update_request_status_user($dbh, $fm_requestid,"APPROVED" , $fm_notes, $_SESSION['username']);
    $reqinfo = db_load_request($dbh, $fm_requestid);
    $requserinfo = db_get_user($dbh, $reqinfo['requester']);
    $revuserinfo = db_get_user($dbh, $reqinfo['reviewer']);
    $rc = send_blast_email($bapi, $reqinfo);
    if ($rc == true) {
      //echo "<p>Your blast e-mail has been sent.</p>";
      if (send_approval_to_requester($reqinfo, $revuserinfo /* sender */, $requserinfo /* recip */) == true) {
        //echo "<p>Sent notice of approval to requester.</p>";
        ?>
        <script type="text/javascript">
          alert('Your blast e-mail has been sent. '+'Sent notice of approval to requester.');
        </script>
<?php
      }
      else {
        //display_warnbox("Unable to send notice of approval back to requester, but blast e-mail was sent.");
?>
      	<script type="text/javascript">
          alert('Unable to send notice of approval back to requester, but blast e-mail was sent. );
        </script>
<?php
      }
      require_once('./include/display_listrequest.php'); //AB
      print_requestid_form();
    }
    else {
      display_errorbox("Unable to send blast e-mail.");
      print_verify_form(VERIFY_TYPE_APPROVE, $fm_sessionid, $fm_requestid, $fm_notes, $fm_initials);
    }
    // send e-mail to requesters
  }
}
else if ($fm_stage == "reject") {
  if (!is_authenticated()) {
    display_errorbox("You are not authenticated; please log in.");
    print_review_login_form();
  }
  else if (empty($fm_notes)) {
    display_errorbox("One or more reasons for rejecting the message must be provided.");
    print_verify_form(VERIFY_TYPE_REJECT, $fm_sessionid, $fm_requestid);
  }
  else {
    $dbh = open_db();
    //db_update_request_status($dbh, $fm_requestid, "REJECTED", $fm_notes);
    db_update_request_status_user($dbh, $fm_requestid,"REJECTED" , $fm_notes, $_SESSION['username']);
    $reqinfo = db_load_request($dbh, $fm_requestid);
    $requserinfo = db_get_user($dbh, $reqinfo['requester']);
    $revuserinfo = db_get_user($dbh, $reqinfo['reviewer']);
    if (send_rejection_to_requester($reqinfo, $revuserinfo /* sender */, $requserinfo /* recip */) == true) {
      //echo "<p>Sent notice of rejection to requester.</p>";
?>
      <script type="text/javascript">
        alert('Sent notice of rejection to requester.');
      </script>
<?php
      require_once('./include/display_listrequest.php'); //AB
      print_requestid_form();
    }
    else {
      display_errorbox("Unable to send rejection notice to requester.");
      print_verify_form(VERIFY_TYPE_REJECT, $fm_sessionid, $fm_requestid, $fm_notes);
    }
  }
}
else if ($fm_stage == "reassign") {
    include ('./include/display_reassign.php');
}
else if ($fm_stage == "cancel") {
	require_once('./include/display_listrequest.php'); //AB
    print_requestid_form();
}

else {
  echo('<div class="errorbox">Unknown stage in the approval process</div>');
}

require_once 'include/footer.php';


function prepare_message_review($dbh, $req_id)
{
  $_SESSION['requestid'] = $req_id;
  /* In order to display the message review screen, we need to first get the request record.  Then the
   * account ID is used to log into the Bronto API to extract other message-oriented information.
   */
  $reqinfo = db_load_request($dbh, $req_id);
  if ($reqinfo != null) {
    $acctid = $reqinfo['account_id'];
    $login_info = bronto_agency_login($acctid);
    if ($login_info) {
      $bapi = $login_info['binding'];
      $session_id = $login_info['sessionID'];
      $_SESSION['session_id'] = $session_id;
      $username = $_SESSION['username'];
      $rc = db_save_session($dbh, $session_id, $username, $acctid);
      if ($rc == false) {
        display_warnbox("Unable to save session information (id=".$session_id.",user=".$username.")");
      }
      print_message_review_form($bapi, $session_id, $reqinfo);
      if (db_update_request_status($dbh, $req_id, "UNDER_REVIEW") == false) {
        display_warnbox("Unable to update request status.");
      }
    }
    else {
      display_errorbox("Unable to contact the Bronto API server.");
      print_requestid_form($req_id);
    }
  }
  else {
    display_errorbox("Request ID ".$req_id." is invalid.");
    print_requestid_form($req_id);
  }
} // prepare_message_review()



/*
 * Look up user in database, and authenticate based on user scope.
 * LOCAL scope = use local (DB) password
 * LDAP scope = use Senate LDAP server
 * BRONTO scope = use Bronto server (not applicable for reviewers, so not handled here)
 */
function authenticate_reviewer($dbh, $username, $password)
{
  $uinfo = db_get_user($dbh, $username);
  if ($uinfo) {
    if ($uinfo['scope'] === 'LOCAL' && !empty($uinfo['password']) && $uinfo['password'] === $password) {
      return $uinfo;
    }
    else if ($uinfo['scope'] === 'LDAP' && auth_ldap_user($username, $password)) {
      return $uinfo;
    }
    else {
      return null;
    }
  }
  else {
    return null;
  }
} // authenticate_reviewer()

?>
