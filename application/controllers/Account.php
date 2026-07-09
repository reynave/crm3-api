<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Account extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->key = $this->db->key;
        $this->openAPI = $this->db->openAPI;
        header('Access-Control-Allow-Origin: *');
        //header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Access-Control-Allow-Headers: key, token,  Content-Type");

        header('Content-Type: application/javascript');
        header('Access-Control-Allow-Methods: GET, POST, PUT');
        // error_reporting(E_ALL);
        date_default_timezone_set('Asia/Jakarta');
        $this->db->query("SET time_zone = '+07:00'");
        //  $this->core->https();

        if (!$this->model->header($this->openAPI)) {
            echo $this->model->error("Error auth");
            exit;
        }
    }

    public function index()
    {
        $data = [];
        $q = 'select * from account where  id = 1'; 
        $query = $this->db->query($q); 

 
        $user = $this->db->query('select id, concat(first_name," ",last_name) as "name" from crm3_user WHERE status = 1 and presence  = 1'); 

        $json = array(
            "error"  =>  0,
            "result" => array( 
                "install" => (bool)$query->result()[0]->id_user, 
                "data" => $query->result()[0], 
                "user" => $user->result(), 
            ),
        ); 
        echo json_encode($json);
    }

  


    function fnUpdate()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
 
            $this->db->update("account", $post['data'], 'id='  . $post['data']['id']);
 
            $json = array(
                "error"  =>  0,
                "result" => array( 
                    "note"  => "Update done",
                ),
            );
            echo json_encode($json);
        }
    }

 
}
