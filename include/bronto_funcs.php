<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Bronto API utility functions
** Organization: New York State Senate
** Department: Office of the CIO
** Author: Ken Zalewski
** Last revision: 2010-07-06
*************************************************************************/

require_once 'defs.php';


/*
** The result from any of the read() methods [readAccounts(), readLists(),
** readMessages(), readSegments(), etc.] has a "return" object.  The "return"
** object has an object whose name matches the type of object that is being
** returned.  However, that named object member is not present if no objects
** are returned.
**
** This method attempts to fix all of these inconsistencies.
*/
function normalize_result($res)
{
  $ret = $res->return;
  $obj_vars = get_object_vars($ret);
  if (count($obj_vars) > 0) {
    return make_array(reset($obj_vars));
  }
  else {
    return array();
  }
} // normalize_result()



// This is a kludge to get around Bronto's inconsistent API return values.
function make_array($obj)
{
  if (is_null($obj)) {
    return array();
  }
  else if (is_array($obj)) {
    return $obj;
  }
  else {
    return array($obj);
  }
} // make_array()



/*
** This function builds a filter that will match any record where the value
** of the given attribute $key is in the set specified by the $vals array.
**
** If the array is empty, then no records will be matched.
** If the $vals parameter is null, then the return filter is null, which will
** match all records.
*/
function make_filter_for_multivalued_criteria($key, $vals)
{
  if (is_null($vals)) {
    return null;
  }
  else if (!is_array($vals)) {
    $vals = array($vals);
  }
  else if (empty($vals)) {
    $vals = array(null);		// null won't match any IDs
  }

  $filter = array();

  if (!is_array($vals)) {
    $vals = array($vals);
  }

  foreach ($vals as $val) {
    $criteria['attribute'] = $key;
    $criteria['comparison'] = "=";
    $criteria['value']['type'] = 'string';
    $criteria['value']['value'] = $val;
    $filter['criteria'][] = $criteria;
  }

  $filter['operator'] = 'or';
  return $filter;
} // make_filter_for_multivalued_criteria()



/*
** This function is a wrapper around make_filter_for_multivalued_criteria.
** It matches records using the "id" attribute.
*/
function make_filter_for_ids($ids)
{
  return make_filter_for_multivalued_criteria("id", $ids);
} // make_filter_for_ids()



/*
** This function is a wrapper around make_filter_for_multivalued_criteria.
** It matches records using the "name" attribute.
*/
function make_filter_for_names($names)
{
  return make_filter_for_multivalued_criteria("name", $names);
} // make_filter_for_names()



/*
** This function is a wrapper around make_filter_for_multivalued_criteria.
** It matches records using the "email" attribute.
*/
function make_filter_for_emails($emails)
{
  return make_filter_for_multivalued_criteria("email", $emails);
} // make_filter_for_emails()



function make_params($attr, $filter)
{
  return array("attributes" => $attr, "filter" => $filter);
} // make_params()



/**
 * Log into Bronto using a sub-account (or possibly agency) username, password, and sitename.
 * @param $username The username of a user that maps to a subaccount, or an agency username.
 * @param $password The password associated with the user.
 * @param $sitename The site name of the subaccount or agency.
 * @param $siteid The ID of a subaccount to log into, if the username references an agency user.
 * @return See return value of bronto_login()
 */
function bronto_user_login($username, $password, $sitename, $siteid = null)
{
  $params = array(
    'username' => $username,
    'password' => $password,
    'sitename' => $sitename,
    'siteId' => $siteid
  );
  
  return bronto_login($params);
} // bronto_user_login()



/**
 * Log into Bronto using the agency account, then changing to the provided sub-account.
 * @param $account_id The sub-account to switch into.  If null, then login as the agency (superuser).
 * @return See return value of bronto_login()
 */
function bronto_agency_login($account_id = null)
{
  // Retrieve Agency account information from the config file.
  $cfg_rec = get_config_params();
  if ($cfg_rec) {
    $agency_username = $cfg_rec['agency_username'];
    $agency_password = $cfg_rec['agency_password'];
    $agency_sitename = $cfg_rec['agency_sitename'];
    $params = array(
                    'username' => $agency_username,
                    'password' => $agency_password,
                    'sitename' => $agency_sitename,
                    'siteId' => $account_id
                   );
    return bronto_login($params);
  }
  else {
    return null;
  }
} // bronto_agency_login()



