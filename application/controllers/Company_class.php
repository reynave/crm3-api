<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Company_class extends CI_Controller
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

        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_company_class where presence = 1 order by name');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"        => $row->id,
                "name" => $row->name,
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
            ),
        );

        echo json_encode($json);
    }

    

    function price_list($id = 0)
    {
        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_product where presence = 1 order by name limit 200');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "sku"            => $row->sku,
                "currency" => $this->model->currency(),
                "customer_price"  => $this->model->crm3('price', 'company_class_price_list', 'id_product = "' . $row->id . '" and id_company_class = "' . $id . '"'),
                "price"       => $row->price,
                "name"         => $row->name,
                "product_type"   =>  $this->model->crm3('name', 'product_type', 'id=' . $row->id_product_type),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),
                "check" => false
            );
            array_push($data, $temp);
            $i++;
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
            ),
        );

        echo json_encode($json);
    }

    function update_price()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {

            if ($this->model->crm3('id', 'company_class_price_list', 'id_company_class =' . $post['id'] . ' and id_product = ' . $post['data']['id'])) {

                $update = array(
                    "price"    => $post['data']['customer_price'],
                    "update_date" => date('Y-m-d H:i:s'),
                    "update_by" =>  $this->model->id_user(),
                );
                $this->db->update("crm3_company_class_price_list", $update, 'id_company_class =' . $post['id'] . ' and id_product = ' . $post['data']['id']);
            } else {

                $insert = array(
                    "id_company_class" => $post['id'],
                    "id_product" => $post['data']['id'],
                    "price"    => $post['data']['customer_price'],
                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                    "update_by" =>  $this->model->id_user(),
                );
                $this->db->insert("crm3_company_class_price_list", $insert);
            }


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


    public function erase_price()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {

            $this->db->delete("crm3_company_class_price_list", 'id_company_class =' . $post['id'] . ' and id_product = ' . $post['data']['id']);

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
}
