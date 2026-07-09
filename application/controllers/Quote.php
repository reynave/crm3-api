<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Quote extends CI_Controller
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

    public function indexOld()
    {

        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_quote where presence = 1 ' . $this->model->user_group('id_user')['query'] . '  order by input_date DESC');
        foreach ($query->result() as $row) {
            $id_company = $this->model->crm3('id_company', 'opportunity', 'id="' . $row->id_opportunity . '" ');
            if ((bool) $this->model->crm3('closed', 'quote_status', 'id=' . $row->id_quote_status_final) == false) {
                $status = $this->model->crm3('name', 'quote_status', 'id=' . $row->id_quote_status);
            } else {
                $status = $this->model->crm3('name', 'quote_status', 'id=' . $row->id_quote_status_final);
            }

            $temp = array(
                "id" => $row->id,
                "quotes_number"  => $row->quotes_number,
                "version" => $row->version > 0 ? 'ver ' . $row->version : "",
                "name"  => $row->name,

                "status"  => $status,

                "grand_total"  => $row->grand_total,
                "expired_date"  => $row->expired_date,
                "discount"  => $row->discount,
                "tax"  => $row->tax,
                "shipping"  => $row->shipping,
                "id_quote_status"  => $row->id_quote_status,

                "currency" => $this->model->currency(),

                "company" => $this->model->crm3('name', 'company', 'id=' . $id_company),
                "id_contact" => $row->id_contact,
                "contact" => $this->model->contact($row->id_contact),

                "user" => $this->model->user($row->id_user),
                "id_user" =>  $row->id_user,

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),

                "check" => false,
            );
            array_push($data, $temp);
            $i++;
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "total" => (int) $this->model->select('count(id)', 'crm3_quote', 'presence = 1  ' . $this->model->user_group('id_user')['query']),
                "total_view" => $i,
                "pages" => 1,
            ),
        );

        echo json_encode($json);
    }

   

    function index()
    {

        $this->load->library('ssp');
        // DB table to use
        //$table = 'crm3_company';
        $currency = $this->model->currency();
        $table = <<<EOT
 (
SELECT c.id, c.quotes_number, c.version, c.name,  c.grand_total, DATE_FORMAT(c.expired_date,'%d/%b/%Y')  AS 'expired_date',
o.name AS 'opportunities', com.name AS 'companies' , 
IFNULL(sf.name,s.name) AS 'status' ,
IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user'

FROM  crm3_quote AS c
LEFT JOIN crm3_quote_status AS s ON s.id = c.id_quote_status
LEFT JOIN crm3_quote_status AS sf ON sf.id = c.id_quote_status_final
LEFT JOIN crm3_opportunity AS o ON o.id = c.id_opportunity
LEFT JOIN crm3_company AS com ON com.id = o.id_company 
LEFT JOIN crm3_user AS u ON u.id = c.id_user
WHERE c.presence = 1
{$this->model->user_group('c.id_user')['query']}
) temp
EOT;
        //  

        // Table's primary key
        $primaryKey = 'id';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array('db' => 'id', 'dt' => 0),
            array('db' => 'quotes_number',  'dt' => 1),
            array('db' => 'companies',  'dt' => 2),
            array('db' => 'grand_total',  'dt' => 3),

            array('db' => 'expired_date',  'dt' => 4), 
            array('db' => 'status',  'dt' => 5), 
            
            array('db' => 'user',  'dt' => 6), 
            array('db' => 'version',  'dt' => 7),  
            array('db' => 'name',  'dt' => 8),  
           
          
           
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



    public function detail($id = 0)
    {

        $id_user = $this->model->crm3('id_user', 'quote', 'id="' . $id . '"');

        if (!$this->model->allow_access_data($id_user)) {
            echo $this->model->error("Access data not allow");
            exit;
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1  ' . $this->model->user_group()['query'] . ' order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }

        $data = array(
            "discount" => 0,
            "tax" => 0,
            "shipping" => 0,
            "version" => 0,
        );
        $id_company  = 0;
        $query = $this->db->query('select * from crm3_quote where presence = 1 and id = "' . $id . '" order by input_date DESC ');
        foreach ($query->result() as $row) {
            $id_company =  $this->model->select('id_company', 'crm3_opportunity', 'id=' . $row->id_opportunity);
            $id_opportunity = $row->id_opportunity;
            $data = array(
                "id" => $row->id,

                "status" => array(
                    "id"    =>  $row->id_quote_status,
                    "note"  =>  $this->model->crm3('name', 'quote_status', 'id=' . $row->id_quote_status),
                ),
                "id_quote_status_final"    =>  $row->id_quote_status_final,
                "closed"    =>  $this->model->crm3('closed', 'quote_status', 'id=' . $row->id_quote_status_final),

                "id_opportunity" => $row->id_opportunity,
                "version" => (int) $row->version,

                "opportunity" => $this->model->select('name', 'crm3_opportunity', 'id=' . $row->id_opportunity),

                "company" => $this->model->select('name', 'crm3_company', 'id=' . $id_company),
                "contact" => array(
                    "id" => $row->id_contact,
                    "note" => $this->model->contact($row->id_contact),
                ),

                "currency" => $this->model->currency(),
                "id_user" => $row->id_user,
                "quotes_number" => $row->quotes_number,
                "name"    => $row->name,
                "expired_date" => array(
                    "year" => (int) date('Y', strtotime($row->expired_date)),
                    "month" =>  (int) date('m', strtotime($row->expired_date)),
                    "day" =>  (int) date('d', strtotime($row->expired_date)),

                ),
                "id_template" => $row->id_template,
                "syncing" => (bool) $row->syncing,
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

                "user" => $this->model->user($row->id_user),
                "id_user" =>  $row->id_user,

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),


            );
        }

        $quote_status = [];
        $query = $this->db->query('select * from crm3_quote_status where 1 order by id ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name"  => $row->name,
            );
            array_push($quote_status, $temp);
        }

        $quote_item = [];
        $query = $this->db->query('select * from crm3_quote_detail where id_quote = "' . $id . '" and presence = 1 order by sorting ASC');
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


        $calculation = $this->model->calculation($id);

        $pricing = array(
            "total" =>  $calculation['pricing']['total'],
            "discount" => $calculation['pricing']['discount'],
            "tax" =>  $calculation['pricing']['tax'],
            "shipping" => $calculation['pricing']['shipping'],
            "grand_total" => $calculation['pricing']['grand_total'],
        );



        $activity = [];


        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "quote" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => str_replace('./', base_url(), $row->url),

                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }

        $output = [];
        $query = $this->db->query('select * from crm3_output where presence = 1 and module = "quote" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "version" => (int) $row->version,
                "full_path" =>  str_replace('./', base_url(), $row->full_path),
                "input_date" => $row->input_date,
            );
            array_push($output, $temp);
        }

        $contact = [];
        if ($data) {
            $query = $this->db->query('select * from crm3_contact where id_company = "' .  $id_company . '" ' . $this->model->user_group('id_user')['query'] . ' and presence = 1  order by first_name ASC, last_name DESC ');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $this->model->contact($row->id),
                    "position" => $row->position,
                    "department" => $row->department,

                    "phone" => $row->phone,
                    "mobile" => $row->mobile,
                );
                array_push($contact, $temp);
            }
        }


        $profile = array(
            "id" => $this->model->id_user(),
            "max_discount" => (int) $this->model->crm3('max_discount', 'user', 'id="' . $this->model->id_user() . '"'),
        );

        $json = array(
            "error"  =>  0,
            "result" => array(
                "profile" => $profile,
                "activity" => $activity,
                "data" => $data,
                "contact" => $contact,
                "quote_item" => $quote_item,
                "quote_status" => $quote_status,
                "pricing" => $pricing,
                "attachment" => $attachment,
                "fileOutput" => $output,
                "user" => $user,
                "total" => $this->model->crm3('count(id)', 'quote_detail', 'presence = 1 AND id_quote = ' . $id),
                "allow_access_data" =>  $this->model->allow_access_data($id_user)
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
            $this->db->update("crm3_quote_detail", $update, ' id=' . $post['data']['id']);

            $calculation =  $this->model->calculation($id);

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

    public function fn_updateQuote()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $id_quote_status = $this->model->crm3('id_quote_status', 'quote', 'id = ' . $post['id']);
            if ($id_quote_status > 10) {
                $update = array(
                    "id_quote_status" => "300",
                    "id_quote_status_final" => 0,
                );
                $this->db->update("crm3_quote", $update, ' id=' . $post['id']);
            }





            $update = array(
                "id_user"  =>  $post['data']['id_user'],
                "id_contact"  =>  $post['data']['id_contact'],

                "name" => $post['data']['name'],
                "expired_date" => $post['data']['expirationDate']['year'] . '-' . $post['data']['expirationDate']['month'] . '-' . $post['data']['expirationDate']['day'],
                //    "id_template" => $post['data'][''],
                "syncing" => $post['data']['syncing'],
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
            $this->db->update("crm3_quote", $update, ' id=' . $post['id']);
            $this->model->calculation($post['id']);

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
            $this->db->update("crm3_quote_detail", $update, ' id=' . $post['id']);

            $calculation =  $this->model->calculation($post['id_quote']);

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
                $this->db->update("crm3_quote_detail", $update, ' id=' . $row['id']);
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

    public function fn_sync()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "syncing" =>   $post['status'],
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_quote", $update, ' id=' . $post['id']);

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
                "id_quote_status" =>   $post['id_quote_status'],
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_quote", $update, ' id=' . $post['id']);

            $id_opportunity = $this->model->crm3('id_opportunity', 'quote', 'id=' . $post['id']);
            $id_company =  $this->model->crm3('id_company', 'opportunity', 'id=' . $id_opportunity);
            $id_contact =  $this->model->crm3('id_contact', 'opportunity', 'id=' . $id_opportunity);

            if ($this->model->crm3('id', 'activity', 'id_company = "' . $id_company . '" and id_contact = "' . $id_contact . '" ')) {
                $id_activity = $this->model->crm3('id', 'activity', 'id_company = "' . $id_company . '" and id_contact = "' . $id_contact . '" ');
            } else {
                $insert = array(
                    "id_contact" =>  $id_contact,
                    "id_company" =>  $id_company,
                    "input_by" => $this->model->id_user(),
                    "input_date" => date('Y-m-d H:i:s'),
                    "update_by" => $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('crm3_activity', $insert);
                $id_activity = $this->model->crm3('id', 'activity', 'presence = 1 order by input_date DESC');
            }

            $insert = array(
                "id_activity"  => $id_activity,
                "id_company" =>  $id_company,
                "id_opportunity" =>  $id_opportunity,
                "log" => "Update Quote <span class='h100'>" . $this->model->crm3('quotes_number', 'quote', 'id=' . $post['id']) . "</span> to <span class='h101'>" . $this->model->crm3('name', 'quote_status', 'id=' . $post['id_quote_status']) . '</span>',
                "closed"    => 1,
                "id_priority"  => 10,
                "input_by" => $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('crm3_activity_detail', $insert);

            $activity = [];

            $json = array(
                "error"  =>  0,
                "result" => $activity,
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

    function send()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "id_quote_status" => 200,
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_quote", $update, ' id=' . $post['id']);

            $json = array(
                "error"  =>  0,
                "result" => "",
            );
            echo json_encode($json);
        }
    }


    function approved()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "id_quote_status_final" => $post['id_quote_status'],
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_quote", $update, ' id=' . $post['id']);

            $json = array(
                "error"  =>  0,
                "result" => "",
            );
            echo json_encode($json);
        }
    }


    /*
    function calculation($id_quote){
        $id = $id_quote;
        $data = [];
        $query = $this->db->query('select * from crm3_quote where presence = 1 and id = "' . $id . '" order by input_date DESC ');
        foreach ($query->result() as $row) { 
            $data = array( 
                "discount" =>  (float)$row->discount,
                "tax" => (float)$row->tax,
                "shipping" =>  (float)$row->shipping, 
            );
        
        }

        $quote_item = [];
        $query = $this->db->query('select * from crm3_quote_detail where id_quote = "'.$id.'" and presence = 1 order by sorting ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id, 
                "id_product" => $row->id_product,
                "product" => $this->model->crm3('name','product','id='.$row->id_product),
                "description"  => $row->description,  
                "discount"  => $row->discount,  
                 "currency" => $this->model->currency(),
                "price"  => $row->price,  
                "qty"  => (int)$row->qty,  
                "subtotal"  => $row->price * $row->qty, 
                "total"  => $row->total,  
                "sorting"  => $row->sorting,   
            );
            array_push($quote_item, $temp);  
        }

        
        $total = $this->model->crm3('SUM(total)','quote_detail','presence = 1 AND id_quote = '.$id);
        $discount = $this->model->crm3('SUM( total * ( '.$data['discount'].' / 100) )','quote_detail','presence = 1 AND id_quote = '.$id);
        $tax =   ( $total + $discount ) * ( $data['tax'] / 100);
        $shipping = $data['shipping'];
        $grand_total = ( $total - $discount) + $tax + $shipping ;
            

        $pricing = array(
            "total" => $total,
            "discount" => $discount,
            "tax" => $tax,
            "shipping" => $shipping,
            "grand_total" => $grand_total, 
        );

        $update = array( 
        //    "discount" => $discount,
         //   "tax" => $tax,
            "shipping" => $shipping,
            "grand_total" => $grand_total, 

        );
        $this->db->update('crm3_quote',$update,'id='.$id );


        $return = array(
            "quote_item" => $quote_item, 
            "pricing" => $pricing, 
            "total"    =>  $this->model->crm3('count(id)','quote_detail','presence = 1 AND id_quote = '.$id),
        );
        return $return;
    }*/
}
