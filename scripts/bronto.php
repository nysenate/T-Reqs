<?php
/*
    Copyright 2006 Bronto Software, Inc.
    All rights reserved.

    $Id$

    This script provides example use of several different methods
    within the API.

    The functions within this file show simplistic uses of each of the more popular
    methods available within the API.

*/ 

//give script some time/memory to work 
//set_time_limit(600);
//ini_set("memory_limit", "150M");
//ini_set("default_socket_timeout", 300);
//ini_set("soap.wsdl_cache_enabled", 0);

// This is the sitename for your account, Go to Home -> Settings to see
// your sitename
$sitename = 'newyorksenateagency'; 
// the username that has been given API permission, see user permissions
$username = 'kristab';
//$username = 'signuplists';
// the password for your site
$password = 'xxxxxxx';

// the location of the wsdl, you shouldn't need to change this
$wsdl = 'http://api.bronto.com/?q=mail_3&wsdl';
// where requests are submitted to, you shouldn't need to change this
$destination_url = 'http://api.bronto.com/?q=mail_3';

echo("Starting...\n");

echo("using PHP-SOAP\n");

$start = microtime(true);

//PHP-SOAP
$binding = new SoapClient($wsdl, array('trace' => 1));

try {
    $binding->__setLocation($destination_url);
    // Create a new session
    $parameters = array('username' => $username,
                        'sitename' => $sitename,
                        'password' => $password);
    $result = $binding->login($parameters);

    if (!$result->return->success) {
        echo("unable to login\n");
        print_r($result);
        print_r($binding->__getLastRequest());
        print_r($binding->__getLastResponse());
        echo "Login failed.\n";
        exit;
    }

    $session_id = $result->return->sessionId;
    echo 'SessionId:'. $session_id ."\n";

    print_r($binding->__getLastRequest());
    print_r($binding->__getLastResponse());

    $session_header = new SoapHeader('http://api.bronto.com', 'sessionHeader', array('sessionId' => $session_id));
    $binding->__setSoapHeaders(array($session_header));


    /**
     * Uncomment the function that you want to try out
     *  each function has further explanation of what they do
     * Many of these will not do anything without some form of modification.
     */

    //$result = readFields($binding);
    //$result = readMessageFolders($binding);
    //$result = writeMessages($binding);
    //$result = readContacts($binding);
    //$contacts = $result->return->contacts;

    $result = readMessages($binding);
    $message_id = $result->return->messages->id;
    print_r($result);

    exit();

    $result = writeFieldDeliveries($binding, $message_id, $contacts);
    //$result = readSends($binding);
    //$result = writeContacts($binding);

    echo("Request:\n\n");
    print_r($binding->__getLastRequest());
    echo("\n\nReponse:\n\n");
    print_r($binding->__getLastResponse());

    $time = microtime(true) - $start;
    echo("\n\ntotal time: $time \n");
}
catch(SoapFault $exception){
    echo('Error Fault: ' . $exception->faultcode . "\n");
    echo("More Detail: " . $exception->faultstring ."\n");
    echo('Error Detail: ' . $exception->detail->fault->code . '::' . $exception->detail->fault->message ."\n");
    echo("Headers:\n\n". $binding->__getLastRequestHeaders());
    echo("\nRequest:\n\n\n". $binding->__getLastRequest() ."\n");
    echo("\nResponse:\n\n\n". $binding->__getLastResponse() ."\n");
}

/**
 * readFields reads all fields for contacts as specified by the filter 
 *  you define below.
 */
function readFields($binding) {
    // Initialize emtpy arrays
    $attributes = $filter = $criteria = $criteria2 = array();

    // Set up the attributes to specify what you want to receive
    $attributes['label'] = TRUE;
    $attributes['type'] = TRUE;
    $attributes['display'] = TRUE;
    $attributes['visibility'] = TRUE;
    $attributes['options'] = TRUE;

    // Specify the criteria to limit the contacts returned
    // attribute is the field you want to compare against
    $criteria['attribute'] = 'modified';
    $criteria['comparison'] = '>'; // how to compare attribute
    $criteria['value']['value'] = '2008-01-01 07:00:00'; // What to compare against
    $criteria['value']['type'] = 'date'; // type of comparison - string, int, double, or date
    // create the filter to be passed in
    $filter['criteria'][] = $criteria;

    $parameters = array('attributes' => $attributes, 'filter' => $filter);
    $result = $binding->readFields($parameters);
    return $result;
}

