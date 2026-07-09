<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
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


    function tree()
    { 
        $data = json_encode($this->model->user_recursive());
        echo $data;
    }


    function haha()
    {
        print_r(self::tree());
    }

    public function index()
    {
        
        $group = [];
        $query = $this->db->query('select * from crm3_user_group where 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($group, $temp);
        }

        $access = [];
        $query = $this->db->query('select * from crm3_user_access where 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($access, $temp);
        }

        $parent = $this->db->query('select id, concat(first_name," ",last_name) as "name" from crm3_user where presence = 1 and status = 1 ');


        $json = array(
            "error"  =>  0,
            "result" => array(
                "access" => $access,
                "group" => $group,
                "data" => $this->model->user_recursive(),
                "parent" =>  $parent->result(),
                
            ),
        );

        echo json_encode($json);
    }

 

    public function detail($id = 0)
    {

        $data = array(
            "id" => 0,
            "id_user_access" => 0,
            "id_user_group" =>  0,
            "first_name" => "",
            "last_name" => "",
            "code" => "",
            "max_discount" => 0,
            "email" => "",
            "status" => 0,
        );

        $query = $this->db->query('select * from crm3_user where presence = 1 and id = "' . $id . '" ');
        foreach ($query->result() as $row) {
            $data = array(
                "id" => $row->id,
                "id_parent" => $row->id_parent,
                "id_user_access" => (int) $row->id_user_access,
                "id_user_group" =>  (int) $row->id_user_group,
                "first_name" => $row->first_name,
                "last_name" => $row->last_name,
                "code" => $row->code,
                "target_amount_year" => $row->target_amount_year,
                "max_discount" => (int) $row->max_discount,
                "email" => $row->email,
                "status" => $row->status,
            );
        }

        $parent = [];
        $query = $this->db->query('select * from crm3_user where presence = 1 and id != "' . $id . '" ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" =>  $row->first_name . ' ' . $row->last_name,
            );
            array_push($parent, $temp);
        }


        $user_access = [];
        $query = $this->db->query('select * from crm3_user_access where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => (int) $row->id,
                "name"  => $row->name,
            );
            array_push($user_access, $temp);
        }


        $user_group = [];
        $query = $this->db->query('select * from crm3_user_group where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => (int) $row->id,
                "name"  => $row->name,
            );
            array_push($user_group, $temp);
        }


        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1 order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }


        $user_target = [];
        $query = $this->db->query('select * from crm3_user_target where id_user = ' . $id . ' order by year ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "currency"      => $this->model->currency(),
                "year"          => $row->year,
                "amount"        => $row->amount,
                "leads"         => $row->leads,
                "opportunities" => $row->opportunities,
                "quotes"        => $row->quotes, 
            );
            array_push($user_target, $temp);
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "parent" => $parent,
                "user_target" => $user_target,
                "user_access" => $user_access,
                "user_group" => $user_group,
                "user" => $user,

            ),
        );

        echo json_encode($json);
    }

    function fn_deleteTarget(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) { 
            $this->db->delete("crm3_user_target", "id = ".$post['id']); 
            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                ),
            );
            echo json_encode($json);
        }
    }

    function fn_insertTargetAmount()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "year"  => $post['data']['year'],
                "amount"  => $post['data']['amount'],
                "leads"  => $post['data']['leads'],
                "opportunities"  => $post['data']['opportunities'],
                "quotes"  => $post['data']['quotes'],
                
                "id_user"  => $post['id'],
            );
            $this->db->insert("crm3_user_target", $update);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                ),
            );
            echo json_encode($json);
        }
    }

    function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $id = $this->model->number('user');
            $data = array(
                "id" => $id,
                "id_parent" => $post['data']['id_parent'],
                "first_name" => $post['data']['first_name'],
                "last_name" => $post['data']['last_name'],
                "email" =>  $post['data']['email'],
                "password" =>  $post['data']['password'] ?  md5($post['data']['password']) : md5("NEWUSER" . rand(10000, 99999)),
                "status" => false,
            );

            $this->db->insert('crm3_user', $data);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "note"  => "Update done",
                    "id" => $id,
                ),
            );
            echo json_encode($json);
        }
    }

    public function fn_update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            if ($post['id'] == 'new') {
                $id = $this->model->number('user');
                $insert = array(
                    "id" =>  $id,
                    "input_date"    => date('Y-m-d H:i:s'),
                    "update_date"   => date('Y-m-d H:i:s'),
                    "input_by"      =>  $this->model->id_user(),
                );
                $this->db->insert('crm3_user', $insert);
            } else {
                $id = $post['id'];
            }


            $update = array(
                "id_user_access"     => isset($post['data']['id_user_access']) ? $post['data']['id_user_access'] : "0",
                "id_user_group"     => isset($post['data']['id_user_group']) ? $post['data']['id_user_group'] : "0",
                "first_name"     => isset($post['data']['first_name']) ? $post['data']['first_name'] : "",
                "last_name"     => isset($post['data']['last_name']) ? $post['data']['last_name'] : "",
                "code"     => isset($post['data']['code']) ? $post['data']['code'] : "",
                "max_discount"     => isset($post['data']['max_discount']) ? $post['data']['max_discount'] : "",
                "email"     => isset($post['data']['email']) ? $post['data']['email'] : "",
                "target_amount_year" => isset($post['data']['target_amount_year']) ? $post['data']['target_amount_year'] : "0",
                "status" =>  $post['data']['status'],
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_user", $update, 'id=' . $id);


            if ($post['data']['pass']) {
                $update = array(
                    "password" =>  md5($post['data']['pass']),
                );
                $this->db->update("crm3_user", $update, 'id=' . $id);
            }

            foreach ($post['user_target'] as $rec) {
                $update = array(
                    "amount" => $rec['amount'],
                    "year" => $rec['year'],
                    "leads" => $rec['leads'],
                    "opportunities" => $rec['opportunities'],
                    "quotes" => $rec['quotes'],

                );
                $this->db->update("crm3_user_target", $update, 'id=' . $rec['id']);
            }



            $json = array(
                "error"  =>  0,
                "result" => array(
                    "update" => $update,
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
                $this->db->update("crm3_contact", $update, 'id=' .  $row['id']);
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
            $this->db->update("crm3_contact", $update, 'id=' .  $post['id']);


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


    function user_group($id)
    {

        echo $this->model->user_group($id, "id_user")['query'];
    }



    function user_grouphh($id)
    {
        $query = $this->db->query('select id,concat(first_name," ",last_name) as "name" from crm3_user where presence = 1 and id = ' . $id . ' order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            echo $row->id . ' ' . $row->name . ' - ';
        }
    }
}
