<?php
require_once '../include/bronto_funcs.php';

$bapi = bronto_login("0bba03f2000000000000000000000000310d");

$deliveries = $handler = array();

$deliveries['start'] = 'now';
$deliveries['messageId'] = "0bba03eb000000000000000000000006da48";

$recips = array(
  array('type' => 'contact', 'id' => "0bba03e80000000000000000000004a9cb9a"),
  array('type' => 'contact', 'id' => "0bba03e80000000000000000000004a9cb98")
);

$deliveries['recipients'] = $recips;
$deliveries['fromEmail'] = 'zalewski@nysenate.gov';
$deliveries['fromName'] = 'Ken Zalewski';
$deliveries['replyEmail'] = 'zalewski@senate.state.ny.us';

$handler['mode'] = 'insert';

$params = array('deliveries' => $deliveries, 'handler' => $handler);
$result = $bapi->writeDeliveries($params);

print_r($result);

//0bba03e80000000000000000000004a9cb98,b.krista@gmail.com
//0bba03e80000000000000000000004a9cb9a,zalewski@senate.state.ny.us


?>