/**
 * Creates a contact with the data provided here.
 *  This can easily be tied into a for loop to create many contacts at once.
 */
function writeContacts($binding) {
    // Initialize emtpy arrays
    $writeContact = $writeContactsHandler = array();
    // The email address to add
    $writeContact['email'] = 'user@yoursite.com';
    // Preference for type of message: text or html
    $writeContact['msgPref'] = 'html';
    // For status the choices are: active, unsubscribed, bounced, or unconfirmed
    // for a new contact, this should be 'active'
    $writeContact['status'] = 'active';
    // Where did this contact come from?
    $writeContact['customSource'] = 'signup during purchase';
    // insert, update, or insertUpdate, most of the time you want insertUpdate
    $writeContactsHandler['mode'] = 'insertUpdate';

    $parameters = array('contacts' => $writeContact, 'handler' => $writeContactsHandler);
    $result = $binding->writeContacts($parameters);
    return $result;
}

/**
 * read a list of contacts from your db in Bronto
 */
function readContacts($binding) {
    // Initialize empty arrays
    $attributes = $filter = $criteria = $criteria2 = array();

    // Set up the attributes to specify what you want to receive
    $attributes['status'] = TRUE;
    $attributes['msgPref'] = TRUE;
    $attributes['created'] = TRUE;
    $attributes['modified'] = TRUE;
    $attributes['source'] = TRUE;
    $attributes['fields'] = array();

    // Specify the criteria to limit your query
    $criteria['attribute'] = 'modified'; // The field you are looking at
    $criteria['comparison'] = '>'; // how to compare
    $criteria['value']['value'] = '2007-04-03 12:00:00'; // The value to compare against
    $criteria['value']['type'] = 'date'; // type of comparison: string, int, double, or date
    $filter['criteria'][] = $criteria;

    $parameters = array('attributes' => $attributes, 'filter' => $filter);

    // this returns an array of contacts
    echo "Just about to call readContacts()\n";
    //$result = $binding->readContacts($parameters);
    $result = $binding->readContacts();
    echo "Got result=$result\n";
    return $result;
}

/**
 * The readSends function attempts to return instances of a delivery being sent to a contact for a given filter. 
 *  A typical use of this function would be to return every contact that was sent a specific delivery or to return 
 *  every delivery sent to a specific contact.
 */
function readSends($binding) {
    // initialize emtpy arrays
    $attributes = $filter = $criteria = $criteria2 = array();
    $attributes['created'] = TRUE;

    // If you were looking for a deliveries then specify contactId here
    // to get the contactId you would need to do a read contact and get their unique id
    $criteria['attribute'] = 'contactId'; // are you can get contacts back by specifying 'deliveryId'
    $criteria['comparison'] = '=';
    $criteria['value']['value'] = '$uniqueId'; //previously obtained from a readContacts call
    $criteria['value']['type'] = 'string';
    $filter['criteria'][] = $criteria;

    $parameters = array('attributes' => $attributes, 'filter' => $filter);
    $result = $binding->readSends($parameters);
    return $result;
    
}

/**
 * The writeDeliveries function allows a user to insert a Delivery and associate a Message with it. 
 * In other words, this function schedules mailings to be sent to a set of recipients. Multiple Deliveries may be inserted in one call.
 */
function writeDeliveries($binding, $message_r = null, $contact_r = null) {
        // initialize empty arrays
        $deliveries = $handler = array();
        $deliveries['start'] = 'now';
        $deliveries['messageId'] = $message_r; //obtained previously from a readMessages call

    # Add the recipients
        $c_ids = array();
        foreach($contact_r as $contact){
            if(ereg('adam', $contact->email)) {
               $ctc = array('type' => 'contact', 'id' => $contact->id);
               $c_ids[] = $ctc;
            }
        }
        print_r($c_ids);

        $deliveries['recipients'] = $c_ids;


        // If you are tying this to an automated message rule then set the handle below
        //$deliveries['automatorHandle'] = 'Automated Message Rule Handle';

        // Set the header info
        $deliveries['fromEmail'] = 'FromName@yoursite.com';
        $deliveries['fromName'] = 'From Name';
        $deliveries['replyEmail'] = 'fromname@yoursite.com';
        $handler['mode'] = 'insert';

        $parameters = array('deliveries' => $deliveries, 'handler' => $handler);
        $result = $binding->writeDeliveries($parameters);
        return $result;
}