/**
 * Log into Bronto using an array of login parameters.
 * @param $params An array containing the login information.
 * @return An array containing the SOAP binding, Bronto sessionID, and other information on successful login,
 *         false if login is unsuccesfful, and null if no connection to the Bronto API server can be established.
 */
function bronto_login($params)
{
  $destination_url = BRONTO_API_URL . "/?q=mail_3";
  $bronto_wsdl = $destination_url . "&wsdl";
  
  try {
    $binding = new SoapClient($bronto_wsdl, array('trace' => true));
    if ($binding) {
      $binding->__setLocation($destination_url);
  
      $result = $binding->login($params);
  
      if ($result->return->success) {
        $session_id = $result->return->sessionId;
        $service_url = $result->return->serviceURL;
        $session_header = new SoapHeader(BRONTO_API_URL, 'sessionHeader', array('sessionId' => $session_id));
        $binding->__setSoapHeaders($session_header);
        //$binding->__setLocation($service_url);
        $accounts = get_all_accounts($binding);
        sort_accounts_by_name($accounts);
        
        // This is a crude method for determining whether or not the current user is an Agency account user.
        if (count($accounts) > 1) {
          $is_agency_account = true;
          $account_id = null;
        }
        else {
          $is_agency_account = false;
          $account_id = $accounts[0]->id;
        }
        
        $res = array("binding" => $binding, "sessionID" => $session_id, "serviceURL" => $service_url,
                     "accountID" => $account_id, "isAgency" => $is_agency_account);
        if ($is_agency_account) {
          $res["accounts"] = $accounts;
        }
        return $res;
      }
      else {
        return false;
      }
    }
    else {
      return null;
    }
  }
  catch (SoapFault $ex) {
    if (isset($binding) && $binding) {
      print_exception($binding, $ex);
    }
    return null;
  }
} // bronto_login()



function connect_bronto_session($session_id)
{
  $destination_url = BRONTO_API_URL . "/?q=mail_3";
  $bronto_wsdl = $destination_url . "&wsdl";
  
  try {
    $binding = new SoapClient($bronto_wsdl, array('trace' => true));
    $binding->__setLocation($destination_url);
    $session_header = new SoapHeader(BRONTO_API_URL, 'sessionHeader', array('sessionId' => $session_id));
    $binding->__setSoapHeaders($session_header);
    return $binding;
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  } 
} // connect_bronto_session()



/**
 * The Bronto API has no simple function to determine if a session is active or not.  I am using the
 * Bronto readAccounts() function to handle it.
 * 
 * @param $binding
 * @return true if the session associated with the provided binding is active, false otherwise
 */
function is_session_active($binding)
{
  try {
    $accounts = $binding->readAccounts();
    if ($accounts) {
      return true;
    }
    else {
      echo "no accounts; binding=";print_r($binding);
      return false;
    }
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return false;
  }
} // is_session_active()



function print_exception($binding, $ex)
{
  echo("<div class='errorbox'>Error Fault: " . $ex->faultcode);
  echo("\n<br/>More Detail: " . $ex->faultstring);
  echo("\n<br/>Error Detail: " . $ex->detail->fault->code . "::" . $ex->detail->fault->message);
  echo("\n\n<br/><br/>Headers:\n<br/>" . $binding->__getLastRequestHeaders());
  echo("\n\n<br/><br/>Request:\n<br/>". $binding->__getLastRequest());
  echo("\n\n<br/><br/>Response:\n<br/>". $binding->__getLastResponse());
  echo("\n</div>\n");
} // print_exception()



function print_errors($errs)
{
  if (!is_array($errs)) {
    $errs = array($errs);
  }
  foreach ($errs as $err) {
    echo "<div class='errorbox'>Return Error: code=".$err->code;
    echo "\n<br/>Message: ".$err->message;
    echo "\n</div>\n";
  }
} // print_errors()



function get_accounts($binding, $filter)
{
  try {
    $attr['currContactCount'] = true;
    $attr['maxContactCount'] = true;
    $attr['monthEmailCount'] = true;
    $attr['currHostingSize'] = true;
    $attr['maxHostingSize'] = true;
    $params = make_params($attr, $filter);
    $result = $binding->readAccounts($params);
    return normalize_result($result);
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  }
} // get_accounts()



