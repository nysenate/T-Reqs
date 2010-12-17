<?php

try {
  echo "<br/>Opening DB connection";
  $dsn = "mysql:host=localhost;dbname=bronto";
  $dbh = new PDO($dsn, "brontoadmin", "nyss2009");
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "<br/>Querying";
  $sth = $dbh->query("select * from user");
  echo "<br/>Fetching";
  $res = $sth->fetchAll(PDO::FETCH_ASSOC);
  echo "<br/>Result: ";
  print_r($res);
}
catch (PDOException $ex) {
  echo "<br/>PDO Error: ".$ex->getMessage();
}
?>
