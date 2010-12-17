<?php
/*
 * This retrieves record with condition like status = awating OR status = under review...
 */
function db_get_records_with_condition($p_dbh, $table_name, $key_field, $key_value, $key_operator = null , $sortby = null, $user = null)
{
  $q = "select * from ".$table_name;
  
  if (!empty($key_operator)){
  	$q .= " where ";
  	$q .= "(";
  	$count = count($key_value);
  	for ($i=0; $i < $count; $i++){
  	 $q .= $key_field ." = ? ";
  	 if ($i < $count - 1)
  	 	$q .= $key_operator. " ";
  	}
  	$q .= ")";
  	if ($user != null)
  	{
  		$key_value[] = $user;
  		
  		$q .= " AND reviewer =  ? ";
  	}
  	 
  }
 
  
  if ($sortby) {
    $q .= " order by ".$sortby;
  }

  try {
    $dbh = ($p_dbh == null) ? open_db() : $p_dbh;
    $dbh->beginTransaction();
    $sth = $dbh->prepare($q);
    $vals = $key_value;
    $sth->execute($vals);
    $res = $sth->fetchAll(PDO::FETCH_ASSOC);
    $dbh->commit();
    $dbh = null;
    if (count($res) > 0) {
      return $res;
    }
    else {
      return null;
    }
  }
  catch (PDOException $ex) {
    $dbh = null;
    return null;
  }
  
} // db_get_records_with_condition()
/*
 * This makes the contents of the request list table
 * If parameter $result = NULL, only displays the column names, else displays the records.
 */
function list_request_with_condition($field, $result = NULL, $display_list = NULL)
{ 
  if ($result == NULL){
	print '<tr>';
    foreach ($field as $key => $value){
  	  print '<th>'.$value.'</th>';
    }
    print '</tr>';
  }
  elseif ($result != NULL) {
 
	  $count = count($result);
	  for ($i = 0; $i < $count; $i++){  	
	  	if ($i % 2)
	  	{
	  	  print '<tr style="background: #51749a; color: white;">';
	  	  if ($display_list =="under_review" ){
   		    print '<td  style="text-decoration:underline;" onclick="msg(\''.$result[$i]['uuid'].'\',\''.$result[$i]['reviewer'].'\');">'.$result[$i]['uuid'].'</td>';
   		     }
   		  else {
   		    print '<td><a style=" color: white;" href="'.REVIEW_SCRIPT.'?fm_requestid='.$result[$i]['uuid'].'" >'.$result[$i]['uuid'].'</a></td>';
   		  }
	  	}
	  	else
	  	{
	  	  print '<tr style="background: #c6b422; ">';
	  	  if ($display_list =="under_review" )
	  	    print '<td style=" color: black;text-decoration:underline;" onclick="msg(\''.$result[$i]['uuid'].'\',\''.$result[$i]['reviewer'].'\');">'.$result[$i]['uuid'].'</td>';    
	  	  else
	  	    print '<td><a style=" color: black;" href="'.REVIEW_SCRIPT.'?fm_requestid='.$result[$i]['uuid'].'" >'.$result[$i]['uuid'].'</a></td>';
	  	}

	    print '<td>'.$result[$i]['requester'].'</td>'
	    .'<td>'.$result[$i]['reviewer'].'</td>'
	    .'<td>'.$result[$i]['account_name'].'</td>'
	    .'<td>'.$result[$i]['message_name'].'</td>'
	    . '<td>'.$result[$i]['delivery_date'].'</td>'
	    . '<td>'.$result[$i]['district'].'</td>'
	    . '<td>'.$result[$i]['from_addr'].'</td>'
	    . '<td>'.$result[$i]['from_name'].'</td>'
	    . '<td>'.str_replace('_',' ',$result[$i]['status']).'</td>'
	    . '<td>'.$result[$i]['request_notes'].'</td>';
	    print '</tr>';
	  } 
  }
}//list_request_with_condition()
?>