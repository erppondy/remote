<?php
/*
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// require_once './extra.php';
$login_status = null;
$userID = null;
$dbname = 'erp_prod';
$userName = 'boss';
$userPassword = 'boss';


$privateURL = "http://192.168.0.139:80";
$publicURL = "http://192.168.0.139:80";

// mention local or public url
// $url = $privateURL;

$url = $publicURL;

$arr = array();
require_once './ripcord/ripcord.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" || $_SERVER['REQUEST_METHOD'] == 'GET') {
    // file_get_contents will use read only raw data stream php://input
    // to get a json like data object
    $entityBodyJSON = file_get_contents('php://input');
    // decode the afore mentoned json like object
    $entityBody = json_decode($entityBodyJSON);
    // echo json_encode(
    //     array(
    //         "p"=> $entityBody,
    //         'g'=> gettype($entityBody),
    //     )
    // );

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(array("message" => "Invalid JSON"));
        exit;
    }

    if (isset($entityBody->user) && isset($entityBody->password)) {
        $userName = $entityBody->user;
        $userPassword = $entityBody->password;
    } else {
        echo json_encode(array("message" => "Missing user or password"));
        exit;
    }
    
    if (isset($entityBody->dbname)) {
        $dbname = $entityBody->dbname;
    } else {
        $dbname = 'erp_prod';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');

    // echo "13";

    $userID = $common->authenticate('erp_prod', $userName, $userPassword, array());
    error_log("Login User ID: $userID, User Name: $userName");

    if (empty($userID) || !isset($userID) || $userID == 0 || $userID == false) {

        $arr = array(
            "message" => "Invalid credentials",
            'login_status' => 0,
            'dbname' => $dbname
        );
        echo json_encode($arr);
    } else {
        
        $models = ripcord::client("$url/xmlrpc/2/object");
        // sleep(1);
        
        $record = $models->execute_kw(
            $dbname,
            $userID,
            $userPassword,
            'school.school',
            'search_read',
            array(
                array(
                    array(
                        'email', '=', $userName,
                    )
                ),
            ),
            array(
                'fields' => array('id','email', 'com_name'),
            ),
        );

        error_log("Record content: " .print_r($record, true));
        if (isset($record) && !isset($record['faultString']) && isset($record[0]['id'])) {
            /// is headmaster
            $arr = array(
                'user' => $userName,
                'password' => $userPassword,
                'dbname' => $dbname,
                'login_status' => 1,
                'userID' => $userID,
                'headMaster' => 'yes',
                'schoolId' => $record[0]['id'],
                'isOnline' => 1
            );
            echo json_encode($arr);
        } else {
            /// is not headmaster
            $school = $models->execute_kw(
                $dbname,
                $userID,
                $userPassword,
                'school.school',
                'search_read',
                array(
                    array(
                        array(
                            'name', '!=', FALSE
                        )
                    )
                ),
                array(
                    'fields' => array(
                        'com_name'
                    ),
                ),
            );
            
            //$schoolId = $school[0]->id;
            // Debugging output to inspect the structure of $school
            //error_log('School Data: ' . print_r($school, true));
            if (is_array($school)) {
                error_log("First element: " . print_r($school[0], true));
            } else {
                error_log("School is not an array: " . print_r($school, true));
            }

            // Check if $school is an array and not empty
            if (is_array($school) && !empty($school)) {
                // Check if the first element is an object and has an 'id' property
                if (isset($school[0]) && isset($school[0]['id'])) {
                    $schoolId = $school[0]['id'];
                } else {
                    // Handle the case where the 'id' property is not available
                    $schoolId = null; // or handle appropriately
                }
            } else {
                // Handle the case where $school is empty or not an array
                $schoolId = null; // or handle appropriately
            }
            
            echo json_encode(
                array(
                    'user' => $entityBody->user,
                    'password' => $entityBody->password,
                    'dbname' => $dbname,
                    'login_status' => 1,
                    'userID' => $userID,
                    'schoolId' => $schoolId,
                    'headMaster' => 'no',
                    'isOnline' => 1
                )
            );
        }
    }
} else {
    echo json_encode(array(
        "message" => "failed", "code" => "not post request", 'j' => $_SERVER['REQUEST_METHOD']
    ));
}*/


header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// require_once './extra.php';
$login_status = null;
$userID = null;
$dbname = 'erp_prod';
$userName = null;
$userPassword = null;


$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";

// mention local or public url
// $url = $privateURL;

$url = $publicURL;

