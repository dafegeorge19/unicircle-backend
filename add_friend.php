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

else:
    $friend_id= trim($data->friend_id);

    try{
        if($auth->checkAuth()):
            $userid = $auth->checkAuth();
            if($auth->isUsersFriends($userid,$friend_id)){
                echo json_encode(['success' => 0, 'status' => 404, 'message' => "You are friends already!"]);
                return false;
            }
            $insert_query = "INSERT INTO `friend_list`(userid,friend_id,status) VALUE (:userid,:friend_id,:status)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bindValue(':userid', htmlspecialchars(strip_tags($userid)),PDO::PARAM_STR);
            $insert_stmt->bindValue(':friend_id', htmlspecialchars(strip_tags($friend_id)),PDO::PARAM_STR);
            $insert_stmt->bindValue(':status', "Pending",PDO::PARAM_STR);
            if($insert_stmt->execute()):
                $returnData = msg(1,200,'Friend added.');
            else:
                return false;
            endif;
        else:
            return null;
        endif;
    }catch(PDOException $e){
        $returnData = msg(0,500,$e->getMessage());
    }
endif;

echo json_encode($returnData);

?>
