<?php

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

$url = $publicURL;

$password = null;
$user = null;
$dbname = null;

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
    $session = $_GET['session'];
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
    $school_details= $models->execute_kw(
        $dbname,
        $uid,
        $password,
        'school.school',
        'search_read',
        [[['code','=',$udise]]],
        array('fields' => array('id'))
    );

    $attendanceRecords = $models->execute_kw(
        $dbname,
        $uid,
        $password,
        'teacher.daily.attendance',
        'search_read',
        [[['school_id','=', (int)$school_details[0]['id']],['date', '=', $date],['session','=',$session]]],
        array('fields' => array('id', 'date', 'school_id', 'total_teacher', 'total_presence', 'total_absent', 'state', 'session', 'create_date'))
    );
    foreach($attendanceRecords as &$attendanceRecord){
        $attendanceRecord['attendanceRecordLines'] =[];
        $attendanceRecord['isAttendanceMarked'] = true;
        $attendanceRecordLines = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'teacher.daily.attendance.line',
            'search_read',
            [[['teachers_ids', '=', (int)$attendanceRecord['id']],['school_id','=', (int)$school_details[0]['id']]]],
            array('fields' => array('id', 'teacher_id', 'reason_id', 'remarks', 'is_present', 'is_absent', 'session'))
        );
        $attendanceRecord['attendanceRecordLines'] = $attendanceRecordLines;
    }

    if (!empty($attendanceRecords)) {
        echo json_encode(array(
            'message' => 'success',
            'attendance_records' => $attendanceRecords,
        ));
    } else {
        $teacherDataRecords = [];
        $teacherDataRecords[0]['isAttendanceMarked'] = false;
        
        $teacherDatas = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'school.teacher',
            'search_read',
            array(
                array(
                    array('employee_id', '!=', FALSE),
                ),
            ),
            array('fields' => array('name'))
        );
        
        $displayTeacherDatas=[];
        foreach($teacherDatas as &$teacherData){
            $teacherLeaveRequests = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.request',
                'search_read',
                array(
                    array(
                        array('staff_id', '=', $teacherData['id']),
                        array('start_date', '<=', $date),
                        array('end_date', '>=', $date),
                    )
                ),
                array('fields' => array('name', 'school_id', 'staff_id', 'start_date', 'end_date', 'user_id', 'days', 'reason', 'state', 'leave_type', 'half_day_start_type', 'half_day_end_type', 'half_day_start', 'half_day_end'))
            );
            $reasonId = false;
            if(!empty($teacherLeaveRequests)){
                if($teacherLeaveRequests[0]['name'][0]==1){
                    if(($teacherLeaveRequests[0]['start_date'] == $teacherLeaveRequests[0]['end_date']) && $date == $teacherLeaveRequests[0]['start_date']){
                        if($teacherLeaveRequests[0]['leave_type'] == 'second_half'){
                            if($session=='afternoon'){
                                $reasonId = $teacherLeaveRequests[0]['name'];
                            }
                        }else if($teacherLeaveRequests[0]['leave_type'] == 'first_half'){
                            if($session=='morning'){
                                $reasonId = $teacherLeaveRequests[0]['name'];
                            }
                        }else if($teacherLeaveRequests[0]['leave_type'] == 'full'){
                            $reasonId = $teacherLeaveRequests[0]['name'];
                        }
                    }else if($date == $teacherLeaveRequests[0]['start_date']){
                        if($teacherLeaveRequests[0]['half_day_start']){
                            if($session=='afternoon'){
                                $reasonId = $teacherLeaveRequests[0]['name'];
                            }
                        }else{
                            $reasonId = $teacherLeaveRequests[0]['name'];
                        }
                    }else if($date == $teacherLeaveRequests[0]['end_date']){
                        if($teacherLeaveRequests[0]['half_day_end']){
                            if($session=='morning'){
                                $reasonId = $teacherLeaveRequests[0]['name'];
                            }
                        }else{
                            $reasonId = $teacherLeaveRequests[0]['name'];
                        }
                    }else{
                        $reasonId = $teacherLeaveRequests[0]['name'];
                    }
                }else{
                    $reasonId = $teacherLeaveRequests[0]['name'];
                }
            }
            $document = [
                'teacher_id' => [$teacherData['id'], $teacherData['name']],
                'reason_id' => $reasonId,
            ];
            $displayTeacherDatas[] = $document;
        }

        $teacherDataRecords[0]['attendanceRecordLines']= $displayTeacherDatas;    
        echo json_encode(array(
            'message' => 'no records found',
            'attendance_records' => $teacherDataRecords,
        ));
    }
    
} else {
    echo json_encode(array(
        'message' => 'failed',
        'error' => 'Invalid request method',
    ));
}