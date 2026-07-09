<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profile extends CI_Controller
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
        //    echo $this->model->error("Error auth");
        //    exit;
        }
    }

    public function index()
    {
        $data = [];
            $id_user =  $this->model->id_user();
        $q = 'select * from crm3_user where presence = 1 and id = "' .   $id_user.'" ';

        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $data = array(
                "id" => $row->id,
                "user_access" => $this->model->crm3('name', 'user_access', 'id=' . $row->id_user_access),
                "user_group" =>  $this->model->crm3('name', 'user_group', 'id=' . $row->id_user_group),
                "code" => $row->code,
                "max_discount" => $row->max_discount,
                "currency" => $this->model->currency(),
                "parent" => $this->model->user($row->id_parent),
                "target_amount_year" =>  $this->model->crm3('amount', 'user_target', 'year =  ' . date('Y') . ' and  id_user = "' . $row->id . '" '),
                "first_name" => $row->first_name,
                "last_name" => $row->last_name,
                "status" => $row->status,
                "email" => $row->email,
                "years" => $this->model->crm3('year', 'user_target', 'year = "' . date('Y') . '"  and id_user = "' . $row->id . '"'),
                "amount" => $this->model->crm3('amount', 'user_target', 'year = "' . date('Y') . '"  and id_user = "' . $row->id . '"'),
                "leads" => $this->model->crm3('leads', 'user_target', 'year = "' . date('Y') . '"  and id_user = "' . $row->id . '"'),
                "opportunities" => $this->model->crm3('opportunities', 'user_target', 'year = "' . date('Y') . '"  and id_user = "' . $row->id . '"'),
                "quotes" => $this->model->crm3('quotes', 'user_target', 'year = "' . date('Y') . '"  and id_user = "' . $row->id . '"'),

                "input_date" => $row->input_date,
                "status" => (bool) $row->status,

            );
        }


        $target = [];
    
         $q2 = 'select * from crm3_user_target where   id_user = ' .   $id_user . ' order BY `year` DESC ';
        // echo $q2;
        // $query = $this->db->query($q2);
        // foreach ($query->result() as $row) {
        //     $temp = array(
        //         "id" => $row->id,
        //         "years" =>  $row->year,
        //         "amount" =>  $row->amount,
        //         "leads" =>  $row->leads,
        //         "opportunities" => $row->opportunities,
        //         "quotes" =>  $row->quotes,
        //     );
        //     array_push($target, $temp);
        // }
        

        $q_user = $this->model->user_group('id_user')['query'];
        
        $year = date('Y');
        $totalTeamTarget = array(
            "year" => $year,
            "amount" => (float) $this->model->crm3('sum(amount)', 'user_target', ' year = "' . $year . '" ' . $q_user),
            "leads" => (float) $this->model->crm3('sum(leads)', 'user_target', ' year = "' . $year . '" ' . $q_user),
            "opportunities" => (float) $this->model->crm3('sum(opportunities)', 'user_target', ' year = "' . $year . '" ' . $q_user),
            "quotes" => (float) $this->model->crm3('sum(quotes)', 'user_target', ' year = "' . $year . '" ' . $q_user),
        );




        $json = array(
            "error"  =>  0,
            "result" => array(
                "id_user" =>   $id_user,
                "q" =>  $q2,
                "data" => $data,
                "totalTeamTarget" => $totalTeamTarget,
                "target" => $target,
                "list" => $this->model->user_recursive(  $id_user),
            ),
        );

        echo json_encode($json);
    }

    function fn_update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {

            $update = array(
                "first_name"     => isset($post['data']['first_name']) ? $post['data']['first_name'] : "",
                "last_name"     => isset($post['data']['last_name']) ? $post['data']['last_name'] : "",
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_user", $update, 'id='  . $this->model->id_user());


            if ($post['data']['password']) {
                $update = array(
                    "password" =>  md5($post['data']['password']),
                );
                $this->db->update("crm3_user", $update, 'id=' . $this->model->id_user());
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


    function user_target($id = 0)
    {
        $user_target = [];
        $query = $this->db->query('select * from crm3_user_target where id_user = ' . $id . ' order by year DESC');
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
                "user_name" => $this->model->user($id),
                "user_target" => $user_target,
            ),
        );

        echo json_encode($json);
    }


    function fn_targetUpdate()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            foreach ($post['user_target'] as $rec) {
                $update = array(
                    "amount" => $rec['amount'],
                    "year" => $rec['year'],
                    "leads" => $rec['leads'],
                    "opportunities" => $rec['opportunities'],
                    "quotes" => $rec['quotes'],

                );
                $this->db->update("crm3_user_target", $update, 'id =' . $rec['id']);
            }

            $json = array(
                "error"  =>  0,
                "result" =>  $update,
                "post" => $post,
            );
        }
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

            $insert = array(
                "year"  => $post['data']['year'],
                "amount"  => $post['data']['amount'],
                "leads"  => $post['data']['leads'],
                "opportunities"  => $post['data']['opportunities'],
                "quotes"  => $post['data']['quotes'], 
                "id_user"  => $post['id'],
            );
            $this->db->insert("crm3_user_target", $insert);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                ),
            );
            echo json_encode($json);
        }
    }
}
