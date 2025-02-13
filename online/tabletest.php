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

    echo json_decode("echo");

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

    $tableName = null;

    if (isset($_GET['tableName'])) {
        $tableName = $_GET['tableName'];
    }else{
        $tableName = 'student.student';
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );
    $common = ripcord::client($url . '/xmlrpc/2/common');  
    $uid = $common->authenticate($dbname, $user, $password, $context);
    $models = ripcord::client("$url/xmlrpc/2/object");

    if (isset($_GET['Persistent'])) {

        $varl = $models->execute_kw(
            $dbname, $uid, $password, $tableName, 'fields_get',
            array(),
            array('attributes' => array('string', 'help', 'type','relation'))
        );

        if (!isset($varl['faultString'])) {
            $response = array(
                "f" => $varl,

            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => $varl,
            );
        }
        echo json_encode($response);
    }
}
