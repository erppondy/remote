<?php

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

$url = $publicURL;

require_once './ripcord/ripcord.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['userName']) || !isset($_GET['userPassword']) || !isset($_GET['date'])) {
        echo json_encode(["error" => "Missing required parameters"]);
        exit;
    }

    $user = $_GET['userName'];
    $password = $_GET['userPassword'];
    $date = $_GET['date'];
    $dbname = isset($_GET['dbname']) ? $_GET['dbname'] : 'erp_prod';
    $udise = isset($_GET['udise']) ? $_GET['udise'] : null;

    $context = [
        'login_type' => 'school',
        'udise' => $udise,
    ];

    $common = ripcord::client($url . '/xmlrpc/2/common');
    $uid = $common->authenticate($dbname, $user, $password, $context);
    
    if (!$uid) {
        echo json_encode(["error" => "Authentication failed"]);
        exit;
    }

    $models = ripcord::client("$url/xmlrpc/2/object");
    
    $teachers = $models->execute_kw(
        $dbname,
        $uid,
        $password,
        'school.teacher',
        'search_read',
        [[['employee_id.user_id.id', '=', $uid]]],
        ['fields' => ['id', 'name']]
    );
    
    if (empty($teachers)) {
        echo json_encode(["error" => "No teacher found"]);
        exit;
    }
    
    $teacher_id = $teachers[0]['id'];

    $attendance_records = $models->execute_kw(
        $dbname,
        $uid,
        $password,
        'daily.attendance',
        'search_read',
        [[['date', '=', $date], ['user_id', '=', $teacher_id]]],
        ['fields' => ['date', 'standard_id', 'create_uid', 'total_student', 'total_presence', 'total_absent', 'state', 'create_date']]
    );
    if (!empty($attendance_records)) {
        foreach ($attendance_records as &$record) {
            // Fetch attendance line records including roll number
            $attendance_line_records = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'daily.attendance.line',
                'search_read',
                [[['standard_id', '=', $record['id']]]],
                ['fields' => ['stud_id', 'is_absent', 'is_present', 'att']]
            );

            // Fetch roll number for each student in attendance line
            foreach ($attendance_line_records as &$line) {
                if (!empty($line['stud_id'])) {
                    $student_id = $line['stud_id'][0];
                    $student_data = $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'student.student',
                        'read',
                        [[$student_id]],
                        ['fields' => ['roll_no']]
                    );

                    $line['roll_no'] = !empty($student_data) ? $student_data[0]['roll_no'] : null;
                }
            }

            $record['attendance_lines'] = $attendance_line_records;
        }
        echo json_encode(["attendance" => $attendance_records]);
    } else {
        echo json_encode(["message" => "No attendance records found for the given date"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}