/**
 * The writeFieldDeliveries function allows a user to insert a Delivery and associate a Message with it. 
 * In other words, this function schedules mailings to be sent to a set of recipients. Multiple Deliveries may be inserted in one call.
 * This version makes use of message fields
 */
function writeFieldDeliveries($binding, $message_r = null, $contact_r = null) {
    // initialize empty arrays
    $deliveries = $handler = array();
    $deliveries['start'] = 'now';
        $deliveries['messageId'] = $message_r; //obtained previously from a readMessages call

        # Add the recipients
        $c_ids = array();
        foreach($contact_r as $contact){
            if(ereg('adam', $contact->email)) {
               $ctc = array('type' => 'contact', 'id' => $contact->id);
               $c_ids[] = $ctc;
            }
        }
        print_r($c_ids);

        $deliveries['recipients'] = $c_ids;

    $fInfo = "Franchise 1 is super cool. We all like it.";
    $fields[] = array('name' => 'franchisename', 'type'=>'html', 'content' => 'Franchise 1');
    $fields[] = array('name' => 'franchiseinfo', 'type'=>'html', 'content' => $fInfo);
    $deliveries['fields'] = $fields;

    // Set the header info
    $deliveries['fromEmail'] = 'FromName@yoursite.com';
    $deliveries['fromName'] = 'From Name';
    $deliveries['replyEmail'] = 'fromname@yoursite.com';
    $handler['mode'] = 'insert';

    $parameters = array('deliveries' => $deliveries, 'handler' => $handler);
    $result = $binding->writeDeliveries($parameters);
    return $result;
}

/**
 * The writeMessages function allows a user to insert or update a message with the given attributes, 
 *  depending upon the handler specified. Note: the name of a message is a unique key
 */
function writeMessages($binding) {
    $messageFolder = readMessageFolders($binding);
    // Here we are just grabbing the first folder, which is the root folder
    $messageFolderId = $messageFolder->return->messageFolders->id;

    $messages = $handler = array();
    $messages['name'] = 'API Created Message';
    $messages['status'] = 'approved';
    $messages['messageFolderId'] = $messageFolderId;
    // an html based message, $message would be populated elsewhere with you html message
    $messages['content'][] = array('type' => 'html', 'subject' => 'This html message was created via the API', 'content' => $message);
    // a plain text message
    //$messages['content'][] = array('type' => 'text', 'subject' => 'This text message was created via the API', 'content' => 'TEXT ONLY');
    $handler['mode'] = 'insertUpdate';
    $handler['contentMode'] = 'insertUpdate';

    $parameters = array('messages' => $messages, 'handler' => $handler);
    $result = $binding->writeMessages($parameters);

    return $result;
}

/**
 * Finds messages that match the given filter.
 */
function readMessages($binding) {
    // initialize empty arrays
    $attributes = $filter = $criteria = array();
    $attributes['status'] = true;
    $attributes['content'] = true;
    // create a message filter
    $criteria['attribute'] = 'name'; // id or name
    $criteria['comparison'] = '=';
    $criteria['value']['type'] = 'string';
    $criteria['value']['value'] = 'Message Field Test';
    $filter['criteria'][] = $criteria;

    $parameters = array('attributes' => $attributes, 'filter' => $filter);
    $result = $binding->readMessages($parameters);
    return $result;
}


/**
 * The readMessageFolders function attempts to return message folders that match all of the given filter. 
 * The specified attributes of the folders are returned for each matching folder.
 */
function readMessageFolders($binding) {
    // initialize empty arrays
    $attributes = $filter = $criteria = array();
    // create a filter to find folders
    $criteria['attribute'] = 'parentId'; // id, name, or parentId
    $criteria['comparison'] = '=';
    $criteria['value']['value'] = $parentId; // populated with another readMessageFolders call
    $criteria['value']['type'] = 'string';
    $filter['criteria'][] = $criteria;

    $parameters = array('attributes' => $attributes, 'filter' => $filter);
    $result = $binding->readMessageFolders($parameters);
    return $result;
    
}

?>

