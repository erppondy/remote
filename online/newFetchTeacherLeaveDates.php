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
        'teacher.leave.request',
        'search_read',
        [[['staff_id', '!=', false]]],
        ['fields' => ['start_date', 'end_date']]
    );
    $leave_dates = [];
    $start_range = new DateTime('2024-04-01');
    $end_range = new DateTime('2025-03-31');

    foreach ($attendance_dates as $leave) {
        $start_date = new DateTime($leave['start_date']);
        $end_date = new DateTime($leave['end_date']);

        if ($end_date < $start_range || $start_date > $end_range) {
            continue;
        }

        if ($start_date < $start_range) {
            $start_date = clone $start_range;
        }
        if ($end_date > $end_range) {
            $end_date = clone $end_range;
        }

        while ($start_date <= $end_date) {
            $leave_dates[$start_date->format('Y-m-d')] = ['date' => $start_date->format('Y-m-d'), 'isWeekend' => false];
            $start_date->modify('+1 day');
        }
    }

    $current_date = clone $start_range;
    while ($current_date <= $end_range) {
        $formatted_date = $current_date->format('Y-m-d');
        if ($current_date->format('N') == 6 || $current_date->format('N') == 7) {
            $leave_dates[$formatted_date] = ['date' => $formatted_date, 'isWeekend' => true];
        }
        $current_date->modify('+1 day');
    }

    $all_dates = array_values($leave_dates);
    usort($all_dates, function ($a, $b) {
        return strcmp($a['date'], $b['date']);
    });

    echo json_encode(["leave_dates" => $all_dates]);
} else {
    echo json_encode(["error" => "Invalid request method"]);
}