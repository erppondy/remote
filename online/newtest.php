<?php

$dbname = 'odoo_test';
$password = '123456';
$user = 'bassam22mji@gmail.com';
$privateURL = "http://10.184.51.70:8069";
$publicURL = "http://10.184.51.70:8069";

// mention local or public url
// $url = $privateURL;

$url = $publicURL;
require_once './ripcord/ripcord.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'get') {
    $common = ripcord::client($url . '/xmlrpc/2/common');

    $uid = $common->authenticate($dbname, $user, $password, array());

    $models = ripcord::client("$url/xmlrpc/2/object");
    header('Content-Type: application/json');
    $table = $_GET['table'];

    $res = $models->execute_kw(
        $dbname,
        $uid,
        $password,
        $table,
        'fields_get',
        array(),
        array('attributes' => array('string','help', 'type'))
    );

    echo json_encode(
        array(
            'f'=> $res
        )
    );
}
