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
            array('fields' => array('name', 'school_id'))

        );

        $teacher_id = $teachers[0]['id'];
        $teacher_name = $teachers[0]['name'];

        $classes2 = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.standard',
            'search',
            array(
                array(
                    '|', '|',
                    array('user_id.name', '=', $teacher_name),
                    array('sec_user_id.name', '=', $teacher_name),
                    array('ter_user_id.name', '=', $teacher_name),
                ),
            ),
        );

        $students = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'student.student',
            'search_read',
            array(
                array(
                    array('standard_id', 'in', $classes2),
                    array('school_id', '=', (int) $teachers[0]['school_id'][0]),
                    array('state', '=', 'done'),
                ),
            ),
            array("fields" => array('student_name','roll_no', 'standard_id')),
            // array()
        );

        if (!isset($students['faultString'])) {
            $response = array(
                "students" => $students,
            );
        } else {
            // 120AB
            $response = array(
                'val' => 'error',
                'error' => array(
                    "students" => $students,
                ),
            );
        }
        echo json_encode($response);
    }
}
