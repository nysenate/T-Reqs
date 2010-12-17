<?php
/*
 * This shows the logout icon and welcome user
 */
if (strchr($_SERVER['PHP_SELF'], REVIEW_SCRIPT ) != FALSE) {
?>
    <input type="button" value="Logout" onclick="window.location='<?php echo REVIEW_SCRIPT?>'"/>
<?php 
}
else {
?>
    <input type="button" value="Logout" onclick="window.location='<?php echo REQUEST_SCRIPT?>'"/>
<?php 
}
print 'Welcome '.$_SESSION['welcome']."<br />";
?>