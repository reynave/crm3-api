<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Access_data extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->key = $this->db->key;
        $this->openAPI = $this->db->openAPI;
        header('Access-Control-Allow-Origin: *');
        //header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Access-Control-Allow-Headers: key, token,  Content-Type");

        header('Access-Control-Allow-Methods: GET, POST, PUT');
        header('Content-Type: application/javascript');
        // error_reporting(E_ALL);
        date_default_timezone_set('Asia/Jakarta');
        $this->db->query("SET time_zone = '+07:00'");
        //  $this->core->https();

        if (!$this->model->header($this->openAPI) ) { 
            echo $this->model->error("Error auth"); 
            exit;
        }
    }

    public function index()
    { 
        $data = []; 
        $query = $this->db->query('select * from crm3_user_group where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"   => $row->id,
                "name" => $row->name,  
                "all_access" => (int)$row->all_access,  
                "sharing_team_access" => (int)$row->sharing_team_access,  
                
            );
            array_push($data, $temp); 
        }
 
        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data, 
            ),
        );

        echo json_encode($json);
    }

   
    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {  
            $insert = array( 
                "name"      => $post['name'], 
               "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_user_group", $insert);
 
            $json = array(
                "error"  =>  0,
                "result" => array( 
                    "query" => $this->db->last_query(),
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }

    public function update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array( 
                "name"     =>  $post['data']['name'], 
                "all_access"     =>  $post['data']['all_access'], 
                "sharing_team_access"     =>  $post['data']['all_access'] ? 1 :  $post['data']['sharing_team_access'], 
               
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_user_group", $update, 'id=' . $post['data']['id']);

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "note"  => "Update done",
                ),
            );
            echo json_encode($json);
        }
    }
 

    public function fn_delete()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) { 
            $update = array(
                "presence"  => 0,
            );
            $this->db->update("crm3_user_group", $update, 'id=' . $post['data']['id']);
        
            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "note"  => "delete done",
                ),
            );
            echo json_encode($json);
        }
    }
 
}
