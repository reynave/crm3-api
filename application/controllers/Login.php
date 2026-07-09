<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->key = $this->db->key;
		header('Access-Control-Allow-Origin: *');
		//header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
		header("Access-Control-Allow-Headers: key, token,  Content-Type");

		header('Access-Control-Allow-Methods: GET, POST, PUT');
	
		// error_reporting(E_ALL);
		date_default_timezone_set('Asia/Jakarta');
		$this->db->query("SET time_zone = '+07:00'");
		//  $this->core->https(); 
	}


	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function signin()
	{
		header('Content-Type: application/json');
		$accessRules = false;
		$post = json_decode(file_get_contents('php://input'), true);
		$time = time() + (86400 * 30);
		$token = false;
		$id = 0;
		if ($post) {

			$password = md5($post['password']);
			$email = $post['email'];
			$id = $this->model->select('id', 'crm3_user', 'email="' . $email . '"');
			if ($id) {
				if ($this->model->select('id', 'crm3_user', 'status = 1 and presence = 1 and  email="' . $email . '" and password = "' . $password . '"')) {
					$login = true;
					$note = "Login OK";

					$token  = md5($id . '-' . date('YmdHis'));


					$id_user_auth = $this->model->select('id', 'crm3_user_auth', 'id_user = ' . $id . ' and device = "' . $post['device'] . '"');
					//if (!$id_user_auth) {
					$insert = array(
						"device" => $post['device'],
						"token" => $token,
						"player_id" => isset($post['player_id'])  ?  $post['player_id'] : "",
						"id_user" => $id,
						"input_date" => date('Y-m-d H:i:s'),
						"update_date" => date('Y-m-d H:i:s'),
						"input_by"	=> $id,

					);
					$this->db->insert('crm3_user_auth', $insert);


					// } else {
					// 	$update = array(
					// 		"device" => $post['device'],
					// 		"token" => $token,
					// 		"player_id" => $post['player_id']
					// 	);

					// 	$this->db->update('crm3_user_auth', $update, ' id = '.$id_user_auth);
					// }

					if ($post['remember'] == true) {
						$time = time() + (86400 * 30 * 30);
					}

					$id_user_access = $this->model->select('id_user_access', 'crm3_user', 'email="' . $email . '"');

					$accessRules =  array(
						"name" => $this->model->select('concat(first_name," ",last_name )', 'crm3_user', 'email="' . $email . '"'),
						"result" => $this->model->access_rules($id_user_access)
					);


					$accessRules =   base64_encode(json_encode($accessRules, TRUE));


					setcookie('cmr3ng8Access', $accessRules, $time, "/"); // 86400 = 1 day
					setcookie('cmr3ng8Token', $token, $time, "/"); // 86400 = 1 day

				} else if (!$this->model->select('id', 'crm3_user', 'presence = 1 and email="' . $email . '"')) {
					$login = false;
					$note = "Wrong email";
				} else if ($this->model->select('id', 'crm3_user', 'presence = 1  and email="' . $email . '" and password !="' . $password . '"')) {
					$login = false;
					$note = "Wrong password";
				} else if ($this->model->select('status', 'crm3_user', 'email="' . $email . '" and password = "' . $password . '"') == 0) {
					$login = false;
					$note = "User suspensed";
				}
			} else {
				$login = false;
				$note = "Email is not regsiter";
			}
		}
		$json = array(
			"error" => 0,
			"data" => array(
				"login" => $login,
				"note" => $note,
				"id_user" => $id,
				"token" => $token,
				//	"accessRules" => $accessRules,
			)
		);
		echo json_encode($json);
	}

	public function signout()
	{
		header('Content-Type: application/json');


		unset($_COOKIE['cmr3ng8Token']);
		setcookie('cmr3ng8Token', null, -1, '/');

		unset($_COOKIE['cmr3ng8Access']);
		setcookie('cmr3ng8Access', null, -1, '/');


		header('location:' . base_url() . '../login/');
	}


	function template(){
		$data = array(
			"name" => "Keshia",
			"email" => "email@mail.com",
			"token" => "345234526435645623452345",
		);
		$this->load->view('email/forgetpassword', $data);

	}

	function forgetpass()
	{
		header('Content-Type: application/json');
		$post = json_decode(file_get_contents('php://input'), true);
		 
		if ($post && $this->model->select('id', 'crm3_user', 'status = 1 and presence = 1 and  email="' .$post['data']['email'] . '"') ) {
			$token = 'PasNew@'.md5("kaori@".$post['data']['email'].' '.date('YmdHis'));
			$insert = array(
				"token" => $token,
				"id_user" => $this->model->select('id', 'crm3_user', 'status = 1 and presence = 1 and  email="' .$post['data']['email'] . '"'),
				"device" => "ForgetPassword"
			);

			$this->db->insert("crm3_user_auth",$insert);

			$email = "noreply@crm.co.id";
			$pass = "3mGKVIBg8bxU"; 
			$data = array(
				"name" => $this->model->crm3("concat(first_name,' ',last_name)","user"," email = '".$post['data']['email']."' "), 
				"email" => $post['data']['email'],
				"token" => $token,
			); 
			$message = $this->load->view('email/forgetpassword', $data, TRUE);
 
			$this->load->library('email');
		 
			$config['protocol']= 'smtp';
			$config['smtp_host']= 'mail.crm.co.id';
			$config['smtp_user']= $email;
			$config['smtp_user']= $pass; 
			$config['smtp_port']= '587';
			$config['mailtype']= 'html'; 
 
			$this->email->initialize($config);

			$this->email->from($email, 'SalesTrax');
			$this->email->to($post['data']['email'], 'SalesTrax'); 

			$this->email->subject('Did you forget your password for SalesTrax CRM ?');
			$this->email->message($message);

			$send = $this->email->send();
			$json = array(
				"error" => 0,
				"note" =>  $send,
			);
		
		}else{
			$json = array(
				"error" => 1, 
				"note" => "Error, Auth Fail !"
			);
		}

		echo json_encode($json);
	}

	function resetpassword(){
		$token = $this->input->get('t') ;
		if( $this->model->crm3("id_user","user_auth","token = '".$token."'") ){
			$id_user = $this->model->crm3("id_user","user_auth","token = '".$token."'");
			$data = array(
				"name" => $this->model->crm3("concat(first_name,' ',last_name)","user","id=".$id_user), 
			);
			$this->load->view('email/resetpassword', $data);
		}
	}

	function resetpassword_submit(){
		if( $this->input->post() ){ 
			$token = $this->input->post('token');
			$id_user = $this->model->crm3("id_user","user_auth","token = '".$token."'");
			$update = array(
				"password" => md5($this->input->post('newpass')),
				"update_date" => date("Y-m-d H:i:s")
			);
 
			$this->db->update("crm3_user",$update,"id = ".$id_user);  
			$this->db->delete("crm3_user_auth","token = '".$token."' ");


			$this->load->view('email/resetpassword_submit');
		}
	}

	function checkSession()
	{
		header('Content-Type: application/json');
		$json = array(
			"error" => 1,
		);
		$post = json_decode(file_get_contents('php://input'), true);
		if ($post) {

			$json = array(
				"error" => 0,
				"login" => $this->model->crm3("id", "user_auth", "token = '" . $post['token'] . "'") ? true : false,
			);
			echo json_encode($json);
		}
	}

	public function accessRight()
	{
		header('Content-Type: application/json');
		$accessRules =  array(
			"name" => $this->model->select('concat(first_name," ",last_name )', 'crm3_user', 'email="' . $email . '"'),
			"result" => $this->model->access_rules(1)
		);

		echo json_encode($accessRules);
	}
}
