<?php
define("LDAP_SERVER", "webmail.senate.state.ny.us");

echo "Connecting to ".LDAP_SERVER."\n";
$ds = ldap_connect(LDAP_SERVER);
ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

if ($ds) {
  echo "Binding...\n";
  $r = ldap_bind($ds);
  if ($r == true) {
    echo "Searching...\n";
    $sr = ldap_search($ds, "o=senate", "sn=Z*");
    print_r($sr);
    $info = ldap_get_entries($ds, $sr);
    print_r($info);
  }
  else {
    echo "Unable to bind to LDAP server\n";
  }
  echo "Closing...\n";
  ldap_close($ds);
}
else {
  echo "Unable to connect to LDAP server\n";
}

echo "Done.\n";
?>
