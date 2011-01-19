<?php
require_once '../include/common.inc.php';

$prog = basename(__FILE__);

$disp_lists = true;
$disp_fields = true;
$disp_contacts = true;
$disp_contact_fields = true;
$disp_messages = true;
$disp_deliveries = true;

for ($i = 1; $i < $argc; $i++) {
  $opt = $argv[$i];
  switch ($opt) {
    case '--no-lists': $disp_lists = false; break;
    case '--no-fields': $disp_fields = false; break;
    case '--no-contacts': $disp_contacts = false; break;
    case '--no-contact-fields': $disp_contact_fields = false; break;
    case '--no-messages': $disp_messages = false; break;
    case '--no-deliveries': $disp_deliveries = false; break;
    default: echo "Usage: $prog [--no-lists] [--no-fields] [--no-contacts] [--no-deliveries]\n"; exit(1);
  }
}


echo "<bronto-data>\n";

$login_result = bronto_agency_login();
if (!$login_result) {
  die("Unable to log in to agency account");
}

$binding = $login_result['binding'];
$accounts = get_all_accounts($binding);

if ($accounts == null) {
  die("Unable to get accounts");
}

echo "<accounts count=\"".count($accounts)."\">\n";

foreach ($accounts as $acct) {
  echo "<account id=\"".$acct->id."\" name=\"".$acct->name."\" currContactCount=\"".$acct->currContactCount."\" ".
       "maxContactCount=\"".$acct->maxContactCount."\" monthEmailCount=\"".$acct->monthEmailCount."\" ".
       "currHostingSize=\"".$acct->currHostingSize."\" maxHostingSize=\"".$acct->maxHostingSize."\">\n";
  
  // Now login to the subaccount using the agency account, but specifying the subaccount ID in the login.
  $login_result = bronto_agency_login($acct->id);
  if (!$login_result) {
    echo "<error text=\"INVALID ACCOUNT\"/>\n";
  }
  else {
    $binding = $login_result['binding'];
    
    if ($disp_lists) {
      display_lists($binding);
    }

    if ($disp_fields) {
      display_fields($binding);
    }

    if ($disp_contacts) {
      display_contacts($binding, $disp_contact_fields);
    }
    
    if ($disp_messages) {
      display_messages($binding);
    }

    if ($disp_deliveries) {
      display_deliveries($binding);
    }
  }
  
  echo "</account>\n";
  //break;
}

echo "</accounts>\n";
echo "</bronto-data>\n";



function display_lists($binding)
{
  $lists = get_all_lists($binding);

  if ($lists) {
    echo "<lists count=\"".count($lists)."\">\n";

    foreach ($lists as $list) {
      echo "<list id=\"".$list->id."\" name=\"".$list->name."\" label=\"".$list->label."\" ".
           "activeCount=\"".$list->activeCount."\" />\n";
    }
      
    echo "</lists>\n";
  }
  else {
    echo "<lists count=\"0\"/>\n";
  }
} // display_lists()



function display_fields($binding)
{
  $fields = get_all_fields($binding);

  if ($fields) {
    echo "<fields count=\"".count($fields)."\">\n";

    foreach ($fields as $field) {
      echo "<field id=\"".$field->id."\" name=\"".$field->name."\" label=\"".$field->label."\" ".
           "type=\"".$field->type."\" display=\"".$field->display."\" ".
           "visibility=\"".$field->visibility."\" />\n";
    }
      
    echo "</fields>\n";
  }
  else {
    echo "<fields count=\"0\"/>\n";
  }
} // display_fields()



function display_contacts($binding, $disp_fields)
{
  if ($disp_fields) {
    $contacts = get_all_contacts($binding);
  }
  else {
    $contacts = get_all_contacts_no_fields($binding);
  }

  if ($contacts) {
    echo "<contacts count=\"".count($contacts)."\">\n";
      
    foreach ($contacts as $contact) {
      echo "<contact id=\"".$contact->id."\" email=\"".$contact->email."\" status=\"".$contact->status."\" msgPref=\"".$contact->msgPref."\" ".
           "source=\"".$contact->source."\" customSource=\"".$contact->customSource."\" ".
           "created=\"".$contact->created."\" modified=\"".$contact->modified."\">\n";
      if (isset($contact->lists)) {
        $listids = make_array($contact->lists);
        if ($listids) {
          echo "<memberships count=\"".count($listids)."\">\n";
          foreach ($listids as $listid) {
            echo "<memberof id=\"$listid\"/>\n";
          }
          echo "</memberships>\n";
        }
      }
      else {
        echo "<memberships count=\"0\"/>\n";
      }

      if (isset($contact->fields)) {
        $fields = make_array($contact->fields);
        echo "<fieldvals count=\"".count($fields)."\">\n";
        foreach ($fields as $field) {
          echo "<fieldval id=\"".$field->fieldId."\">".$field->value."</fieldval>\n";
        }
        echo "</fieldvals>\n";
      }
      else {
        echo "<fieldvals count=\"0\"/>\n";
      }

      echo "</contact>\n";
    }

    echo "</contacts>\n";
  }
  else {
    echo "<contacts count=\"0\"/>\n";
  }
} // display_contacts()



function display_messages($binding)
{
  $messages = get_all_messages($binding);

  if ($messages) {
    echo "<messages count=\"".count($messages)."\">\n";

    foreach ($messages as $message) {
      echo "<message id=\"".$message->id."\" name=\"".$message->name."\" status=\"".$message->status."\"/>\n";
    }

    echo "</messages>\n";
  }
  else {
    echo "<messages count=\"0\"/>\n";
  }
} // display_messages()



function display_deliveries($binding)
{
  $deliveries = get_all_deliveries($binding);

  if ($deliveries) {
    echo "<deliveries count=\"".count($deliveries)."\">\n";

    foreach ($deliveries as $delivery) {
      echo "<delivery id=\"".$delivery->id."\" start=\"".$delivery->start."\" messageId=\"".$delivery->messageId."\" status=\"".$delivery->status."\">\n";
      if (isset($delivery->recipients)) {
        $recipients = make_array($delivery->recipients);
        echo "<recipients count=\"".count($recipients)."\">\n";
        foreach ($recipients as $recipient) {
          echo "<recipient type=\"".$recipient->type."\" id=\"".$recipient->id."\"/>\n";
        }
        echo "</recipients>\n";
      }
      else {
        echo "<recipients count=\"0\"/>\n";
      }

      echo "</delivery>\n";
    }

    echo "</deliveries>\n";
  }
  else {
    echo "<deliveries count=\"0\"/>\n";
  }
} // display_deliveries()

?>
