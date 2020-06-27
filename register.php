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
    || empty(trim($data->email))
    || empty(trim($data->username))
    || empty(trim($data->phone_number))
    || empty(trim($data->password))
    ):

    $fields = ['fields' => ['email','username','phone_number','password']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:

    $email = trim($data->email);
    $username = trim($data->username);
    $phone_number = trim($data->phone_number);
    $password = trim($data->password);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,422,'Invalid Email Address!');

    elseif(strlen($password) < 8):
        $returnData = msg(0,422,'Your password must be at least 8 characters long!');

    elseif(strlen($username) < 3):
        $returnData = msg(0,422,'Your name must be at least 3 characters long!');

    elseif(strlen($phone_number) < 11 || preg_match("/^[0-9]{3}-[0-9]{4}-[0-9]{4}$/", $phone_number)):
        $returnData = msg(0,422,'Your phone number is ');

    else:
        try{

            $check_email = "SELECT `email` FROM `user_tbl` WHERE `email`=:email";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):
                $returnData = msg(0,422, 'This E-mail already in use!');
            else:
                $insert_query = "INSERT INTO `user_tbl`(`username`,`password`,`email`,`is_email_verified`,`phone`,`role`) VALUES(:username,:password,:email,:is_email_verified,:phone,:role)";

                $insert_stmt = $conn->prepare($insert_query);

                // DATA BINDING
                $insert_stmt->bindValue(':username', htmlspecialchars(strip_tags($username)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT),PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                $insert_stmt->bindValue(':is_email_verified', 1,PDO::PARAM_INT);
                $insert_stmt->bindValue(':phone', $phone_number,PDO::PARAM_STR);
                $insert_stmt->bindValue(':role', 0,PDO::PARAM_INT);

                $insert_stmt->execute();

                $returnData = msg(1,200,'You have successfully registered.');

            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;

endif;

echo json_encode($returnData);
?>
