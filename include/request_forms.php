<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Web forms used for the Approval Request interface
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2010-02-09
*************************************************************************/

require_once 'defs.php';


function display_date_selector($p_year = null, $p_month = null, $p_day = null)
{
  $cur_year = get_current_year();
  $cur_month = get_current_month();
  $cur_day = get_current_day();
  
  $sel_year = ($p_year == null) ? $cur_year : $p_year;
  $sel_month = ($p_month == null) ? $cur_month : $p_month;
  $sel_day = ($p_day == null) ? $cur_day : $p_day;

  echo "<select name=\"fm_year\">\n";
  for ($year = $cur_year; $year <= $cur_year+10; $year++) {
    $selected = ($year == $sel_year) ? "selected" : "";
    echo "<option value=\"$year\" $selected>$year</option>\n";
  }
  echo "</select>\n";
  echo "<select name=\"fm_month\">\n";
  for ($month = 1; $month <= 12; $month++) {
    $selected = ($month == $sel_month) ? "selected" : "";
    $month = sprintf("%02d", $month);
    echo "<option value=\"$month\" $selected>$month</option>\n";
  }
  echo "</select>\n";
  echo "<select name=\"fm_day\">\n";
  for ($day = 1; $day <= 31; $day++) {
    $selected = ($day == $sel_day) ? "selected" : "";
    $day = sprintf("%02d", $day);
    echo "<option value=\"$day\" $selected>$day</option>\n";
  }
  echo "</select>\n";
} // display_date_selector()



function print_request_login_form($uname = null, $pass = null, $sitename = null)
{
  return print_login_form(ACTION_TYPE_REQUEST, null, $uname, $pass, $sitename);
} // print_request_login_form()



function print_agency_login_form($uname, $pass, $sitename, $siteid, $sessionid, $accounts)
{
?>
<h2><?php echo HEADER_REQUEST?> - Sub-account Selection</h2>
<p>
Please select the target sub-account.
</p>

<form method="post" action="<?php echo REQUEST_SCRIPT?>">
<input type="hidden" name="fm_stage" value="suauth"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $sessionid?>"/>
<input type="hidden" name="fm_username" value="<?php echo $uname?>"/>
<input type="hidden" name="fm_password" value="<?php echo $pass?>"/>
<input type="hidden" name="fm_sitename" value="<?php echo $sitename?>"/>
Agency Username: <b><?php echo $uname?></b>
<br/><br/>
Agency Sitename: <b><?php echo $sitename?></b>
<br/><br/>
Subaccount: <select name="fm_siteid">
<option value="">Please select a sub-account...</option>
<?php
foreach ($accounts as $acct) {
  $sel_flag = ($acct->id == $siteid) ? "selected" : "";
  echo "<option value=\"$acct->id\" $sel_flag>$acct->name</option>\n";
}
?>
</select>
<br/><br/>
<input type="submit" value="Login"/>
&nbsp;&nbsp;
<input type="button" value="Cancel" onclick="window.location='<?php echo REQUEST_SCRIPT?>'"/>
</form>

<script>
document.forms[0].fm_password.focus();
</script>


<?php
} // print_agency_login_form()



function print_message_select_form($bapi, $session_id, $msg_id = null, $list_ids = null, $seg_ids = null,
                                   $year = null, $month = null, $day = null,
                                   $from_addr = null, $from_name = null, $reply_addr = null)
{
  // Use the API to retrieve messages, lists, and segments for client.
  if (!$bapi) {
    return false;
  }

  // The next 3 API calls will fail on the Agency account itself.  $bapi must reference a subaccount.
  $messages = get_all_messages($bapi);
  $lists = get_all_lists($bapi);
  $segments = get_all_segments($bapi);
?>
<h2><?php echo HEADER_REQUEST?> - Message Selection</h2>

<form method="post" action="<?php echo REQUEST_SCRIPT?>">
<input type="hidden" name="fm_stage" value="select"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $session_id?>"/>
<p>
<label class="required">Please select the message to be approved:</label>
</p>

<?php
if (count($messages) > 0) {
?>
<select name="fm_msgid">
<option value="">Please select a message...</option>
<?php
foreach ($messages as $msg) {
  $sel_flag = ($msg->id == $msg_id) ? "selected" : "";
  echo "<option value=\"$msg->id\" $sel_flag>$msg->name</option>\n";
}
?>
</select>
<?php
}
else {
  echo "<div class=\"warning\">There are no messages to select.</div>";
}
?>

<p>
<label class="required">Please select the list(s) and/or segment(s) to be targeted:</label>
</p>

<div id="list_seg_2_cols">
<?php
echo "<div id=\"list_select\">\n<p><u>Lists</u></p>\n";
if (count($lists) > 0) {
  foreach ($lists as $list) {
    $chk_flag = ($list_ids && in_array($list->id, $list_ids)) ? "checked" : "";
    echo "<input type=\"checkbox\" name=\"fm_listids[]\" value=\"$list->id\" $chk_flag/>$list->name &nbsp;[$list->activeCount]<br/>\n";
  }
}
else {
  echo "<div class=\"warning\">There are no lists to select.</div>\n";
}

echo "\n</div>\n<div id=\"segment_select\">\n<p><u>Segments</u></p>\n";
if (count($segments) > 0) {
  foreach ($segments as $segment) {
    $chk_flag = ($seg_ids && in_array($segment->id, $seg_ids)) ? "checked" : "";
    echo "<input type=\"checkbox\" name=\"fm_segids[]\" value=\"$segment->id\" $chk_flag/>$segment->name<br/>\n";
  }
}
else {
  echo "<div class=\"warning\">There are no segments to select.</div>\n";
}
?>
</div>

<div style="clear: both"></div>


<p>
Please enter the date on which the message should be sent:
</p>

<?php
display_date_selector($year, $month, $day);
?>

<p>
Please enter message header information:
</p>

<p>
<label class="required">From Address:</label>
<input type="text" name="fm_fromaddr" value="<?php echo $from_addr?>"/>
&nbsp;&nbsp;
<label class="required">From Full Name:</label>
<input type="text" name="fm_fromname" value="<?php echo $from_name?>"/>
&nbsp;&nbsp;
<label class="optional">Reply Address:</label>
<input type="text" name="fm_replyaddr" value="<?php echo $reply_addr?>"/>
</p>

<p>
<input type="submit" value="Submit"/>
&nbsp;&nbsp;
<input type="button" value="Cancel" onclick="window.location='<?php echo REQUEST_SCRIPT?>'"/>
</p>
</form>

<?php
  return true;
} // print_message_select_form()



