<?php

$privateURL = "http://10.0.1.5:8069";
$publicURL = "http://10.0.1.5:8069";

// $url = $privateURL;
$url = $publicURL;

$password = null;
$user = null;
$dbname = null;

require_once './ripcord/ripcord.php';
header('Access-Control-Allow-Origin: *', false);
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json');

header("Access-Control-Allow-Headers: X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $entityBodyJSON = file_get_contents("php://input");
    $entityBody = get_object_vars(json_decode($entityBodyJSON));

    $user = $entityBody['userName'];
    $password = $entityBody['userPassword'];

    if (isset($entityBody['dbname'])) {
        $dbname = $entityBody['dbname'];
    } else {
        $dbname = 'erp_prod';
    }
    if (isset($entityBody['udise'])) {
        $udise = $entityBody['udise'];  // Capture udise value if provided
    }

    $context = array(
        'login_type' => 'school',
        'udise' => $udise,  // Pass the UDISE code here
    );

    if(isset($entityBody['Persistent'])){
        $presentCount = $entityBody['present'];
        $absentCount = $entityBody['absent'];
        $attendanceSheet = $entityBody['attendanceSheet'];
        $date = $entityBody['date'];
        $submissionDate = $entityBody['submissionDate'];
        $userId = $entityBody['headMasterUserId'];
        
        error_log('Contents of AttendanceSheet: ' . print_r($attendanceSheet, true));
        error_log('Contents of submissionDate: ' . print_r($submissionDate, true));

        if(isset($date)){
            $weekDayCapital = date('l', strtotime($date));
            $weekDay = strtolower($weekDayCapital);

            $common = ripcord::client($url . '/xmlrpc/2/common');
            $uid = $common->authenticate($dbname, $user, $password, $context);

            $models = ripcord::client("$url/xmlrpc/2/object");

            $totalTeachers = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'school.teacher',
                'search_count',
                array(
                    array(
                        array('active', '=', TRUE),
                    ),
                ),
                
            );

            sleep(0.5);
            $school = $models->execute_kw(
                $dbname,
                $uid,
                $password,
                'school.school',
                'search_read',
                array(
                    array(
                        array('email','=', $user)
                    ),
                ),
                array(
                    'fields'=> array('com_name')
                ),
                
            );
            sleep(0.5);
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
                array(
                    'fields'=> array('name')
                ),
                
            );

            $schoolId = $school[0]['id'];
            $yearId = $year[0]['id'];
            sleep(0.5); 

            $pushEntry = $models-> execute_kw(
                $dbname,
                $uid,
                $password,
                'teacher.daily.attendance',
                'create',
                array(
                    array(
                        'date'=> $date,
                        'school_id'=> $schoolId,
                        'year'=> $yearId,
                        'create_uid'=> $userId,
                        'state'=> 'draft'
                    )
                ),
                
            );           

            if(isset($pushEntry['faultString'])){
                echo json_encode(
                    array(
                        'message'=> 'failed',
                        'create failed'=> $pushEntry,
                    )
                );
            }else{
                $iser = 0;
                $message = null;

                // Retrieve all active teachers
                $teachers = $models->execute_kw(
                    $dbname,
                    $uid,
                    $password,
                    'school.teacher',
                    'search_read',
                    array(
                        array(
                            array('active', '=', TRUE),
                        )
                    ),
                    array(
                        'fields' => array('id')
                    ),
                    
                );

                $attendanceMap = [];
                foreach ($attendanceSheet as $attendance) {
                    $attendanceMap[(int) $attendance->teacherId] = [
                        'is_absent' => true,
                        'reason_id' => (int) $attendance->reasonId,
                    ];
                }

                foreach ($teachers as $teacher) {
                    $teacherId = $teacher['id'];
                    $isAbsent = isset($attendanceMap[$teacherId]) ? true : false;

                    // Create or update attendance line for each teacher
                    $lineId = $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'teacher.daily.attendance.line',
                        'search',
                        array(
                            array(
                                array('teachers_ids', '=', $pushEntry),
                                array('teacher_id', '=', $teacherId),
                            ),
                        ),
                        
                    );

                    if (empty($lineId)) {
                        // Create a new record if no existing record is found
                        $newRecordId = $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'teacher.daily.attendance.line',
                            'create',
                            array(
                                array(
                                    'teachers_ids' => $pushEntry,
                                    'teacher_id' => $teacherId,
                                    'is_absent' => $isAbsent,
                                    'is_present' => !$isAbsent,
                                    'reason_id' => $isAbsent ? $attendanceMap[$teacherId]['reason_id'] : false,
                                )
                            ),
                           
                        );

                        error_log('New record created with ID: ' . $newRecordId);
                    } else {
                        // Update the existing record
                        $models->execute_kw(
                            $dbname,
                            $uid,
                            $password,
                            'teacher.daily.attendance.line',
                            'write',
                            array(
                                array($lineId[0]),
                                array(
                                    'is_absent' => $isAbsent,
                                    'is_present' => !$isAbsent,
                                    'reason_id' => $isAbsent ? $attendanceMap[$teacherId]['reason_id'] : false,
                                ),
                            ),
                            
                        );

                        error_log('Updated existing record with ID: ' . $lineId[0]);
                    }
                }

                if($iser != 0){
                    echo json_encode(
                        array(
                            "message"=> "failed",
                            'f'=> $message
                        )
                    );
                }else{
                    $models->execute_kw(
                        $dbname,
                        $uid,
                        $password,
                        'teacher.daily.attendance',
                        'write',
                        array(array($pushEntry), array(
                            'total_teacher' => $totalTeachers,
                            'total_presence' => $presentCount,
                            'total_absent' => $absentCount,
                            'state' => 'draft',
                            ),
                        ),
                        
                    );
                    sleep(0.25);

                    foreach($attendanceSheet as $attendance){
                        $teacherId = (int) $attendance->teacherId;
                        $proxies = $attendance->proxy;

                        foreach($proxies as $key=>$value){
                            $periodName = $key;
                            $assignedTeacherId = $proxies->$periodName;

                            $proxyLine = $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'teacher.daily.attendance.proxy',
                                'search_read',
                                array(
                                    array(
                                        array('teachers_ids','=', $pushEntry),
                                        array('teacher_id','=',$teacherId),
                                        array('period', '=', $periodName),
                                    )
                                ),
                                array(
                                    'fields'=> array('teachers_ids', 'teacher_id', 'period', 'assigned_teacher_id')
                                ),
                                
                            );

                            $proxyLineId = $proxyLine[0]['id'];

                            $p = $models->execute_kw(
                                $dbname,
                                $uid,
                                $password,
                                'teacher.daily.attendance.proxy',
                                'write',
                                array(
                                    array($proxyLineId),
                                    array(
                                        'assigned_teacher_id'=> (int) $assignedTeacherId
                                    )
                                ),
                                
                            );

                            sleep(0.5);
                        }
                    }
                    
                    echo json_encode(
                        array(
                            'message'=> 'success',
                        )
                    );
                }
            }
        }else{
            echo json_encode(
                array(
                    "message"=> "failed",
                    "no date"=> true
                )
            );
        }
    }else{
        echo json_encode(
            array(
                "message"=> "failed",
                "no valid"=> true
            )
        );
    }
}else{
    echo json_encode(
        array(
            "message"=> "failed",
            "no param"=> true
        )
    );
}


