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

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

$upload_dir = 'investment_picture/';
$server_url = __DIR__."/investment_picture/";

if($auth->chechAuth()):
    $userid = $auth->chechAuth();
// IF REQUEST METHOD IS NOT POST
    if($_SERVER["REQUEST_METHOD"] != "POST"):
        $returnData = msg(0,404,'Page Not Found!');

        if(!$_FILES['avatar']):
            $returnData = array(
                "success" => 0,
                "status" => 422,
                "message" => "No file was sent!"
            );
            return false;
        endif;
    elseif(!isset($_POST['investment_name'])
        || !isset($_POST['investment_description'])
        || !isset($_POST['price_per_unit'])
        || !isset($_POST['unit_available'])
        || !isset($_POST['roi_in_percent'])
        || empty(trim($_POST['investment_name']))
        || empty(trim($_POST['investment_description']))
        || empty(trim($_POST['price_per_unit']))
        || empty(trim($_POST['unit_available']))
        || empty(trim($_POST['roi_in_percent']))
        ):

        $fields = ['fields' => ['investment_name','investment_description','price_per_unit','unit_available','roi_in_percent']];
        $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);

    // IF THERE ARE NO EMPTY FIELDS THEN-
    else:

        $investment_name = trim($_POST['investment_name']);
        $investment_description = trim($_POST['investment_description']);
        $price_per_unit = trim($_POST['price_per_unit']);
        $unit_available = trim($_POST['unit_available']);
        $roi_in_percent = trim($_POST['roi_in_percent']);
        $status = 0;

        if($investment_name == ''):
            $returnData = msg(0,422,'Firstname field cannot be empty!');

        elseif($investment_description == ''):
            $returnData = msg(0,422,'Lastname field cannot be empty!');

        // elseif($price_per_unit):
        //     $returnData = msg(0,422,'investment price not set.');

        // elseif($unit_available):
        //     $returnData = msg(0,422,'nvestment availability not set.');


        // elseif($roi_in_percent):
        //     $returnData = msg(0,422,'investment percentage not set.');

        else:
            try{
                $avatar_name = $_FILES["avatar"]["name"];
                $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];

                $random_name = rand(1000,1000000)."-".$avatar_name;
                $upload_name = $upload_dir.strtolower($random_name);
                $upload_name = preg_replace('/\s+/', '-', $upload_name);

                if(move_uploaded_file($avatar_tmp_name , $upload_name)):

                    $profile_pic_url = $server_url."/".$upload_name;

                    $insert_query = "INSERT INTO `investment_tbl`(`userid`,`investment_name`,`investment_description`,`price_per_unit`,`unit_available`,`roi_in_percent`,`investment_pic`,`status`) VALUES(:userid,:investment_name,:investment_description,:price_per_unit,:unit_available,:roi_in_percent,:investment_pic,:status)";
                    $insert_stmt = $conn->prepare($insert_query);
                    // DATA BINDING
                    $insert_stmt->bindValue(':userid', $userid,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':investment_name', htmlspecialchars(strip_tags($investment_name)),PDO::PARAM_STR);
                    $insert_stmt->bindValue(':investment_description', $investment_description,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':price_per_unit', $price_per_unit,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':unit_available', $unit_available,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':roi_in_percent', $roi_in_percent,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':investment_pic', $profile_pic_url,PDO::PARAM_STR);
                    $insert_stmt->bindValue(':status', $status,PDO::PARAM_STR);

                    if($insert_stmt->execute()):
                        $returnData = array(
                            "success" => 1,
                            "status" => 200,
                            "message" => "Investment added!"
                        );
                    else:
                        $returnData = array(
                            "success" => 0,
                            "status" => 422,
                            "message" => "Could not store data in database.!"
                        );
                    endif;
                else:
                    $returnData = array(
                        "success" => 0,
                        "status" => 422,
                        "message" => "Error uploading the file!"
                    );
                endif;
            }
            catch(PDOException $e){
                $returnData = msg(0,500,$e->getMessage());
            }
        endif;
    endif;
else:
    $returnData = array(
        "success" => 0,
        "status" => 422,
        "message" => "Not authenticated!"
    );
endif;
echo json_encode($returnData);

?>