function print_confirm_form($bapi, $session_id, $msg_id, $list_ids, $seg_ids, $year, $month, $day,
                            $from_addr, $from_name, $reply_addr, $is_ccuser, $user_email, $cc_email)
{
  // Use API to retrieve messages, lists, and segments for client.
  if (!$bapi) {
    return false;
  }

  if (empty($list_ids)) {
    $list_ids = array();
  }

  if (empty($seg_ids)) {
    $seg_ids = array();
  }

  /**** no need to store CC address in cookie any longer, since the user's e-mail address is now stored in the DB.
  if ($cc_email == null && !empty($_COOKIE['email'])) {
    $cc_email = $_COOKIE['email'];
  }
  *****/
  
  $list_ids_str = implode(",", $list_ids);
  $seg_ids_str = implode(",", $seg_ids);
  $msg_name = get_message_name($bapi, $msg_id);
  $msg_preview = get_message_preview($bapi, $msg_id);
?>
<h2><?php echo HEADER_REQUEST?> - Confirmation</h2>

<iframe id="message_preview" src="<?php echo $msg_preview?>"></iframe>

<form method="post" action="<?php echo REQUEST_SCRIPT?>">
<input type="hidden" name="fm_stage" value="confirm"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $session_id?>"/>
<input type="hidden" name="fm_msgid" value="<?php echo $msg_id?>"/>
<?php
foreach ($list_ids as $id) {
  echo "<input type=\"hidden\" name=\"fm_listids[]\" value=\"$id\"/>\n";
}
foreach ($seg_ids as $id) {
  echo "<input type=\"hidden\" name=\"fm_segids[]\" value=\"$id\"/>\n";
}

?>
<!-- old way was comma-separated list
<input type="hidden" name="fm_listids[]" value="<?php echo $list_ids_str?>"/>
<input type="hidden" name="fm_segids[]" value="<?php echo $seg_ids_str?>"/>
-->
<input type="hidden" name="fm_year" value="<?php echo $year?>"/>
<input type="hidden" name="fm_month" value="<?php echo $month?>"/>
<input type="hidden" name="fm_day" value="<?php echo $day?>"/>
<input type="hidden" name="fm_fromaddr" value="<?php echo $from_addr?>"/>
<input type="hidden" name="fm_fromname" value="<?php echo $from_name?>"/>
<input type="hidden" name="fm_replyaddr" value="<?php echo $reply_addr?>"/>

<p>
Message to be approved:
</p>

<p>
<b><?php echo $msg_name?></b>
</p>


<?php
  print_nonmessage_info($bapi, $list_ids, $seg_ids, "$year-$month-$day", $from_addr, $from_name, $reply_addr);
  $chk_flag = ($is_ccuser == true) ? "checked" : "";
?>

<p>
<input type="checkbox" name="fm_iscc" value="1" <?php echo $chk_flag?>/>
Send copy of request e-mail to me (<?php echo $user_email?>)
</p>
<p>
Send an (optional) additional copy to the address below:
<br/><input type="text" size="40" name="fm_ccemail" value="<?php echo $cc_email?>"/>
</p>

<input type="submit" value="Verify Content"/>
&nbsp;&nbsp;
<input type="button" value="Go Back" onclick="history.go(-1)"/>
</form>
<div style="clear:both"></div>


<?php
} // print_confirm_form()



