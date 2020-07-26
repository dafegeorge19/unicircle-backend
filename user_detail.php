<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__.'classes/Database.php';
require __DIR__.'middlewares/Auth.php';

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
elseif(!isset($data->userid) || empty(trim($data->userid))):
    $fields = ['fields' => ['investment_id']];
    $returnData = msg(0,422,'Invalid user id!',$fields);
else:
    if($auth->checkAuth()):
        try{
            $fetch_user_by_id = "SELECT * FROM `user_tbl` WHERE `userid`=:userid";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':userid', $data->userid,PDO::PARAM_INT);
            $query_stmt->execute();

            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'success' => 1,
                    'status' => 200,
                    'user' => $row
                ];
            else:
                return [
                    'success' => 0,
                    'status' => 402,
                    'This user does not exist'
                ];
            endif;
        }
        catch(PDOException $e){
            return null;
        }
    else:
        return [
            'success' => 0,
            'status' => 402,
            'This user does not exist'
        ];
    endif;
endif;
