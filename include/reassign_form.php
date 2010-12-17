<?php
/*
 * This function builds the page for reassign form
 */
function review_re_assign_form($dbh, $table_name, $key_field, $key_value, $req_id)
{
  $result = db_get_records($dbh, $table_name, $key_field, $key_value, $sortby = null);
  if ($result != null){

	$count = count($result); 
?>
	<table>
	<form action="<?php echo REVIEW_SCRIPT; ?>" method ="post">
	
	<input type="hidden" name="submitted" value="submitted"/>
	<input type="hidden" name="fm_stage" value="reassign"/>
	<input type="hidden" name="fm_requestid" value="<?php echo $req_id; ?>"/>
	<input type="hidden" name="fm_username" value ="<?php echo $_SESSION['username']; ?>"/>
	
	<tr>
	<td><LABEL FOR=reviewer>Reviewer</LABEL></td>
	<td>
	<select name="username" size ="7">
	
	
<?php
	//print '<option selected="'.DEFAULT_REVIEWER. '" value="'.DEFAULT_REVIEWER.'">'.'DEFAULT REVIEWER'.'</option>';
	print '<option  value="'.DEFAULT_REVIEWER.'">'.'DEFAULT REVIEWER'.'</option>';
	for ($i = 0; $i < $count; $i++) {	
		print $result[$i]['username'];
		print '<br />';
		print '<option value="'.$result[$i]['username'].'">'.$result[$i]['username'].'</option>';
	}	
?>
	</td>
	</tr>
	<tr><td col='2'></td></tr>
	<tr>
	<td><LABEL FOR="status">Status</LABEL></td>
	<td>
	<select name="status" size ="2">
	<option value="AWAITING_REVIEW">AWAITING REVIEW</option>
	<option value="UNDER_REVIEW">UNDER REVIEW</option>
	</td></tr>
	<tr><td col='2'></td></tr>
	<tr><td></td>
	<td>
	
	<input type="submit" value="Cancel" onClick="window.location='<?php echo REVIEW_SCRIPT; ?>'"/>
<?php
	
	print '<input type="submit" value="Submit" />';
	print '</select></form>';
?>
	</td></tr>
    </table>
<?php
  }
}
?>