function get_accounts_using_ids($binding, $ids)
{
  return get_accounts($binding, make_filter_for_ids($ids));
} // get_accounts_using_ids()



function get_accounts_using_names($binding, $names)
{
  return get_accounts($binding, make_filter_for_names($names));
} // get_accounts_using_names()



function get_all_accounts($binding)
{
  return get_accounts($binding, null);
} // get_all_accounts()



function get_first_account($binding)
{
  $accounts = get_all_accounts($binding);
  return $accounts[0];
} // get_first_account()



function get_account_name($binding, $id)
{
  $accounts = get_accounts_using_ids($binding, $id);
  if ($accounts) {
    return $accounts[0]->name;
  }
  else {
    return null;
  }
} // get_account_name()



function get_contacts($binding, $filter, $field_ids = null)
{
  try {
    $attr = array("status" => true, "msgPref" => true, "created" => true, "modified" => true,
                  "source" => true, "customSource" => true, "lists" => true);
    if ($field_ids && is_array($field_ids) && count($field_ids) > 0) {
      $attr['fields'] = $field_ids;
    }
    $params = make_params($attr, $filter);
    $result = $binding->readContacts($params);
    return normalize_result($result);
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  }  
} // get_contacts()



function get_contacts_inc_fields($binding, $filter)
{
  $field_ids = array();
  $fields = get_all_fields($binding);
  foreach ($fields as $field) {
    $field_ids[] = $field->id;
  }
  return get_contacts($binding, $filter, $field_ids);
} // get_contacts_inc_fields()



function get_contacts_no_fields($binding, $filter)
{
  return get_contacts($binding, $filter, null);
} // get_contacts_no_fields()



function get_contacts_using_ids($binding, $ids)
{
  return get_contacts_inc_fields($binding, make_filter_for_ids($ids));
} // get_contacts_using_ids()



function get_contacts_using_emails($binding, $emails)
{
  return get_contacts_inc_fields($binding, make_filter_for_emails($emails));
} // get_contacts_using_emails()



function get_contacts_using_listids($binding, $listids)
{
  return get_contacts_inc_fields($binding, make_filter_for_multivalued_criteria("listId", $listids));
} // get_contacts_using_listids()



/*
 * Retrieve all contacts in the current subaccount that are not a member of any list.
 */
function get_listless_contacts($binding)
{
  // I wish I could just use a filter, where "lists" is empty, but that does not work.
  // So I retrieve all contacts, then eliminate the ones that have no lists.
  $all_contacts = get_all_contacts($binding);
  $contacts = array();
  foreach ($all_contacts as $contact) {
    if (!isset($contact->lists)) {
      $contacts[] = $contact;
    }
  }
  return $contacts;
} // get_listless_contacts()



function get_all_contacts($binding)
{
  return get_contacts_inc_fields($binding, null);
} // get_all_contacts()



function delete_contact($binding, $id)
{
  $deleteObj['type'] = "contact";
  $deleteObj['id'] = $id;
  $res = $binding->delete(array($deleteObj));
  $res = normalize_result($res);
  return $res[0]->success ? true : false;
} // delete_contact()



function get_fields($binding, $filter)
{
  try {
    $attr = array("label" => true, "type" => true, "display" => true,
                  "visibility" => true, "options" => true);
    $params = make_params($attr, $filter);
    $result = $binding->readFields($params);
    return normalize_result($result);
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  }
} // get_fields()



function get_fields_using_ids($binding, $ids)
{
  return get_fields($binding, make_filter_for_ids($ids));
} // get_fields_using_ids()



function get_all_fields($binding)
{
  return get_fields($binding, null);
} // get_all_fields()



/*
** For get_messages(), get_lists(), and get_segments(), the $ids parameter
** specifies an array of IDs to be matched.
** If null, then all available messages/lists/segments will be returned.  
** If empty, then no messages/lists/segments will be returned.
*/

function get_messages($binding, $ids, $bContent = false, $bPreviewUrl = false)
{
  try {
    $attr['status'] = true;
    $attr['content'] = $bContent;
    $attr['preview_url'] = $bPreviewUrl;
    $filter = make_filter_for_ids($ids);
    $params = make_params($attr, $filter);
    $result = $binding->readMessages($params);
    return normalize_result($result);
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  }
} // get_messages()



