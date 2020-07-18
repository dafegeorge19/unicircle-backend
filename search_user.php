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
elseif(!isset($data->search_query)
    || empty(trim($data->search_query))
):
//    $returnData = msg(0,422,'Please Fill in all Required Fields!');
    if($auth->checkAuth()):
        $data = [];
        try {
            $sql = "SELECT * FROM `user_tbl`";
            foreach ($conn->query($sql, PDO::FETCH_ASSOC) as $row) {
                $data[] = $row;
            }
            echo json_encode(["status" => 1, "user" => $data]);
            return true;
        }catch(PDOException $e) {
            echo json_encode([
                'success' => 0,
                'status' => 402,
                'message' => 'No record found'
            ]);
        }
    else:
        $returnData = json_encode(msg(0,500,"Could not authenticate you."));
        return false;
    endif;
else:
    $search_query = $data->search_query;

    if($auth->checkAuth()):
        $data = [];
        try {
            $query = '';
            $params = [];

            if(isset($search_query)){
                $query= 'SELECT * FROM `user_tbl` WHERE username LIKE :search_term OR email LIKE :search_term OR unicircle_email LIKE :search_term OR first_name LIKE :search_term OR last_name LIKE :search_term OR phone LIKE :search_term';
                $params['search_term'] = '%'.$search_query.'%';
            }
            $stmt = $conn->prepare($query);
            foreach($params as $key=>$value){
                $stmt->bindValue(':'.$key, $value);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["status" => 1, "user" => $data]);
            return true;
        }catch(PDOException $e) {
            echo json_encode([
                'success' => 0,
                'status' => 402,
                'message' => 'No record found'
            ]);
        }
    else:
        $returnData = json_encode(msg(0,500,"Could not authenticate you."));
        return false;
    endif;
endif;
?>
