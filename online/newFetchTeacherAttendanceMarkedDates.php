<?php

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

$url = $publicURL;

require_once './ripcord/ripcord.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['userName']) || !isset($_GET['userPassword']) || !isset($_GET['academic_year'])) {
        echo json_encode(["error" => "Missing required parameters"]);
        exit;
    }

    $user = $_GET['userName'];
    $password = $_GET['userPassword'];
    $academic_year = $_GET['academic_year'];
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

    $academic_year = $models->execute_kw(
        $dbname,
        $uid,
        $password,
        'academic.year',
        'search_read',
        [[['name', '=', $academic_year]]],
        ['fields' => ['id']]
    );

    if (empty($academic_year)) {
        echo json_encode(["error" => "Academic year not found $academic_year and $academic_year_name"]);
        exit;
    }

    $academic_year_id = $academic_year[0]['id'];

    $attendance_dates = $models->execute_kw(
        $dbname,
        $uid,
        $password,
        'teacher.daily.attendance',
        'search_read',
        [[['year', '=', $academic_year_id]]],
        ['fields' => ['date']]
    );
    if (!empty($attendance_dates)) {
        $dates = array_column($attendance_dates, 'date');
        echo json_encode(["attendance_dates" => $dates]);
    } else {
        echo json_encode(["message" => "No attendance records found for the given academic year"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}