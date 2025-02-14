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

    header('Access-Control-Allow-Origin: *');
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
    error_log("User ID: $uid, User Name: $user");
    if (!$uid) {
        error_log("Authentication failed for user: $user");
    }
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
        //error_log("Fetch Class: " . print_r($teachers, true));
        $teacher_id = $teachers[0]['id'];
        $teacher_name = $teachers[0]['name'];

        // $classes = $models->execute_kw(
        //     $dbname,
        //     $uid,
        //     $password,
        //     'school.standard',
        //     'search_read',
        //     array(
        //         array(
        //             '|', '|',
        //             array('user_id.name', '=', $teacher_name),
        //             array('sec_user_id.name', '=', $teacher_name),
        //             array('ter_user_id.name', '=', $teacher_name),
        //         ),
        //     ),
        //     array('fields' => array('name', 'standard_id', 'medium_id', 'division_id'))
        // );
        //error_log("Fetch Class2: " . print_r($classes, true));
        // if (
        //     !isset($classes['faultString'])
        // ) {
        //     $response = array(
        //         // "teacher" => $teachers,
        //         "classes" => $classes,
        //     );
        // } else {
        //     // 120AB
        //     $response = array(
        //         'val' => 'error',
        //         'error' => $classes,

        //     );
        // }
        $classes = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.standard',
            'search_read',
            array(
                array(
                    '|', '|',
                    array('user_id.name', '=', $teacher_name),
                    array('sec_user_id.name', '=', $teacher_name),
                    array('ter_user_id.name', '=', $teacher_name),
                ),
            ),
            array('fields' => array('name', 'standard_id', 'medium_id', 'division_id', 'user_id', 'sec_user_id', 'ter_user_id'))
        );
        $formatted_classes = [];

        foreach ($classes as $class) {
            $role = 'Unknown';

            if (!empty($class['user_id']) && $class['user_id'][1] == $teacher_name) {
                $role = 'Class Teacher';
            } elseif (!empty($class['sec_user_id']) && $class['sec_user_id'][1] == $teacher_name) {
                $role = 'Secondary Teacher';
            } elseif (!empty($class['ter_user_id']) && $class['ter_user_id'][1] == $teacher_name) {
                $role = 'Tertiary Teacher';
            }

            $formatted_classes[] = array_merge($class, ['role' => $role]);
        }

        if (!isset($classes['faultString'])) {
            $response = array(
                "classes" => $formatted_classes,
            );
        } else {
            $response = array(
                'val' => 'error',
                'error' => $classes,
            );
        }
        
        
        echo json_encode($response);
    }else{
        echo json_encode(array(
            "message"=> "error",
            "error"=> "persistent not set"
        ));
    }
}else{
    echo json_encode(array(
        "message"=> "error",
        "error"=> "Not get request"
    ));
}
