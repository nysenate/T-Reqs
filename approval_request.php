<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Main entry point for the approval request interface
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2010-07-06
*************************************************************************/

require_once 'include/common.inc.php';
require_once 'include/request_forms.php';
set_app_cookies();  // must set the Cookie headers prior to any output
require_once 'include/header.php';

$post_vars = array("stage", "username", "password", "sitename", "siteid", "iscc", "ccemail",
                   "sessionid", "accountid", "msgid", "listids", "segids",
                   "year", "month", "day", "district", "fromaddr", "fromname", "replyaddr",
                   "firstname", "lastname", "email", "phone", "notes", "initials");

foreach ($post_vars as $post_var) {
  $post_var = "fm_".$post_var;
  $$post_var = isset($_POST[$post_var]) ? $_POST[$post_var] : null;
}

// Use the Bronto SessionID to establish a binding to the active session.
if ($fm_sessionid) {
  $bapi = connect_bronto_session($fm_sessionid);
  if (!is_session_active($bapi)) {
    display_errorbox("Your ".APP_NAME." session has expired; please log in again");
    $fm_stage = "start";
  }
}
else {
  $bapi = null;
}

// make sure that both listids and segids are arrays
if (!is_array($fm_listids)) {
  $fm_listids = array();
}
if (!is_array($fm_segids)) {
  $fm_segids = array();
}

