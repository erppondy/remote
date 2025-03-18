<?php

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

$url = $publicURL;

$password = null;
$user = null;
$dbname = null;

require_once './ripcord/ripcord.php';
header('Access-Control-Allow-Origin: *', false);
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $entityBodyJSON = file_get_contents("php://input");
    $entityBody = json_decode($entityBodyJSON, true);

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];
    $dbname = $entityBody['dbname'] ?? 'erp_prod';
    $udise = $entityBody['udise'] ?? null;

    $context = ['login_type' => 'school', 'udise' => $udise];

    if (isset($entityBody['sync'])) {
        $response = [];

        $absentees = $entityBody['absentees'];
        $className = $entityBody['className'];
        $date = $entityBody['date'];
        $schoolId = $entityBody['schoolId'];
        $schoolName = $entityBody['schoolName'];
        $classId = $entityBody['classId'];
        $teacherId = $entityBody['teacherId'];
        $submissionDate = $entityBody['submissionDate'];

        if ($className && $classId && $date && $schoolName && $schoolId && $teacherId && $submissionDate) {
            $common = ripcord::client($url . '/xmlrpc/2/common');
            $uid = $common->authenticate($dbname, $user, $password, $context);
            $models = ripcord::client("$url/xmlrpc/2/object");

            // Check if attendance record exists
            $attendance_exists = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'daily.attendance',
                'search',
                [[['date', '=', $date], ['standard_id.name', '=', $className]]]
            );

            if (!empty($attendance_exists)) {
                $record_create_id = $attendance_exists[0]; // Existing record ID
                error_log("Updating attendance record ID: " . $record_create_id);
            } else {
                // Get total number of students
                $total_students = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'student.student',
                    'search_count',
                    [[['standard_id.name', '=', $className], ['school_id.name', '=', $schoolName], ['state', '=', 'done']]]
                );

                $total_absent = count($absentees);
                $total_present = $total_students - $total_absent;

                if ($total_present + $total_absent != $total_students) {
                    echo json_encode(['problem' => 'Mismatch in counts']);
                    exit;
                }

                // Create new attendance record
                $state = 'draft';

                $record_create_id = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'daily.attendance',
                    'create',
                    [[
                        'date' => $date,
                        'standard_id' => (int)$classId,
                        'user_id' => (int)$teacherId,
                        'state' => $state,
                        'school_id' => (int)$schoolId,
                        'sub_date' => $submissionDate,
                    ]]
                );

                if (isset($record_create_id['faultString'])) {
                    echo json_encode(["faultString" => $record_create_id['faultString']]);
                    exit;
                }
            }

            // Process absentees
            if (!empty($absentees)) {
                foreach ($absentees as $entry) {
                    $entryA = (int)$entry;

                    // Check if student attendance line exists
                    $lineid = $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'daily.attendance.line',
                        'search',
                        [[['standard_id', '=', $record_create_id], ['stud_id', '=', $entryA]]]
                    );

                    if (!empty($lineid)) {
                        // Update existing absentee record
                        $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'daily.attendance.line',
                            'write',
                            [[$lineid[0]], ['is_absent' => true, 'is_present' => false, 'att' => 'absent']]
                        );
                    } else {
                        // Create new absentee record
                        $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'daily.attendance.line',
                            'create',
                            [[
                                'standard_id' => $record_create_id,
                                'stud_id' => $entryA,
                                'is_absent' => true,
                                'is_present' => false,
                                'att' => 'absent',
                            ]]
                        );
                    }
                }
            }

            // Update attendance summary
            $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'daily.attendance',
                'write',
                [[$record_create_id], [
                    'total_student' => $total_students,
                    'total_presence' => $total_present,
                    'total_absent' => $total_absent,
                    'state' => 'validate',
                ]]
            );

            echo json_encode(['record_create_id' => $record_create_id, 'message' => 'Attendance updated successfully']);
        } else {
            echo json_encode(['error' => 'Missing required parameters']);
        }
    }
}
