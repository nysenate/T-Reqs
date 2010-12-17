<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Database access functions
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2010-06-15
*************************************************************************/

require_once 'funcs.php';


function open_db($p_dbdsn = null, $p_dbuser = null, $p_dbpass = null)
{
  $dbdsn = DEFAULT_DB_DSN;
  $dbuser = DEFAULT_DB_USER;
  $dbpass = DEFAULT_DB_PASS;
  
  $cfg_rec = get_config_params();
  if ($cfg_rec) {
    $dbdsn = $cfg_rec['db_dsn'];
    $dbuser = $cfg_rec['db_user'];
    $dbpass = $cfg_rec['db_pass'];
  }
  
  $dbdsn = ($p_dbdsn == null) ? $dbdsn : $p_dbdsn;
  $dbuser = ($p_dbuser == null) ? $dbuser : $p_dbuser;
  $dbpass = ($p_dbpass == null) ? $dbpass : $p_dbpass;
  
  try {
    $dbh = new PDO($dbdsn, $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
  }
  catch (PDOException $ex) {
    echo "Connection failed: ".$ex->getMessage();
    return null;
  }
} // open_db()



function db_get_records($p_dbh, $table_name, $key_field, $key_value, $sortby = null)
{
  $q = "select * from ".$table_name;
  if (!empty($key_field)) {
    $q .= " where ".$key_field." = ?";
  }
  if ($sortby) {
    $q .= " order by ".$sortby;
  }

  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);
    $vals = array($key_value);
    $sth->execute($vals);
    $res = $sth->fetchAll(PDO::FETCH_ASSOC);
    $dbh->commit();
    $dbh = null;
    if (count($res) > 0) {
      return $res;
    }
    else {
      return null;
    }
  }
  catch (PDOException $ex) {
    $dbh = null;
    return null;
  }
} // db_get_single_record()



function db_get_single_record($p_dbh, $table_name, $key_field, $key_value)
{
  $res = db_get_records($p_dbh, $table_name, $key_field, $key_value, null);
  if ($res) {
    return $res[0];
  }
  else {
    return null;
  }
} // db_get_single_record()



function db_get_request($p_dbh, $req_id)
{
  return db_get_single_record($p_dbh, "request", "id", $req_id);
} // db_get_request()



function db_get_request_using_uuid($p_dbh, $uuid)
{
  return db_get_single_record($p_dbh, "request", "uuid", $uuid);
} // db_get_request_using_uuid()



function db_load_request($p_dbh, $uuid)
{
  $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
  $reqinfo = db_get_request_using_uuid($p_dbh, $uuid);
  if ($reqinfo) {
    $req_id = $reqinfo['id'];
    $list_ids = $list_names = $seg_ids = $seg_names = $cont_ids = $cont_names = array();
    $res = db_get_records($p_dbh, "request_list", "req_id", $req_id);
    for ($i = 0; $i < count($res); $i++) {
      $list_ids[] = $res[$i]['list_id'];
      $list_names[] = $res[$i]['list_name'];
    }
    $res = db_get_records($p_dbh, "request_segment", "req_id", $req_id);
    for ($i = 0; $i < count($res); $i++) {
      $seg_ids[] = $res[$i]['seg_id'];
      $seg_ids[] = $res[$i]['seg_name'];
    }
    $dbh = null;
    $reqinfo['list_ids'] = $list_ids;
    $reqinfo['list_names'] = $list_names;
    $reqinfo['seg_ids'] = $seg_ids;
    $reqinfo['seg_names'] = $seg_names;
    $reqinfo['cont_ids'] = $cont_ids;
    $reqinfo['cont_names'] = $cont_names;
    return $reqinfo;
  }
  else {
    $dbh = null;
    return null;
  }
} // db_load_request()



function create_request_info($id, $uuid, $retries, $requester, $reviewer, $session_id, $account_id, $message_id,
                             $account_name, $message_name, $delivery_date, $district, $from_addr, $from_name,
                             $reply_addr, $cc_user, $cc_email, $created_on, $updated_on, $reviewed_on, $closed_on,
                             $status, $reqnotes, $revnotes,
                             $list_ids, $list_names, $seg_ids, $seg_names, $cont_ids, $cont_names)
{
  $reqinfo = array(
    "id"=>$id, "uuid"=>$uuid, "retries"=>$retries, "requester"=>$requester, "reviewer"=>$reviewer,
    "session_id"=>$session_id, "account_id"=>$account_id, "message_id"=>$message_id, "account_name"=>$account_name,
    "message_name"=>$message_name, "delivery_date"=>$delivery_date, "district"=>$district,
    "from_addr"=>$from_addr, "from_name"=>$from_name, "reply_addr"=>$reply_addr,
    "cc_user"=>(int)$cc_user, "cc_email"=>$cc_email,
    "created_on"=>$created_on, "updated_on"=>$updated_on, "reviewed_on"=>$reviewed_on, "closed_on"=>$closed_on,
    "status"=>$status, "request_notes"=>$reqnotes, "review_notes"=>$revnotes,
    "list_ids"=>$list_ids, "list_names"=>$list_names,
    "seg_ids"=>$seg_ids, "seg_names"=>$seg_names,
    "cont_ids"=>$cont_ids, "cont_names"=>$cont_names
  );
  return $reqinfo;
} // create_request_info()



