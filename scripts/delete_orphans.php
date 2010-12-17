<?php
/**************************************************************************
* delete_orphans.php
* 
* Organization: New York State Senate
* Department: Office of the CIO
* Author: Ken Zalewski
* Created: 2010-05-17
* Last Revision: 2010-05-20
* 
* Description: Given a list of e-mail addresses (contacts), remove each
*              contact from all Bronto subaccounts for which that contact
*              is not a member of any lists (ie. is an orphan).
**************************************************************************/

require_once '../include/common.inc.php';

$dry_run = false;
$addr_file = null;
$acct_name = null;

for ($i = 1; $i < $argc; $i++) {
  $arg = $argv[$i];
  if ($arg == "--dry-run" || $arg == "-n") {
    $dry_run = true;
  }
  else if ($arg == "--account" || $arg == "-a") {
    $acct_name = $argv[++$i];
  }
  else if ($arg == "--help" || $arg == "-h") {
    echo "Usage: $argv[0] [--account name] [--dry-run] address_file\n";
    exit(0);
  }
  else if (substr($arg, 0, 1) == "-") {
    echo "Error: $arg: Invalid option\n";
    exit(1);
  }
  else {
    $addr_file = $arg;
  }
}

if (!$addr_file) {
  echo "Error: Must specify the address file\n";
  exit(1);
}
else if (!file_exists($addr_file)) {
  echo "Error: $addr_file: File not found\n";
  exit(1);
}

$addrs = file($addr_file, FILE_IGNORE_NEW_LINES);
if ($addrs === false) {
  echo "Error: $addr_file: Unable to read file\n";
  exit(1);
}

// Convert all e-mail addresses to lower-case for easier searching later.
$addrs = array_map('strtolower', $addrs);

$login_result = bronto_agency_login();
if (!$login_result) {
  echo "Unable to log in to agency account";
  exit(1);
}

$binding = $login_result['binding'];

if ($acct_name) {
  $accounts = get_accounts_using_names($binding, $acct_name);
}
else {
  $accounts = get_all_accounts($binding);
}

if ($accounts == null) {
  echo "Unable to retrieve any matching accounts\n";
  exit(1);
}

foreach ($accounts as $acct) {
  // Now login to the subaccount using the agency account, but specifying the subaccount ID in the login.
  echo "===\nChecking account: $acct->name [id=$acct->id]\n===\n";
  
  $login_result = bronto_agency_login($acct->id);
  if (!$login_result) {
    echo "Error: Account id=".$acct->id." [".$acct->name."] is not a valid subaccount.\n";
  }
  else {
    $orphan_count = 0;
    $matched_count = 0;
    $deleted_count = 0;
    $binding = $login_result['binding'];
    $contacts = get_listless_contacts($binding);
    foreach ($contacts as $contact) {
      $email = strtolower($contact->email);
      echo "ORPHAN: $email [id=$contact->id]\n";
      $orphan_count++;
      if (in_array($email, $addrs)) {
        echo "MATCHED: $email [id=$contact->id]\n";
        $matched_count++;
        if (!$dry_run) {
          if (delete_contact($binding, $contact->id)) {
            echo "DELETED: $email [id=$contact->id]\n";
            $deleted_count++;
          }
          else {
            echo "Error: Unable to delete $email [id=$contact->id]\n";
          }
        }
      }
      else {
        echo "IGNORED: $email [id=$contact->id]\n";
      }
    }
    echo "Finished account: $acct->name [id=$acct->id]\n";
    echo "Stats for account $acct->name: orphaned=$orphan_count, matched=$matched_count, deleted=$deleted_count\n";
  }
}
?>
