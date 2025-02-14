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

    // echo json_decode("echo");

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
        $school = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.school',
            'search_read',
            array(
                array(
                    array('email','=', $user)
                ),
            ),
            array('fields'=> array('com_name'))
        );
        sleep(0.5);
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
            array('fields'=> array('name'))
        );

        $schoolId = $school[0]['id'];
        $yearId = $year[0]['id'];
        sleep(0.5);

        $timeTable = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'regular.time.table',
            'search',
            array(
                array(
                    array('school_id', '=', $schoolId),
                    array('year_id', '=', $yearId)
                ),
            ),
            // array(
            //     'fields' => array(
            //         'name', 'standard_id', 'timetable_ids', 'week_day', 'period'
            //     ),
            // )
        );
        sleep(0.5);
        $timeTableLine = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'regular.time.table.line',
            'search_read',
            array(
                array(
                    array('table_id', 'in', $timeTable),
                ),
            ),
            array(
                'fields' => array(
                    'teacher_id', 'week_day', 'period', 'school_id'
                ),
            )
        );
        if (!isset($timeTable['faultString'])) {
            $response = array(
                'message'=> 'success',
                "timeTable" => $timeTableLine,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => array(
                    "students" => $timeTable,
                ),
            );
        }
        echo json_encode($response);
    }
}