$arr = array();
require_once './ripcord/ripcord.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" || $_SERVER['REQUEST_METHOD'] == 'GET') {
    // Read raw JSON input
    $entityBodyJSON = file_get_contents('php://input');
    $entityBody = json_decode($entityBodyJSON);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(array("message" => "Invalid JSON"));
        exit;
    }

    // Check if user, password, and udise are set
    if (isset($entityBody->user) && isset($entityBody->password) && isset($entityBody->udise)) {
        $userName = $entityBody->user;
        $userPassword = $entityBody->password;
        $udise = $entityBody->udise;  // Get the udise value
        $login_type = $entityBody->login_type;
        echo json_encode(array("message" => "User ok"));
    } else {
        echo json_encode(array("message" => "Missing user, password, or udise"));
        exit;
    }

    // Check for database name, if not present, default to 'erp_prod'
    if (isset($entityBody->dbname)) {
        echo json_encode(array("message" => "Db ok"));
        $dbname = $entityBody->dbname;
    } else {
        $dbname = 'erp_prod';
    }
    echo json_encode(array("message" => "Test"));
    $common = ripcord::client($url . '/xmlrpc/2/common');
    echo json_encode(array("message" => "Test"));
    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );
    
    $userID = $common->authenticate($dbname, $userName, $userPassword, $context);
    echo json_encode(array("message" => "UserID: $userID"));
    error_log("Login User ID: $userID, User Name: $userName");

    if (empty($userID) || !isset($userID) || $userID == 0 || $userID == false) {

        $arr = array(
            "message" => "Invalid credentials",
            'login_status' => 0,
            'dbname' => $dbname
        );
        
        echo json_encode($arr);
    } else {
        echo json_encode(array("message" => "Credentials ok"));
        $models = ripcord::client("$url/xmlrpc/2/object");
        // sleep(1);
        
        $record = $models->execute_kw(
            $dbname,
            $userID,
            $userPassword,
            'school.school',
            'search_read',
            array(
                array(
                    array(
                        'email', '=', $userName,
                    )
                ),
            ),
            array(
                'fields' => array('id','email', 'com_name'),
            ),
        );

        error_log("Record content: " .print_r($record, true));
        if (isset($record) && !isset($record['faultString']) && isset($record[0]['id'])) {
            /// is headmaster
            $arr = array(
                'user' => $userName,
                'password' => $userPassword,
                'dbname' => $dbname,
                'login_status' => 1,
                'userID' => $userID,
                'headMaster' => 'yes',
                'schoolId' => $record[0]['id'],
                'isOnline' => 1
            );
            echo json_encode(array("message" => "Headmaster"));
            echo json_encode($arr);
        } else {
            /// is not headmaster
            $school = $models->execute_kw(
                $dbname,
                $userID,
                $userPassword,
                'school.school',
                'search_read',
                array(
                    array(
                        array(
                            'name', '!=', FALSE
                        )
                    )
                ),
                array(
                    'fields' => array(
                        'com_name'
                    ),
                ),
            );
            
            //$schoolId = $school[0]->id;
            // Debugging output to inspect the structure of $school
            //error_log('School Data: ' . print_r($school, true));
            if (is_array($school)) {
                echo json_encode(array("message" => "School array"));
                error_log("First element: " . print_r($school[0], true));
            } else {
                echo json_encode(array("message" => "School not Array"));
                error_log("School is not an array: " . print_r($school, true));
            }

            // Check if $school is an array and not empty
            if (is_array($school) && !empty($school)) {
                // Check if the first element is an object and has an 'id' property
                if (isset($school[0]) && isset($school[0]['id'])) {
                    $schoolId = $school[0]['id'];
                } else {
                    // Handle the case where the 'id' property is not available
                    $schoolId = null; // or handle appropriately
                }
            } else {
                // Handle the case where $school is empty or not an array
                $schoolId = null; // or handle appropriately
            }
            echo json_encode(array("message" => "School"));
            echo json_encode(
                array(
                    'user' => $entityBody->user,
                    'password' => $entityBody->password,
                    'dbname' => $dbname,
                    'login_status' => 1,
                    'userID' => $userID,
                    'schoolId' => $schoolId,
                    'headMaster' => 'no',
                    'isOnline' => 1
                )
            );
        }

    }
} else {
    echo json_encode(array("message" => "Errror"));
    echo json_encode(array(
        "message" => "failed", "code" => "not post request", 'j' => $_SERVER['REQUEST_METHOD']
    ));
}
?>

