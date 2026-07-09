<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Access_right extends CI_Controller
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


        if (!$this->model->header($this->openAPI)) {
            echo $this->model->error("Error auth");
            exit;
        }
    }

    function index()
    {
        $user_access = [];
        $query = $this->db->query('select * from crm3_user_access where presence = 1');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => (int) $row->id,
                "name" => $row->name,
            );
            array_push($user_access, $temp);
        } 
        $json = array(
            "error"  =>  0,
            "result" => array(
                "user_access" => $user_access,
            ),
        );
        echo json_encode($json);
       
    }

    function auth($id=0)
    {
        $id_user_access = $id; 
        $json = array(
            "error"  =>  0,
            "result" =>  $this->model->access_rules( $id_user_access ),
        );
        echo json_encode($json); 
       
    }

    function detail($id_user_access = 0)
    {

        $user_access_rules = [];
        $query = $this->db->query('select * from crm3_user_access_rules where id_user_access = "' . $id_user_access . '" order by id_module asc');
        foreach ($query->result() as $row) {
            $temp = array(
                "module" => array(
                    "label" => $this->model->crm3('lang1', 'module', 'id=' . $row->id_module),
                    "name" => $this->model->crm3('name', 'module', 'id=' . $row->id_module),
                    "id" => $row->id_module,
                    "_access" => (bool) $row->_access,
                    "_update" => (bool) $row->_update,
                    "_insert" => (bool) $row->_insert,
                    "_delete" => (bool) $row->_delete
                ),
            );
            array_push($user_access_rules, $temp);
        }

        $data = array(
            "error" => 0,
            "result" => array(
                "id_user_access" => $id_user_access,
                "name" => $this->model->crm3('name', 'user_access', 'id=' . $id_user_access),
                "user_access_rules" => $user_access_rules,


            ),

        );

        echo json_encode($data);
    }

    function update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array(
                "name" => $post['name'],  
            ); 
            $this->db->update('crm3_user_access',$update,' id =  '. $post['id'] );
    
            foreach ($post['data'] as $row) {
                
                $update = array(
                    "_access" => $row['module']['_access'] ? 1 :0,
                    "_update" => $row['module']['_update'] ? 1 :0,
                    "_insert" => $row['module']['_insert'] ? 1 :0,
                    "_delete" => $row['module']['_delete'] ? 1 :0,

                ); 
                $this->db->update('crm3_user_access_rules',$update,'id_module = "'.$row['module']['id'].'" and id_user_access =  '. $post['id']);
            }


            $id_user_access = $post['id']; 
            $json = array(
                "error"  =>  0,
                "result" =>  $this->model->access_rules( $id_user_access ),
            );
            echo json_encode($json);
        }
    }


    function sync(){
        $query = $this->db->query('select * from crm3_user_access where presence = 1 order by id');
        foreach ($query->result() as $row) {

            $query2 = $this->db->query('select * from crm3_module where presence = 1 order by id');
            foreach ($query2->result() as $row2) {
                $insert = array(
                    "id_user_access" => $row->id,
                    "id_module" => $row2->id,
                    "_access" => 1,
                    "_insert" => 1,
                    "_update" => 1, 
                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    
                );

                if(!$this->model->crm3('id','user_access_rules','id_user_access = '. $row->id.' and  id_module = '.$row2->id)  ){
                    $this->db->insert('crm3_user_access_rules',$insert);
                }
           
            }


        }
    }

    function onSubmit(){
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            
            $insert = array(
                "name" => $post['data']['name'], 
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
            );
            $this->db->insert('crm3_user_access',$insert);

            $id_user_access = $this->model->crm3('id','user_access','1 order by input_date  DESC');


            $query = $this->db->query('select * from crm3_module where presence = 1 order by id');
            foreach ($query->result() as $row) {
                
                $rules = array(
                    "id_user_access" => $id_user_access, 
                    "id_module" => $row->id,
                    "_access" => 1,
                    "_update" => 1,
                    "_insert" => 1,
                    "_delete" => 1,
                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                ); 
                $this->db->insert('crm3_user_access_rules',$rules);
            }
 
            $json = array(
                "error"  =>  0,
                "result" =>  array(
                    "id" => $id_user_access,
                ),
            );
            echo json_encode($json);
        }
    }
}