function get_message($binding, $id, $bContent = false, $bPreviewUrl = false)
{
  $msgs = get_messages($binding, $id, $bContent, $bPreviewUrl);
  if ($msgs) {
    return $msgs[0];
  }
  else {
    return null;
  }
} // get_message()



function get_all_messages($binding)
{
  return get_messages($binding, null, false, false);
} // get_all_messages()



function get_message_name($binding, $id)
{
  $messages = get_messages($binding, $id, false, false);
  if ($messages) {
    return $messages[0]->name;
  }
  else {
    return null;
  }
} // get_message_name()



function get_message_preview($binding, $id)
{
  $messages = get_messages($binding, $id, false, true);
  if ($messages) {
    return $messages[0]->preview_url;
  }
  else {
    return null;
  }
} // get_message_preview()



function get_lists($binding, $filter)
{
  try {
    $attr = array("label" => true, "activeCount" => true);
    $params = make_params($attr, $filter);
    $result = $binding->readLists($params);
    return normalize_result($result);
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  }
} // get_lists()



function get_lists_using_ids($binding, $ids)
{
  return get_lists($binding, make_filter_for_ids($ids));
} // get_lists_using_ids()



function get_lists_using_names($binding, $names)
{
  return get_lists($binding, make_filter_for_names($names));
} // get_lists_using_names()



function get_all_lists($binding)
{
  return get_lists($binding, null);
} // get_all_lists()



function get_list_names($binding, $ids)
{
  $lnames = array();
  $lists = get_lists_using_ids($binding, $ids);
  if ($lists) {
    foreach ($lists as $list) {
      $lnames[] = $list->name;
    }
  }
  return $lnames;
} // get_list_names()



function get_segments($binding, $filter)
{
  try {
    $attr = array();
    $params = make_params($attr, $filter);
    $result = $binding->readSegments($params);
    return normalize_result($result);
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return null;
  }
} // get_segments()



function get_segments_using_ids($binding, $ids)
{
  return get_segments($binding, make_filter_for_ids($ids));
} // get_segments_using_ids()



function get_all_segments($binding)
{
  return get_segments($binding, null);
} // get_all_segments()



function get_segment_names($binding, $ids)
{
  $snames = array();
  $segs = get_segments_using_ids($binding, $ids);
  if ($segs) {
    foreach ($segs as $seg) {
      $snames[] = $seg->name;
    }
  }
  return $snames;
} // get_segment_names()



function create_recip_array($recip_ids, $recip_type)
{
  $recips = array();
  foreach ($recip_ids as $recip_id) {
    $recip = array("type" => $recip_type, "id" => $recip_id);
    $recips[] = $recip;
  }
  return $recips;
} // create_recip_array()



function create_delivery($req_info)
{
  $delivery = array();
  $delivery['start'] = 'now';
  $delivery['messageId'] = $req_info['message_id'];
  
  $list_recips = create_recip_array($req_info['list_ids'], "list");
  $seg_recips = create_recip_array($req_info['seg_ids'], "segment");
  $cont_recips = create_recip_array($req_info['cont_ids'], "contact");
  $recips = array_merge($list_recips, $seg_recips, $cont_recips);

  $delivery['recipients'] = $recips;
  if (!empty($req_info['from_addr'])) {
    $delivery['fromEmail'] = $req_info['from_addr'];
  }
  if (!empty($req_info['from_name'])) {
    $delivery['fromName'] = $req_info['from_name'];
  }
  if (!empty($req_info['reply_addr'])) {
    $delivery['replyEmail'] = $req_info['reply_addr'];
  }
  return $delivery;
} // create_delivery()



function send_blast_email($binding, $req_info)
{
  $delivery = create_delivery($req_info);
  $handler = array();
  $handler['mode'] = "insert";
  $params = array("deliveries" => $delivery, "handler" => $handler);
  
  try {
    $res = $binding->writeDeliveries($params);
    $res = normalize_result($res);
    if (count($res) != 1) {
      echo "Expecting only one result for writeDeliveries(), since we sent only one delivery.";
    }
    $res = $res[0];
    if ($res->success) {
      return true;
    }
    else {
      print_errors($res->errors);
      return false;
    }
  }
  catch (SoapFault $ex) {
    print_exception($binding, $ex);
    return false;
  }
} // send_blast_email()

?>
