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
elseif(!isset($data->email) ||  empty(trim($data->email))):

    $fields = ['fields' => ['email']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $digits = 4;
    $data->code = rand(pow(10, $digits-1), pow(10, $digits)-1);

    ini_set( 'display_errors', 1 );
    error_reporting( E_ALL );


    $to = $data->email;
    $from = 'noreply@unicicle.com';
    $fromName = 'UNICIRCLE';

    $subject = "User Activation Code";

    $templater = file_get_contents("email_format.php");
    $htmlContent = str_replace("ttt555rrrttt", $data->code, $templater);

    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // Additional headers
    $headers .= 'From: '.$fromName. "\r\n";

    // Send email
    if(mail($to, $subject, $htmlContent, $headers)) {
        $returnData2 = [
            "code" => $data->code
        ];
        $returnData = msg(1,200,'A verification has been sent to your email.',$returnData2);
    }

endif;

echo json_encode($returnData);

?>