<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

$friend_id = isset($_POST['friend_id']) ? $_POST['friend_id'] : "Friend id empty!";

if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');
elseif(!isset($data->friend_id) || empty(trim($data->friend_id))):
    $fields = ['fields' => ['investment_id']];
    $returnData = json_encode(["success" =>0,"status"=>422,"message"=>'Invalid user id!',$fields]);
else:
    if($auth->checkAuth()):
        $userid = $auth->checkAuth();
        try{
            $fetch_user_by_id = "SELECT * FROM `user_tbl` WHERE `userid`=:userid";
            $query_stmt = $conn->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':userid', $friend_id,PDO::PARAM_INT);
            $query_stmt->execute();

            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);

                $query = "SELECT * FROM friend_list WHERE userid=:userid AND friend_id=:friend_id";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':userid', $userid);
                $stmt->bindValue(':friend_id', $row['userid']);
                $stmt->execute();
                if($stmt->rowCount() > 0){
                    $d['friend_status'] = $stmt->fetch(PDO::FETCH_ASSOC);
                }else{
                    $d['friend_status'] = ['status' => "not_friend"];
                }

                array_push($row, $d);

                $rower[] = $row;

                echo json_encode([
                    'success' => 1,
                    'status' => 200,
                    'user' => $rower
                ]);
            else:
                echo json_encode([
                    'success' => 0,
                    'status' => 402,
                    'This user does not exist'
                ]);
            endif;
        }
        catch(PDOException $e){
            return null;
        }
    else:
        echo json_encode([
            'success' => 0,
            'status' => 402,
            'This user does not exist'
        ]);
    endif;
endif;
