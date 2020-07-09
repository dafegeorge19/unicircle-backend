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
elseif(!isset($data->email)
    || !isset($data->phone_number)
    || !isset($data->first_name)
    || !isset($data->last_name)
    || empty(trim($data->email))
    || empty(trim($data->phone_number))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    ):

    $fields = ['fields' => ['phone_number','first_name','last_name','email']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);
// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $email = trim($data->email);
    $phone_number = trim($data->phone_number);
    $first_name = trim($data->first_name);
    $last_name = trim($data->last_name);

    if(strlen($phone_number) < 11 || preg_match("/^[0-9]{3}-[0-9]{4}-[0-9]{4}$/", $phone_number)):
        $returnData = msg(0,422,'Your phone number is not properly formatted');

    elseif($first_name == ''):
        $returnData = msg(0,422,'Firstname field cannot be empty!');

    elseif($last_name == ''):
        $returnData = msg(0,422,'Lastname field cannot be empty!');

    else:
        try{
            if($auth->checkAuth()):
                $userid = $auth->checkAuth();
                $insert_query = "UPDATE `user_tbl` SET first_name=:first_name, last_name=:last_name, email=:email, phone=:phone_number WHERE userid=:userid";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':first_name', htmlspecialchars(strip_tags($first_name)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':last_name', htmlspecialchars(strip_tags($last_name)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', htmlspecialchars(strip_tags($email)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':phone_number', htmlspecialchars(strip_tags($phone_number)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':userid', htmlspecialchars(strip_tags($userid)),PDO::PARAM_INT);
                if($insert_stmt->execute()):
                    $fetch_user_by_id = "SELECT * FROM `user_tbl` WHERE `email`=:email";
                    $query_stmt = $conn->prepare($fetch_user_by_id);
                    $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                    $query_stmt->execute();

                    if($query_stmt->rowCount()):
                        $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                        $returnData2 = [
                            'success' => 1,
                            'status' => 200,
                            'user' => $row
                        ];
                        $returnData = msg(1,200,'Profile updated.',$returnData2);
                    else:
                        return null;
                    endif;
                endif;
            else:
                return null;
            endif;
        }catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
endif;

echo json_encode($returnData);
?>
