<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Opportunity_closed_reason extends CI_Controller
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
        $query = $this->db->query('select * from crm3_opportunity_closed_reason where presence = 1 order by closed_win DESC, note ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"   => $row->id,
                "note" => $row->note,   
                "closed_win" => $row->closed_win,   
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
                "note"      => $post['note'],  
                "closed_win" => $post['closed_win'],  
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'), 

            );
            $this->db->insert("crm3_opportunity_closed_reason", $insert);
 
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
                "note"     =>  $post['data']['note'], 
                "closed_win" => $post['data']['closed_win'],  
                "closed_win"     =>  $post['data']['closed_win'], 
                "update_date" => date('Y-m-d H:i:s'), 
            );
            $this->db->update("crm3_opportunity_closed_reason", $update, 'id=' . $post['data']['id']);

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
            $this->db->update("crm3_opportunity_closed_reason", $update, 'id=' . $post['data']['id']);
        
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
