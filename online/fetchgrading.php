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
        
        $grading = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'pace.grade',
            'search_read',
            array(
                array(
                    array('from_mark', '!=', False),
                    array('to_mark', '!=',  False),
                    array('result', '!=', False)
                ),
            ),
            array('fields' => array('from_mark', 'to_mark', 'result')),
        );

        if ( !isset($grading['faultString'])
        ) {
            $response = array(
                'grading' => $grading,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $languages
            );
        }
        echo json_encode($response);
    }
}
