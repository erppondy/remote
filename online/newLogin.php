<?php
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


$privateURL = "http://10.0.1.4:8069";
$publicURL = "http://10.0.1.4:8069";

// mention local or public url
// $url = $privateURL;

$url = $publicURL;
$key = '1ed890c66194ca9bed84a7a0c7caf4ab';
$arr = array();
require_once './ripcord/ripcord.php';

function encryptPassword($password, $key) {
    $cipher = "aes-256-cbc";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $encrypted = openssl_encrypt($password, $cipher, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}
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
    } else {
        echo json_encode(array("message" => "Missing user, password, or udise"));
        exit;
    }

    // Check for database name, if not present, default to 'erp_prod'
    if (isset($entityBody->dbname)) {
        $dbname = $entityBody->dbname;
    } else {
        $dbname = 'erp_prod';
    }

    $common = ripcord::client($url . '/xmlrpc/2/common');

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );
    
    $userID = $common->authenticate($dbname, $userName, $userPassword, $context);

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
        $encryptedPassword = encryptPassword($userPassword, $key); 
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
                    'password' => $userPassword,
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
}
?>

