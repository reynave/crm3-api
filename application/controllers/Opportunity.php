<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Opportunity extends CI_Controller
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
    IFNULL(s.name,'None')  AS 'progress_name',   
    IFNULL(concat(s.progress,' %'),'-')  AS 'progress_percent',    
    DATE_FORMAT(c.expecting_closing_date,'%d/%b/%Y') as 'expecting_closing_date',
    IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user'
 
    FROM crm3_opportunity AS c
    LEFT JOIN crm3_opportunity_stage AS s ON s.id = c.id_stage
    LEFT JOIN crm3_user AS u ON u.id = c.id_user
    LEFT JOIN crm3_company AS com ON com.id = c.id_company
    WHERE c.presence = 1 and c.closed = 0
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

            array('db' => 'progress_name',  'dt' => 3), 
            array('db' => 'progress_percent',  'dt' => 4), 
            array('db' => 'amount',  'dt' => 5), 

            array('db' => 'expecting_closing_date',  'dt' => 6), 
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

    public function getSelected()
    {

        $industry = [];
        $q = " select *  from crm3_industry where presence = 1 order by name ASC ";

        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($industry, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "industry" => $industry,
            ),
        );
        echo json_encode($json);
    }


    public function detail($id = 0)
    {

        $id_user = $this->model->crm3('id_user','opportunity','id="'.$id.'" '.$this->model->user_group('id_user')['query']); 
        
        if( !$this->model->allow_access_data( $id_user)  || !$id_user  ){
            echo $this->model->error("Access data not allow"); 
            exit;
        }

        $data  = [];
        $id_company = 0;
        $id_stage = 0;
        $query = $this->db->query('select * from crm3_opportunity where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $id_industry = $this->model->select('id_industry', 'crm3_company', ' id = "' . $row->id_company . '" ');
            $id_company = $row->id_company;

            $weight = $this->model->select('sum(s.weight)', 'crm3_opportunity_stage_done  AS sd JOIN crm3_opportunity_stage AS s ON s.id = sd.id_stage', 'sd.id_opportunity = ' . $row->id . ' AND  sd.done = 1 AND sd.presence = 1');

            $data = array(
                "id" => $row->id,
                "closed" => (bool) $row->closed,
                "name"  => $row->name,
                "stage" => $this->model->select('name', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),

                "id_opportunity_business" => $row->id_opportunity_business,
                "weight" => $weight,
                "currency" => $this->model->currency(),
                "amount" => $row->amount,
                "budget" =>    $row->budget,
                "id_company" => $row->id_company,
                "company" => $this->model->company($row->id_company),
                "progress" =>  $this->model->select('progress', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "id_contact" => $row->id_contact,
                "contact" => array(
                    "name" => $this->model->contact($row->id_contact),
                    "email" =>  $this->model->crm3('email', 'contact', 'id="' . $row->id_contact . '" '),
                    "phone" =>  $this->model->crm3('phone', 'contact', 'id="' . $row->id_contact . '" '),

                ),

                "id_lead_source" =>  $row->id_lead_source,
                "industry" => $this->model->industry($id_industry),


                "competitor" =>  $row->competitor,
                "comparison_with_competitor" =>  $row->comparison_with_competitor,
                "critical_point" =>  $row->critical_point,
                "our_proposal" =>  $row->our_proposal,
                "notes1" =>  $row->notes1,
                "notes2" =>  $row->notes2,
                "notes3" =>  $row->notes3,
                "po" =>  $row->po,
                "id_quote" =>  $row->id_quote,

                "user" => $this->model->user($row->id_user),
                "id_user" =>  $row->id_user,
                "closed_date" => array(
                    "year" => (int) date('Y', strtotime($row->closed_date)),
                    "month" => (int) date('m', strtotime($row->closed_date)),
                    "day" => (int) date('d', strtotime($row->closed_date)),
                ),
                "expecting_closing_date" => array(
                    "year" => (int) date('Y', strtotime($row->expecting_closing_date)),
                    "month" => (int) date('m', strtotime($row->expecting_closing_date)),
                    "day" => (int) date('d', strtotime($row->expecting_closing_date)),
                ),
                "start_date" => array(
                    "year" => (int) date('Y', strtotime($row->start_date)),
                    "month" => (int) date('m', strtotime($row->start_date)),
                    "day" => (int) date('d', strtotime($row->start_date)),
                ),
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
                "id_stage" =>  $row->id_stage,
                "final_stage" => $row->id_stage == $this->model->select('id', 'crm3_opportunity_stage', 'id < 999 order by progress DESC limit 1') ? true : false,


                "check" => false,
            );
            $id_stage = $row->id_stage;
        }


        $stage = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_opportunity_stage where presence = 1 and selectable = 1 order by progress ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"            => $row->id,
                "id_next"            => $row->id_next,

                "name"          => $row->name,
                "progress"      => $row->progress . '%',
                "done"          => $this->model->select('done', 'crm3_opportunity_stage_done', 'id_stage = "' . $row->id . '" and id_opportunity =  "' . $id . '"') ? "done" : false,
                // "notes"         => $row->notes,
                "current"       => $row->id == $id_stage ? "current" : false,
                "isSelected" => $row->id == $id_stage ? true : false
            );
            array_push($stage, $temp);
            $i++;
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1   '.$this->model->user_group()['query'].'  order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }

        $quotes = [];
        $query = $this->db->query('select * from crm3_quote where presence = 1 and id_opportunity = "' . $id . '"  '.$this->model->user_group('id_user')['query'].'  order by input_date DESC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "version" => $row->version,
                "quotes_number" => $row->quotes_number,
                "currency" => $this->model->currency(),
                "syncing" => $row->syncing ? true : false,
                "grand_total" => $row->grand_total,
                "expired_date" => $row->expired_date,
                "discount" => $row->discount,
                "input_date" => $row->input_date,
                "input_by" => $row->input_by,
                "id_quote_status" => $row->id_quote_status,

            );
            array_push($quotes, $temp);
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


        $business = [];
        $query = $this->db->query('select * from crm3_opportunity_business where presence = 1  order by ID ASC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($business, $temp);
        }

        $lead_source = [];
        $query = $this->db->query('select * from crm3_lead_source where presence = 1  order by name ASC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($lead_source, $temp);
        }

        $contact = [];
        if ($data) {

            $query = $this->db->query('select * from crm3_contact where id_company = "' . $data['id_company'] . '" and presence = 1  '.$this->model->user_group('id_user')['query'].'  order by first_name ASC, last_name DESC ');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $this->model->contact($row->id),
                    "position" => $row->position,
                    "department" => $row->department,
                    "email" => $row->email,
                    "phone" => $row->phone,
                    "mobile" => $row->mobile,
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
                "url" => str_replace('./',base_url(), $row->url) ,

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
                "url" => str_replace('./',base_url(), $row->url) ,

                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($po, $temp);
        }
        $information = [];
        $query = $this->db->query('select * from crm3_company where presence = 1 and id = ' .  $id_company);
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


        $reason_win = [];
        $query = $this->db->query('select * from crm3_opportunity_closed_reason where closed_win = 1  order by note ASC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "note" => $row->note,
            );
            array_push($reason_win, $temp);
        }

        
        $reason_lose = [];
        $query = $this->db->query('select * from crm3_opportunity_closed_reason where closed_win = 0  order by note ASC ');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "note" => $row->note,
            );
            array_push($reason_lose, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "stage" => $stage,
                "quotes" => $quotes,
                "information" => $information,
                "reason_win" => $reason_win,
                "reason_lose" => $reason_lose,

                "product" => $product,
                "lead_source" => $lead_source,
                "business" => $business,
                "width" => 94 / $i,
                "user" => $user,
                "attachment" => $attachment,
                "attachmentPO" => $po,
                "contact" => $contact,
                "allow_access_data" =>  $this->model->allow_access_data( $id_user)
            ),
        );

        echo json_encode($json);
    }


    public function updateStep()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $id = $this->model->select('id', 'crm3_opportunity_stage_done', 'id_opportunity = ' . $post['id_opportunity'] . ' and  id_stage = ' . $post['id_stage']);
            /**
             * Update Checkbox quiz
             */
            if ($id) {
                $update = array(
                    "done" => 1,
                    "update_by" => $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->update("crm3_opportunity_stage_done", $update, ' id=' . $id);
            } else {
                $insert = array(
                    "id_opportunity" => $post['id_opportunity'],
                    "id_stage" => $post['id_stage'],
                    "done" => 1,
                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" => $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->insert("crm3_opportunity_stage_done", $insert);
            }



            /**
             * Update Opportunity 
             */
            $insert = array(
                "id_opportunity" => $post['id_opportunity'],
                "id_stage" =>  $this->model->select('id_stage', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
                "id_user" =>  $this->model->select('id_user', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
                "id_currency" =>  $this->model->select('id_currency', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
                "amount" =>  $this->model->select('amount', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" => $this->model->id_user(),
            );
            $this->db->insert("crm3_opportunity_log", $insert);

            $update = array(
                "id_stage" =>  $this->model->select('id_next', 'crm3_opportunity_stage', 'id=' . $post['id_stage']),

                "budget" =>  $post['data']['budget'],
                "competitor" =>  $post['data']['competitor'],
                "comparison_with_competitor" =>  $post['data']['comparison_with_competitor'],
                "critical_point" =>  $post['data']['critical_point'],
                "our_proposal" =>  $post['data']['our_proposal'],
                "id_contact" =>  $post['data']['id_contact'],
                "po" =>  $post['data']['po'],
                "notes1" =>  $post['data']['notes1'],
                "notes2" =>  $post['data']['notes2'],
                "notes3" =>  $post['data']['notes3'],
                "id_quote" =>  $post['data']['id_quote'],


                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_opportunity", $update, ' id=' .  $post['id_opportunity']);


            /**
             * CLOSED PROJECT
             */
            $respons_closed = [];
            if ($post['closed'] > 999) {
                $data = array(
                    "id_opportunity" =>   $post['id_opportunity'],
                    "id_stage" =>   $post['closed'],
                );
                $respons_closed = self::closed_project($data);
            }


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "respons_closed" =>  $respons_closed,
                    "note"  => "update stage done",
                ),
            );
            echo json_encode($json);
        }
    }


    function closeOpportunity(){
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $id = $this->model->select('id', 'crm3_opportunity_stage_done', 'id_opportunity = ' . $post['id_opportunity'] . ' and  id_stage = ' . $post['id_stage']);
            /**
             * Update Checkbox quiz
             */
            if ($id) {
                $update = array(
                    "done" => 1,
                    "update_by" => $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->update("crm3_opportunity_stage_done", $update, ' id=' . $id);
            } else {
                $insert = array(
                    "id_opportunity" => $post['id_opportunity'],
                    "id_stage" => $post['id_stage'],
                    "done" => 1,
                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" => $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->insert("crm3_opportunity_stage_done", $insert);
            }



            /**
             * Update Opportunity 
             */ 
            $update = array(
                "id_stage" =>  $this->model->select('closed_win', 'crm3_opportunity_closed_reason', 'id=' . $post['id_closed_reason']) ? "1000":"3000",
                "id_closed_reason" => $post['id_closed_reason'],

                "budget" =>  $post['data']['budget'],
                "competitor" =>  $post['data']['competitor'],
                "comparison_with_competitor" =>  $post['data']['comparison_with_competitor'],
                "critical_point" =>  $post['data']['critical_point'],
                "our_proposal" =>  $post['data']['our_proposal'],
                "id_contact" =>  $post['data']['id_contact'],
                "po" =>  $post['data']['po'],
                "notes1" =>  $post['data']['notes1'],
                "notes2" =>  $post['data']['notes2'],
                "notes3" =>  $post['data']['notes3'],
                "id_quote" =>  $post['data']['id_quote'],
                 
                "closed" => 1,
                "closed_date" => date('Y-m-d'),
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_opportunity", $update, ' id=' .  $post['id_opportunity']);

 

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $update, 
                    "id_stage" => (int)$this->model->crm3('id_stage','opportunity',' id=' .  $post['id_opportunity']),
                    "note"  => "update stage done",
                ),
            );
            echo json_encode($json);
        }
    }

    function closed_project($post)
    {
        $id = $this->model->select('id', 'crm3_opportunity_stage_done', 'id_opportunity = ' . $post['id_opportunity'] . ' and  id_stage = ' . $post['id_stage']);

        if ($id) {
            $update = array(
                "done" => 1,
                "update_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->update("crm3_opportunity_stage_done", $update, ' id=' . $id);
        } else {
            $insert = array(
                "id_opportunity" => $post['id_opportunity'],
                "id_stage" => $post['id_stage'],
                "done" => 1,
                "input_date" => date('Y-m-d H:i:s'),
                "input_by" => $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->insert("crm3_opportunity_stage_done", $insert);
        }

        $insert = array(
            "id_opportunity" => $post['id_opportunity'],
            "id_stage" =>  $this->model->select('id_stage', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
            "id_user" =>  $this->model->select('id_user', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
            "id_currency" =>  $this->model->select('id_currency', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
            "amount" =>  $this->model->select('amount', 'crm3_opportunity', 'id=' . $post['id_opportunity']),
            "input_date" => date('Y-m-d H:i:s'),
            "update_date" => date('Y-m-d H:i:s'),
            "input_by" => $this->model->id_user(),
        );
        $this->db->insert("crm3_opportunity_log", $insert);

        $update = array(
            "id_stage" => $post['id_stage'],
            "closed" => 1,
            "closed_date" => date('Y-m-d H:i:s'),
            "update_date" => date('Y-m-d H:i:s'),
            "update_by" => $this->model->id_user(),
        );
        $this->db->update("crm3_opportunity", $update, ' id=' .  $post['id_opportunity']);
        return $update;
    }


    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $id = $this->model->number("opportunity");

            if ($post['data']['insertContact'] == true) {
                $id_contact = $this->model->number("contact");
                $insert = array(
                    "id" => $id_contact,
                    "first_name"     => isset($post['data']['first_name']) ? $post['data']['first_name'] : "",
                    "last_name"     => isset($post['data']['last_name']) ? $post['data']['last_name'] : "",
                    "id_title"     => isset($post['data']['id_title']) ? $post['data']['id_title'] : "0",
                    "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : $this->model->id_user(),
                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->insert("crm3_contact", $insert);
            } else {
                $id_contact = isset($post['data']['id_contact']) ? $post['data']['id_contact'] : "0";
            }

            $insert = array(
                "id" => $id,
                "name"     => isset($post['data']['opportunity']) ? $post['data']['opportunity'] : "",
                "id_company" => isset($post['data']['id_company']) ? $post['data']['id_company'] : "0",
                "id_contact" => $id_contact,
                "id_lead_source" => isset($post['data']['id_lead_source']) ? $post['data']['id_lead_source'] : "0",
                "id_stage" => $this->model->select('id', 'crm3_opportunity_stage', 'presence = 1 order by progress ASC limit 1'),

                "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->insert("crm3_opportunity", $insert);


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

    public function widget_new_opportunity($module = "", $id = 0)
    {

        $data = [];

        if ($module == 'contact') {


            $id_company = $this->model->crm3('id_company', 'contact', 'id="' . $id . '" ');
            $company = [];
            $query = $this->db->query('select * from crm3_company where id = ' . $id_company . ' and presence = 1 order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }


            $contact = [];
            $query = $this->db->query('select * from crm3_contact where id_company = ' . $id_company . '  '.$this->model->user_group('id_user')['query'].' and  presence = 1 order by first_name ASC, last_name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->first_name . ' ' . $row->last_name,
                );
                array_push($contact, $temp);
            }

            $data = array(
                "id_company" => $id_company,
                "id_contact" => $id,
                "id_user"   => $this->model->id_user(),
            );
        } else if ($module == 'company') {

            $id_company =  $id;
            $company = [];
            $query = $this->db->query('select * from crm3_company where id = ' . $id_company . ' and presence = 1 order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }


            $contact = [];
            $query = $this->db->query('select * from crm3_contact where id_company = ' . $id_company . ' '.$this->model->user_group('id_user')['query'].' and  presence = 1 order by first_name ASC, last_name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->first_name . ' ' . $row->last_name,
                );
                array_push($contact, $temp);
            }

            $data = array(
                "id_company" => $id_company,
                "id_contact" => $id,
                "id_user"   => $this->model->id_user(),
            );
        } else if ($module == 'opportunity') {

            $company = [];
            $query = $this->db->query('select * from crm3_company  where presence = 1   order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }


            $contact = [];


            $data = array(
                "id_company" => 0,
                "id_contact" => 0,
                "id_user"   => $this->model->id_user(),
            );
        } else {
            $company = [];
            $query = $this->db->query('select * from crm3_company where presence = 1  order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }


            $contact = [];
            $query = $this->db->query('select * from crm3_contact where presence = 1 '.$this->model->user_group('id_user')['query'].'  order by first_name ASC, last_name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->first_name . ' ' . $row->last_name,
                );
                array_push($contact, $temp);
            }
        }



        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1   '.$this->model->user_group()['query'].'  order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }

        $stage = [];
        $query = $this->db->query('select * from crm3_opportunity_stage where presence = 1 and ilock = 0  order by progress ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "progress" => $row->progress,

            );
            array_push($stage, $temp);
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

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data"  => $data,
                "company" => $company,
                "contact" => $contact,
                "user"  => $user,
                "stage"  => $stage,
                "lead_source" => $lead_source,
            ),
        );

        echo json_encode($json);
    }

    public function contact($id_company = 0)
    {

        $contact = [];
        $query = $this->db->query('select * from crm3_contact where presence = 1 and id_company = "' . $id_company . '"  '.$this->model->user_group('id_user')['query'].'   order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => (int) $row->id,
                "name" => $this->model->contact($row->id),
            );
            array_push($contact, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "contact"  => $contact,
            ),
        );
        echo json_encode($json);
    }

    public function update()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array(

                "name"     => isset($post['data']['name']) ? $post['data']['name'] : "",
                "amount"     => isset($post['data']['amount']) ? $post['data']['amount'] : 0,
                "id_opportunity_business" => isset($post['data']['id_opportunity_business']) ? $post['data']['id_opportunity_business'] : "0",
                "id_lead_source" => isset($post['data']['id_lead_source']) ? $post['data']['id_lead_source'] : "0",
                "id_user" => isset($post['data']['id_user']) ? $post['data']['id_user'] : "0",
                "id_contact" => isset($post['data']['id_contact']) ? $post['data']['id_contact'] : "0",
                "budget" => isset($post['data']['budget']) ? $post['data']['budget'] : "0",

                "competitor" => isset($post['data']['competitor']) ? $post['data']['competitor'] : "",

                "expecting_closing_date" => $post['data']['expecting_closing_date']['year'] . '-' . $post['data']['expecting_closing_date']['month'] . '-' . $post['data']['expecting_closing_date']['day'],

                "comparison_with_competitor" => isset($post['data']['comparison_with_competitor']) ? $post['data']['comparison_with_competitor'] : "",
                "critical_point" => isset($post['data']['critical_point']) ? $post['data']['critical_point'] : "",
                "our_proposal" => isset($post['data']['our_proposal']) ? $post['data']['our_proposal'] : "",
                "other" => isset($post['data']['other']) ? $post['data']['other'] : "",

                "id_quote" => isset($post['data']['id_quote']) ? $post['data']['id_quote'] : "",
                "notes1" => isset($post['data']['notes1']) ? $post['data']['notes1'] : "",
                "notes2" => isset($post['data']['notes2']) ? $post['data']['notes2'] : "",
                "notes3" => isset($post['data']['notes3']) ? $post['data']['notes3'] : "",


                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_opportunity", $update, 'closed = 0 and  id=' . $post['id']);

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

        $company = [];
        $query = $this->db->query('select * from crm3_company where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company, $temp);
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

        $json = array(
            "error"  =>  0,
            "result" => array(
                "company"  => $company,
                "title"  => $title,
                "lead_source" => $lead_source,
                "user"  => $user,
            ),
        );
        echo json_encode($json);
    }


    public function fn_delete()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];

            foreach ($post as $row) {
                $update = array(
                    "update_date" => date('Y-m-d H:i:s'),
                    "update_by" => $this->model->id_user(),
                    "presence"  => 0,
                );
                $this->db->update("crm3_opportunity", $update, 'id=' .  $row['id']);
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

    function fn_delete_prorduct()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $update = array(
                "presence" => 0,
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_opportunity_product", $update, ' id=' .  $post['data']['id']);
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

    public function fn_deleteSolo()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
                "presence"  => 0,
            );
            $this->db->update("crm3_opportunity", $update, 'id=' .  $post['id']);


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

    public function updateStage()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {

            $insert = array(
                "id_opportunity" =>  $post['id'],
                "id_stage" =>  $this->model->select('id_stage', 'crm3_opportunity', 'id=' . $post['id']),
                "id_user" =>  $this->model->select('id_user', 'crm3_opportunity', 'id=' . $post['id']),
                "id_currency" =>  $this->model->select('id_currency', 'crm3_opportunity', 'id=' . $post['id']),
                "amount" =>  0,
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" => $this->model->id_user(),
            );
            $this->db->insert("crm3_opportunity_log", $insert);

            $update = array(
                "id_stage" => $post['id_stage'],

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

    public function edit($id = 0)
    {
        $id_company = 0;
        $data = [];
        $query = $this->db->query('select * from crm3_opportunity where presence = 1 and closed = 0 and id = "' . $id . '"');
        foreach ($query->result() as $row) {
            $id_company =  $row->id_company;
            $data = array(
                "id" => $row->id,
                "name"  => $row->name,
                "stage" => $this->model->select('name', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "progress" => $this->model->select('progress', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "currency" => $this->model->currency(),
                "amount" =>  $row->amount,
                "id_company" => $row->id_company,

                "id_lead_source" => $row->id_lead_source,
                "company" => $this->model->company($row->id_company),

                "id_contact" => $row->id_contact,
                "contact" => $this->model->contact($row->id_contact),

                "user" => $this->model->user($row->id_user),
                "id_user" =>  $row->id_user,

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),


            );
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

        $contact = [];
        $query = $this->db->query('select * from crm3_contact where presence = 1 and id_company = "' . $id_company . '" order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" =>  $this->model->contact($row->id),
            );
            array_push($contact, $temp);
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


        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "contact"  => $contact,
                "lead_source" => $lead_source,
                "user"  => $user,
            ),
        );

        echo json_encode($json);
    }

    public function updateClosed()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {

            $insert = array(
                "id_opportunity" =>  $post['id'],
                "id_stage" =>  $this->model->select('id_stage', 'crm3_opportunity', 'id=' . $post['id']),
                "id_user" =>  $this->model->select('id_user', 'crm3_opportunity', 'id=' . $post['id']),
                "amount" =>  $this->model->select('amount', 'crm3_opportunity', 'id=' . $post['id']),
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" => $this->model->id_user(),
            );
            $this->db->insert("crm3_opportunity_log", $insert);

            $update = array(
                "id_stage" => $post['status'] == 'win' ? 1000 : 3000,
                "closed" => 1,
                "closed_date" => $post['data']['date']  ?  $post['data']['date']['year'] . '-' . $post['data']['date']['month'] . '-' . $post['data']['date']['day'] : date('Y-m-d'),
                "notes" => $post['data']['notes'],
                "amount" => $post['data']['amount'],

                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_opportunity", $update, 'id=' .  $post['id']);



            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "note"  => "Closed done",
                ),
            );
            echo json_encode($json);
        }
    }


    public function fn_newQuote()
    {
        $error = 0;
        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {
            $this->db->trans_start();
            $insert = array(
                "quotes_number" => $this->model->number("quote"),
                "id_opportunity" =>  $post['id'],
                "grand_total" =>  0,
                "name" =>   $post['data']['name'],
                "expired_date" =>   $post['data']['expirationDate']['year'] . '-' . $post['data']['expirationDate']['month'] . '-' . $post['data']['expirationDate']['day'],

                "syncing" =>   $post['data']['syncing'],
                "id_user" =>   $post['data']['id_user'],

                "id_contact" =>   $post['data']['id_contact'],
                "email" =>   $post['data']['email'],
                "phone" =>   $post['data']['phone'],
                "fax" =>   $post['data']['fax'],
                "description" =>   $post['data']['description'],

                "bill_name" =>   $post['data']['bill_name'],
                "bill_street1" =>   $post['data']['bill_street1'],
                "bill_city" =>   $post['data']['bill_city'],
                "bill_state" =>   $post['data']['bill_state'],
                "bill_code" =>   $post['data']['bill_code'],
                "bill_country" =>   $post['data']['bill_country'],

                "ship_name" =>   $post['data']['ship_name'],
                "ship_street1" =>   $post['data']['ship_street1'],
                "ship_city" =>   $post['data']['ship_city'],
                "ship_state" =>   $post['data']['ship_state'],
                "ship_code" =>   $post['data']['ship_code'],
                "ship_country" =>   $post['data']['ship_country'],

                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "presence" => '1',
                "input_by" => $this->model->id_user(),
            );
            $this->db->insert("crm3_quote", $insert);
            $id = $post['id'];
            $id_quote = $this->model->crm3('id', 'quote', ' id_opportunity = "' . $post['id'] . '"  and presence = 1  order by input_date DESC');

            $sorting = 1;
            $query = $this->db->query('select * from crm3_opportunity_product where presence = 1 and id_opportunity = "' . $id . '" order by input_date DESC ');
            foreach ($query->result() as $row) {
                $insert = array(
                    "id_quote" =>  $id_quote,
                    "id_product" => $row->id_product,
                    "description" => $this->model->crm3('description', 'product', 'id=' . $row->id_product),
                    "sorting" => $sorting++,
                    "price" => $row->price,
                    "qty" => $row->qty,
                    "discount" => 0,
                    "total" => $row->price * $row->qty,

                    "input_date" => $row->input_date,
                    "input_by" => $this->model->user($row->input_by),
                    "update_date" => $row->update_date,
                    "update_by" =>  $this->model->user($row->update_by),

                );
                $this->db->insert("crm3_quote_detail", $insert);
            }





            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $error = 666;
            }

            $json = array(
                "error"  => $error,
                "result" => array(
                    "id" =>   $this->model->crm3('id', 'quote', 'input_by = ' . $this->model->id_user() . ' order by input_date DESC'),
                    "note"  => "Qutes new done",
                ),
            );
            echo json_encode($json);
        }
    }




    // new ng8
    function fn_newOpportunity()
    {

        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $id = $this->model->number("opportunity");

            $insert = array(
                "id" => $id,
                "name" => $post['data']['name'],
                "id_contact" => $post['data']['id_contact'],
                "id_company" => $post['data']['id_company'],
                    "id_stage" => '100',
                "id_lead_source" => $post['data']['id_lead_source'],

                "amount" => $post['data']['amount'],
                "expecting_closing_date" => $post['data']['expecting_closing_date']['year'] . '-' . $post['data']['expecting_closing_date']['month'] . '-' . $post['data']['expecting_closing_date']['day'],
                "start_date" => date('Y-m-d H:i:s'),
                "id_user" =>  isset($post['data']['id_user']) ?  $post['data']['id_user'] : $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->insert("crm3_opportunity", $insert);


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
}
