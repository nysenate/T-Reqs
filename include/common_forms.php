<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Web forms used in multiple interfaces
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2009-12-08
*************************************************************************/

require_once 'defs.php';


function print_login_form($action_type, $req_id, $uname = null, $pass = null, $sitename = null)
{
  if ($sitename == null && !empty($_COOKIE['sitename'])) {
    $sitename = $_COOKIE['sitename'];
  }
  
  if ($action_type == ACTION_TYPE_REQUEST) {
    $header_text = HEADER_REQUEST;
    $action_script = REQUEST_SCRIPT;
  }
  else {
    $header_text = HEADER_REVIEW;
    $action_script = REVIEW_SCRIPT;
  }
?>

<h1>Welcome to the <?php echo ORG_NAME?> <?php echo APP_NAME?> system</h1>
<h2><?php echo $header_text?> - Login</h2>

<div style="float: left; margin-right: 50px">
<!--<img src="images/trex_logo_trans_w_text.gif" alt="T-Rex image"/> -->
<img src="images/firey_orange_dino_w_text.png" alt="T-Rex image" width="180" height="150"/>
</div>

<div style="float: left; margin-right: 50px">
<p>
Please enter your login credentials.
</p>

<form method="post" action="<?php echo $action_script?>">
<input type="hidden" name="fm_stage" value="auth"/>
<input type="hidden" name="fm_requestid" value="<?php echo $req_id?>"/>
Username: <input type="text" size="30" name="fm_username" value="<?php echo $uname?>"/>
<br/><br/>
Password: <input type="password" size="30" name="fm_password" value="<?php echo $pass?>"/>

<?php
  if ($action_type == ACTION_TYPE_REQUEST) {
?>
<br/><br/>
Sitename: <input type="text" size="30" name="fm_sitename" value="<?php echo $sitename?>"/>
<?php
  }
?>
<br/><br/>
<input type="submit" value="Login"/>
</form>
</div>

<script>
document.forms[0].fm_username.focus();
</script>

<div style="float: left; margin-top: 50px">
<img src="images/bronto_logo_trans.gif" alt="Bronto logo"/>
</div>

<div style="clear: both"></div>

<?php
  return true;
} // print_login_form()



function print_user_info_form($session_id, $uinfo)
{
?>
<h2><?php echo HEADER_REQUEST?> - User Information</h2>

<p>
Please provide the following required information before continuing:
</p>

<form method="post" action="<?php echo REQUEST_SCRIPT?>">
<input type="hidden" name="fm_stage" value="userinfo"/>
<input type="hidden" name="fm_sessionid" value="<?php echo $session_id?>"/>

<p>
Username: <b><?php echo $uinfo['username']?></b>
</p>
<p>
<label class="required">First Name:</label>
<input type="text" name="fm_firstname" value="<?php echo $uinfo['firstname']?>"/>
</p>
<p>
<label class="required">Last Name:</label>
<input type="text" name="fm_lastname" value="<?php echo $uinfo['lastname']?>"/>
</p>
<p>
<label class="required">E-mail Address:</label>
<input type="text" name="fm_email" value="<?php echo $uinfo['email']?>" size="40"/>
</p>
<p>
<label class="required">Phone Number:</label>
<input type="text" name="fm_phone" value="<?php echo $uinfo['phone']?>" size="20"/>
</p>

<p>
<input type="submit" value="Submit"/>
&nbsp;&nbsp;
<input type="button" value="Cancel" onclick="window.location='<?php echo REQUEST_SCRIPT?>'"/>
</p>
</form>
<script>
document.forms[0].fm_firstname.focus();
</script>

<?php
} // print_user_info_form()



function print_nonmessage_info($bapi, $list_ids, $seg_ids, $delivery_date, $from_addr, $from_name, $reply_addr)
{
  $lists = get_lists_using_ids($bapi, $list_ids);
  $seg_names = get_segment_names($bapi, $seg_ids);
  $reply_str = (empty($reply_addr) ? "N/A" : $reply_addr);
?>
<p>
List(s) to be targeted:
</p>

<p>
<?php
if ($lists) {
  foreach ($lists as $list) {
    echo "<b>".$list->name."</b> [".$list->activeCount."]<br/>\n";
  }
}
else {
  echo "<div class=\"warning\">No lists were selected.</div>";
}
?>
</p>

<p>
Segment(s) to be targeted:
</p>

<p>
<?php
if (count($seg_names) > 0) {
  foreach ($seg_names as $sname) {
    echo "<b>$sname</b><br/>\n";
  }
}
else {
  echo "<div class=\"warning\">No segments were selected.</div>";
}
?>
</p>

<p>
Date to send: <b><?php echo $delivery_date?></b>
</p>

<p>
From: <b><?php echo $from_name?> &lt;<?php echo $from_addr?>&gt;</b>
<br/>Reply-To: <b><?php echo $reply_str?></b>
</p>

<?php
} // print_nonmessage_info()
?>