<?php
require_once '../include/common.inc.php';

echo("Starting...\n");

$login_result = bronto_agency_login();
$binding = $login_result['binding'];

$accounts = get_all_accounts($binding);

if ($accounts == null) {
  die("Unable to get accounts");
}

foreach ($accounts as $acct) {
  echo($acct->id.",".$acct->name."\n");
}

?>
