<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Web forms used for the Message Review interface
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2009-12-08
*************************************************************************/

require_once 'defs.php';


function print_review_login_form($uname = null, $pass = null, $req_id = null)
{
  return print_login_form(ACTION_TYPE_REVIEW, $req_id, $uname, $pass);
} // print_review_login_form()



function print_requestid_form($reqid = "")
{
?>
<h2><?php echo HEADER_REVIEW?> - Request ID entry</h2>

<form method="post" action="<?php echo REVIEW_SCRIPT?>">
<input type="hidden" name="fm_stage" value="reqid"/>
<p>
Please enter the ID of the Approval Request below:
</p>
<p>
<label class="required">Request ID:</label>
<input type="text" name="fm_requestid" size="25" value="<?php echo $reqid?>"/>
</p>
<p>
<input type="submit" value="Submit"/>
</p>
</form>
<script>
document.forms[0].fm_requestid.focus();
</script>

<?php
} // print_requestid_form()



function print_message_review_form($bapi, $session_id, $reqinfo)
{
  $req_id = $reqinfo['uuid'];
  $account_id = $reqinfo['account_id'];
  $msg_id = $reqinfo['message_id'];
  $list_ids = $reqinfo['list_ids'];
  $seg_ids = $reqinfo['seg_ids'];
  $from_addr = $reqinfo['from_addr'];
  $from_name = $reqinfo['from_name'];
  $reply_addr = $reqinfo['reply_addr'];
  $delivery_date = $reqinfo['delivery_date'];

  $message = get_message($bapi, $msg_id, true, true);
  //error_log($bapi->__getLastResponse(), 3, REQUEST_DIR."/soap_response");
  $msg_name = $message->name;
  $msg_contents = $message->content;
  $msg_preview_url = $message->preview_url;
?>
<h2><?php echo HEADER_REVIEW?> - Message Display and Approval</h2>

<iframe id="message_preview" src="<?php echo $msg_preview_url?>"></iframe>

<?php 
$msg_preview_url = urlencode($msg_preview_url);
print '<a href="#" onclick="open_win(\''.$msg_preview_url.'\')">Click here to see all the links in the message</a>';
print '<br />';
?>

<form method="post" action="<?php echo REVIEW_SCRIPT?>">
<input type="hidden" name="fm_stage" value="reviewed"/>
<input type="hidden" name="fm_substage" value="<?php echo VERIFY_TYPE_APPROVE?>"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $session_id?>"/>
<input type="hidden" name="fm_requestid" value="<?php echo $req_id?>"/>
<input type="hidden" name="fm_msgid" value="<?php echo $msg_id?>"/>

<p>
Please review the message itself on the right, and the message information below.  Then select Approve to send
the message out to the intended recipient(s), or Reject to defer sending.
</p>

<p>
Message Name: <b><?php echo $msg_name?></b>
</p>

<?php
  /****
  foreach ($msg_contents as $msg_content) {
    $msg_type = $msg_content->type;
    $msg_subject = $msg_content->subject;
    $msg_data = $msg_content->content;

    echo("<div class=\"message_metadata\">\n".
         "<p>Message as <i>$msg_type</i>, with Subject ".
         "[<b>$msg_subject</b>]</p>\n".
         "</div>\n".
         "<div class=\"message\">\n$msg_data\n</div>\n");
    //error_log($msg_data, 3, REQUEST_DIR."/error_log");
  }
  ****/

  print_nonmessage_info($bapi, $list_ids, $seg_ids, $delivery_date, $from_addr, $from_name, $reply_addr);
  include ('./include/display_onbehalfof.php');//ab
?>

  <input type="button" value="Approve" onClick="document.forms[0].fm_substage.value='<?php echo VERIFY_TYPE_APPROVE?>'; submit()"/>
  &nbsp;&nbsp;
  <input type="button" value="Reject" onClick="document.forms[0].fm_substage.value='<?php echo VERIFY_TYPE_REJECT?>'; submit()"/>
  &nbsp;&nbsp;
  <input type="button" value="Cancel" onClick="window.location='<?php echo REVIEW_SCRIPT?>'"/>
  &nbsp;&nbsp;
  <input type="button" value="ReAssign" onClick="document.forms[0].fm_stage.value='reassign'; submit()"/>
</form>

<div style="clear:both"></div>
<?php
} // print_message_review_form()



function print_verify_form($verify_type, $session_id, $req_id, $notes = null, $initials = null)
{
  if ($verify_type == VERIFY_TYPE_APPROVE) {
    $header_str = "Approval";
    $stage = "send";
    $submit = "Send";
  }
  else if ($verify_type == VERIFY_TYPE_REJECT) {
    $header_str = "Rejection";
    $stage = "reject";
    $submit = "Reject";
  }
?>
<h2><?php echo HEADER_REVIEW?> - Message <?php echo $header_str?> Verification</h2>

<form method="post" action="<?php echo REVIEW_SCRIPT?>">
<input type="hidden" name="fm_stage" value="<?php echo $stage?>"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $session_id?>"/>
<input type="hidden" name="fm_requestid" value="<?php echo $req_id?>"/>

<?php
  if ($verify_type == VERIFY_TYPE_APPROVE) {
?>
<p>
You are about to send a blast e-mail on behalf of a New York State Senator.  Please be
certain that the e-mail meets all Senate guidelines.
</p>
<p>
By clicking the Send button below, you are confirming that you have reviewed the message in its
entirety and grant authorization for its transmission.  The message will be scheduled for delivery and cannot be
intercepted.
</p>
<p>In order to verify that you have reviewed the message and granted permission for its transmission, please
enter your initials below:
</p>
<p>
<label class="required">Your initials:</label>
<input type="text" size="3" maxlength="3" name="fm_initials" value="<?php echo $initials?>"/>
</p>
<p>
In the text box below, you can record optional notes regarding this approval:
</p>
<?php
  }
  else {
?>
<p>
You are about to reject a blast e-mail.  In the text box below, please provide
one or more reasons explaining why the message violates Senate guidelines.
</p>
<?php
  }
?>

<p>
<textarea name="fm_notes" rows="8" cols="80"><?php echo $notes?></textarea>
</p>
<p>
<input type="submit" value="<?php echo $submit?>"/>
&nbsp;&nbsp;
<input type="button" value="Cancel" onClick="window.location='<?php echo REVIEW_SCRIPT?>'"/>
</p>
</form>

<?php  
} // print_verify_form()

include ('reassign_form.php');
?>