<?php

$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";

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
        // get teacherLeaveRequests data
        $teacherLeaveRequests = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'teacher.leave.request',
            'search_read',
            array(
                array(
                    array(
                        'name', '!=', false
                    )
                )
            ),
            array('fields' => array('name', 'school_id', 'staff_id','start_date','end_date','user_id','days','reason','state'))
        );


        if (
            !isset($teacherLeaveRequests['faultString']) && isset($teacherLeaveRequests) && $teacherLeaveRequests != false
        ) {
            $response = array(
                "message" => "success",
                'uid'=> $uid,
                "teacherLeaveRequests" => $teacherLeaveRequests,
            );
        echo json_encode($response);

        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $teacherLeaveRequests,
                'uid'=> $uid,
                'p'=> array(!isset($teacherLeaveRequests['faultString']), isset($teacherLeaveRequests), $teacherLeaveRequests != false)
            );
        echo json_encode($response);

        }
    }
}
