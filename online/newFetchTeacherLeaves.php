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

    $staff_id = (int)$_GET['user_id'];
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
        $teacherLeaveRequests = $models->execute_kw(
            $dbname,
            $uid,
            $password,
            'teacher.leave.request',
            'search_read',
            [[['staff_id', '=', $staff_id]]],
            array('fields' => array('name', 'school_id', 'staff_id', 'start_date', 'end_date', 'user_id', 'days', 'reason', 'state', 'leave_type', 'half_day_start_type', 'half_day_end_type', 'half_day_start', 'half_day_end'))
        );
    
        if (!isset($teacherLeaveRequests['faultString']) && isset($teacherLeaveRequests) && $teacherLeaveRequests != false) {
            $teacherLeaveAllocation = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.allocation',
                'search_read',
                [[['teacher_id', '=', $staff_id]]],
                array('fields' => array('teacher_id', 'name'))
            );
            $leaveTypes = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'leave.type',
                'search_read',
                array(
                    array(
                        array('name', '!=', FALSE),
                    ),
                ),
                array('fields' => array('name', 'description', 'total_leaves'))
            );
            $teacherLeaveAllocationLine = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.allocation.line',
                'search_read',
                [[['allocation_id', '!=', false]]],
                array('fields' => array('leave_type_id', 'allocated_leaves'))
            );
            
            foreach ($teacherLeaveRequests as &$leaveRequest) {
                $leaveRequest['allocated_leaves'] = null;
                if($leaveRequest['name'][0]==1 || $leaveRequest['name'][0]==4){
                    foreach ($leaveTypes as $leaveType) {
                        if ($leaveRequest['name'][0] == $leaveType['id']) {
                            $leaveRequest['allocated_leaves'] = $leaveType['total_leaves'];
                            break;
                        }
                    }
                } else {
                    foreach ($teacherLeaveAllocationLine as $allocationLine) {
                        if ($leaveRequest['name'][0] == $allocationLine['leave_type_id'][0]) {
                            $leaveRequest['allocated_leaves'] = $allocationLine['allocated_leaves'];
                            break;
                        }
                    }
                }                          
            }

            foreach ($teacherLeaveRequests as &$leaveRequest) {
                $total_cl_days = 0;
                foreach ($teacherLeaveRequests as &$leaveRequest1){
                    if ($leaveRequest['name'][0] === $leaveRequest1['name'][0]) {
                        $total_cl_days += $leaveRequest1['days'];
                    }
                }
                foreach ($teacherLeaveRequests as &$leaveRequest1) {
                    $leaveRequest['availed_leaves'] = ($leaveRequest1['name'][0]) ? $total_cl_days : null;                    
                }
            }

            
            unset($leaveRequest);
            
            $response = array(
                "message" => "success",
                "uid" => $uid,
                "teacherLeaveRequests" => $teacherLeaveRequests
            );
            
            echo json_encode($response);
        } else {
            $response = array(
                'val' => 'error',
                'error' => $teacherLeaveRequests,
                'uid' => $uid,
                'faultString' => array(!isset($teacherLeaveRequests['faultString']), isset($teacherLeaveRequests), $teacherLeaveRequests != false)
            );
            echo json_encode($response);
        }
    }      
}
