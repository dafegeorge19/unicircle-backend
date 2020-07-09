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

$upload_dir = 'profile_picture/';
$server_url = __DIR__."/profile_picture/";

if($auth->isProfileImage()):
    $userid = $auth->isProfileImage();
    if($_FILES['avatar']):
        $avatar_name = $_FILES["avatar"]["name"];
        $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];
        $error = $_FILES["avatar"]["error"];

        if($error > 0):
            $returnData = array(
                "success" => 0,
                "status" => 422,
                "message" => "Error uploading the file!"
            );
        else:
            $random_name = rand(1000,1000000)."-".$avatar_name;
            $upload_name = $upload_dir.strtolower($random_name);
            $upload_name = preg_replace('/\s+/', '-', $upload_name);

            if(move_uploaded_file($avatar_tmp_name , $upload_name)):

                $profile_pic_url = $server_url."/".$upload_name;

                $update_profile_pic = "UPDATE `user_tbl` SET profile_picture=:profile_picture WHERE userid=:userid";
                $update_profile_pic_stmt = $conn->prepare($update_profile_pic);
                $update_profile_pic_stmt->bindValue(':profile_picture', $profile_pic_url,PDO::PARAM_STR);
                $update_profile_pic_stmt->bindValue(':userid', $userid,PDO::PARAM_INT);
                if($update_profile_pic_stmt->execute()):

                    $returnData2 = array(
                        "url" => $server_url."/".$upload_name
                    );
                    $returnData = msg(1,200,'Profile picture updated!',$returnData2);

                else:
                    return null;
                endif;

            else:
                $returnData = array(
                    "success" => 0,
                    "status" => 422,
                    "message" => "Error uploading the file!"
                );
            endif;
        endif;
    else:
        $returnData = array(
            "success" => 0,
            "status" => 422,
            "message" => "No file was sent!"
        );
    endif;
endif;

echo json_encode($returnData);
?>
