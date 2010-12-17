<?php
require_once '../include/common.inc.php';

echo("Starting...\n");

$binding = bronto_login("0bba03f2000000000000000000000000310d");

$contacts = get_contacts($binding);

if ($contacts == null) {
  die("Unable to get contacts");
}

foreach ($contacts as $contact) {
  echo($contact->id.",".$contact->email."\n");
}

?>
