<?php
require_once '../include/common.inc.php';

define("FORMAT_XML", "xml");
define("FORMAT_CSV", "csv");

$display_lists = true;
$display_fields = true;
$display_contacts = false;
//$format = FORMAT_CSV;
$format = FORMAT_XML;

if ($format == FORMAT_XML) {
  echo "<bronto-data>\n";
}
else {
  echo "account_id,account_name,list_id,list_name,list_label,list_count\n";
}

$login_result = bronto_agency_login();
if (!$login_result) {
  die("Unable to log in to agency account");
}

$binding = $login_result['binding'];
$accounts = get_all_accounts($binding);

if ($accounts == null) {
  die("Unable to get accounts");
}

if ($format == FORMAT_XML) {
  echo "<accounts count=\"".count($accounts)."\">\n";
}

foreach ($accounts as $acct) {
  if ($format == FORMAT_XML) {
    echo "<account id=\"".$acct->id."\" name=\"".$acct->name."\" currContactCount=\"".$acct->currContactCount."\" ".
         "maxContactCount=\"".$acct->maxContactCount."\" monthEmailCount=\"".$acct->monthEmailCount."\" ".
         "currHostingSize=\"".$acct->currHostingSize."\" maxHostingSize=\"".$acct->maxHostingSize."\">\n";
  }
  
  // Now login to the subaccount using the agency account, but specifying the subaccount ID in the login.
  $login_result = bronto_agency_login($acct->id);
  if (!$login_result) {
    if ($format == FORMAT_XML) {
      echo "<error text=\"INVALID ACCOUNT\"/>\n";
    }
  }
  else {
    $binding = $login_result['binding'];
    
    if ($display_lists) {
      $lists = get_all_lists($binding);
    }
    else {
      $lists = null;
    }
    
    if ($lists) {
      if ($format == FORMAT_XML) {
        echo "<lists count=\"".count($lists)."\">\n";
      }
    	
      foreach ($lists as $list) {
        if ($format == FORMAT_XML) {
          echo "<list id=\"".$list->id."\" name=\"".$list->name."\" label=\"".$list->label."\" ".
               "activeCount=\"".$list->activeCount."\" />\n";
        }
        else {
          echo $acct->id.",".$acct->name.",".$list->id.",\"".$list->name."\",\"".$list->label."\",".$list->activeCount."\n";
        }
      }
      
      if ($format == FORMAT_XML) {
        echo "</lists>\n";
      }
    }
    
    if ($display_fields) {
      $fields = get_all_fields($binding);
    }
    else {
      $fields = null;
    }
    
    if ($fields) {
      if ($format == FORMAT_XML) {
        echo "<fields count=\"".count($fields)."\">\n";
      }
      
      foreach ($fields as $field) {
        if ($format == FORMAT_XML) {
          echo "<field id=\"".$field->id."\" name=\"".$field->name."\" label=\"".$field->label."\" ".
               "type=\"".$field->type."\" display=\"".$field->display."\" ".
               "visibility=\"".$field->visibility."\" />\n";
        }
        else {
          echo $acct->id.",".$acct->name.",".$field->id.",\"".$field->name."\",\"".$field->label."\",".
               $field->type.",".$field->display.",".$field->visibility."\n";
        }
      }
      
      if ($format == FORMAT_XML) {
        echo "</fields>\n";
      }
    }
    
    if ($display_contacts) {
      $contacts = get_all_contacts($binding);
    }
    else {
      $contacts = null;
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
              echo "<memberof id=\"$listid\" />\n";
            }
            echo "</memberships>\n";
          }
        }
        else {
          echo "<memberships count=\"0\" />\n";
        }
        echo "</contact>\n";
      }
      echo "</contacts>\n";
    }
  }
  
  if ($format == FORMAT_XML) {
    echo "</account>\n";
  }
  //break;
}

if ($format == FORMAT_XML) {
  echo "</accounts>\n";
  echo "</bronto-data>\n";
}
?>
