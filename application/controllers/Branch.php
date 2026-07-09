<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Branch extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->key = $this->db->key;
        header('Access-Control-Allow-Origin: *');
        //header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Access-Control-Allow-Headers: key, token,  Content-Type");

        header('Access-Control-Allow-Methods: GET, POST, PUT');
        header('Content-Type: application/javascript');
        // error_reporting(E_ALL);
        date_default_timezone_set('Asia/Jakarta');
        $this->db->query("SET time_zone = '+07:00'");
        //  $this->core->https();

        if (!$this->model->header()) {
            echo $this->model->error("Error auth");
           exit;
        }
    }

    public function index()
    {

        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_branch where presence = 1 order by name ASC limit 50');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"        => $row->id,
                "name" => $row->name,
                "website" => $row->website,
                "phone" => $row->phone,
                "email" => $row->email,
                "fax" => $row->fax,
                "industry" => $this->model->industry($row->id_industry),
                "address" => $row->address_street . ' ' . $row->address_city . ' ' . $row->address_state . ' ' . $row->address_code,
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
                "check" => false

            );
            array_push($data, $temp);
            $i++;
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "total" => (int) $this->model->select('count(id)', 'crm3_branch', 'presence = 1'),
                "total_view" => $i,
                "pages" => 1,
            ),
        );

        echo json_encode($json);
    }


    public function detail($id = 0)
    {

        $query = $this->db->query('select * from crm3_branch where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id"        => $row->id,
                "name" => $row->name,
                "website" => $row->website,
                "phone" => $row->phone,
                "email" => $row->email,
                "branches" => $this->model->select('count(id)','crm3_branch','id_company = '.$row->id),
                "fax" => $row->fax,
                "industry" => $this->model->industry($row->id_industry),
                "address" => $row->address_street . ' ' . $row->address_city . ' ' . $row->address_state . ' ' . $row->address_code,
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
        }

        $contact = [];
        $query = $this->db->query('select * from crm3_contact where presence = 1 and id_company = '.$id.' order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name.' '.$row->last_name.', '.$this->model->title($row->id_title),
                "email" => $row->email,
                "phone" => $row->phone,
                "mobile" => $row->mobile,
                "user" => $this->model->user($row->id_user),

            );
            array_push($contact, $temp);
        }
        
        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "contact" => $contact,
                "opportunity" => "",
                "branch" => "",
            ),
        );

        echo json_encode($json);
    }
   
    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $id = $this->model->number("branch");
            $insert = array(
                "id"            => $id,
                "name"          => $post['branchName'],
                "id_company"    => $post['id_company'],
                
                "code" =>  isset($post['code']) ?  $post['code'] : "",
                "phone" =>  isset($post['phone']) ?  $post['phone'] : "",
                "fax" =>  isset($post['fax']) ?  $post['fax'] : "",
                "email" =>  isset($post['email']) ?  $post['email'] : "",

                "address_street" =>  isset($post['address_street']) ?  $post['address_street'] : "",
                "address_city" =>  isset($post['address_city']) ?  $post['address_city'] : "",
                "address_state" =>  isset($post['address_state']) ?  $post['address_state'] : "",
                "address_code" => isset($post['address_code']) ?  $post['address_code'] : "",

                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_branch", $insert);

           
            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id" => $id,
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

                "name"     => isset($post['data']['name']) ? $post['data']['name'] : "",
                "email"    => isset($post['data']['email']) ? $post['data']['email'] : "",
                "fax"      => isset($post['data']['fax']) ? $post['data']['fax'] : "",
                "website"  => isset($post['data']['website']) ? $post['data']['website'] : "",

                "address_street" => isset($post['data']['address_street']) ? $post['data']['address_street'] : "",
                "address_city" => isset($post['data']['address_city']) ? $post['data']['address_city']: "",
                "address_state" => isset($post['data']['address_state']) ? $post['data']['address_state'] : "",
                "address_code" => isset($post['data']['address_code']) ? $post['data']['address_code'] : "",
                
                "phone" => isset($post['data']['phone']) ? $post['data']['phone'] : "",
                "id_industry" => isset($post['data']['id_industry']) ? $post['data']['id_industry'] : "0",
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_branch", $update, 'id=' . $post['id']);

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
            $post = $post['data'];

            foreach ($post as $row) {
                $update = array(
                    "presence"  => 0,
                );
                $this->db->update("crm3_branch", $update, 'id=' .  $row['id']);
            }

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

    public function fn_deleteSolo()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "presence"  => 0,
            );
            $this->db->update("crm3_branch", $update, 'id=' .  $post['id']);


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

    public function edit($id = 0)
    {
      
        $query = $this->db->query('select * from crm3_branch where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
      
            $data = array(
                "id"        => $row->id,
                "name" => $row->name,
                "website" => $row->website,
                "phone" => $row->phone,
                "email" => $row->email,
                "fax" => $row->fax,
                "id_industry" => $row->id_industry,

                "address_street" => $row->address_street,
                "address_city" => $row->address_city,
                "address_state" =>  $row->address_state,
                "address_code" => $row->address_code,
                
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),

            );
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
            ),
        );

        echo json_encode($json);
    }
}