if (empty($fm_stage) || $fm_stage == "start") {
  print_request_login_form();
}
else if ($fm_stage == "auth") {
  if (empty($fm_username) || empty($fm_password) || empty($fm_sitename)) {
    display_errorbox("Must specify a username, password, and sitename.");
    print_request_login_form($fm_username, $fm_password, $fm_sitename);
  }
  else {
    $login_info = bronto_user_login($fm_username, $fm_password, $fm_sitename, $fm_siteid);
    process_login($login_info, $fm_username, $fm_password, $fm_sitename);
  }
}
else if ($fm_stage == "suauth") {
  if (empty($fm_sessionid) || empty($fm_username) || empty($fm_sitename)) {
    display_errorbox("Must have a valid user session.");
    print_request_login_form($fm_username, $fm_password, $fm_sitename);
  }
  else if (empty($fm_password) || empty($fm_siteid)) {
    display_errorbox("Must select the target sub-account.");
    $bapi = connect_bronto_session($fm_sessionid);
    $accounts = get_all_accounts($bapi);
    sort_accounts_by_name($accounts);
    print_agency_login_form($fm_username, $fm_password, $fm_sitename, $fm_siteid, $fm_sessionid, $accounts);
  }
  else {
    $login_info = bronto_user_login($fm_username, $fm_password, $fm_sitename, $fm_siteid);
    process_login($login_info, $fm_username, $fm_password, $fm_sitename);
  }
}
else if ($fm_stage == "userinfo") {
  $dbh = open_db();
  // we could obtain the username from the userinfo form itself, but this could allow a malicious user to
  // change the user information for a user other than him/herself; a DB lookup is used instead
  $username = db_get_session_user($dbh, $fm_sessionid);
  if ($username) {
    $got_error = false;
    $userinfo = array('firstname' => $fm_firstname, 'lastname' => $fm_lastname,
                      'email' => $fm_email, 'phone' => $fm_phone);
    if ($fm_firstname && $fm_lastname && $fm_email && $fm_phone) {
      if (is_valid_email($fm_email)) {
        if (db_update_user_info($dbh, $username, $userinfo) == true) {
          print_message_select_form($bapi, $fm_sessionid);
        }
        else {
          display_errorbox("Unable to update user information for user ".$username.".");
          print_request_login_form($username);
        }
      }
      else {
        display_errorbox("Must provide a valid e-mail address.");
        $got_error = true;
      }
    }
    else {
      display_errorbox("Must provide first name, last name, phone number, and e-mail address.");
      $got_error = true;
    }
    if ($got_error) {
      $userinfo['username'] = $username;
      print_user_info_form($fm_sessionid, $userinfo);
    }
  }
  else {
    display_errorbox("Unable to correlate session (id=".$fm_sessionid.") to a user.");
    print_request_login_form();
  }
}
else if ($fm_stage == "select") {
  if (empty($fm_sessionid)) {
    display_errorbox("Lost session ID; must log in again.");
    print_request_login_form();
  }
  else if ((empty($fm_listids) && empty($fm_segids)) || empty($fm_msgid)) {
    display_errorbox("Must select a message and at least one list or segment.");
    print_message_select_form($bapi, $fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                              $fm_fromaddr, $fm_fromname, $fm_replyaddr);
  }
  else if (empty($fm_fromaddr) || empty($fm_fromname)) {
    display_errorbox("Must provide the From Address and the Full Name of the sender.");
    print_message_select_form($bapi, $fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                              $fm_fromaddr, $fm_fromname, $fm_replyaddr);
  }
  else {
    $user_email = db_get_session_user_email(null, $fm_sessionid);
    print_confirm_form($bapi, $fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                       $fm_fromaddr, $fm_fromname, $fm_replyaddr, $fm_iscc, $user_email, $fm_ccemail);
  }
}
else if ($fm_stage == "confirm") {
  if (empty($fm_sessionid)) {
    display_errorbox("Lost session ID; must log in again.");
    print_request_login_form();
  }
  else if (empty($fm_msgid) || (empty($fm_listids) && empty($fm_segids)) || empty($fm_fromaddr)) {
    display_errorbox("Unable to confirm approval request; invalid data.");
    print_message_select_form($bapi, $fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                              $fm_fromaddr, $fm_fromname, $fm_replyaddr);
  }
  else if (!empty($fm_ccemail) && !is_valid_email($fm_ccemail)) {
    display_errorbox("The CC e-mail address [".$fm_ccemail."] does not appear to be valid.");
    $user_email = db_get_session_user_email(null, $fm_sessionid);
    print_confirm_form($bapi, $fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                       $fm_fromaddr, $fm_fromname, $fm_replyaddr, $fm_iscc, $user_email, $fm_ccemail);
  }
  else {
    print_verify_form($fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day, $fm_district,
                      $fm_fromaddr, $fm_fromname, $fm_replyaddr, $fm_iscc, $fm_ccemail, $fm_notes, $fm_initials);
  }
}
else if ($fm_stage == "verify") {
  if (empty($fm_sessionid)) {
    display_errorbox("Lost session ID; must log in again.");
    print_request_login_form();
  }
  else if (empty($fm_msgid) || (empty($fm_listids) && empty($fm_segids)) || empty($fm_fromaddr)) {
    display_errorbox("Unable to send approval request; invalid data.");
    print_message_select_form($bapi, $fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                              $fm_fromaddr, $fm_fromname, $fm_replyaddr);
  }
  else if (strlen($fm_initials) < 2) {
    display_errorbox("You must enter your initials in order to verify compliance with Senate guidelines.");
    print_verify_form($fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day, $fm_district,
                      $fm_fromaddr, $fm_fromname, $fm_replyaddr, $fm_iscc, $fm_ccemail, $fm_notes, $fm_initials);
  }
  else {
    $dbh = open_db();
    $session_rec = db_get_session($dbh, $fm_sessionid);
    if ($session_rec) {
      $got_error = false;
      $username = $session_rec['username'];
      $reviewer = DEFAULT_REVIEWER;
      $requserinfo = db_get_user($dbh, $username);
      $revuserinfo = db_get_user($dbh, $reviewer);
      
      if ($requserinfo && $revuserinfo && $requserinfo['email'] && $revuserinfo['email']) {
        $account_id = $session_rec['account_id'];
        $account_name = get_account_name($bapi, $account_id);
        $msg_name = get_message_name($bapi, $fm_msgid);
        $list_names = get_list_names($bapi, $fm_listids);
        $seg_names = get_segment_names($bapi, $fm_segids);
        
        $req_uuid = generate_request_uuid($account_id);
        $reqinfo = create_request_info(null, $req_uuid, 0, $username, $reviewer, $fm_sessionid, $account_id, $fm_msgid,
                                      $account_name, $msg_name, "$fm_year-$fm_month-$fm_day", $fm_district,
                                      $fm_fromaddr, $fm_fromname, $fm_replyaddr, $fm_iscc, $fm_ccemail,
                                      null, null, null, null, "AWAITING_REVIEW", $fm_notes, null,
                                      $fm_listids, $list_names, $fm_segids, $seg_names, null, null);
        $rc = db_save_request($dbh, $reqinfo);

        if ($rc === true) {
          if (send_request_to_reviewer($reqinfo, $requserinfo /* sender */, $revuserinfo /* recip */) == true) {
            print_send_form($reqinfo);
          }
          else {
            display_errorbox("Your request for approval could not be sent to a reviewer.");
            $got_error = true;
          }
        }
        else {
          display_errorbox("Unable to save request parameters; request not sent.");
          $got_error = true;
        }
      }
      else {
        display_errorbox("Unable to retrieve user and/or reviewer information.");
        $got_error = true;
      }
      
      if ($got_error) {
        print_verify_form($fm_sessionid, $fm_msgid, $fm_listids, $fm_segids, $fm_year, $fm_month, $fm_day,
                          $fm_fromaddr, $fm_fromname, $fm_replyaddr, $fm_iscc, $fm_ccemail, $fm_notes, $fm_initials);
      }
    }
    else {
      display_errorbox("Unable to find session (id=".$fm_sessionid."); data inconsistency");
      print_request_login_form();
    }
    
    $dbh = null;
  }
}
else {
  echo('<div class="errorbox">Unknown stage in the approval process</div>');
}

