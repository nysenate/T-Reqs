<?php
/*************************************************************************
 ** Bronto T-Reqs E-mail Approval Interface
 ** Purpose: Utility functions
 ** Organization: New York State Senate
 ** Author: Ken Zalewski
 ** Last revision: 2010-03-10
 *************************************************************************/

require_once 'defs.php';
require_once LIB_DIR.'/class.phpmailer.php';


function set_app_cookies()
{
  if (isset($_POST['fm_sitename'])) {
    setcookie("sitename", $_POST['fm_sitename']);
  }
  /************* no longer storing the e-mail address in a cookie, since it is stored in the DB.
   if (isset($_POST['fm_email'])) {
   setcookie("email", $_POST['fm_email']);
   }
   **************/
} // set_app_cookies()



function generate_request_uuid($account_id)
{
  // Last 8 chars of the account ID will be the prefix of the UUID.
  $account_id_suffix = substr($account_id, -8);
  $req_uuid = uniqid($account_id_suffix."-");
  return $req_uuid;
} // generate_request_uuid()



function display_warnbox($msg)
{
  echo('<div class="warnbox">'.$msg."</div>\n");
} // display_warnbox()



function display_errorbox($msg)
{
  echo('<div class="errorbox">'.$msg."</div>\n");
} // display_errorbox()



function get_current_year()
{
  return date('Y');
} // get_current_year()



function get_current_month()
{
  return date('m');
} // get_current_month()



function get_current_day()
{
  return date('d');
} // get_current_day()



function is_reviewer($uinfo)
{
  if ($uinfo['role'] == 'REVIEWER') {
    return true;
  }
  else {
    return false;
  }
} // is_reviewer()



/**
 * Generic function to read in a file of key/value pairs and return an associative array of those pairings.
 * Any key name that ends with "[]" is assumed to be an array, and the value will be parsed into constituent values
 * using a comma as a delimiter.
 *
 * @param $filepath Path to a file containing key=value pairs, one per line.
 * @return Associative array of key/value pairs.
 */
function get_key_value_pairs($filepath)
{
  $fd = @fopen($filepath, "r");
  if ($fd) {
    $kvpairs = array();
    while (($s = fgets($fd, 1024)) !== false) {
      $s = trim($s);
      $pos = strpos($s, "=");
      if ($pos !== false) {
        $key = substr($s, 0, $pos);
        $val = substr($s, $pos + 1);
        if (strlen($key) > 2 && substr($key, -2) == "[]") {
          // key represents an array value
          $key = substr($key, 0, -2);
          $val = explode(",", $val);
        }
        $kvpairs[$key] = $val;
      }
    }
    fclose($fd);
    return $kvpairs;
  }
  else {
    return null;
  }
} // get_key_value_pairs()



function get_config_params($config_filepath = CONFIG_FILEPATH)
{
  return get_key_value_pairs($config_filepath);
} // get_config_params()



function get_request_params($req_id)
{
  return get_key_value_pairs(REQUEST_DIR."/".$req_id);
} // get_request_params()



function save_request_params($session_id, $account_id,
$msg_id, $list_ids, $seg_ids,
$year, $month, $day)
{
  $list_ids_str = (empty($list_ids)) ? "" : implode(",", $list_ids);
  $seg_ids_str = (empty($seg_ids)) ? "" : implode(",", $seg_ids);

  // Last 8 chars of the client ID.
  $account_id_suffix = substr($account_id, -8);
  $request_id = uniqid($account_id_suffix."-");

  $fd = @fopen(REQUEST_DIR."/".$request_id, "w");
  if ($fd) {
    fwrite($fd, "fm_requestid=".$request_id."\n".
                "fm_sessionid=".$session_id."\n".
                "fm_accountid=".$account_id."\n".
                "fm_msgid=".$msg_id."\n".
                "fm_listids[]=".$list_ids_str."\n".
                "fm_segids[]=".$seg_ids_str."\n".
                "fm_year=".$year."\n".
                "fm_month=".$month."\n".
                "fm_day=".$day."\n");
    fclose($fd);
    return $request_id;
  }
  else {
    return null;
  }
} // save_request_params()



function get_review_url($req_id)
{
  $host = $_SERVER['HTTP_HOST'];
  $path = dirname($_SERVER['PHP_SELF']);
  $query = "fm_requestid=$req_id";
  $url_str = 'http://'.$host.$path."/".REVIEW_SCRIPT."?".$query;
  return $url_str;
} // get_review_url()



