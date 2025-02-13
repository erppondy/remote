<?php
function isSiteAvailible($url){
    // Check, if a valid url is provided
    if(!filter_var($url, FILTER_VALIDATE_URL)){
        return false;
    }

    // Initialize cURL
    $curlInit = curl_init($url);
    
    // Set options
    curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
    curl_setopt($curlInit,CURLOPT_HEADER,true);
    curl_setopt($curlInit,CURLOPT_NOBODY,true);
    curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

    // Get response
    $response = curl_exec($curlInit);
    
    // Close a cURL session
    curl_close($curlInit);

    return $response?true:false;
}

function serverUnReachable(){
    return  array(
        'message'=> 'failed',
        'error'=> 'ERP server not reachable',
    );
}
?>