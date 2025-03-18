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

    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];
    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'erp_prod';
    }
    if (isset($entityBody['udise'])) {
        $udise = $entityBody['udise'];
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,
    );
    if (isset($entityBody['sync'])) {
        $response = array();

        $leaveTypeName = $entityBody['leaveTypeId'];
        $startDate = $entityBody['start_date'];
        $endDate = $entityBody['end_date'];
        $schoolId = $entityBody['schoolId'];
        $teacherId = $entityBody['teacherId'];
        $reason = $entityBody['reason'];
        $leaveType = $entityBody['leave_type'];
        $half_day_start = $entityBody['half_day_start'];
        $half_day_end = $entityBody['half_day_end'];
        $half_day_end_type = $entityBody['half_day_end_type'];
        $half_day_start_type = $entityBody['half_day_start_type'];

        if (
            isset($endDate)
            && isset($startDate)
            && isset($schoolId)
            && isset($teacherId)
            && isset($reason)
            && isset($leaveTypeName)
        ) {
            $common = ripcord::client($url . '/xmlrpc/2/common');

            $uid = $common->authenticate($dbname, $user, $password, $context);

            $models = ripcord::client("$url/xmlrpc/2/object");

            $recordCreateId = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.request',
                'create',
                array(
                    array(
                        'name'=>(int) $leaveTypeName,
                        'school_id'=>(int)$schoolId,
                        'staff_id'=>(int) $teacherId,
                        'start_date'=> $startDate,
                        'end_date'=> $endDate,
                        'reason'=> $reason,
                        'leave_type' => $leaveType,
                        'half_day_start' => (bool)$half_day_start,
                        'half_day_end' => (bool)$half_day_end,
                        'half_day_start_type'=>$half_day_start_type,
                        'half_day_end_type'=>$half_day_end_type,
                        'state'=> 'toapprove'
                    )
                )
            );
            
            $response = array(
                'record_create_id' => $recordCreateId,
            );
            echo json_encode($response);            
        } else {
            echo json_encode(
                array(
                    't' => $entityBody,
                )
            );
        }
    }
}