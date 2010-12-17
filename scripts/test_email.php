<?php
require_once '../include/funcs.php';

$prog = $argv[0];

if ($argc < 2) {
  fwrite(STDERR, "Usage: $prog <options>");
  exit(1);
}
  
$fromaddr = $toaddr = "";
$fromname = "PHP Mailer";
$toname = "Test Recipient";
$subject = "Test message for PHPMailer module.";
$msgtext = "This is a test message.  It can be safely ignored.";
$ccaddrs = array();

for ($i = 1; $i < $argc; $i++) {
  $arg = $argv[$i];
  switch ($arg) {
    case "-f": case "--from":
      $fromaddr = $argv[++$i];
      break;
    case "-F": case "--fromname":
      $fromname = $argv[++$i];
      break;
    case "-t": case "--to":
      $toaddr = $argv[++$i];
      break;
    case "-T": case "--toname":
      $toname = $argv[++$i];
      break;
    case "-c": case "--cc":
      $ccaddrs[] = $argv[++$i];
      break;
    case "-s": case "--subject":
      $subject = $argv[++$i];
      break;
    default:
      if ($arg[0] == '-') {
        fwrite(STDERR, "$prog: $arg: Invalid option");
        exit(1);
      }
      else {
        $msgtext .= $arg;
      }
  }
}

if (empty($fromaddr) || empty($toaddr)) {
  fwrite(STDERR, "$prog: Both --from and --to must be specified");
  exit(1);
}

if (is_valid_email($fromaddr) == false) {
  fwrite(STDERR, "$prog: $fromaddr: Address (From:) not valid");
  exit(1);
}

if (is_valid_email($toaddr) == false) {
  fwrite(STDERR, "$prog: $toaddr: Address (To:) not valid");
  exit(1);
}

if (send_email_message($fromaddr, $fromname, $toaddr, $toname, $ccaddrs, $subject, $msgtext) == true) {
  echo "Message sent.";
}
else {
  echo "Message could not be sent.";
}
?>
