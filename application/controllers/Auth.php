<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
    public function __construct()
    {
       parent::__construct();
        $this->load->model('User_model');
        $this->load->library('Jwt_lib');

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
        header('Content-Type: application/json');

        date_default_timezone_set('Asia/Jakarta');
        $this->db->query("SET time_zone = '+07:00'");

        // Handle preflight OPTIONS request (perlu untuk CORS dari browser)
        if ($this->input->method() === 'options') {
            $this->output->set_status_header(200);
            exit;
        }
 
         
    }   
	public function index()
	{
		echo 'masuk';
    }
    
    public function login()
    {
        if ($this->input->method() !== 'post') {
            $this->_response(['message' => 'Method not allowed'], 405);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);

        $email    = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($email) || empty($password)) {
            $this->_response(['message' => 'Email dan password wajib diisi'], 400);
            return;
        }

        $user = $this->User_model->get_by_email($email);

        if (!$user) {
            $this->_response(['message' => 'Email atau password salah'], 401);
            return;
        }

        if ($user->status != 1) {
            $this->_response(['message' => 'Akun tidak aktif'], 401);
            return;
        }

        if (md5($password) !== $user->password) {
            $this->_response(['message' => 'Email atau password salah'], 401);
            return;
        }

       

        $id = $user->id;
        $login = true;
            $note = "Login OK"; 
            $insert = array(
                "device" => '',
                "token" => 'JWT', 
                "id_user" => $id,
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by"	=> $id, 
            );
            $this->db->insert('crm3_user_auth', $insert);
 
            $id_user_access = $this->model->select('id_user_access', 'crm3_user', 'email="' . $email . '"');

            $accessRules =  array(
                "name" => $this->model->select('concat(first_name," ",last_name )', 'crm3_user', 'email="' . $email . '"'),
                "level" => $this->model->select('name', 'crm3_user_access', 'id="' . $user->id_user_access . '"'),
                
                "result" => $this->model->access_rules($user->id_user_access)
            ); 

        $token_payload = [
            'id'             => $user->id,
            'email'          => $user->email, 
            'id_user_access' => $user->id_user_access,
            'id_user_group'  => $user->id_user_group,
            'id_parent'      => $user->id_parent,
            'folder'         => $user->folder,
            'access_rules'   =>  $accessRules,
        ];


        $token = $this->jwt_lib->generate_token($token_payload);

        $this->_response([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'email'      => $user->email,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'code'       => $user->code,
            ]
        ], 200);
    }

    private function _response($data, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }





    public function login_DEL()
	{
        $post = json_decode(file_get_contents('php://input'), true);
        if(  $post  && $this->model->key() ){
            echo "masuk";
        }else{
            echo $this->model->error("error token");
        }
        
    }

    public function user()
	{
       $data = array(
           "id_user" => "",
           "account" => "",
           "id_account" => "",
           
       );

       echo json_encode($data);
    }

     

    function access_rules(){
 
        $id_user_access = 1; 
        $data = array(
            "error" => 0,
            "result" =>  $this->model->access_rules( $id_user_access ),  
        );
 
        echo json_encode($data);
    }
    
}
