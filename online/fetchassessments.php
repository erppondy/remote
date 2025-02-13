<?php

$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";
//
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

        // get academic year data
        $year = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'academic.year',
            'search_read',
            array(
                array(
                    array('current', '=', True),
                ),
            ),
            array('fields' => array('name'))
        );

        $academic_year = $year[0]['name'];


        $assessment_records = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'pace.examsched',
            'search_read',
            array(
                array(
                    array('year_id.name', '=', $academic_year),
                    array('state', 'in', ['scheduled', 'conducted']),
                ),
            ),
            array('fields' => array('name', 'subject', 'qp_code', 'date', 'standard_id', 'medium'))
        );



        if (
            !isset($year['faultString'])
            && !isset($assessment_records['faultString'])
        ) {
            $response = array(
                'assessments' => $assessment_records,
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
