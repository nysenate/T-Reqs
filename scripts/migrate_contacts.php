<?php
/**************************************************************************
* migrate_contacts.php
* 
* Organization: New York State Senate
* Department: Office of the CIO
* Author: Ken Zalewski
* Created: 2010-05-22
* Last Revision: 2010-05-23
* 
* Description: Move/Copy all contacts from one Bronto list into another.
**************************************************************************/

require_once '../include/common.inc.php';

$prog = $argv[0];
$dry_run = false;
$delete_source = false;
$use_ids = false;
$src_acct = "";
$src_list = "";
$dest_acct = "";
$dest_list = "";

for ($i = 1; $i < $argc; $i++) {
  $arg = $argv[$i];
  if ($arg == "--dry-run" || $arg == "-n") {
    $dry_run = true;
  }
  else if ($arg == "--delete-source" || $arg == "-d") {
    $delete_source = true;
  }
  else if ($arg == "--use-ids" || $arg == "-i") {
    $use_ids = true;
  }
  else if ($arg == "--help" || $arg == "-h") {
    echo "Usage: $prog [--dry-run] [--delete-source] src_account src_list dest_account dest_list\n";
    exit(0);
  }
  else if (substr($arg, 0, 1) == "-") {
    echo "$prog: $arg: Invalid option\n";
    exit(1);
  }
  else if (!$src_acct) {
    $src_acct = $arg;
  }
  else if (!$src_list) {
    $src_list = $arg;
  }
  else if (!$dest_acct) {
    $dest_acct = $arg;
  }
  else if (!$dest_list) {
    $dest_list = $arg;
  }
  else {
    echo "$prog: $arg: Too many arguments\n";
    exit(1);
  }
}

if (!$src_acct || !$src_list || !$dest_acct || !$dest_list) {
  echo "$prog: Must provide all source and destination account/list information\n";
  exit(1);
}

// Must convert account names to account IDs
if (!$use_ids) {
  $login_result = bronto_agency_login();
  if (!$login_result) {
    echo "Error: Unable to log in to agency account\n";
    exit(1);
  } 
  $binding = $login_result['binding'];
  $accounts = get_accounts_using_names($binding, array($src_acct, $dest_acct));
  if (count($accounts) != 2) {
    echo "Error: Unable to map both account names to account IDs\n";
    exit(1);
  }
  $src_acct = $accounts[0]->id;
  $dest_acct = $accounts[1]->id;
}

echo "Logging in to account id=$src_acct\n";

$login_result = bronto_agency_login($src_acct);
if (!$login_result) {
  echo "Error: Unable to login to source account id=$src_acct\n";
  exit(1);
}

$binding = $login_result['binding'];

if (!$use_ids) {
  $lists = get_lists_using_names($binding, $src_list);
  if (!$lists) {
    echo "Error: Unable to map source list name [$src_list] to list ID\n";
    exit(1);
  }
  else if (count($lists) > 1) {
    echo "Error: More than one list matched source list name [$src_list]\n";
    exit(1);
  }
  $src_list = $lists[0]->id;
}

echo "Retrieving fields for source account id=$src_acct\n";
$src_fields = get_all_fields($binding);
echo "Got ".count($src_fields)." fields for this account\n";

// Create extra arrays to index the fields by fieldID and fieldName
$src_field_ids = array();
$src_field_names = array();
foreach ($src_fields as $fld) {
  $src_field_ids[$fld->id] = $fld;
  $src_field_names[$fld->name] = $fld;
}

echo "Retrieving contacts for list id=$src_list\n";
$contacts = get_contacts_using_listids($binding, $src_list);
print_r($contacts);
echo "Ready to migrate ".count($contacts)." contacts from acct:list = $src_acct:$src_list\n";

$login_result = bronto_agency_login($dest_acct);
if (!$login_result) {
  echo "Error: Unable to login to destination account id=$dest_acct\n";
  exit(1);
}

$binding = $login_result['binding'];

if (!$use_ids) {
  $lists = get_lists_using_names($binding, $dest_list);
  if (!$lists) {
    echo "Error: Unable to map destination list name [$dest_list] to list ID\n";
    exit(1);
  }
  else if (count($lists) > 1) {
    echo "Error: More than one list matched destination list name [$dest_list]\n";
    exit(1);
  }
  $dest_list = $lists[0]->id;
}

echo "Retrieving fields for destination account id=$dest_acct\n";
$dest_fields = get_all_fields($binding);
echo "Got ".count($dest_fields)." fields for this account\n";

// Create extra arrays to index the fields by fieldID and fieldName
$dest_field_ids = array();
$dest_field_names = array();
foreach ($dest_fields as $fld) {
  $dest_field_ids[$fld->id] = $fld;
  $dest_field_names[$fld->name] = $fld;
}

?>
