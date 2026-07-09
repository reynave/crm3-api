<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sales_order extends CI_Controller
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
        SELECT c.id, c.so_number, c.name,  c.grand_total,DATE_FORMAT(c.expired_date,'%d/%b/%Y')  AS 'expired_date',
        sos.name AS 'status',  com.name AS 'company',
        IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user'
        FROM crm3_sales_order AS c
        LEFT JOIN crm3_user AS u ON u.id = c.id_user
        LEFT JOIN crm3_sales_order_status AS sos ON sos.id = c.id_sales_order_status
        LEFT JOIN crm3_company AS com ON com.id = c.id_company
        WHERE c.presence = 1
        {$this->model->user_group('c.id_user')['query']}
) temp
EOT;
        //  {$this->model->user_group('c.id_user')['query']}

        // Table's primary key
        $primaryKey = 'id';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array('db' => 'id', 'dt' => 0),
            array('db' => 'so_number',  'dt' => 1),
            array('db' => 'name',  'dt' => 2),
            array('db' => 'company',  'dt' => 3),

            array('db' => 'grand_total',  'dt' => 4),
            array('db' => 'expired_date',  'dt' => 5),
            array('db' => 'status',  'dt' => 6),

            array('db' => 'user',  'dt' => 7),



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



    public function create($module = "", $id = 0)
    {
        $data = [];
        $company = [];
        $contact = [];
        $opportunity = [];
        $quotes = [];

        $data = array(
            "id_opportunity" => "",
            "name"  => "",

            "currency" => $this->model->currency(),
            "amount" => 0,
            "id_company" => 0,
            "id_quote" => 0,

            "id_contact" => 0,
            "email" => "",
            "phone" => "",
            "fax" => "",
            "description" => "",
            "discount" => 0,
            "tax" => 0,
            "shipping" => 0,
            "grand_total" => 0,

            "id_user" =>  $this->model->id_user(),

            "bill" => array(
                "bill_name" => "",
                "bill_street1" => "",
                "bill_street2" => "",
                "bill_city" => "",
                "bill_state" => "",
                "bill_code" => "",
                "bill_country" => "",

            ),
            "ship" => array(
                "bill_name" => "",
                "bill_street1" => "",
                "bill_street2" => "",
                "bill_city" => "",
                "bill_state" => "",
                "bill_code" => "",
                "bill_country" => "",
            ),
        );

        if ($module == 'deal') {

            $data['id_company'] = 0;
            $query = $this->db->query('select * from crm3_opportunity where presence = 1 and id = ' . $id);
            foreach ($query->result() as $row) {
                $data = array(
                    "id_opportunity" => $row->id,
                    "name"  => $this->model->crm3('name', 'quote', 'id= "' . $row->id_quote . '" '),

                    "currency" => $this->model->currency(),
                    "amount" => (int) $row->amount,
                    "id_company" => $row->id_company,
                    "id_quote" => $row->id_quote,

                    "id_contact" => $row->id_contact,
                    "email" => $this->model->crm3('email', 'quote', 'id= "' . $row->id_quote . '" '),
                    "phone" => $this->model->crm3('phone', 'quote', 'id= "' . $row->id_quote . '" '),
                    "fax" => $this->model->crm3('fax', 'quote', 'id= "' . $row->id_quote . '" '),
                    "description" => $this->model->crm3('description', 'quote', 'id= "' . $row->id_quote . '" '),

                    "id_user" =>  $row->id_user,

                    "bill" => array(
                        "bill_name" => $this->model->crm3('bill_name', 'quote', 'id= "' . $row->id_quote . '" '),
                        "bill_street1" => $this->model->crm3('bill_street1', 'quote', 'id= "' . $row->id_quote . '" '),
                        "bill_street2" => $this->model->crm3('bill_street2', 'quote', 'id= "' . $row->id_quote . '" '),
                        "bill_city" => $this->model->crm3('bill_city', 'quote', 'id= "' . $row->id_quote . '" '),
                        "bill_state" => $this->model->crm3('bill_state', 'quote', 'id= "' . $row->id_quote . '" '),
                        "bill_code" => $this->model->crm3('bill_code', 'quote', 'id= "' . $row->id_quote . '" '),
                        "bill_country" => $this->model->crm3('bill_country', 'quote', 'id= "' . $row->id_quote . '" '),

                    ),
                    "ship" => array(
                        "ship_name" => $this->model->crm3('ship_name', 'quote', 'id= "' . $row->id_quote . '" '),
                        "ship_street1" => $this->model->crm3('ship_street1', 'quote', 'id= "' . $row->id_quote . '" '),
                        "ship_street2" => $this->model->crm3('ship_street2', 'quote', 'id= "' . $row->id_quote . '" '),
                        "ship_city" => $this->model->crm3('ship_city', 'quote', 'id= "' . $row->id_quote . '" '),
                        "ship_state" => $this->model->crm3('ship_state', 'quote', 'id= "' . $row->id_quote . '" '),
                        "ship_code" => $this->model->crm3('ship_code', 'quote', 'id= "' . $row->id_quote . '" '),
                        "ship_country" => $this->model->crm3('ship_country', 'quote', 'id= "' . $row->id_quote . '" '),

                    ),
                );
            }

            $query = $this->db->query('select * from crm3_company where presence = 1 ' . ($data['id_company'] > 0 ? 'and id = ' . $data['id_company'] : ''));
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }

            if ($data['id_company'] > 0) {

                $query = $this->db->query('select * from crm3_contact where presence = 1 and id_company = ' . $data['id_company'] . ' ' . $this->model->user_group('id_user')['query'] . '  order by first_name ASC, last_name ASC ');
                foreach ($query->result() as $row) {
                    $temp = array(
                        "id" => $row->id,
                        "name" => $this->model->contact($row->id),
                    );
                    array_push($contact, $temp);
                }
            }

            $query = $this->db->query('select * from crm3_opportunity where presence = 1 ' . ($id > 0 ? 'and id = ' . $id : '') . ' and closed = 1 order by closed_date DESC ');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($opportunity, $temp);
            }

            if ($data['id_quote'] > 0) {
                $quotes = [];
                $query = $this->db->query('select * from crm3_quote where presence = 1   and id = "' . $data['id_quote'] . '" ');
                foreach ($query->result() as $row) {
                    $temp = array(
                        "id" => $row->id,
                        "quotes_number" => $row->quotes_number,
                    );
                    array_push($quotes, $temp);
                }
            }
        } else if ($module == 'salesOrder') {

            $query = $this->db->query('select * from crm3_company where presence = 1 ');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                    "phone" => $row->phone,
                    "email" => $row->email,
                    "fax" => $row->fax,
                    "industry" => $this->model->industry($row->id_industry),
                    "ship_name" => $row->ship_name,
                    "bill_street1" => $row->bill_street1,
                    "bill_street2" => $row->bill_street2,
                    "bill_city" => $row->bill_city,
                    "bill_state" => $row->bill_state,
                    "bill_code" => $row->bill_code,
                    "bill_country" => $row->bill_country,

                    "ship_name" => $row->ship_name,
                    "ship_street1" => $row->ship_street1,
                    "ship_street2" => $row->ship_street2,
                    "ship_city" => $row->ship_city,
                    "ship_state" => $row->ship_state,
                    "ship_code" => $row->ship_code,
                    "ship_country" => $row->ship_country,
                );
                array_push($company, $temp);
            }
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1 ' . $this->model->user_group()['query'] . ' order by first_name ASC, last_name ASC');
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
                "data"          => $data,
                "contact"       => $contact,
                "quotes"        => $quotes,
                "opportunity"   => $opportunity,
                "company"       => $company,
                "user"          => $user,
            ),
        );

        echo json_encode($json);
    }

    public function fn_insertSalesOrder()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $so_number = $this->model->number("sales_order");
            $insert = array(
                "so_number" =>  $so_number,
                "id_opportunity"     => isset($post['data']['id_opportunity']) ? $post['data']['id_opportunity'] : "",
                "id_contact"      => isset($post['data']['id_contact']) ? $post['data']['id_contact'] : "0",
                "id_quote"      => isset($post['data']['id_quote']) ? $post['data']['id_quote'] : "0",
                "name"      => isset($post['data']['name']) ? $post['data']['name'] : "0",
                "id_company"      => isset($post['data']['id_company']) ? $post['data']['id_company'] : "0",




                "expired_date"      => isset($post['data']['expired_date']) ? $post['data']['expired_date']['year'] . '-' . $post['data']['expired_date']['month'] . '-' . $post['data']['expired_date']['day'] : date('Y-m-d'),
                "id_template"      => "0",
                "description"      => isset($post['data']['description']) ? $post['data']['description'] : "0",


                "email"      => isset($post['data']['email']) ? $post['data']['email'] : "0",
                "phone"      => isset($post['data']['phone']) ? $post['data']['phone'] : "0",
                "fax"      => isset($post['data']['fax']) ? $post['data']['fax'] : "0",
                "term"      => isset($post['data']['term']) ? $post['data']['term'] : "0",


                "bill_name"      => isset($post['data']['bill_name']) ? $post['data']['bill_name'] : "",
                "bill_street1"      => isset($post['data']['bill_street1']) ? $post['data']['bill_street1'] : "",
                "bill_city"      => isset($post['data']['bill_city']) ? $post['data']['bill_city'] : "",
                "bill_state"      => isset($post['data']['bill_state']) ? $post['data']['bill_state'] : "",
                "bill_code"      => isset($post['data']['bill_code']) ? $post['data']['bill_code'] : "",
                "bill_country"      => isset($post['data']['bill_country']) ? $post['data']['bill_country'] : "",

                "ship_name"      => isset($post['data']['ship_name']) ? $post['data']['ship_name'] : "",
                "ship_street1"      => isset($post['data']['ship_street1']) ? $post['data']['ship_street1'] : "",
                "ship_city"      => isset($post['data']['ship_city']) ? $post['data']['ship_city'] : "",
                "ship_state"      => isset($post['data']['ship_state']) ? $post['data']['ship_state'] : "",
                "ship_code"      => isset($post['data']['ship_code']) ? $post['data']['ship_code'] : "",
                "ship_country"      => isset($post['data']['ship_country']) ? $post['data']['ship_country'] : "",

                "id_user"       => $post['data']['id_user'],
                "input_date"    => date('Y-m-d H:i:s'),
                "input_by"      =>  $this->model->id_user(),
                "update_date"   => date('Y-m-d H:i:s'),
            );
            $this->db->insert("crm3_sales_order", $insert);
            $id = $this->model->crm3('id', 'sales_order', 'so_number = "' . $so_number . '"');


            if ($post['module']  == 'deal') {

                $update = array(

                    "discount" => isset($post['data']['id_quote']) ? $this->model->crm3('discount', 'quote', 'id=' . $post['data']['id_quote']) : "0",
                    "tax" => isset($post['data']['id_quote']) ? $this->model->crm3('tax', 'quote', 'id=' . $post['data']['id_quote']) : "0",
                    "shipping" => isset($post['data']['id_quote']) ? $this->model->crm3('shipping', 'quote', 'id=' . $post['data']['id_quote']) : "0",

                    "grand_total"      => isset($post['data']['id_quote']) ?  $this->model->crm3('grand_total', 'quote', 'id="' . $post['data']['id_quote'] . '"') : "0",
                );
                $this->db->update("crm3_sales_order", $update, 'id="' . $id . '" ');

                $query = $this->db->query('select * from crm3_quote_detail where id_quote = "' . $post['data']['id_quote'] . '" and presence = 1  order by sorting ASC, input_date DESC ');
                foreach ($query->result() as $row) {

                    $insert = array(
                        "id_sales_order" => $id,
                        "id_product" => $row->id_product,
                        "id_price_list" => $row->id_price_list,
                        "description" => $row->description,
                        "price" => $row->price,
                        "discount" => $row->discount,
                        "qty" => $row->qty,
                        "total" => $row->total,
                        "input_date" => date('Y-m-d H:i:s'),
                        "input_by" =>  $this->model->id_user(),
                        "update_date" => date('Y-m-d H:i:s'),
                    );
                    $this->db->insert("crm3_sales_order_detail", $insert);
                }

                $this->model->calculation_so($id);
            }



            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id" => $id,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert opportunity done",
                ),
            );
            echo json_encode($json);
        }
    }

   

    //
    //  DETAIL
    //
    public function detail($id = 0)
    {
 
        $id_user = $this->model->crm3('id_user', 'sales_order', 'id="' . $id . '"');

        if (!$this->model->allow_access_data($id_user)) {
            echo $this->model->error("Access data not allow");
            exit;
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
        $id_company = 0;
        $data = array(
            "discount" => 0,
            "tax" => 0,
            "shipping" => 0,
        );
        $query = $this->db->query('select * from crm3_sales_order where presence = 1 and id = "' . $id . '" order by input_date DESC ');
        foreach ($query->result() as $row) {
            $id_opportunity = $row->id_opportunity;
            $id_company = $row->id_company;
            $data = array(
                "id" => $row->id,
                "id_opportunity" => $row->id_opportunity,
                "opportunity" => $this->model->select('name', 'crm3_opportunity', 'id="' . $row->id_opportunity . '"'),

                "company" => $this->model->select('name', 'crm3_company', 'id="' . $row->id_company . '"'),
                "contact" => array(
                    "id" => $row->id_contact,
                    "name" => $this->model->contact($row->id_contact),
                ),
                "currency" => $this->model->currency(),
                "id_user" => $row->id_user,
                "quotes_number" => $row->so_number,
                "name"    => $row->name,
                "expired_date" => array(
                    "year" => (int) date('Y', strtotime($row->expired_date)),
                    "month" =>  (int) date('m', strtotime($row->expired_date)),
                    "day" =>  (int) date('d', strtotime($row->expired_date)),
                ),
                "id_template" => $row->id_template,
                "description" => $row->description,
                "discount" =>  (float) $row->discount,
                "tax" => (float) $row->tax,
                "shipping" =>  (float) $row->shipping,
                "email" => $row->email,
                "phone" => $row->phone,
                "fax" => $row->fax,
                "term" => $row->term,
                "bill_name"  => $row->bill_name,
                "bill_street1"   => $row->bill_street1,
                "bill_street2"    => $row->bill_street2,
                "bill_city"    => $row->bill_city,
                "bill_state"    => $row->bill_state,
                "bill_code"    => $row->bill_code,
                "bill_country"    => $row->bill_country,
                "ship_name"    => $row->ship_name,
                "ship_street1"    => $row->ship_street1,
                "ship_street2"    => $row->ship_street2,
                "ship_city"    => $row->ship_city,
                "ship_state"    => $row->ship_state,
                "ship_code"    => $row->ship_code,
                "ship_country"    => $row->ship_country,
                "id_sales_order_status" => $row->id_sales_order_status,
                "sales_order_status" => $this->model->crm3('name', 'sales_order_status', 'id=' . $row->id_sales_order_status),
                "editable"  => (bool) $this->model->crm3('editable', 'sales_order_status', 'id=' . $row->id_sales_order_status),
                "user" => $this->model->user($row->id_user),
                "id_user" =>  $row->id_user,

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),


            );
        }

        $sales_order_status = [];
        $query = $this->db->query('select * from crm3_sales_order_status where presence = 1  order by id ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name"  => $row->name,
            );
            array_push($sales_order_status, $temp);
        }

        $quote_item = [];
        $query = $this->db->query('select * from crm3_sales_order_detail where id_sales_order  = "' . $id . '" and presence = 1 order by sorting ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "id_product" => $row->id_product,
                "product" => $this->model->crm3('name', 'product', 'id=' . $row->id_product),
                "description"  => $row->description,
                "discount"  => $row->discount,
                "currency" => $this->model->currency(),
                "price"  => $row->price,
                "qty"  => (int) $row->qty,
                "subtotal"  => $row->price * $row->qty,
                "total"  => $row->total,
                "sorting"  => $row->sorting,
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
                "hide_description" => true
            );
            array_push($quote_item, $temp);
        }

        $activity = [];


        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "sales_order" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => $row->url,

                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }


        $contact = [];
        $query = $this->db->query('select * from crm3_contact where id_company = "' . $id_company . '" and presence = 1 order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $this->model->contact($row->id),
            );
            array_push($contact, $temp);
        }


        $sales_order_log = [];
        $query = $this->db->query('select * from crm3_sales_order_log where id_sales_order  = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "status" => $this->model->crm3('name', 'sales_order_status', 'id=' . $row->id_sales_order_status),
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
            );
            array_push($sales_order_log, $temp);
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "activity" => $activity,
                "data" => $data,
                "quote_item" => $quote_item,
                "sales_order_status" => $sales_order_status,
                "pricing" => $this->model->calculation_so($id),
                "attachment" => $attachment,
                "user" => $user,
                "contact" => $contact,
                "sales_order_log" => $sales_order_log,
                "total" => $this->model->crm3('count(id)', 'quote_detail', 'presence = 1 AND id_quote = ' . $id),
            ),
        );

        echo json_encode($json);
    }

    public function fn_updateQuoteitem()
    {

        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $id =  $post['id'];
            $update = array(
                "qty" => $post['data']['qty'],
                "discount" => $post['data']['discount'],
                "description" => $post['data']['description'],

                "total" => ($post['data']['qty'] * $post['data']['price']) - (($post['data']['qty'] * $post['data']['price']) * ($post['data']['discount'] / 100)),

                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_sales_order_detail", $update, ' id=' . $post['data']['id']);

            $calculation =  $this->model->calculation_so($id);

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "quote_item" => $calculation['quote_item'],
                    "pricing" => $calculation['pricing'],
                    "total" =>  $calculation['total'],
                    "update" => $update,
                ),
            );
        } else {
            $json = array(
                "error"  =>  1,
            );
        }
        echo json_encode($json);
    }

    public function fn_updateSO()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "id_user"  =>  $post['data']['id_user'],
                "id_contact"  =>  $post['data']['id_contact'],

                "name" => $post['data']['name'],
                "expired_date" => $post['data']['expirationDate']['year'] . '-' . $post['data']['expirationDate']['month'] . '-' . $post['data']['expirationDate']['day'],
                //    "id_template" => $post['data'][''], 
                "description" => $post['data']['description'],
                "discount"  => (float) $post['data']['discount'],
                "tax"  => $post['data']['tax'],
                "shipping" => $post['data']['shipping'],
                "email" => $post['data']['email'],
                "phone" => $post['data']['phone'],
                "fax" => $post['data']['fax'],
                "bill_name" => $post['data']['bill_name'],
                "bill_street1" => $post['data']['bill_street1'],
                "bill_street2" => $post['data']['bill_street2'],
                "bill_city" => $post['data']['bill_city'],
                "bill_state" => $post['data']['bill_state'],
                "bill_code" => $post['data']['bill_code'],
                //    "bill_country" => $post['data']['bill_country'],
                "ship_name" => $post['data']['ship_name'],
                "ship_street1" => $post['data']['ship_street1'],
                "ship_street2" => $post['data']['ship_street2'],
                "ship_city" => $post['data']['ship_city'],
                "ship_state" => $post['data']['ship_state'],
                "ship_code" => $post['data']['ship_code'],
                //   "ship_country" => $post['data']['ship_country'],

                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_sales_order", $update, ' id=' . $post['id']);
            $this->model->calculation_so($post['id']);

            $json = array(
                "error" => 0,
                "result" => "update done",
            );
            echo json_encode($json);
        }
    }

    public function fn_removeQuoteitem()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "presence" => 0,
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_sales_order_detail", $update, ' id=' . $post['id']);

            $calculation =  $this->model->calculation_so($post['id_quote']);

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "quote_item" => $calculation['quote_item'],
                    "pricing" => $calculation['pricing'],
                    "total" =>  $calculation['total'],
                    "update" => $update,
                ),
            );
            echo json_encode($json);
        }
    }

    public function fn_sortable_item()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $i = 1;
            foreach ($post['data'] as $row) {
                $update = array(
                    "sorting" => $i++,
                    "update_by" => $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->update("crm3_sales_order_detail", $update, ' id=' . $row['id']);
            }
            $json = array(
                "error"  =>  0,
                "last_query" => $this->db->last_query(),
            );
        } else {
            $json = array(
                "error"  =>  1,
            );
        }
        echo json_encode($json);
    }

    public function fn_update_status()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "id_sales_order_status" =>   $post['id_sales_order_status'],
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_sales_order", $update, ' id=' . $post['id']);


            $insert = array(
                "id_sales_order_status" =>   $post['id_sales_order_status'],
                "id_sales_order" => $post['id'],
                "input_by" => $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
            );
            $this->db->insert("crm3_sales_order_log", $insert);


            $activity = [];

            $json = array(
                "error"  =>  0,

                "result" => array(
                    "activity" => $activity,
                    "name"  => $this->model->crm3('name', 'sales_order_status', 'id=' . $post['id_sales_order_status']),
                    "editable"  => (bool) $this->model->crm3('editable', 'sales_order_status', 'id=' . $post['id_sales_order_status']),

                ),
                "last_query" => $this->db->last_query(),
            );
        } else {
            $json = array(
                "error"  =>  1,
            );
        }
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
}