/**
 * Save request information into the DB.
 * @return true on success, false on failure
 */
function db_save_request($p_dbh, $reqinfo)
{
  $req_fields = array("uuid", "requester", "reviewer", "session_id", "account_id", "message_id",
                      "account_name", "message_name", "delivery_date", "district",
                      "from_addr", "from_name", "reply_addr", "cc_user", "cc_email", "status", "request_notes");
  
  $list_ids = $reqinfo['list_ids'];
  $list_names = $reqinfo['list_names'];
  $seg_ids = $reqinfo['seg_ids'];
  $seg_names = $reqinfo['seg_names'];
  
  if (count($list_ids) != count($list_names)) {
    echo "Number of list IDs (".count($list_ids).") and list names (".count($list_names).") does not match";
    return null;
  }
  
  if (count($seg_ids) != count($seg_names)) {
    echo "Number of segment IDs (".count($seg_ids).") and segment names (".count($seg_names).") does not match";
    return null;
  }

  $q1 = $p1 = "";
  $vals = array();
  foreach ($req_fields as $req_field) {
    $q1 .= empty($q1) ? $req_field : ",".$req_field;
    $p1 .= empty($p1) ? "?" : ",?";
    $vals[] = $reqinfo[$req_field];
  }
  
  $q1 = "insert into request ($q1,created_on,updated_on) values ($p1,NOW(),NOW())";
  $q2 = "insert into request_list (req_id, list_id, list_name) values (LAST_INSERT_ID(),?,?)";
  $q3 = "insert into request_segment (req_id, seg_id, seg_name) values (LAST_INSERT_ID(),?,?)";
  
  try {
    // open DB connection for the extent of this function if a DB handle was not provided
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $dbh->beginTransaction();
    $sth1 = $dbh->prepare($q1);
    $sth2 = $dbh->prepare($q2);
    $sth3 = $dbh->prepare($q3);
    
    $sth1->execute($vals);
    
    for ($i = 0; $i < count($list_ids); $i++) {
      $vals = array($list_ids[$i], $list_names[$i]);
      $sth2->execute($vals);
    }
    
    for ($i = 0; $i < count($seg_ids); $i++) {
      $vals = array($seg_ids[$i], $seg_names[$i]);
      $sth3->execute($vals);
    }
    
    $dbh->commit();
    $dbh = null;    // close the connection if it was opened locally
    return true;
  }
  catch (PDOException $ex) {
    echo "PDO Error: ".$ex->getMessage();
    if ($dbh) {
      $dbh->rollBack();
      $dbh = null;
    }
    return false;
  }
} // db_save_request()



function db_update_request_status($p_dbh, $req_uuid, $status, $revnotes = null)
{
  if ($status == "INCOMPLETE" || $status == "AWAITING_REVIEW") {
    $time_field = "created_on";
  }
  else if ($status == "UNDER_REVIEW") {
    $time_field = "reviewed_on";
  }
  else if ($status == "APPROVED" || $status == "REJECTED") {
    $time_field = "closed_on";
  }
  else {
    display_errorbox("Invalid status [".$status."] was provided.");
    return false;
  }
  
  $q = "update request set status=?, review_notes=?, $time_field=NOW(), updated_on=NOW() where uuid=?";
  
  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);
    $vals = array($status, $revnotes, $req_uuid);
    $sth->execute($vals);
    $dbh->commit();
    $dbh = null;
    return true;
  }
  catch (PDOException $ex) {
    echo "PDO Error: ".$ex->getMessage();
    if ($dbh) {
      $dbh->rollBack();
      $dbh = null;
    }
    return false;
  }
} // db_update_request_status()



function db_update_request_status_user($p_dbh, $req_uuid, $status, $revnotes = null, $user = null)
{
  if ($status == "INCOMPLETE" || $status == "AWAITING_REVIEW") {
    $time_field = "created_on";
  }
  else if ($status == "UNDER_REVIEW") {
    $time_field = "reviewed_on";
  }
  else if ($status == "APPROVED" || $status == "REJECTED") {
    $time_field = "closed_on";
  }
  else {
    display_errorbox("Invalid status [".$status."] was provided.");
    return false;
  }
  //$q = "update request set status=?, review_notes=?, $time_field=NOW(), updated_on=NOW() where uuid=?"; //AB
  $q = "update request set status=?, review_notes=?, $time_field=NOW(), updated_on=NOW(), reviewer=? where uuid=?";
  
  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);
    $vals = array($status, $revnotes, $user, $req_uuid);
    $sth->execute($vals);
    $dbh->commit();
    $dbh = null;
    return true;
  }
  catch (PDOException $ex) {
    echo "PDO Error: ".$ex->getMessage();
    if ($dbh) {
      $dbh->rollBack();
      $dbh = null;
    }
    return false;
  }
} // db_update_request_status()



