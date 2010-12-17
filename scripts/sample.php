<?php
/**
 * 
 * The login sample code can be imported into any script requiring access
 * to the Bronto API.  The bApi soap client object created below can be used
 * to invoke any of the available operations. 
 * 
 * Note: You will need a Bronto account with API access enabled.  The USERNAME,
 * PASSWORD, and SITENAME constants will need to be set accordingly.
 */

// Account Settings 
$USERNAME       = 'signuplists';
$PASSWORD       = 'signmeup';
$SITENAME       = 'newyorksenateagency';

// Bronto API WSDL
$BRONTO_WSDL    = 'http://api.bronto.com/?q=mail_3&wsdl';


// Set some defaults
//set_time_limit(600);
//ini_set("memory_limit", "150M");
//ini_set("default_socket_timeout", 300);
//ini_set("soap.wsdl_cache_enabled", 0);

// creates a handle to bronto API to be used in all subsequent calls
$bApi = new SoapClient( $BRONTO_WSDL, array('trace' => 1) );
$bApi->__setLocation("http://api.bronto.com/?q=mail_3");

// call login operation to obtain a sessionId
$parameters = array( 'username' => $USERNAME, 'password' => $PASSWORD, 'sitename' => $SITENAME );
$result = $bApi->login( $parameters );

// just exit if something goes wrong
if( !$result->return->success ) 
{
    exit( "Could not login to Bronto API.  Verify all settings are correct.\n" );   
}

// setup soap header which will be used on all calls to the api
$session_header = new SoapHeader( 'http://api.bronto.com', 'sessionHeader', array('sessionId' => $result->return->sessionId) );
$bApi->__setSoapHeaders( array($session_header) );

$attributes = array();
$filter = array();
$parameters = array("attributes"=>$attributes, "filter"=>$filter);
$result = $bApi->readAccounts($parameters);
print_r($result);

?>

