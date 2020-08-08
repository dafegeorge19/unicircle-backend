<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}


require __DIR__.'/classes/Database.php';
require __DIR__.'/middlewares/Auth.php';

$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn,$allHeaders);

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

$returnData = [
    "success" => 0,
    "status" => 401,
    "message" => "Unauthorized"
];


if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
else:
    if($auth->checkAuth()):
        $dd = [];
        $d = [];
        $userid = $auth->checkAuth();
        $sql="SELECT * FROM `feed_tbl` WHERE `userid` = :userid";
        $q=$conn->prepare($sql);
        $q->bindValue(':userid',$userid, PDO::PARAM_INT);
        $q->execute();
        while ($d=$q->fetch(PDO::FETCH_ASSOC)){
            $row = [];
            $query = "SELECT * FROM comment_tbl WHERE feed_id=:feed_id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':feed_id', $d['feed_id']);
            $stmt->execute();
            while ($row=$stmt->fetch(PDO::FETCH_ASSOC)){
                array_push($d, $row);
            }
            $dd[] = $d;

        }
        echo json_encode(["status"=> 1, $dd]);
//        if($d['userid'] != $userid):
//            echo json_encode(["status"=> 1, "feeds" => $dd]);
//        endif;
    else:
        echo json_encode(msg(0,500,"Could not authenticate you."));
        return false;
    endif;

endif;