function db_get_user($p_dbh, $username)
{
  return db_get_single_record($p_dbh, "user", "username", $username);
} // db_get_user()



function db_user_exists($p_dbh, $username)
{
  return db_get_user($p_dbh, $username) ? true : false;
} // db_user_exists()



function db_get_user_email($p_dbh, $username)
{
  $user = db_get_user($p_dbh, $username);
  if ($user) {
    return $user['email'];
  }
  else {
    return null;
  }
} // db_get_user_email()



function db_get_session_user_email($p_dbh, $session_id)
{
  $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
  $username = db_get_session_user($dbh, $session_id);
  $email = null;
  if ($username) {
    $email = db_get_user_email($dbh, $username);
  }
  $dbh = null;
  return $email;
} // db_get_session_user_email()



function db_auth_local_user($dbh, $username, $password)
{
  $uinfo = db_get_user($dbh, $username);
  if ($uinfo && !empty($uinfo['password']) && $uinfo['password'] === $password & $uinfo['scope'] === 'LOCAL') {
    return $uinfo;
  }
  else {
    return null;
  }
} // db_auth_local_user()



function db_save_user($p_dbh, $username, $password, $scope, $role, $sitename)
{  
  $q_insert = "insert into user (username, password, scope, role, sitename, created_on, updated_on) ".
              "values (?,?,?,?,?,NOW(),NOW())";
  $q_update = "update user set password=?, scope=?, role=?, sitename=?, updated_on=NOW() where username=?";
  
  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    if (db_user_exists($dbh, $username)) {
      $q = $q_update;
      $vals = array($password, $scope, $role, $sitename, $username);
    }
    else {
      $q = $q_insert;
      $vals = array($username, $password, $scope, $role, $sitename);
    }
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);    
    $sth->execute($vals);
    $dbh->commit();
    $dbh = null;
    return true;
  }
  catch (PDOException $ex) {
    echo "Error Message: ".$ex->getMessage();
    if ($dbh) {
      $dbh->rollBack();
      $dbh = null;
    }
    return false;
  }
} // db_save_user()



function db_update_user_info($p_dbh, $username, $uinfo)
{
  if (count($uinfo) < 1) {
    return false;
  }
  
  $q = "";
  $vals = array();
  foreach ($uinfo as $fldname => $fldval) {
    if (!empty($q)) {
      $q .= ", ";
    }
    // Any field value that begins with "%%" will use its remaining text literally, without binding a parameter.
    if (strlen($fldval) > 2 && substr($fldval, 0, 2) == "%%") {
      $q .= $fldname."=".substr($fldval, 2);
    }
    else {
      $q .= $fldname."=?";
      $vals[] = $fldval;
    }
  }
  
  $q = "update user set ".$q." where username=?";
  $vals[] = $username;
  
  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);
    $sth->execute($vals);
    $dbh->commit();
    $dbh = null;
    return true;
  }
  catch (PDOException $ex) {
    echo "PDO Error: ".$ex->getMessage();
    if ($dbh) {
      $dbh->rollBack();
      $dbh = null;
    }
    return false;
  }
} // db_update_user_info()



function db_update_user_last_login($dbh, $username)
{
  $userinfo['last_login'] = "%%NOW()";
  return db_update_user_info($dbh, $username, $userinfo);
} // db_update_user_last_login()



function db_get_session($p_dbh, $session_id)
{
  return db_get_single_record($p_dbh, "session", "id", $session_id);
} // db_get_session()



function db_get_session_user($p_dbh, $session_id)
{
  $res = db_get_session($p_dbh, $session_id);
  if ($res) {
    return $res['username'];
  }
  else {
    return null;
  }
} // db_get_session_user()



function db_save_session($p_dbh, $session_id, $username, $account_id)
{
  $q_insert = "insert into session (id, username, account_id, created_on) values (?,?,?,NOW())";
  $q_update = "update session set username=?, account_id=?, created_on=NOW() where id=?";
  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $sess = db_get_session($dbh, $session_id);
    if ($sess === null) {
      $q = $q_insert;
      $vals = array($session_id, $username, $account_id);
    }
    else {
      $q = $q_update;
      $vals = array($username, $account_id, $session_id);
    }
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);
    $sth->execute($vals);
    $dbh->commit();
    $dbh = null;
    return true;
  }
  catch (PDOException $ex) {
    echo "Error Message: ".$ex->getMessage();
    if ($dbh) {
      $dbh->rollBack();
      $dbh = null;
    }
    return false;
  }
} // db_save_session()



function db_get_all_senators($p_dbh = null)
{
  return db_get_records($p_dbh, "senator", null, null, "lastname");
} // db_get_all_senators()



function get_senator($p_dbh, $p_district)
{
  return db_get_single_record($p_dbh, "senator", "district", $p_district);
} // get_senator()

include ('display_listrequest_function.php');
?>