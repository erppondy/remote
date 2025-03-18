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
    $isHM = (bool) $_GET['is_hm'];

    if($isHM){
        $session = $_GET['session'];
        if($session === 'morning'){
            $response = array(
                'time' => '11:00'
            );
        }elseif($session === 'afternoon'){
            $response = array(
                'time' => '15:00'
            );
        }
    }else{
        $response = array(
            'time' => '11:00'
        );
    }
    echo json_encode($response);
}
