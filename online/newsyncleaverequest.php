<?php

$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";

// $url = $privateURL;
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
    // echo "POST request recieved to give attendance data";

    $entityBodyJSON = file_get_contents("php://input");

    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    // echo json_encode($entityBody);

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];

    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'odoo_test';
    }
    if (isset($entityBody['udise'])) {
        $udise = $entityBody['udise'];  // Capture udise value if provided
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );
    if (isset($entityBody['sync'])) {
        $response = array();

        $leaveTypeName = $entityBody['leaveTypeId'];

        $startDate = $entityBody['start_date'];
        $endDate = $entityBody['end_date'];
        $schoolId = $entityBody['schoolId'];
        $teacherId = $entityBody['teacherId'];

        $days = $entityBody['days'];
        $reason = $entityBody['reason'];
        $place = $entityBody['place'];

        if (
            isset($endDate)
            && isset($startDate)
            && isset($schoolId)
            && isset($teacherId)
            && isset($days)
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
                        // 'user_id'=> (int) 12,
                        'staff_id'=>(int) $teacherId,
                        'start_date'=> $startDate,
                        'end_date'=> $endDate,
                        'reason'=> $reason,
                        'place'=> $place,
                        'days'=> (int) $days,
                        'state'=> 'toapprove'
                    )
                )
            );
            

            echo json_encode($recordCreateId);
            
        } else {
            echo json_encode(
                array(
                    't' => $entityBody,
                )
            );
        }
    }
}
