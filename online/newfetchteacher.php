<?php

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

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
        $dbname = 'erp_prod';
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

        $teachers = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.teacher',
            'search_read',
            array(
                array(
                    array('employee_id.user_id.id', '=', $uid),
                ),
            ),
            array('fields' => array('name', 'school_id', 'employee_id',
                    'teacher_code', 'user_id', "photo"))

        );

        $teacher_id = $teachers[0]['id'];
        $teacher_name = $teachers[0]['name'];
        $school_name = $teachers[0]['school_id'][1];
        $school_id = $teachers[0]['school_id'][0];

        if (
            !isset($teachers['faultString'])
        ) {
            $response = array(
                "teacher" => $teachers,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $languages,
                'faultString'=>$teachers['faultString']
            );
        }
        echo json_encode($response);
    }
}
