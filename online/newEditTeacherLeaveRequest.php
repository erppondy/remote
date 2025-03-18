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
    $leave_request_id = $entityBody['leave_request_id'];

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

    if (isset($leave_request_id)) {
        $common = ripcord::client($url . '/xmlrpc/2/common');
        $uid = $common->authenticate($dbname, $user, $password, $context);
        $models = ripcord::client("$url/xmlrpc/2/object");
        if (!$uid) {
            echo json_encode(['error' => 'Authentication failed']);
            exit;
        }
        
        $updateData = array();
        if (isset($entityBody['start_date'])) {
            $updateData['start_date'] = $entityBody['start_date'];
        }
        if (isset($entityBody['end_date'])) {
            $updateData['end_date'] = $entityBody['end_date'];
        }
        if (isset($entityBody['leaveTypeId'])) {
            $updateData['name'] = (int) $entityBody['leaveTypeId'];
        }
        if (isset($entityBody['reason'])) {
            $updateData['reason'] = $entityBody['reason'];
        }
        if (isset($entityBody['leave_type'])) {
            $updateData['leave_type'] = $entityBody['leave_type'];
        }
        if (isset($entityBody['half_day_start'])) {
            $updateData['half_day_start'] = (bool) $entityBody['half_day_start'];
        }
        if (isset($entityBody['half_day_end'])) {
            $updateData['half_day_end'] = (bool) $entityBody['half_day_end'];
        }
        if (isset($entityBody['half_day_start_type'])) {
            $updateData['half_day_start_type'] = $entityBody['half_day_start_type'];
        }
        if (isset($entityBody['half_day_end_type'])) {
            $updateData['half_day_end_type'] = $entityBody['half_day_end_type'];
        }

        if (!empty($updateData)) {
            $update_status = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.leave.request',
                'write',
                array(
                    array($leave_request_id),
                    $updateData
                )
            );

            if ($update_status) {
                echo json_encode(array(
                    'message' => 'Record updated successfully',
                    'updated_id' => $leave_request_id,
                    'debug' => $update_status
                ));
            } else {
                echo json_encode(array(
                    'error' => 'Failed to update record',
                    'debug' => $update_status
                ));
            }
        } else {
            echo json_encode(array(
                'error' => 'No valid fields to update'
            ));
        }
    } else {
        echo json_encode(array(
            'error' => 'leave_request_id is required'
        ));
    }
}