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

// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__.'/classes/Database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->email)
    || !isset($data->username)
    || !isset($data->phone_number)
    || !isset($data->password)
    || !isset($data->first_name)
    || !isset($data->last_name)
    || empty(trim($data->email))
    || empty(trim($data->username))
    || empty(trim($data->phone_number))
    || empty(trim($data->password))
    || empty(trim($data->first_name))
    || empty(trim($data->last_name))
    ):

    $fields = ['fields' => ['email','username','phone_number','password','first_name','last_name']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:

    $email = trim($data->email);
    $username = trim($data->username);
    $phone_number = trim($data->phone_number);
    $password = trim($data->password);
    $first_name = trim($data->first_name);
    $last_name = trim($data->last_name);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,422,'Invalid Email Address!');

    elseif(strlen($password) < 8):
        $returnData = msg(0,422,'Your password must be at least 8 characters long!');

    elseif(strlen($username) < 3):
        $returnData = msg(0,422,'Your name must be at least 3 characters long!');

    elseif(strlen($phone_number) < 11 || preg_match("/^[0-9]{3}-[0-9]{4}-[0-9]{4}$/", $phone_number)):
        $returnData = msg(0,422,'Your phone number is not properly formatted');

    elseif($first_name == ''):
        $returnData = msg(0,422,'Firstname field cannot be empty!');

    elseif($last_name == ''):
        $returnData = msg(0,422,'Lastname field cannot be empty!');

    else:
        try{

            $check_email = "SELECT `email` FROM `user_tbl` WHERE `email`=:email";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):
                $returnData = msg(0,422, 'This E-mail already in use!');
            else:

                $check_username = "SELECT `username` FROM `user_tbl` WHERE `username`=:username";
                $check_username_stmt = $conn->prepare($check_username);
                $check_username_stmt->bindValue(':username', $username,PDO::PARAM_STR);
                $check_username_stmt->execute();

                if($check_username_stmt->rowCount()):
                    $returnData = msg(0,422, 'This Username already in use!');
                else:
                    $insert_query = "INSERT INTO `user_tbl`(`username`,`password`,`email`,`is_email_verified`,`phone`,`first_name`,`last_name`,`role`) VALUES(:username,:password,:email,:is_email_verified,:phone,:first_name,:last_name,:role)";

                    $insert_stmt = $conn->prepare($insert_query);

                    // DATA BINDING
                    $insert_stmt->bindValue(':username', htmlspecialchars(strip_tags($username)),PDO::PARAM_STR);
                    $insert_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT),PDO::PARAM_STR);
                    $insert_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':is_email_verified', 1,PDO::PARAM_INT);
                    $insert_stmt->bindValue(':phone', $phone_number,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':first_name', $first_name,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':last_name', $last_name,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':role', 0,PDO::PARAM_INT);

                    if($insert_stmt->execute()):
                        $fetch_user_by_id = "SELECT * FROM `user_tbl` WHERE `email`=:email";
                        $query_stmt = $conn->prepare($fetch_user_by_id);
                        $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                        $query_stmt->execute();

                        if($query_stmt->rowCount()):
                            $row = $query_stmt->fetch(PDO::FETCH_ASSOC);

                            $insert_wallet = "INSERT INTO `wallet`(userid) VALUES(:userid)";
                            $insert_wallet_stmt = $conn->prepare($insert_wallet);
                            $insert_wallet_stmt->bindValue(':userid', $row['userid'],PDO::PARAM_INT);
                            if($insert_wallet_stmt->execute()):

                                $returnData2 = [
                                    'success' => 1,
                                    'status' => 200,
                                    'user' => $row
                                ];
                                $returnData = msg(1,200,'You have successfully registered.',$returnData2);
                            else:
                                return null;
                            endif;
                        else:
                            return null;
                        endif;
                    else:
                        return null;

                    endif;
                endif;

            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
endif;

echo json_encode($returnData);

?>
