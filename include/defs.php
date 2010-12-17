<?php
/*************************************************************************
** Bronto T-Reqs E-mail Approval Interface
** Purpose: Application constants
** Organization: New York State Senate
** Author: Ken Zalewski
** Last revision: 2010-07-08
*************************************************************************/

define("THIS_DIR", dirname(__FILE__));
define("CONFIG_DIR", THIS_DIR."/..");
define("LIB_DIR", THIS_DIR."/../lib");

define("APP_VERSION", "1.1");
define("APP_NAME", "T-Reqs");
define("APP_FULLNAME", "Bronto T-Reqs");
define("ORG_NAME", "New York State Senate");
define("ORG_SHORTNAME", "NY Senate");
define("CONFIG_FILEPATH", CONFIG_DIR."/treqs.conf");
define("HEADER_REQUEST", APP_NAME." blast e-mail approval request");
define("HEADER_REVIEW", APP_NAME." blast e-mail message review");
define("REQUEST_DIR", "REQUESTS");
define("REQUEST_SCRIPT", "approval_request.php");
define("REVIEW_SCRIPT", "review_message.php");
define("ACTION_TYPE_REQUEST", 1);
define("ACTION_TYPE_REVIEW", 2);
define("VERIFY_TYPE_APPROVE", 1);
define("VERIFY_TYPE_REJECT", 2);
define("USER_TYPE_NORMAL", 1);
define("USER_TYPE_REVIEWER", 2);
define("USER_TYPE_MAJCONFSVC", 3);
define("USER_TYPE_MINCONFSVC", 4);
define("BRONTO_API_URL", "http://api.bronto.com");

define("DEFAULT_REVIEWER", "reviewer1");
define("DEFAULT_DB_DSN", "mysql:host=localhost;dbname=bronto");
define("DEFAULT_DB_USER", "brontoadmin");
define("DEFAULT_DB_PASS", "");
define("DEFAULT_LDAP_HOST", "webmail.senate.state.ny.us");
define("DEFAULT_SMTP_HOST", "senapps.senate.state.ny.us");
define("DEFAULT_SMTP_PORT", 22);
define("DEFAULT_SMTP_USER", "");
define("DEFAULT_SMTP_PASS", "");


?>
