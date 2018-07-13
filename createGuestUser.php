<?php

require 'vendor/autoload.php';
use GuzzleHttp\Client;

#  *** How to create sponsor login/password and find ISE portal ID ***
#  https://communities.cisco.com/docs/DOC-71891

# login for sponsor account
$user = 'sponsor-username';
# password for sponsor account
$password = 'sponsor-password';
# ISE portal ID
$portalId = '40343300-24302-11348-bs71-00324fsd327f';
# ISE hostname
$iseHostname = 'ise.prod.tesla.com';

function createUser($firstName,$lastName,$emailAddress,$reasonForVisit,$company) {
    global $user,$password,$portalId,$iseHostname;
    $fromDate = date("m/d/y") . ' 06:00'; # date in format 02/23/18 06:00
    $toDate = date("m/d/y") . ' 23:00';

    $client = new \GuzzleHttp\Client([
        'base_uri' => "https://$iseHostname:9060/",
    ]);

    $headers = [
        'Accept' => 'application/json',
        'Content-type' => 'application/json'
    ];

    $JsonData = [
        "GuestUser" => [
            "guestType" => "Contractor (default)",
            "reasonForVisit" => "$reasonForVisit",
            "portalId" => "$portalId",
            "guestInfo" => [
                "firstName" => "$firstName",
                "lastName" => "$lastName",
                "emailAddress" => "$emailAddress",
                "company" => "$company",
                "enabled" => "true"
            ],
            "guestAccessInfo" => [
                "validDays" => 1,
                "fromDate" => "$fromDate",
                "toDate" => "$toDate",
                "location" => "Toronto"
            ]
        ]
    ];

    $response = $client->post('/ers/config/guestuser/', [
        'headers' => $headers,
        'json' => $JsonData,
        'auth' => ["$user", "$password"]
    ]);

    $code = $response->getStatusCode();

    if ($code == "201") {
        $headers = $response->getHeaders();
        $userId = $headers["Location"][0];
        $userInfo = getUser($userId);

        # returns array
        # array(5) {
        # ["firstName"]=> string(3) "Andrew"
        # ["lastName"]=> string(3) "Che"
        # ["company"]=> string(3) "Tesla"
        # ["iseUsername"]=> string(5) "ache1"
        # ["isePassword"]=> string(9) "iPhj85BX6"
        # }

        return $userInfo;
    }
    else {
        echo "Something is wrong...";
    }
}

function getUser($userId) {
    global $user,$password,$iseHostname;
    $userInfo = array();
    $headers = [
        'Accept' => 'application/json',
        'Content-type' => 'application/json'
    ];

    $client = new \GuzzleHttp\Client([
        'base_uri' => "https://$iseHostname:9060/",
    ]);

    $response = $client->get(
        $userId,
        [
            'auth' => ["$user", "$password"],
            'headers' => $headers
        ]);

    $body = json_decode($response->getBody(),true);
    # filling array with data
    $userInfo['firstName'] = $body['GuestUser']['guestInfo']['firstName'];
    $userInfo['lastName'] = $body['GuestUser']['guestInfo']['lastName'];
    $userInfo['company'] = $body['GuestUser']['guestInfo']['company'];
    $userInfo['iseUsername'] = $body['GuestUser']['guestInfo']['userName'];
    $userInfo['isePassword'] = $body['GuestUser']['guestInfo']['password'];

    return $userInfo;

}

# ------------------------------ Getting POST  -----------------------------

error_reporting(E_ALL & ~E_NOTICE);

try {
    if(count($_POST) == 0) throw new \Exception('Form is empty');
# Receive POST parameters
    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $emailAddress = $_POST["emailAddress"];
    $reasonForVisit = $_POST["reasonForVisit"];
    $company = $_POST["company"];

    $user = createUser($firstName,$lastName,$emailAddress,$reasonForVisit,$company);

    # preparing JSON output
    $data = [ 'firstName' => $user["firstName"],
        'lastName' => $user["lastName"],
        'iseUsername' => $user["iseUsername"],
        'isePassword' => $user["isePassword"] ];

    # returns JSON
    # {"firstName":"John","lastName":"Doe","iseUsername":"jdoe4","isePassword":"x1wVpWM93"}

    header('Content-type: application/json');
    echo json_encode( $data );
}

catch (\Exception $e)
{
    $responseArray = array('type' => 'danger', 'message' => $errorMessage);
}