require_once 'include/footer.php';


function process_login($login_info, $username, $password, $sitename)
{
  if (is_array($login_info)) {
    // if an array is returned, then login was successful
    $bapi = $login_info['binding'];
    $sessionID = $login_info['sessionID'];
    $accountID = $login_info['accountID'];
    $isAgency = $login_info['isAgency'];
    
    if ($isAgency == true) {
      print_agency_login_form($username, $password, $sitename, "", $sessionID, $login_info['accounts']);
    }
    else {
      $dbh = open_db();
      if ($dbh) {
        $rc = db_save_user($dbh, $username, $password, 'BRONTO', 'REQUESTER', $sitename);
        if ($rc == false) {
          display_warnbox("Unable to save user information (user=".$username.",sitename=".$sitename.")");
        }
        $rc = db_save_session($dbh, $sessionID, $username, $accountID);
        if ($rc == false) {
          display_warnbox("Unable to save session information (id=".$sessionID.",user=".$username.")");
        }
        if (db_update_user_last_login($dbh, $username) == false) {
          echo "Unable to record login date/time.";
        }
        
        // Confirm that user information is available.
        $userinfo = db_get_user($dbh, $username);
        if (empty($userinfo['firstname']) || empty($userinfo['lastname']) || empty($userinfo['email'])) {
          print_user_info_form($sessionID, $userinfo);
        }
        else if (print_message_select_form($bapi, $sessionID) == false) {
          display_errorbox("Unable to connect to Bronto API.");
          print_request_login_form($username, $password, $sitename);
        }
      }
      else {
        display_errorbox("Unable to connect to database.");
        print_request_login_form($username, $password, $sitename);
      }
    }
  }
  else {
    if ($login_info === false) {
      // if "false" was returned, then login was unsuccessful (incorrect username, password, or sitename)
      display_errorbox("Invalid username, password, or sitename.");
    }
    else {
      // otherwise, "null" is returned, meaning no connectivity to Bronto API
      display_errorbox("Unable to connect to the Bronto API server.");
    }
    print_request_login_form($username, $password, $sitename);
  }
} // process_login()
?>
