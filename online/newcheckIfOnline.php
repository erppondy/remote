<?php
// header('Content-Type:text/plain');
// http_response_code(201);
// $http_response_header;
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");


$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";

// $url = $privateURL;
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
