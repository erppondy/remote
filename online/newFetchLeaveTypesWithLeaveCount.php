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
            array('fields' => array('name', 'school_id', 'staff_id', 'start_date', 'end_date', 'user_id', 'days', 'reason', 'state'))
        );
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
        if (!empty($teacherLeaveAllocation) && isset($teacherLeaveAllocation[0]['id'])) {
            $allocation_id = $teacherLeaveAllocation[0]['id'];
        
            $teacherLeaveAllocationLine = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.allocation.line',
                'search_read',
                [[['allocation_id', '=', $allocation_id]]],
                array('fields' => array('leave_type_id', 'allocated_leaves'))
            );
        } else {
            $teacherLeaveAllocationLine = array("error" => "No valid allocation ID found");
        }
        foreach ($leaveTypes as &$leaveType) {
            if ($leaveType['id'] != 1 && $leaveType['id'] != 4) {
                $leaveType['total_leaves'] = 7;
            }           
            foreach ($teacherLeaveAllocationLine as $allocationLine) {
                if ($leaveType['id'] == $allocationLine['leave_type_id'][0]) {
                    $leaveType['total_leaves'] = $allocationLine['allocated_leaves'];
                    break;
                }
            }
        }
        unset($leaveType);
        $availedLeaves = [];
        if (!empty($teacherLeaveRequests) && is_array($teacherLeaveRequests)) {
            foreach ($teacherLeaveRequests as $leaveRequest) {
                if (isset($leaveRequest['name'][0]) && isset($leaveRequest['days'])) {
                    $leaveTypeId = $leaveRequest['name'][0];
                    $days = $leaveRequest['days'];

                    if (!isset($availedLeaves[$leaveTypeId])) {
                        $availedLeaves[$leaveTypeId] = 0;
                    }
                    $availedLeaves[$leaveTypeId] += $days;
                }
            }
        }
        foreach ($leaveTypes as &$leaveType) {
            $leaveTypeId = $leaveType['id'];
            $leaveType['availed_leaves'] = isset($availedLeaves[$leaveTypeId]) ? $availedLeaves[$leaveTypeId] : 0;
        }
        unset($leaveType);
        $response = array(
            "message" => "success",
            "uid" => $uid,
            "leaveTypes" => $leaveTypes
        );       
        echo json_encode($response);
    }
}