function send_email_message($from_addr, $from_name, $to_addr, $to_name, $cc_addrs, $subject, $body)
{
  $smtp_host = DEFAULT_SMTP_HOST;
  $smtp_port = DEFAULT_SMTP_PORT;
  $smtp_user = DEFAULT_SMTP_USER;
  $smtp_pass = DEFAULT_SMTP_PASS;

  $cfg_rec = get_config_params();
  if ($cfg_rec) {
    $smtp_host = $cfg_rec['smtp_host'];
    $smtp_port = $cfg_rec['smtp_port'];
    $smtp_user = $cfg_rec['smtp_username'];
    $smtp_pass = $cfg_rec['smtp_password'];
  }

  $mailer = new PHPMailer(true); // turn on exceptions to avoid echoed output
  try {
    $mailer->IsSMTP();
    $mailer->Host = $smtp_host;
    $mailer->Port = $smtp_port;
    $mailer->SMTPAuth = (empty($smtp_user) && empty($smtp_pass)) ? false : true;
    $mailer->Username = $smtp_user;
    $mailer->Password = $smtp_pass;
    $mailer->SetFrom($from_addr, $from_name, 1);
    $mailer->AddAddress($to_addr, $to_name);
    if ($cc_addrs) {
      foreach ($cc_addrs as $cc_addr) {
        $mailer->AddCC($cc_addr);
      }
    }
    $mailer->Subject = $subject;
    $mailer->Body = $body;
    return $mailer->Send();
  }
  catch (phpmailerException $ex) {
    display_errorbox("Unable to send request to ".$to_addr."<br/>Error: ".$mailer->ErrorInfo);
    return false;
  }
} // send_email_message()



function send_request_to_reviewer($reqinfo, $senderinfo, $recipinfo)
{
  $revfnm = $recipinfo['firstname'];
  $revlnm = $recipinfo['lastname'];
  $revemail = $recipinfo['email'];
  $user_name = $senderinfo['firstname']." ".$senderinfo['lastname'];
  $useremail = $senderinfo['email'];
  $sitename = $senderinfo['sitename'];
  $req_id = $reqinfo['uuid'];
  $msg_name = $reqinfo['message_name'];
  $reqnotes = $reqinfo['request_notes'];
  $reqnotes = (empty($reqnotes)) ? "N/A" : $reqnotes;

  $cc_emails = array();
  if ($reqinfo['cc_user'] == 1) {
    $cc_emails[] = $useremail;
  }
  if (!empty($reqinfo['cc_email'])) {
    $cc_emails[] = $reqinfo['cc_email'];;
  }
  
  $url_str = get_review_url($req_id);
  $subject = APP_FULLNAME." E-mail Approval Request [$req_id]";
  $body = "Hello ".$revfnm."!\n\n".
          "You have received a request from $user_name ($useremail) to approve ".
          "an e-mail message that is queued up for delivery.\n\n".
          "Please use the link below to sign into the ".APP_NAME." website, review the message, ".
          "and either approve or reject it based on Senate guidelines.\n\n\n".
          "    $url_str\n\n\n".
          "Please reference the information provided below when contacting Senate support staff ".
          "with technical questions.\n\n".
          "Request Details:\n".
          "    Request ID: $req_id\n".
          "    Message Name: $msg_name\n".
          "    Site Name: $sitename\n\n".
          "    Notes: $reqnotes\n\n".
          "Thank you for your attention to this matter.\n\n\n".
          "Sincerely,\n\n".
          "$user_name\n".
          "(via ".APP_NAME.")\n\n";
  return send_email_message($useremail, $user_name, $revemail, $revfnm." ".$revlnm, $cc_emails, $subject, $body);
} // send_request_to_reviewer()



