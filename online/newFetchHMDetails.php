<?php

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

$url = $publicURL;

$user = null;
$password = null;
$dbname = null;
$response = null;

require_once './ripcord/ripcord.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
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
        $udise = $_GET['udise'];
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,
    );

    $common = ripcord::client($url . '/xmlrpc/2/common');
    $uid = $common->authenticate($dbname, $user, $password, $context);
    $models = ripcord::client("$url/xmlrpc/2/object");

    if (isset($_GET['Persistent'])) {
        // Fetch school details
        $school = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.school',
            'search_read',
            array(
                array(
                    array('code', '=', $udise),
                ),
            ),
            array('fields' => array('id', 'com_name', 'code', 'school_type',
                'email', 'type_of_school', "block", "cluster", "medium_id"))
        );

        if (!empty($school) && !isset($school['faultString'])) {
            $school_id = $school[0]['id'];

            // Fetch teacher count
            $teacher_count = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'school.teacher',
                'search_count',
                array(
                    array(
                        array('school_id', '=', $school_id)
                    )
                )
            );

            // Fetch teacher count
            $today_date = date('Y-m-d');
            $attendance_line_count = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'daily.attendance.line',
                'search_count',
                array(
                    array(
                        array('school_id', '=', $school_id),
                        array('date', '=', $today_date)
                    )
                )
            );
            $attendance_line_count_absent = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'daily.attendance.line',
                'search_count',
                array(
                    array(
                        array('school_id', '=', $school_id),
                        array('date', '=', $today_date),
                        array('att', '=', 'absent')
                    )
                )
            );
            $attendance_line_count_present = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'daily.attendance.line',
                'search_count',
                array(
                    array(
                        array('school_id', '=', $school_id),
                        array('date', '=', $today_date),
                        array('att', '=', 'present')
                    )
                )
            );
            // Fetch student count
            $student_count = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'student.student',
                'search_count',
                array(
                    array(
                        array('school_id', '=', $school_id)
                    )
                )
            );

            $response = array(
                "school" => $school,
                "teacher_count" => $teacher_count,
                "student_count" => $student_count,
                "attendance_marked"=> $attendance_line_count,
                "absent_students_count" => $attendance_line_count_absent,
                "present_students_count" => $attendance_line_count_present
            );
        } else {
            $response = array(
                'val' => 'error',
                'error' => 'School not found or authentication failed',
                'faultString' => isset($school['faultString']) ? $school['faultString'] : ''
            );
        }
        echo json_encode($response);
    }
}
