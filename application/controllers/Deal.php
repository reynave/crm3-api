<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deal extends CI_Controller
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
    SELECT c.id, c.name, com.name as 'company', amount,  
    DATE_FORMAT(c.closed_date,'%d/%b/%Y') as 'closed_date',
    IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user'
 
    FROM crm3_opportunity AS c
    LEFT JOIN crm3_opportunity_stage AS s ON s.id = c.id_stage
    LEFT JOIN crm3_user AS u ON u.id = c.id_user
    LEFT JOIN crm3_company AS com ON com.id = c.id_company
    WHERE c.presence = 1 and c.closed = 1 and c.id_stage = 1000 
    {$this->model->user_group('c.id_user')['query']}
 ) temp
EOT;

 
        // Table's primary key
        $primaryKey = 'id';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array('db' => 'id', 'dt' => 0),
            array('db' => 'name',  'dt' => 1),
            array('db' => 'company',  'dt' => 2), 
 
            array('db' => 'amount',  'dt' => 3), 

            array('db' => 'closed_date',  'dt' => 4), 
            array('db' => 'user',  'dt' =>5),   

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

        $id_user = $this->model->crm3('id_user', 'opportunity', 'id="' . $id . '"');

        if (!$this->model->allow_access_data($id_user)) {
            echo $this->model->error("Access data not allow");
            exit;
        }



        $data  = [];
        $id_stage = 0;
        $id_quote = 0;


        $query = $this->db->query('select *, DATEDIFF(closed_date, start_date) as "timeline" from crm3_opportunity where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $id_industry = $this->model->select('id_industry', 'crm3_company', ' id = "' . $row->id_company . '" ');
            $id_quote =  $row->id_quote;
            $data = array(
                "id" => $row->id,
                "closed" => (bool) $row->closed,
                "name"  => $row->name,
                "stage" => $this->model->select('name', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),

                "id_opportunity_business" => $row->id_opportunity_business,
                "progress" => $this->model->select('progress', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "currency" => $this->model->currency(),
                "amount" => (int) $row->amount,
                "id_company" => $row->id_company,
                "company" => $this->model->company($row->id_company),

                "id_contact" => $row->id_contact,
                "contact" => $this->model->contact($row->id_contact),

                "id_lead_source" =>  $row->id_lead_source,
                "lead_source" => $this->model->crm3('name', 'lead_source', 'id="' . $row->id_lead_source . '"'),
                "industry" => $this->model->industry($id_industry),
                "closed_reason" =>  $this->model->crm3('note','opportunity_closed_reason','id='. $row->id_closed_reason) ,
                "sales_order" => $row->sales_order,
                "quote" => array(
                    "id" => $row->id_quote,
                    "quote_number" => $this->model->crm3('quotes_number', 'quote', 'id=' . $row->id_quote),
                    "name" => $this->model->crm3('name', 'quote', 'id=' . $row->id_quote),
                    "grand_total" => $this->model->crm3('grand_total', 'quote', 'id=' . $row->id_quote),
                    "contact" => $this->model->contact($this->model->crm3('id_contact', 'quote', 'id=' . $row->id_quote)),
                    "user" => $this->model->user($this->model->crm3('id_user', 'quote', 'id=' . $row->id_quote)),

                ),
                "user" => $this->model->user($row->id_user),
                "id_user" =>  $row->id_user,
                "start_date" => $row->start_date,
                "expecting_closing_date" => $row->expecting_closing_date,
                "closed_date" => $row->closed_date,
                "timeline" => $row->timeline,
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
                "id_stage" =>  $row->id_stage,
                "final_stage" => $row->id_stage == $this->model->select('id', 'crm3_opportunity_stage', 'id < 999 order by progress DESC limit 1') ? true : false,



                "budget" =>  (int) $row->budget,
                "competitor" =>  $row->competitor,
                "comparison_with_competitor" =>  $row->comparison_with_competitor,
                "critical_point" =>  $row->critical_point,
                "our_proposal" =>  $row->our_proposal,
                "notes1" =>  $row->notes1,
                "notes2" =>  $row->notes2,
                "notes3" =>  $row->notes3,

                "check" => false,
            );
            $id_stage = $row->id_stage;
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1   ' . $this->model->user_group()['query'] . '  order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }



        $sales_order = [];
        $query = $this->db->query('select * from crm3_sales_order where presence = 1 and id_opportunity = "' . $id . '"  order by input_date DESC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "so_number" => $row->so_number,
                "currency" => $this->model->currency(),
                "grand_total" => $row->grand_total,
                "expired_date" => $row->expired_date,
                "discount" => $row->discount,
                "input_date" => $row->input_date,
                "input_by" => $row->input_by,
                "id_sales_order_status" => $row->id_sales_order_status,

            );
            array_push($sales_order, $temp);
        }

        $product = [];
        $query = $this->db->query('select * from crm3_opportunity_product where presence = 1 and id_opportunity = "' . $id . '" order by input_date DESC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,

                "product" => array(
                    "id" => $row->id_product,
                    "name" => $this->model->crm3('name', 'product', 'id=' . $row->id_product),
                ),
                "price_list" => array(
                    "id" => $row->id_price_list,
                    "name" => $this->model->crm3('name', 'price_list', 'id=' . $row->id_price_list),
                ),
                "currency" => $this->model->currency(),
                "price" => $row->price,
                "qty" => $row->qty,
                "discount" => $row->discount,
                "total" => $row->total,


                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),

            );
            array_push($product, $temp);
        }


        $contact = [];
        if ($data) {

            $query = $this->db->query('select * from crm3_contact where id_company = "' . $data['id_company'] . '" ' . $this->model->user_group('id_user')['query'] . ' and presence = 1  order by first_name ASC, last_name DESC ');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $this->model->contact($row->id),
                    "position" => $row->position,
                    "department" => $row->department,
                    "active" => $data['id_contact'] ==  $row->id ? true : false,
                );
                array_push($contact, $temp);
            }
        }


        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "opportunity" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => str_replace('./', base_url(), $row->url),

                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }

        $po = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "po" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => str_replace('./', base_url(), $row->url),

                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($po, $temp);
        }

        $query = $this->db->query('select * from crm3_company where presence = 1 and id = "' .   $data['id_company'] . '" ');
        foreach ($query->result() as $row) {
            $information = array(
                "fax" => $row->fax,
                "phone" => $row->phone,
                "email" => $row->email,
                "bill_name" => $row->ship_name,
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
        }

       

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "sales_order" => $sales_order,
                "information" => $information,
                "product" => $product,
                "user" => $user,
                "contact" => $contact,
                "attachment" => $attachment,
                "attachmentPO" => $po, 
            ),
        );

        echo json_encode($json);
    }

    function rollback()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array(
                "closed" => 0,
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_opportunity", $update, ' id=' .  $post['id']);

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "note"  => "update stage done",
                ),
            );
            echo json_encode($json);
        }
    }
}
