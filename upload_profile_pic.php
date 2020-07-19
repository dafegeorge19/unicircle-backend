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
        'code' => $message
    ],$extra);
}

// INCLUDING DATABASE AND MAKING OBJECT
require __DIR__.'/classes/Database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$response = [];

$upload_dir = 'profile_picture/';
$server_url = __DIR__;

if($_FILES['avatar'])
{
    $avatar_name = $_FILES["avatar"]["name"];
    $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];
    $error = $_FILES["avatar"]["error"];

    if($error > 0):
        $response = array(
            "success" => 0,
            "status" => 422,
            "message" => "Error uploading the file!"
        );
    else:
        $random_name = rand(1000,1000000)."-".$avatar_name;
        $upload_name = $upload_dir.strtolower($random_name);
        $upload_name = preg_replace('/\s+/', '-', $upload_name);

        if(move_uploaded_file($avatar_tmp_name , $upload_name)):
            $response = array(
                "success" => 1,
                "status" => 200,
                "message" => "File uploaded successfully",
                "url" => $server_url."/".$upload_name
            );
        else:

            $response = array(
                "success" => 0,
                "status" => 422,
                "message" => "Error uploading the file!"
            );
        endif;
    endif;





}else{
    $response = array(
        "success" => 0,
        "status" => 422,
        "message" => "No file was sent!"
    );
}

echo json_encode($response);
?>