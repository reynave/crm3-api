<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product extends CI_Controller
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

        $this->load->library('ssp');
        // DB table to use
        //$table = 'crm3_company';
        $currency = $this->model->currency();
        $table = <<<EOT
 (
    SELECT p.id, p.sku, p.price, p.name, pt.name AS 'type', r.symbol as 'currency'
    FROM crm3_product AS p 
    LEFT JOIN crm3_product_type AS pt ON pt.id = p.id_product_type
    LEFT JOIN crm3_currency AS r ON r.id = p.id_currency
    
    WHERE p.presence = 1
  
 ) temp
EOT;
        //    {$this->model->user_group('c.id_user')['query']}

        // Table's primary key
        $primaryKey = 'id';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array('db' => 'id', 'dt' => 0),
            array('db' => 'sku',  'dt' => 1),
            array('db' => 'name',  'dt' => 2),

            array('db' => 'price',  'dt' => 3),
            array('db' => 'type',  'dt' => 4),
            
            array('db' => 'currency',  'dt' => 5),
 
        );

        // SQL server connection information
        $sql_details = array(
            'user' => $this->db->username,
            'pass' => $this->db->password,
            'db'   => $this->db->database,
            'host' => $this->db->hostname
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        * If you just want to use the basic configuration for DataTables with PHP
        * server-side, there is no need to edit below this line.
        */
        //require('ssp.class.php');

        echo json_encode(
            $this->ssp->simple($_GET, $sql_details, $table, $primaryKey, $columns)
        );
    }



    public function detail($id=0)
    {

       
        $query = $this->db->query('select * from crm3_product where id= "'.$id.'" and presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $data = array(
                "id"            => $row->id,
                "sku"           => $row->sku,
                "name"         => $row->name,
                "qty"         => $row->qty,
                "id_product_type"         => $row->id_product_type,
                "product_type"  => $this->model->crm3('name', 'product_type', 'id = ' . $row->id_product_type),
                "input_date"    => $row->input_date,
                "input_by"      => $this->model->user($row->input_by),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),

            ); 
        }


        $product_type = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_product_type order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "name"         => $row->name,
                "stock"         => (bool) $row->stock,
            );
            array_push($product_type, $temp);
            $i++;
        }

        $product_location = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_product_location where id_product =  "'.$id.'" and presence = 1  order by id ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "location"      => $row->location, 
                "location_code"      => $row->location_code, 
                
            );
            array_push($product_location, $temp);  
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data"              => $data,
                "product_type"      => $product_type,  
                "product_location"  => $product_location,  
            ),
        );

        echo json_encode($json);
    }

    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $insert = array(
                "name"              => $post['name'],
                "sku"               => $post['sku'],
                "qty"               => $post['qty'],
                "id_product_type"   => $post['id_product_type'],

                "input_date"    => date('Y-m-d H:i:s'),
                "update_date"   => date('Y-m-d H:i:s'),
                "input_by"      =>  $this->model->id_user(),
                "check"         => false

            );
            $this->db->insert("crm3_product", $insert);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }

    public function update(){
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array( 
                "name"              => $post['data']['name'],
                "sku"               => $post['data']['sku'],
                "qty"               => $post['data']['qty'],
                "id_product_type"   => $post['data']['id_product_type'],
 
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_product", $update, 'id=' . $post['id']);

            $json = array(
                "error"  =>  0,
                "result" => array( 
                    "note"  => "Update done 2",
                ),
            );
            echo json_encode($json);
        }
    }

    public function fn_delete(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];

            foreach ($post as $row) {
                $update = array(
                    "presence"  => 0,
                );
                $this->db->update("crm3_product", $update, 'id=' .  $row['id']);
            }

            $json = array(
                "error"  =>  0,
                "result" => array( 
                    "note"  => "delete done",
                ),
            );
            echo json_encode($json);
        }
    }

    function fn_addProduct_location(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $insert = array(
                "id_product" => $post['id'],  

            );
            $this->db->insert("crm3_product_location", $insert);

            $product_location = [];
            $id = $post['id'];
            $query = $this->db->query('select * from crm3_product_location where id_product =  "'.$id.'"  order by id ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id"            => $row->id,
                    "location"      => $row->location, 
                );
                array_push($product_location, $temp);  
            }

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "product_location" =>  $product_location,
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }

    public function fn_deleteProduct_location(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) { 
 
            $update = array(
                "presence"  => 0,
            );
            $this->db->update("crm3_product_location", $update, 'id=' .  $post['id']);
          

            $json = array(
                "error"  =>  0,
                "result" => array( 
                    "db"  => $this->db->last_query(),
                    "note"  => "delete done",
                ),
            );
            echo json_encode($json);
        }
    }

    function fn_updateLocation(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) { 
 
            $update = array(
                "location"  => $post['location'],
                "location_code"  => $post['location_code'], 
            );
            $this->db->update("crm3_product_location", $update, 'id=' .  $post['id']);
          

            $json = array(
                "error"  =>  0,
                "result" => array( 
                    "db"  => $update,
                    "note"  => "Update done 1",
                ),
            );
            echo json_encode($json);
        }
    }
}
