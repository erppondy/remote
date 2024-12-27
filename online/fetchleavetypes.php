<?php

$privateURL = "http://10.184.51.70:8069";
$publicURL = "http://10.184.51.70:8069";

$url = $publicURL;

// $url = $privateURL;

$user = null;
$password = null;
$dbname = null;
$response = null;


require_once './ripcord/ripcord.php';

// check if server request method is get
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // $response = 'Fetch Request received';

    header('Access-Control-Allow-Origin: *', false);
    header('Content-Type: application/json');


    if (isset($_GET['userName'])) {
        $user = $_GET['userName'];
    }
    if (isset($_GET['userPassword'])) {
        $password = $_GET['userPassword'];
    }
    if (isset($_GET['dbname'])) {
        $dbname = $_GET['dbname'];
    } else {
        $dbname = 'odoo_test';
    }
    if (isset($_GET['udise'])) {
        $udise = $_GET['udise'];  // Capture udise value if provided
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );
    $common = ripcord::client($url . '/xmlrpc/2/common');  
    $uid = $common->authenticate($dbname, $user, $password, $context);
    $models = ripcord::client("$url/xmlrpc/2/object");

    if (isset($_GET['Persistent'])) {
        // get academic leaveTypes data
        $leaveTypes = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'leave.type',
            'search_read',
            array(
                array(
                    array('name', '!=', FALSE),
                ),
            ),
            array('fields' => array('name', 'description'))
        );


        if (
            !isset($leaveTypes['faultString']) && isset($leaveTypes) && $leaveTypes != false
        ) {
            $response = array(
                "message" => "success",
                "leaveTypes" => $leaveTypes,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $leaveTypes
            );
        }
        echo json_encode($response);
    }
}
