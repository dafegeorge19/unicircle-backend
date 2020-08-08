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

$upload_dir = 'comment_media/';
$server_url = BASE_URL."/comment_media/";
$allowed = array('gif', 'png', 'jpg', 'mp4', 'jpeg', 'bmp');

if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($_POST['feed_id'])
    || !isset($_POST['comment'])
    || empty(trim($_POST['feed_id']))
    || empty(trim($_POST['comment']))
):
    $returnData = msg(0,422,'Please Fill in all Required Fields!');
else:
    if($auth->checkAuth()):
        $userid = $auth->checkAuth();
        $status = 'Active';
        $feed_id = $_POST['feed_id'];
        $comment = $_POST['comment'];
        if(is_uploaded_file($_FILES['files']['tmp_name'])){
            $query = "INSERT into comment_tbl(`feed_id`,`comment`,`media`,`status`)
             VALUES(:feed_id,:comment,:media,:status)";
            $stmt  = $conn->prepare($query);
            $medias = [];
            foreach($_FILES['files']['tmp_name'] as $key => $error ){

                if ($error != UPLOAD_ERR_OK) {
                    echo json_encode(msg(0,500,$_FILES['files']['name'][$key] . ' was not uploaded.'));
                    return false;
                }
                $file_name = $key.$_FILES['files']['name'][$key];
                $file_size = $_FILES['files']['size'][$key];
                $file_tmp  = $_FILES['files']['tmp_name'][$key];
                $file_type = $_FILES['files']['type'][$key];


                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                if (!in_array($ext, $allowed)) {
                    echo json_encode(msg(0,500,"File is in bad format."));
                    return false;
                }elseif($ext == 'mp4' && $file_size > 10097152){
                    echo json_encode(msg(0,500,"video media too large."));
                    return false;
                }elseif($ext != 'mp4' && $file_size > 2097152){
                    echo json_encode(msg(0,500,"Picture file too large."));
                    return false;
                }

                $random_name = rand(1000,1000000)."-".$file_name;
                $upload_name = $upload_dir.strtolower($random_name);
                $upload_name = preg_replace('/\s+/', '-', $upload_name);
                $medias[] = BASE_URL."/".$upload_name;

                if(is_dir($upload_dir)==false){
                    mkdir($upload_dir, 0700);// Create directory if it does not exist
                }
                move_uploaded_file($file_tmp, $upload_name);
            }
            $med = json_encode($medias);
            $stmt->bindParam(':feed_id', $feed_id, PDO::PARAM_STR);
            $stmt->bindParam( ':comment', $comment , PDO::PARAM_STR );
            $stmt->bindParam( ':media', $med, PDO::PARAM_STR );
            $stmt->bindParam( ':status', $status, PDO::PARAM_STR );
            if($stmt->execute()){
                echo json_encode(msg(1,200,"Success"));
                return true;
            }
        }else{
            $med = '';
            $query = "INSERT into comment_tbl(`feed_id`,`comment`,`media`,`status`)
             VALUES(:feed_id,:comment,:media,:status)";
            $stmt  = $conn->prepare($query);
            $stmt->bindParam(':feed_id', $feed_id, PDO::PARAM_STR);
            $stmt->bindParam( ':comment', $comment , PDO::PARAM_STR );
            $stmt->bindParam( ':media', $med, PDO::PARAM_STR );
            $stmt->bindParam( ':status', $status, PDO::PARAM_STR );
            if($stmt->execute()){
                echo json_encode(msg(1,200,"Success"));
                return true;
            }
        }
    endif;
endif;

echo json_encode($returnData);
