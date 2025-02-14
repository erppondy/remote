<?php
$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";

$url = $privateURL;
// $url = $publicURL;


$password = null;
$user = null;
$dbname =  null;

require_once './ripcord/ripcord.php';

$failed = array('res' => 'failed');

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'post') {
    header('Access-Control-Allow-Origin: *', false);
    header('Access-Control-Allow-Methods: GET, POST');

    header("Access-Control-Allow-Headers: X-Requested-With");
    header('Content-Type: application/json');

    $entityBodyJSON = file_get_contents("php://input");

    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];

    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'odoo_test';
    }
    if (isset($entityBody['udise'])) {
        $udise = $entityBody['udise'];  // Capture udise value if provided
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );

    // if parameters are passed
    if (isset($user) && isset($password)) {
        $common = ripcord::client($url . '/xmlrpc/2/common');

        $uid = $common->authenticate($dbname, $user, $password, $context);

        // if credentials are correct
        if (!isset($uid['faultString']) && isset($uid)) {
            $models = ripcord::client("$url/xmlrpc/2/object");
            $state = 'draft';
            $year = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'academic.year',
                'search_read',
                array(
                    array(
                        array('current', '=', True),
                    ),
                ),
                array('fields' => array('name'))
            );
            $academic_year_id = $year[0]['id'];

            // if there is current academic year
            if (isset($academic_year_id)) {
                // if models is successfully generated
                if (isset($models)) {

                    // if sync is numeric
                    if (isset($entityBody['numeric'])) {
                        $date = $entityBody['date'];
                        $className = $entityBody['className'];
                        $classId = $entityBody['classId'];
                        $teacherId = $entityBody['teacherId'];
                        $schoolId = $entityBody['schoolId'];
                        $entries = $entityBody['entries'];

                        
                        $name = 'Result of FLN-Numeric Ability Assessment of Class ' . $className . ' on ' . $date;

                        $record_create_id = $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'fln.examresult',
                            'create',
                            array(
                                array(
                                    'year_id' => $academic_year_id,
                                    'school_id' => $schoolId,
                                    'academic_class' => $classId,
                                    'date' => $date,
                                    'state' => $state,
                                    'exam_type' => 'numeric',
                                    'user_id' => $teacherId,
                                    'name' => $name
                                )
                            ),
                        );
                        if (!isset($record_create_id['faultString'])) {

                            $records = [];

                            foreach ($entries as $entryQ) {
                                $entry = get_object_vars($entryQ);

                                foreach ($entry as $key => $val) {
                                    $sid = (string) $key;
                                    $lineid = $models->execute_kw(
                                        $dbname,
                                        $uid,
                                        $password,
                                        'numeric.examresult.line',
                                        'search',
                                        array(
                                            array(
                                                array('mark_id', '=', $record_create_id),
                                                array('name', '=', (int)$sid)
                                            ),
                                        )
                                    );
                                    if (!isset($lineid['faultString']) && isset($lineid) && count($lineid) > 0) {
                                        $resultI = $val[1];
                                        if ($resultI == 'acc') {
                                            $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $password,
                                                'numeric.examresult.line',
                                                'write',
                                                array(array($lineid[0]), array(
                                                    'numeric_level' => $val[0],
                                                    'marks' => $val[1]
                                                ))
                                            );
                                        } else {
                                            $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $password,
                                                'numeric.examresult.line',
                                                'write',
                                                array(array($lineid[0]), array(
                                                    'marks' => $val[1]
                                                ))
                                            );
                                        }
                                        $models->execute_kw(
                                            $dbname,
                                            $uid,
                                            $password,
                                            'fln.examresult',
                                            'write',
                                            array(array($record_create_id), array(
                                                'state' => 'done'
                                            ))
                                        );
                                        $write_done = true;
                                    }

                                    $result = array(
                                        $sid => $lineid
                                    );
                                    array_push($records, $result);
                                }
                            } // end of for every assessmentRecord

                            echo json_encode(array(
                                'rc' => $record_create_id,
                                'classId' => $classId,
                                'date' => $date,
                                'stringData' => $entries

                            ));
                        } else {
                            $response = array(
                                'fields' => $record_create_id['faultString']
                            );

                            echo json_encode($response);
                        }
                    } // end if sync is numeric

                    else {
                        // if sync is basic
                        if (isset($entityBody['basic'])) {

                            $entries = $entityBody['entries'];
                            $date = $entityBody['date'];
                            $teacherId = $entityBody['teacherId'];
                            $schoolId = $entityBody['schoolId'];
                            $classId = $entityBody['classId'];
                            $className = $entityBody['className'];
                            $language = $entityBody['language'];
                            $langId = $entityBody['langId'];

                            $name = 'Result of ' . $language . ' FLN-Basic Ability Assessment of Class ' . $className . ' on ' . $date;

                            $state = 'draft';

                            $record_create_id = $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'fln.examresult',
                                'create',
                                array(
                                    array(
                                        'year_id' => $academic_year_id,
                                        'school_id' => (int)$schoolId,
                                        'academic_class' => (int)$classId,
                                        'date' => $date,
                                        'state' => $state,
                                        'exam_type' => 'basic',
                                        'user_id' => (int)$teacherId,
                                        'name' => $name,
                                        'medium' => (int)$langId
                                    )
                                ),
                            );

                            if (!isset($record_create_id['faultString'])) {
                                $records = [];

                                foreach ($entries as $entryQ) {
                                    $entry = get_object_vars($entryQ);
                                    foreach ($entry as $key => $val) {
                                        $sid = (string)$key;
                                        $lineid = $models->execute_kw(
                                            $dbname,
                                            $uid,
                                            $password,
                                            'reading.examresult.line',
                                            'search',
                                            array(
                                                array(
                                                    array('mark_id', '=', $record_create_id),
                                                    array('name', '=', (int)$sid)
                                                ),
                                            )
                                        );
                                        if (!isset($lineid['faultString']) && isset($lineid) && count($lineid) > 0) {
                                            $resultI = $val[1];
                                            if ($resultI == 'acc') {
                                                $models->execute_kw(
                                                    $dbname,
                                                    $uid,
                                                    $password,
                                                    'reading.examresult.line',
                                                    'write',
                                                    array(array($lineid[0]), array(
                                                        'reading_level' => $val[0],
                                                        'marks' => $val[1]
                                                    ))
                                                );
                                            } else {
                                                $models->execute_kw(
                                                    $dbname,
                                                    $uid,
                                                    $password,
                                                    'reading.examresult.line',
                                                    'write',
                                                    array(array($lineid[0]), array(
                                                        'marks' => $val[1]
                                                    ))
                                                );
                                            }
                                            $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $password,
                                                'fln.examresult',
                                                'write',
                                                array(array($record_create_id), array(
                                                    'state' => 'done'
                                                ))
                                            );
                                            $write_done = true;
                                        }
                                    } // end of for each $entry as key val

                                    $result = array(
                                        $sid => $lineid
                                    );
                                    array_push($records, $result);
                                } // end of for every entry
                                echo json_encode(array(
                                    'rc' => $record_create_id,
                                    'classId' => $classId,
                                    'date' => $date,
                                    'langauge' => $language,
                                    'langId' => $langId,
                                    'stringData' => $entries

                                ));
                            } else {
                                $response = array(
                                    'e' => $record_create_id['faultString']
                                );

                                echo json_encode($response);
                            }
                        } // end of if sync is basic

                        else {
                            // if sync is pace
                            if (isset($entityBody['pace'])) {

                                $assessmentName = $entityBody['assessmentName'];
                                $scheduledDate  = $entityBody['scheduledDate'];
                                $uploadDate = $entityBody['uploadDate'];
                                $schoolId = $entityBody['schoolId'];
                                $teacherId = $entityBody['teacherId'];
                                $className = $entityBody['className'];
                                $classId = $entityBody['classId'];
                                $qpCode = $entityBody['qpCode'];
                                $subjectId = $entityBody['subjectId'];
                                $mediumname = $entityBody['mediumName'];
                                $assessmenId = $entityBody['assessmentId'];
                                $standardId = $entityBody['standardId'];
                                $mediumId = $entityBody['mediumId'];
                                $entries = $entityBody['entries'];


                                $state = 'draft';

                                $record_create_id = $models->execute_kw(
                                    $dbname,
                                    $uid,
                                    $password,
                                    'pace.examresult',
                                    'create',
                                    array(
                                        array(
                                            'school_id' => (int) $schoolId,
                                            'year_id' => $academic_year_id,
                                            'user_id' => (int)$teacherId,
                                            'academic_class' => (int)$classId,
                                            'subject' => (int)$subjectId,
                                            'standard_id' => (int)$standardId,
                                            'name' => (int)$assessmenId,
                                            'medium' => (int) $mediumId,
                                            'date' => $uploadDate,
                                            'qp_code' => (int)$qpCode,
                                            'state' => $state
                                        )
                                    )
                                );

                                if (!isset($record_create_id['faultString'])) {
                                    $records = [];
                                    foreach ($entries as $entryVal) {
                                        $entry = get_object_vars($entryVal);
                                        // array_push($records, $entry, gettype($entry));
                                        $studentId = $entry['sId'];
                                        $result = $entry['res'];
                                        $percentage = $entry['percentage'];
                                        $sum = $entry['sum'];

                                        $lineid = $models->execute_kw(
                                            $dbname,
                                            $uid,
                                            $password,
                                            'pace.examresult.line',
                                            'search',
                                            array(
                                                array(
                                                    array('mark_id', '=', $record_create_id),
                                                    array('name', '=', (int)$studentId)
                                                ),
                                            )
                                        );
                                        if (isset($lineid)) {
                                            $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $password,
                                                'pace.examresult.line',
                                                'write',
                                                array(array($lineid[0]), array(
                                                    'marks' => (int)$sum,
                                                    'result' => $result
                                                ))
                                            );

                                            $models->execute_kw(
                                                $dbname,
                                                $uid,
                                                $password,
                                                'pace.examresult',
                                                'write',
                                                array(array($record_create_id), array(
                                                    'state' => 'done'
                                                ))
                                            );
                                            // echo json_encode(array('$lineid'=>array($sum, $result, $lineid, $studentId) ));
                                            $write_done = true;
                                        } else {
                                            echo json_encode($failed);
                                        }
                                    }
                                    $response = array(
                                        'e' => $record_create_id,

                                    );
                                    echo json_encode($response);
                                } // if record faulted pace
                                else {
                                    echo json_encode(array('f' => $standardId, 'fault' => $record_create_id['faultString']));
                                }
                            } // end of if sync is pace
                        } // end of else if sync is not basic
                    } // end of else if sync is not numeric

                } // end of if models is successfully generated
                else {
                    array_push($failed, array('t' => 'no ac1'));

                    echo json_encode($failed);
                }
            } // end of if current academic yaer
            else {
                array_push($failed, array('t' => 'no ac2'));

                echo json_encode($failed);
            }
        } // end of if credentials are correct
        else {
            array_push($failed, array('t' => 'no ac3'));

            echo json_encode($failed);
        }
    } // end of if parameters are passed
    else {
        array_push($failed, array('t' => 'no ac4'));

        echo json_encode($failed);
    }
} else {
    array_push($failed, array('t' => 'no ac5'));

    echo json_encode($failed);
}