function print_verify_form($session_id, $msg_id, $list_ids, $seg_ids, $year, $month, $day,
                           $district, $from_addr, $from_name, $reply_addr, $is_ccuser, $cc_email,
                           $notes = null, $initials = null)
{
?>
<script language="JavaScript">
function confirmCancel() {
    rc = confirm("You are about to cancel your Approval Request.  If you do so, all information about this "+
			     "request will be lost.\n\nAre you sure you sure you wish to continue?");
    if (rc) {
        window.location = '<?php echo REQUEST_SCRIPT?>';
    }
}
</script>
<h2><?php echo HEADER_REQUEST?> - Verification</h2>

<form method="post" action="<?php echo REQUEST_SCRIPT?>">
<input type="hidden" name="fm_stage" value="verify"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $session_id?>"/>
<input type="hidden" name="fm_msgid" value="<?php echo $msg_id?>"/>
<?php
foreach ($list_ids as $id) {
  echo "<input type=\"hidden\" name=\"fm_listids[]\" value=\"$id\"/>\n";
}
foreach ($seg_ids as $id) {
  echo "<input type=\"hidden\" name=\"fm_segids[]\" value=\"$id\"/>\n";
}

$senators = db_get_all_senators();

?>
<input type="hidden" name="fm_year" value="<?php echo $year?>"/>
<input type="hidden" name="fm_month" value="<?php echo $month?>"/>
<input type="hidden" name="fm_day" value="<?php echo $day?>"/>
<input type="hidden" name="fm_fromaddr" value="<?php echo $from_addr?>"/>
<input type="hidden" name="fm_fromname" value="<?php echo $from_name?>"/>
<input type="hidden" name="fm_replyaddr" value="<?php echo $reply_addr?>"/>
<input type="hidden" name="fm_iscc" value="<?php echo $is_ccuser?>"/>
<input type="hidden" name="fm_ccemail" value="<?php echo $cc_email?>"/>

<p>
You are about to send an Approval Request for the selected blast e-mail to an authorized Senate reviewer.
</p>
<p>
By clicking the Request Approval button below, you are confirming that, to the best of your knowledge,
your message meets Senate guidelines and is appropriate for mass distribution.  In addition, you are confirming
that your message meets the following criteria:
</p>
<ul>
<li>You have received permission for the use of the contents of this e-mail,
including published material, images, and logos.</li> 
<li>You have received permission from the requesting office to send this
e-mail to the specified recipients.</li>
</ul>
<p>In order to verify that you have reviewed the criteria listed above and acknowledge that you are in compliance,
please enter your initials below:
</p>
<p>
<label class="required">Your initials:</label>
<input type="text" size="3" maxlength="3" name="fm_initials" value="<?php echo $initials?>"/>
&nbsp;&nbsp;
On behalf of Senator
<select name="fm_district">
<option value="">Select a Senator</option>
<?php 
foreach ($senators as $senator) {
  $dist = $senator['district'];
  $lname = $senator['lastname'];
  $fname = $senator['firstname'];
  $selected = ($dist == $district) ? "selected" : "";
  echo "<option value=\"$dist\" $selected>$lname, $fname</option>\n";
}
?>
</select>
</p>
<p>
In the text box below, you can record optional notes regarding this request:
</p>

<p>
<textarea name="fm_notes" rows="8" cols="80"><?php echo $notes?></textarea>
</p>
<p>
<input type="submit" value="Request Approval"/>
&nbsp;&nbsp;
<input type="button" value="Cancel" onClick="confirmCancel()"/>
</p>
</form>

<?php  
} // print_verify_form()



function print_send_form($reqinfo)
{
  $req_id = $reqinfo['uuid'];
  $msg_name = $reqinfo['message_name'];
  $url_str = get_review_url($req_id);
?>
<h2><?php echo HEADER_REQUEST?> - Send Request</h2>

<form method="post" action="<?php echo REQUEST_SCRIPT?>">
<input type="hidden" name="fm_stage" value="start"/>
<input type="hidden" name="fm_requestid" value="<?php echo $req_id?>"/>

<p>
Thank you for submitting your request to approve a blast e-mail.
</p>

<p>
Your request ID is <b><?php echo $req_id?></b>
</p>

<p>
Your request to send the message <b><?php echo $msg_name?></b> has been forwarded to an authorized
Senate reviewer via e-mail.
</p>

<p>
If you wish to send the request to another authorized reviewer, you can copy and paste the following link
into an e-mail message and send it to the appropriate reviewer for approval:
</p>

<p>
<a href="<?php echo $url_str?>"><?php echo $url_str?></a>
</p>

<input type="submit" value="Start Over"/>
</form>

<?php
} // print_send_form()
?>