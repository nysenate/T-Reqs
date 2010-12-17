<?php
/*
 * This find all the hidden href in the message and display them
 */
print '<div style=" padding-left:50px;">';
$url = $_GET['url'];

$needle = 'href';
$contents = file_get_contents($url);
if(strpos($contents, $needle)!== false) {
  echo '<h3>Found ';
} else {
  echo '<h3>Found ';
}
$href_num = substr_count($contents,$needle);

print $href_num.' links.<br />';

print '</h3>';
$href_pos = 0;
$i =0;
print '<table style="text-align:left;font-size:95%;font-family:"Times New Roman", Times, serif;">';
print '<tr style="background: #99cc99;"><th style="width: 80%;">Link</th><th style="width: 20%;">Text</th></tr>';
while ( $i < $href_num)
{
  
  $next_href_pos = stripos($contents,$needle,$href_pos);
  $gt = stripos($contents,'>',$next_href_pos + 1);
  $first_q = stripos($contents,'"',$next_href_pos + 1);
  $second_q =  stripos($contents,'"',$first_q + 1);
   
  
  $length = $second_q - $first_q + 1;
  if ($i % 2)
  {
	print '<tr style="background: #eaf2d3; color: black;">';
  }
  else
  {
  	print '<tr style="background: #DAF2D3; color: black;">';
  }
  
  print '<td >';
  print substr($contents,$first_q,$length);
  print '</td>';
  
  print '<td >';
  print '<a href=';
  print substr($contents,$first_q,$length);
  
  $st = stripos($contents,'</a',$next_href_pos + 1);
  $length = $st - $gt;
   
  print '>  '.substr($contents,$gt+1,$length-1).'  </a>';
  print '</td>';  
  print '</tr>';
  $href_pos = $st + 1;
  $i++;
}
print '</table>';
print '</div>';
?>