function send_approval_to_requester($reqinfo, $senderinfo, $recipinfo)
{
  $req_id = $reqinfo['uuid'];
  $msg_name = $reqinfo['message_name'];
  $revnotes = $reqinfo['review_notes'];
  $revnotes = (empty($revnotes)) ? "N/A" : $revnotes;
  $from_addr = $senderinfo['email'];
  $from_fnm = $senderinfo['firstname'];
  $from_lnm = $senderinfo['lastname'];
  $from_name = $from_fnm." ".$from_lnm;
  $to_addr = $recipinfo['email'];
  $to_fnm = $recipinfo['firstname'];
  $to_lnm = $recipinfo['lastname'];
  $to_name = $to_fnm." ".$to_lnm;
  $sitename = $recipinfo['sitename'];
  $cc_addrs = array();
  $subject = APP_FULLNAME." E-mail Approval [$req_id]";
  $body = "Hello ".$to_fnm."!\n\n".
          "Your request to send a blast e-mail entitled '$msg_name' ".
          "has been APPROVED!\n\n".
          "The message has already been scheduled for delivery.  Please reference the information ".
          "provided below when contacting Senate support staff with any follow-up questions.\n\n".
          "Request Details:\n".
          "    Request ID: $req_id\n".
          "    Message Name: $msg_name\n".
          "    Site Name: $sitename\n\n".
          "    Notes: $revnotes\n\n".
          "We appreciate your willingness to follow Senate guidelines when drafting your correspondence ".
          "and look forward to reviewing further requests from your office.\n\n\n".
          "Sincerely,\n\n".
          "$from_name\n".
          "(via ".APP_NAME.")\n\n";
  
  return send_email_message($from_addr, $from_name, $to_addr, $to_name, $cc_addrs, $subject, $body);
} // send_approval_to_requester()



function send_rejection_to_requester($reqinfo, $senderinfo, $recipinfo)
{
  $req_id = $reqinfo['uuid'];
  $msg_name = $reqinfo['message_name'];
  $revnotes = $reqinfo['review_notes'];
  $revnotes = (empty($revnotes)) ? "N/A" : $revnotes;
  $from_addr = $senderinfo['email'];
  $from_fnm = $senderinfo['firstname'];
  $from_lnm = $senderinfo['lastname'];
  $from_name = $from_fnm." ".$from_lnm;
  $to_addr = $recipinfo['email'];
  $to_fnm = $recipinfo['firstname'];
  $to_lnm = $recipinfo['lastname'];
  $to_name = $to_fnm." ".$to_lnm;
  $sitename = $recipinfo['sitename'];
  $cc_addrs = array();
  $subject = APP_FULLNAME." E-mail Rejection [$req_id]";
  $body = "Hello ".$to_fnm."!\n\n".
          "Your request to send a blast e-mail entitled '$msg_name' ".
          "has been rejected by an authorized Senate reviewer.\n\n".
          "Please reference the information provided below when contacting Senate support staff ".
          "with technical questions.\n\n".
          "Request Details:\n".
          "    Request ID: $req_id\n".
          "    Message Name: $msg_name\n".
          "    Site Name: $sitename\n\n".
          "    Notes: $revnotes\n\n".
          "We apologize for this inconvenience, and look forward to assisting you with revising ".
          "your correspondence.\n\n\n".
          "Sincerely,\n\n".
          "$from_name\n".
          "(via ".APP_NAME.")\n\n";
  
  return send_email_message($from_addr, $from_name, $to_addr, $to_name, $cc_addrs, $subject, $body);
} // send_rejection_to_requester()



function is_authenticated()
{
  if (isset($_SESSION) && $_SESSION['auth'] == true) {
    return true;
  }
  else {
    return false;
  }
} // is_authenticated()



function is_valid_email($addr)
{
  if (filter_var($addr, FILTER_VALIDATE_EMAIL) === false) {
    return false;
  }
  else {
    // made it past the PHP validator, now add our own criteria
    // 1. username must be 2 or more characters
    // 2. domain portion must end with a "." followed by 2 or more characters
    return preg_match('/^[^@]{2,}@[^@]+\.[^@]{2,}$/', $addr);
  }
} // is_valid_email()



function auth_ldap_user($username, $password)
{
  $ldap_host = DEFAULT_LDAP_HOST;
  $cfg_rec = get_config_params();
  if ($cfg_rec) {
    $ldap_host = $cfg_rec['ldap_host'];
  }
  $ds = ldap_connect($ldap_host);
  if ($ds) {
    $rc = ldap_bind($ds, $username, $password);
    ldap_close($ds);
    return $rc ? true : false;
  }
  else {
    return false;
  }
} // auth_ldap_user()



/*
 * This is the comparison function used by usort() in order to sort account objects by name.
 */
function compare_account_names($a, $b)
{
  if ($a->name == $b->name) {
    return 0;
  }
  return ($a->name < $b->name) ? -1 : 1;
} // compare_account_names()



function sort_accounts_by_name(&$accounts)
{
  usort($accounts, "compare_account_names");
} // sort_accounts_by_name()



function fix_preview_url_bug($url)
{
  // Currently, a bug exists in the Bronto API that causes it to return an empty ssid.  This is a work-around.
  return str_replace("ssid=&", "ssid=12557&", $url);
} // fix_preview_url_bug()

?>
