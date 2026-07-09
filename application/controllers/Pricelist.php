<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pricelist extends CI_Controller
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

    public function indexd()
    {

        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_product where presence = 1 order by sku ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "sku"            => $row->sku,
                "currency" => $this->model->currency(),
                "price"       => $row->price,
                "name"         => $row->name,
                "description"   => $row->description,
                "id_product_type" => $row->id_product_type,
                "product_type"   =>  $this->model->crm3('name', 'product_type', 'id=' . $row->id_product_type),
                "qty"               => $this->model->crm3('sum(qty)', 'product_location', 'presence = 1 and  id_product =' . $row->id),
                "input_date"    => $row->input_date,
                "input_by"      => $this->model->user($row->input_by),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),
                "status"        =>  $row->status,
                "check" => false
            );
            array_push($data, $temp);
            $i++;
        }

        $customer_class = [];
        $query = $this->db->query('select * from crm3_company_class where presence = 1 order by input_date');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "name"         => $row->name,
                "input_date"    => $row->input_date,
                "update_date"   => $row->update_date,

            );
            array_push($customer_class, $temp);
            $i++;
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "customer_class" => $customer_class,
                "total" => (int) $this->model->select('count(id)', 'crm3_product', 'presence = 1'),
                "total_view" => $i,
                "pages" => 1,

            ),
        );

        echo json_encode($json);
    }



    function index()
    {

        $this->load->library('ssp');
        $currency = $this->model->currency();
        $table = <<<EOT
 (
    SELECT p.id, p.sku, p.price, p.name, pt.name AS 'type', r.symbol as 'currency', sum(l.qty) AS 'qty'
    FROM crm3_product AS p 
    LEFT JOIN crm3_product_type AS pt ON pt.id = p.id_product_type
    LEFT JOIN crm3_currency AS r ON r.id = p.id_currency
    LEFT JOIN crm3_product_location AS l ON l.id_product = p.id
    WHERE p.presence = 1
    GROUP BY p.id
  
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
            array('db' => 'currency',  'dt' => 3),
            array('db' => 'price',  'dt' => 4),
            array('db' => 'type',  'dt' => 5),
            array('db' => 'qty',  'dt' => 6),



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

    public function modal($module = "", $id = "")
    {
        if ($module == "opportunity") {
            $id_company = $this->model->crm3('id_company', 'opportunity', 'id="' . $id . '" ');
            $id_company_class =  $this->model->crm3('id_company_class', 'company', 'id = "' . $id_company . '" ');
        } else if ($module == "lead") {
            $id_company_class =  $this->model->crm3('id_company_class', 'lead', 'id = "' . $id . '" ');
        } else if ($module == "quote") {
            // http://localhost/application/crm3-ng8/api-v1/pricelist/modal/quote/5
            $id_opportunity = $this->model->crm3('id_opportunity', 'quote', 'id="' . $id . '" ');
            $id_company = $this->model->crm3('id_company', 'opportunity', 'id="' . $id_opportunity . '" ');
            $id_company_class =  $this->model->crm3('id_company_class', 'company', 'id = "' . $id_company . '" ');
        } else if ($module == "salesOrder") {
            // http://localhost/application/crm3-ng8/api-v1/pricelist/modal/salesOrder/5 
            $id_company = $this->model->crm3('id_company', 'sales_order', 'id="' . $id . '" ');
            $id_company_class =  $this->model->crm3('id_company_class', 'company', 'id = "' . $id_company . '" ');
        }

        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_product where presence = 1 order by sku ASC');
        foreach ($query->result() as $row) {

            $customer_price = $this->model->crm3('price', 'company_class_price_list', 'id_product = "' . $row->id . '" and  id_company_class = "' . $id_company_class . '" ');
            $temp = array(
                "id"            => $row->id,
                "sku"            => $row->sku,
                "currency" => $this->model->currency(),
                "price"       => $customer_price > 0 ? $customer_price : $row->price,
                "customer_price" => $customer_price > 0 ? true : false,
                "name"         => $row->name,
                "description"   => $row->description,
                "id_product_type" => $row->id_product_type,
                "product_type"   =>  $this->model->crm3('name', 'product_type', 'id=' . $row->id_product_type),
                "qty"               => $this->model->crm3('sum(qty)', 'product_location', 'presence = 1 and  id_product =' . $row->id),
                "input_date"    => $row->input_date,
                "input_by"      => $this->model->user($row->input_by),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),
                "status"        =>  $row->status,
                "check" => false
            );
            array_push($data, $temp);
            $i++;
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "module" => $module,
                "id" => $id,
                "pages" => 1,

            ),
        );

        echo json_encode($json);
    }

    public function insert_pricelist()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $insert = array(
                "name"          => $post['name'],
                "sku"           => $post['sku'],
                "id_currency"   => $this->model->crm3('id', 'currency', 'isDefault=1'),
                "id_product_type" => $post['id_product_type'],
                "description"   => $post['description'],
                "price"         => $post['price'],
                "status"        => $post['status'],

                "input_date"    => date('Y-m-d H:i:s'),
                "update_date"   => date('Y-m-d H:i:s'),
                "input_by"      =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_product", $insert);

            $id = $this->model->select('id', 'crm3_product', '1 order by input_date desc');

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id"    =>  $id,
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }

    /** DETAIL  */
    public function detail($id = 0)
    {
        $data = [];
        $query = $this->db->query('select * from crm3_product where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id"            => $row->id,
                "sku"            => $row->sku,
                "currency" => $this->model->currency(),
                "price"       => $row->price,
                "name"         => $row->name,
                "description"   => $row->description,
                "id_product_type" => $row->id_product_type,
                "product_type"   =>  $this->model->crm3('name', 'product_type', 'id=' . $row->id_product_type),
                "qty"               => $this->model->crm3('sum(qty)', 'product_location', 'presence = 1 and id_product =' . $row->id),
                "input_date"    => $row->input_date,
                "input_by"      => $this->model->user($row->input_by),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),
                "status"        =>  $row->status,
            );
        }


        $product_location = [];
        $q = " select * from crm3_product_location where id_product = '" . $id . "' and presence = 1 order by id asc ";
        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "location" => $row->location,
                "location_code" => $row->location_code,
                "qty" => $row->qty,
            );
            array_push($product_location, $temp);
        }


        $product_type = [];
        $q = " select * from crm3_product_type ";
        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "stock" => $row->stock,
            );
            array_push($product_type, $temp);
        }

        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "priceList" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => '../../api-v1/'.$row->url,

                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "attachment" => $attachment,
                "product_location" => $product_location,
                "product_type" => $product_type,
            ),
        );

        echo json_encode($json);
    }


    public function fn_attach_delete()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "presence" => 0,
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_attachment", $update, ' id=' . $post['id']);

            unlink($this->model->crm3('full_path', 'attachment', 'id=' . $post['id']));
            $json = array(
                "error"  =>  0,
                "result" => "",
            );
            echo json_encode($json);
        }
    }

    public function update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array(
                "name"              => $post['data']['name'],
                "sku"               => $post['data']['sku'],
                "price"               => $post['data']['price'],
                "description"       => $post['data']['description'],
                "id_product_type"   => $post['data']['id_product_type'],

                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_product", $update, 'id=' . $post['id']);

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "upddate" => $update,
                    "note"  => "Update done",
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
            $this->db->update("crm3_product", $update, 'id=' .  $post['id']);

            $update = array(
                "presence"  => 0,
            );
            $this->db->update("crm3_product_location", $update, 'id_product =' .  $post['id']);



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

    function fn_addProduct_location()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $insert = array(
                "id_product" => $post['id'],

            );
            $this->db->insert("crm3_product_location", $insert);

            $product_location = [];
            $id = $post['id'];
            $query = $this->db->query('select * from crm3_product_location where id_product =  "' . $id . '" and presence =1  order by id ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id"            => $row->id,
                    "location"      => $row->location,
                    "qty"      => $row->qty,
                );
                array_push($product_location, $temp);
            }

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "product_location" =>  $product_location,
                    "note"  => 'select * from crm3_product_location where id_product =  "' . $id . '"  order by id ASC',
                ),
            );
            echo json_encode($json);
        }
    }

    function fn_deleteProduct_location()
    {
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

    function fn_updateLocation()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "location"  => $post['location'],
                "location_code"  => $post['location_code'],
                "qty"  => $post['qty'],
            );
            $this->db->update("crm3_product_location", $update, 'id=' .  $post['id']);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "db"  => $this->db->last_query(),
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
                $this->db->update("crm3_price", $update, 'id=' .  $row['id']);
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



    /**
     * PRICE LIST here !
     */

    /**
     * catalog
     */


    public function catalog($id = 0)
    {
        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_price_list_catalog where id_price_list = "' . $id . '" and presence = 1 order by input_date');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "name"          => $row->name,
                "description"   => $row->description,
                "qty"           => (int) $this->model->select('count(id)', 'crm3_price_list_product', 'id_price_list_catalog = "' . $row->id . '" and presence = 1'),
                "input_date"    => $row->input_date,
                "input_by"      => $this->model->user($row->input_by),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),
                "status"        =>  $row->status,

            );
            array_push($data, $temp);
            $i++;
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "total" => (int) $this->model->select('count(id)', 'crm3_price_list_catalog', 'id_price_list = "' . $id . '" and presence = 1'),
                "total_view" => $i,
                "pages" => 1,
                "pricelist" => $this->model->select('name', 'crm3_price_list', 'id = "' . $id . '" and presence = 1'),

            ),
        );

        echo json_encode($json);
    }

    public function insert_catalog()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $insert = array(
                "name"          => $post['name'],
                "id_price_list" => $post['id_price_list'],
                "description"   => $post['description'],
                "status"        => $post['status'],

                "input_date"    => date('Y-m-d H:i:s'),
                "update_date"   => date('Y-m-d H:i:s'),
                "input_by"      =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_price_list_catalog", $insert);

            $id = $this->model->select('id', 'crm3_price_list_catalog', '1 order by input_date desc');

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id"    =>  $id,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }



    /**
     * Product List
     */

    public function productlist($id = 0)
    {
        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_price_list_product where id_price_list_catalog = "' . $id . '" and presence = 1 order by input_date');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"                        => $row->id,
                "id_price_list"             => $row->id_price_list,
                "id_price_list_catalog"     => $row->id_price_list_catalog,
                "id_product"                => $row->id_product,
                "product"                => $this->model->select('name', 'crm3_product', 'id=' . $row->id_product),
                "sku"                => $this->model->select('sku', 'crm3_product', 'id=' . $row->id_product),

                "currency" => $this->model->currency(),

                "description"   => $row->description,
                "price"         => $row->price,

                "input_date"    => $row->input_date,
                "input_by"      => $this->model->user($row->input_by),
                "update_date"   => $row->update_date,
                "update_by"     =>  $this->model->user($row->update_by),
                "status"        =>  $row->status,

            );
            array_push($data, $temp);
            $i++;
        }

        $selectProduct = [];
        $query = $this->db->query('select * from crm3_product where presence = 1 order by name');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"                        => $row->id,
                "name"             => $row->name,
            );
            array_push($selectProduct, $temp);
            $i++;
        }
        $id_price_list =  $this->model->select('id_price_list', 'crm3_price_list_catalog', 'id = "' . $id . '" and presence = 1');

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data"      => $data,
                "total"     => (int) $this->model->select('count(id)', 'crm3_price_list_product', 'id_price_list_catalog = "' . $id . '" and presence = 1'),
                "selectProduct"   =>  $selectProduct,
                "pricelist" => $this->model->select('name', 'crm3_price_list', 'id = "' . $id_price_list . '" and presence = 1'),
                "catalog"   => $this->model->select('name', 'crm3_price_list_catalog', 'id = "' . $id . '" and presence = 1'),
                "id_pricelist" => $id_price_list,


            ),
        );

        echo json_encode($json);
    }

    public function insertProductPrice()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $id_price_list_catalog = $post['id'];
            $id_price_list = $this->model->select('id_price_list', 'crm3_price_list_catalog', 'id=' . $post['id']);
            $post = $post['data'];
            $insert = array(
                "id_product"          => $post['id_product'],
                "id_price_list_catalog" => $id_price_list_catalog,
                "id_price_list" => $id_price_list,
                "price"         => $post['price'],
                "description"         => $post['description'],
                "status"        => 1,

                "input_date"    => date('Y-m-d H:i:s'),
                "update_date"   => date('Y-m-d H:i:s'),
                "input_by"      =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_price_list_product", $insert);

            $id = $this->model->select('id', 'crm3_price_list_product', '1 order by input_date desc');

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id"    =>  $id,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }

    public function deleteProductPrice()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "presence"    => 0,
                "update_date"   => date('Y-m-d H:i:s'),
                "update_by"      =>  $this->model->id_user(),

            );
            $this->db->update("crm3_price_list_product", $update, 'id = ' . $post['id']);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id"    => $post['id'],
                    "query" => $this->db->last_query(),
                    "note"  => "delete done",
                ),
            );
            echo json_encode($json);
        }
    }

    public function updateProductPrice()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "id_product"    => $post['data']['id_product'],
                "description"   => $post['data']['description'],
                "price"         => $post['data']['price'],

                "update_date"   => date('Y-m-d H:i:s'),
                "update_by"     => $this->model->id_user(),
            );
            $this->db->update("crm3_price_list_product", $update, 'id = ' . $post['id']);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "post"    => $post['data'],
                    "update" => array(
                        "product" => $this->model->select('name', 'crm3_product', 'id=' . $post['data']['id_product']),
                    ),
                    "query" => $this->db->last_query(),
                    "note"  => "delete done",
                ),
            );
            echo json_encode($json);
        }
    }




    public function onChangeLeadStatus()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "id_lead_status"  => $post['id_lead_status'],
            );
            $this->db->update("crm3_lead", $update, 'id=' .  $post['id_lead']);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "data" => array(
                        "name" => $this->model->select('name', 'crm3_lead_status', 'id=' . $post['id_lead_status']),
                        "color" => $this->model->select('color', 'crm3_lead_status', 'id=' . $post['id_lead_status']),
                    ),
                    "query" => $this->db->last_query(),
                    "note"  => "update status done",
                ),
            );
            echo json_encode($json);
        }
    }
    public function priceList_insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $id_lead = $this->model->number("lead");
            $insert = array(
                "id" => $id_lead,
                "id_user"      => $post['id_user'],
                "id_title"      => $post['id_title'],
                "first_name"      => $post['first_name'],
                "last_name"      => $post['last_name'],
                "email"      => $post['email'],
                "company"   => $post['company'],
                "id_lead_source" => $post['id_lead_source'],
                "website" => $post['website'],
                "phone" => $post['phone'],
                "mobile" => $post['mobile'],

                "id_company" => $post['id_company'],
                "id_industry" => $post['id_industry'],


                "address_street" => $post['address_street'],
                "address_city" => $post['address_city'],
                "address_state" => $post['address_state'],
                "address_code" => $post['address_code'],

                "id_lead_status" => $post['id_lead_status'],


                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_lead", $insert);

            if ($post['id_company'] != "0") {
                $update = array(
                    "company"           => $this->model->select('name', 'crm3_company', 'id=' . $post['id_company']),
                    "address_street"    => "",
                    "address_city"      => "",
                    "address_state"     => "",
                    "address_code"      => "",

                    "id_industry"       =>  $this->model->select('id_industry', 'crm3_company', 'id=' . $post['id_company']),
                    "website"           => "",


                );
                $this->db->update("crm3_lead", $update, 'id=' . $id_lead);
            }


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id_lead" => $id_lead,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert done",
                ),
            );
            echo json_encode($json);
        }
    }

    public function priceList_update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array(

                "id_user"      => $post['data']['id_user'],
                "id_title"      => $post['data']['id_title'],
                "first_name"      => $post['data']['first_name'],
                "last_name"      => $post['data']['last_name'],
                "email"      => $post['data']['email'],
                "company"   => $post['data']['company'],
                "id_lead_source" => $post['data']['id_lead_source'],
                "id_company" => $post['data']['id_company'],
                "id_lead_status" => $post['data']['id_lead_status'],
                "website" => $post['data']['website'],

                "address_street" => $post['data']['address_street'],
                "address_city" => $post['data']['address_city'],
                "address_state" => $post['data']['address_state'],
                "address_code" => $post['data']['address_code'],

                "phone" => $post['data']['phone'],
                "mobile" => $post['data']['mobile'],
                "id_industry" => $post['data']['id_industry'] ? $post['data']['id_industry'] : "0",
                "opportunity" => $post['data']['opportunity'],
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_lead", $update, 'id=' . $post['id_lead']);

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

    public function selected()
    {
        $title = [];
        $query = $this->db->query('select * from crm3_title where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($title, $temp);
        }

        $lead_source = [];
        $query = $this->db->query('select * from crm3_lead_source where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($lead_source, $temp);
        }

        $industry = [];
        $query = $this->db->query('select * from crm3_industry where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($industry, $temp);
        }

        $status = [];
        $query = $this->db->query('select * from crm3_lead_status where presence = 1 order by value ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "value" => $row->value,
                "color" => $row->color,

            );
            array_push($status, $temp);
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

        $json = array(
            "error"  =>  0,
            "result" => array(
                "title" => $title,
                "lead_source"  => $lead_source,
                "industry"  => $industry,
                "id_user"  => $user,
                "status"    => $status

            ),
        );
        echo json_encode($json);
    }

    public function httpSelectUser()
    {

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1 order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "user"  => $user,
            ),
        );
        echo json_encode($json);
    }

    public function getCompany()
    {

        $data = [];
        $q = " select id, name, id_industry, address_street, address_city, address_state, address_code, website
            from crm3_company where presence = 1 order by name ASC ";

        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "industry" =>  $this->model->industry($row->id_industry),
                "website" => $row->website,
                "address" => $row->address_street . ' ' . $row->address_city . ' ' . $row->address_state . ' ' . $row->address_code . ' ',

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





    public function fn_updateLeadStatus()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {


            foreach ($post['data'] as $row) {
                $update = array(
                    "id_lead_status"  =>  $post['id_lead_status'],
                );
                $this->db->update("crm3_lead", $update, 'id=' .  $row['id']);
            }

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "note"  => "update Status done",
                ),
            );
            echo json_encode($json);
        }
    }


    public function edit($id = 0)
    {

        $query = $this->db->query('select * from crm3_lead where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $dbAddress =
                $this->model->select('address_street', 'crm3_company', 'id=' . $row->id_company) . ' ' .
                $this->model->select('address_city', 'crm3_company', 'id=' . $row->id_company) . ' ' .
                $this->model->select('address_state', 'crm3_company', 'id=' . $row->id_company) . ' ' .
                $this->model->select('address_code', 'crm3_company', 'id=' . $row->id_company);

            $data = array(
                "id"            => $row->id,
                "id_user"       => $row->id_user,
                "id_title"      => $row->id_title,
                "first_name"    => $row->first_name,
                "last_name"     => $row->last_name,
                "id_company"    => $row->id_company,
                "id_lead_status"    => $row->id_lead_status,
                "company"       => $row->company,
                "name"      => $this->model->title($row->id_title) . ' ' . $row->first_name . ' ' . $row->last_name,

                "id_lead_source" => $row->id_lead_source,
                "website" => $row->website,
                "phone" => $row->phone,
                "mobile" => $row->mobile,
                "fax" => $row->fax,
                "id_industry" => $row->id_industry,
                "email" => $row->email,

                "opportunity" => $row->opportunity,

                "address_street" => $row->address_street,
                "address_city"  => $row->address_city,
                "address_state" =>  $row->address_state,
                "address_code"  => $row->address_code,

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),

                "dbWebsite" =>  $row->id_company > 0 ? $this->model->select('website', 'crm3_company', 'id=' . $row->id_company) : "",
                "dbIndustry" => $row->id_company > 0 ? $this->model->industry($this->model->select('id_industry', 'crm3_company', 'id=' . $row->id_company))  : "",
                "dbAddress" =>  $row->id_company > 0 ? $dbAddress : "",
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

    public function convert()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        $error = 0;
        $last_query = "";
        if ($post) {
            $this->db->trans_start();

            $id_lead = $post['id'];

            $update = array(
                "presence"  => 0,
                "convert"   => 1,
            );
            $this->db->update("crm3_lead", $update, 'id=' . $id_lead);
            $last_query =  $this->db->last_query();


            if ($this->model->select('id_company', 'crm3_lead', 'id=' . $id_lead) > 0) {
                $id_company = $this->model->select('id_company', 'crm3_lead', 'id=' . $id_lead);
            } else {
                $id_company = $this->model->number("company");

                $insert = array(
                    "id" => $id_company,
                    "name"      => $this->model->select('company', 'crm3_lead', 'id=' . $id_lead),
                    "website" => $this->model->select('website', 'crm3_lead', 'id=' . $id_lead),
                    "phone" => "",
                    "fax" =>  $this->model->select('fax', 'crm3_lead', 'id=' . $id_lead),
                    "email" =>  "",

                    "id_industry" =>  $this->model->select('id_industry', 'crm3_lead', 'id=' . $id_lead),

                    "address_street" => $this->model->select('address_street', 'crm3_lead', 'id=' . $id_lead),
                    "address_city" =>  $this->model->select('address_city', 'crm3_lead', 'id=' . $id_lead),
                    "address_state" => $this->model->select('address_state', 'crm3_lead', 'id=' . $id_lead),
                    "address_code" => $this->model->select('address_code', 'crm3_lead', 'id=' . $id_lead),

                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),

                );
                $this->db->insert("crm3_company", $insert);
            }
            $last_query =  $last_query . ';' . $this->db->last_query();


            $id_contact = $this->model->number("contact");
            $insert = array(
                "id"                => $id_contact,
                "id_company"      => $id_company,
                "first_name"      => $this->model->select('first_name', 'crm3_lead', 'id=' . $id_lead),
                "last_name"     =>  $this->model->select('last_name', 'crm3_lead', 'id=' . $id_lead),
                "id_title" => $this->model->select('id_title', 'crm3_lead', 'id=' . $id_lead),
                "email" =>   $this->model->select('email', 'crm3_lead', 'id=' . $id_lead),
                "mobile" =>  $this->model->select('mobile', 'crm3_lead', 'id=' . $id_lead),
                "phone" =>   $this->model->select('phone', 'crm3_lead', 'id=' . $id_lead),
                "id_user" =>   $this->model->select('id_user', 'crm3_lead', 'id=' . $id_lead),
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
            );
            $this->db->insert("crm3_contact", $insert);
            $last_query =  $last_query . ';' . $this->db->last_query();



            $id_opportunity = $this->model->number("opportunity");
            $insert = array(
                "id"                => $id_opportunity,
                "id_user"           => $post['data']['id_user'],
                "id_lead"           => $id_lead,
                "id_contact"        => $id_contact,
                "id_company"        => $id_company,
                "id_currency"       => $this->model->id_currency(),

                "name"                  =>  $post['data']['opportunityName'],
                "amount"                => 0,
                "id_stage"            =>    $this->model->select('id', 'crm3_opportunity_stage', '1 order by progress ASC limit 1'),
                "id_lead_source"      =>   $this->model->select('id_lead_source', 'crm3_lead', 'id=' . $id_lead),
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
            );
            $this->db->insert("crm3_opportunity", $insert);
            $last_query =  $last_query . ';' . $this->db->last_query();



            $id_lead_convert = $this->model->number("lead_convert");
            $insert = array(
                "id"                => $id_lead_convert,
                "id_user"           => $post['data']['id_user'],
                "id_lead"           => $id_lead,
                "id_contact"        => $id_contact,
                "id_company"        => $id_company,
                "id_opportunity"        => $id_opportunity,

                "lead_input_date" => $this->model->select('input_date', 'crm3_lead', 'id="' . $id_lead . '"'),
                "lead_input_by" => $this->model->select('input_by', 'crm3_lead', 'id="' . $id_lead . '"'),

                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
            );
            $this->db->insert("crm3_lead_convert", $insert);


            $this->db->trans_complete();
        }

        if ($this->db->trans_status() === FALSE) {
            $error = 666;
        }

        $json = array(
            "error"  => $error,
            "result" => array(
                "query" => $last_query,
                "note"  => "convert done",
            ),
        );
        echo json_encode($json);
    }

    public function converted($id = "")
    {
        $data = [];
        $query = $this->db->query('select * from crm3_lead_convert  where presence = 1 and id_lead = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id"                => $row->id,
                "id_user"           => $row->id_user,
                "user"           =>  $this->model->user($row->id_user),

                "id_lead"           => $row->id_lead,
                "lead"           => $this->model->lead($row->id_lead),

                "id_company"        => $row->id_company,
                "company"        => $this->model->company($row->id_company),

                "id_opportunity"        => $row->id_opportunity,
                "opportunity"        => $this->model->opportunity($row->id_opportunity),


                "id_contact"        => $row->id_contact,
                "contact"        => $this->model->contact($row->id_contact),

                "lead_input_date" => $row->lead_input_date,

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


    /** MODAL INSERT PRICE LIST */
    public function insermodule()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post['module'] == 'quote') {
            $this->db->trans_start();
            $syncing  = $this->model->crm3('syncing', 'quote', 'id = ' . $post['id']);
            $id_opportunity = $this->model->crm3('id_opportunity', 'quote', 'id = ' . $post['id']);
            foreach ($post['data'] as $row) {

                $insert = array(
                    "id_quote"  => $post['id'],
                    "id_product"    =>  $row['id_product'],
                    "id_price_list" => '0',
                    "price" => $row['price'],
                    "description" => $this->model->select('description', 'crm3_product', 'id=' . $row['id_product']),
                    "discount" => 0,
                    "qty" => 1,
                    "total" => $row['price'],

                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),

                );
                $this->db->insert("crm3_quote_detail", $insert);


                if ($syncing  == 1) {
                    $insert = array(
                        "id_opportunity" => $id_opportunity,
                        "id_product"     => $insert['id_product'],
                        "id_price_list" => '0',
                        "price" => $insert['price'],
                        "discount" => 0,
                        "qty" => 1,
                        "total" => $insert['total'],

                        "update_date" => date('Y-m-d H:i:s'),
                        "input_date" => date('Y-m-d H:i:s'),
                        "input_by" =>  $this->model->id_user(),

                    );
                    $this->db->insert("crm3_opportunity_product", $insert);
                }
            }
            $this->db->trans_complete();
            $json = array(
                "error" => 0,
                "id" => $post['id'],
                "module" => "",
            );
        } else if ($post['module'] == 'lead') {
            $this->db->trans_start();

            $id =  $post['id'];
            foreach ($post['data'] as $row) {

                $insert = array(
                    "id_lead"      => $post['id'],
                    "id_product"    =>  $row['id_product'],
                    "id_price_list" => '0',
                    "price" => $row['price'],
                    "discount" => 0,
                    "qty" => 1,
                    "total" => $row['price'],

                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),

                );
                $this->db->insert("crm3_lead_product", $insert);
            }
            $this->db->trans_complete();
            $json = array(
                "error" => 0,
                "id" => $post['id'],
                "module" => "",
            );
        } else if ($post['module'] == 'opportunity') {
            $this->db->trans_start();

            $id =  $post['id'];
            foreach ($post['data'] as $row) {

                $insert = array(
                    "id_opportunity"      => $post['id'],
                    "id_product"    =>  $row['id_product'],
                    "id_price_list" => 0,
                    "price" => $row['price'],
                    "discount" => 0,
                    "qty" => 1,
                    "total" => $row['price'],

                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),

                );
                $this->db->insert("crm3_opportunity_product", $insert);
            }
            $this->db->trans_complete();
            $json = array(
                "error" => 0,
                "id" => $post['id'],
                "module" => "",
            );
        } else if ($post['module'] == 'salesOrder') {
            $this->db->trans_start();

            foreach ($post['data'] as $row) {

                $insert = array(
                    "id_sales_order"  => $post['id'],
                    "id_product"    =>  $row['id_product'],
                    "id_price_list" => '0',
                    "price" => $row['price'],
                    "description" => $this->model->select('description', 'crm3_product', 'id=' . $row['id_product']),
                    "discount" => 0,
                    "qty" => 1,
                    "total" => $row['price'],

                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),

                );
                $this->db->insert("crm3_sales_order_detail", $insert);
            }
            $this->db->trans_complete();
            $json = array(
                "error" => 0,
                "id" => $post['id'],
                "module" => "",
            );
        } else {
            $json = array(
                "error" => 1,
                "id" => $post['id'],
            );
        }

        echo json_encode($json);
    }
}
