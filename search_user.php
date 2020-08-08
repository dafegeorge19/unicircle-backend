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
elseif(!isset($data->search_query)
    || empty(trim($data->search_query))
):
//    $returnData = msg(0,422,'Please Fill in all Required Fields!');
    if($auth->checkAuth()):
        $userid = $auth->checkAuth();
        $d = [];
        $sql = "SELECT * FROM `user_tbl` WHERE `userid` != :userid";
        $que=$conn->prepare($sql);
        $que->bindValue(':userid', $userid);
        $que->execute();
        while ($d=$que->fetch(PDO::FETCH_ASSOC)){
            $row= [];
            $query = "SELECT * FROM friend_list WHERE userid=:userid AND friend_id=:friend_id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':userid', $userid);
            $stmt->bindValue(':friend_id', $d['userid']);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $row['friend_status'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }else{
                $row['friend_status'] = ['status' => "not_friend"];
            }

            array_push($d, $row);

            $dd[] = $d;
        }
        if($d['userid'] != $userid):
            echo json_encode(["status"=> 1, "user" => $dd]);
        endif;
    else:
        $returnData = json_encode(msg(0,500,"Could not authenticate you."));
        return false;
    endif;
else:
    $search_query = $data->search_query;

    if($auth->checkAuth()):
        $d = [];
        $userid = $auth->checkAuth();

        $sql="SELECT * FROM `user_tbl` WHERE `username` LIKE :search_term OR `email` LIKE :search_term OR `unicircle_email` LIKE :search_term OR `first_name` LIKE :search_term OR `last_name` LIKE :search_term OR `phone` LIKE :search_term";
        $q=$conn->prepare($sql);
        $q->bindValue(':search_term','%'.$search_query.'%');
        $q->execute();
        while ($d=$q->fetch(PDO::FETCH_ASSOC)){
            $row = [];
            $query = "SELECT * FROM friend_list WHERE userid=:userid AND friend_id=:friend_id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':userid', $userid);
            $stmt->bindValue(':friend_id', $d['userid']);
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $row['friend_status'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }else{
                $row['friend_status'] = ['status' => "not_friend"];
            }

            array_push($d, $row);

            $dd[] = $d;
        }
        if($d['userid'] != $userid):
            echo json_encode(["status"=> 1, "user" => $dd]);
        endif;
    else:
        echo json_encode(msg(0,500,"Could not authenticate you."));
        return false;
    endif;
endif;


