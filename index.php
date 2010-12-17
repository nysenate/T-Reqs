<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Database access functions
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2009-12-10
*************************************************************************/

require_once("include/defs.php");
require_once("include/header.php");

echo "<h1>Welcome to the ".ORG_NAME." ".APP_NAME." system</h1>\n";
?>

<div style="float: left; margin-right: 50px">
<img src="images/firey_orange_dino_w_text.png" alt="T-Reqs Logo" width="150" height="140"/>
</div>

<div style="margin-top: 30px">
<h3>To submit a request to approve a blast e-mail, please use the <a href="<?php echo REQUEST_SCRIPT?>">Approval
Request Form</a></h3>
<h3>To review a request that was submitted to you, please use the <a href="<?php echo REVIEW_SCRIPT?>">Message Review
Interface</a></h3>
</div>
<div style="clear: both"></div>

<?php
require_once("include/footer.php");
?>