<?php
require __DIR__.'/../classes/JwtHandler.php';
class Auth extends JwtHandler{

    protected $db;
    protected $headers;
    protected $token;
    public function __construct($db,$headers) {
        parent::__construct();
        $this->db = $db;
        $this->headers = $headers;
    }

    public function isAuth(){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    $user = $this->fetchUser($data['data']->userid);
                    return $user;

                else:
                    return null;

                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))

            else:
                return null;

            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))

        else:
            return null;

        endif;
    }

    public function checkAuth(){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    // $all_user = $this->uploadProfilePicture($data['data']->userid);
                    // return $all_user;
                     return $data['data']->userid;
                else:
                    return false;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else:
            return null;
        endif;
    }


    public function isAuth2(){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    $all_user = $this->fetchAllUser();
                    return $all_user;
                else:
                    return null;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else:
            return null;
        endif;
    }

    public function isAuth3(){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    $all_user = $this->fetchSingleInvestment();
                    return $all_user;
                else:
                    return null;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else:
            return null;
        endif;
    }


    public function isProfileImage(){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    // $all_user = $this->uploadProfilePicture($data['data']->userid);
                    // return $all_user;
                    return $data['data']->userid;
                else:
                    return false;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else:
            return null;
        endif;
    }

    protected function fetchUser($userid){
        try{
            $fetch_user_by_id = "SELECT * FROM `user_tbl` WHERE `userid`=:userid";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':userid', $userid,PDO::PARAM_INT);
            $query_stmt->execute();

            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'success' => 1,
                    'status' => 200,
                    'user' => $row
                ];
            else:
                return null;
            endif;
        }
        catch(PDOException $e){
            return null;
        }
    }

    protected function fetchAllUser(){
        $process = array();
        try{
            $sql = 'SELECT * FROM `user_tbl`';
            foreach ($this->db->query($sql, PDO::FETCH_ASSOC) as $row) {
                $data[] = $row;
            }
            echo json_encode(['status'=>1, 'user'=>$data]);
        }catch(PDOException $e){
            echo [
                'success' => 0,
                'status' => 402,
                'message' => 'No record found'
            ];
        }
    }

    public function fetch_all_investment(){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    $all_investment = $this->fetchAllInvestment();
                    return $all_investment;
                else:
                    return null;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else:
            return null;
        endif;
    }

    protected function fetchAllInvestment(){
        $process = array();
        try{
            $sql = 'SELECT * FROM `investment_tbl`';
            foreach ($this->db->query($sql, PDO::FETCH_ASSOC) as $row) {
                $data[] = $row;

            }
            echo json_encode(['status'=>1, 'invest'=>$data]);
        }catch(PDOException $e){
            echo [
                'success' => 0,
                'status' => 402,
                'message' => 'No record found'
            ];
        }
    }

    public function fetch_single_investment($investment_id){
        if(array_key_exists('Authorization',$this->headers) && !empty(trim($this->headers['Authorization']))):
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if(isset($this->token[1]) && !empty(trim($this->token[1]))):

                $data = $this->_jwt_decode_data($this->token[1]);

                if(isset($data['auth']) && isset($data['data']) && $data['auth']):
                    try{
                        $sql = 'SELECT * FROM `investment_tbl` WHERE ';
                        foreach ($this->db->query($sql, PDO::FETCH_ASSOC) as $row) {
                            $data[] = $row;

                        }
                        echo json_encode(['status'=>1, 'invest'=>$data]);
                    }catch(PDOException $e){
                        echo [
                            'success' => 0,
                            'status' => 402,
                            'message' => 'No record found'
                        ];
                    }
                    // $all_user = $this->fetchSingleInvestment();
                    // return $all_user;
                else:
                    return null;
                endif; // End of isset($this->token[1]) && !empty(trim($this->token[1]))
            else:
                return null;
            endif;// End of isset($this->token[1]) && !empty(trim($this->token[1]))
        else:
            return null;
        endif;
    }
}
?>
