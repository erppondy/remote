<?php
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

$url = $publicURL;

$connectArray = null;
$connect = fopen($url, "r");
if($connect){
    $connectArray = array(
        'connected' => 'true'
    );
}else{
    $connectArray = array(
        'connected' => $connect
    );
}

echo json_encode($connectArray);