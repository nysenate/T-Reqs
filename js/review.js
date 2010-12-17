function msg(id, name){
var r=confirm("This is currently under review by " + name + " already, proceed anyway...?");
if (r == true)
	window.open("review_message.php?fm_requestid="+id, "_self");
else
	return false;
}

function open_win($msg_preview_url){
	window.open("display_readurl.php?url="+$msg_preview_url,"","width=600, height=400,scrollbars=yes");
}
