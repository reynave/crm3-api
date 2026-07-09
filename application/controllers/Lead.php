<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Lead extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->key = $this->db->key;
        $this->openAPI = $this->db->openAPI;
         header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
        header('Content-Type: application/json');

        date_default_timezone_set('Asia/Jakarta');
        $this->db->query("SET time_zone = '+07:00'");
        //  $this->core->https();

        //if (!$this->model->header($this->openAPI)) {
          //  echo $this->model->error("Error auth");
          //  exit;
        //}
    }


    function index()
    {

        $this->load->library('ssp');
        // DB table to use
        //$table = 'crm3_company';
        $currency = $this->model->currency();
        $table = <<<EOT
 (
    SELECT  c.id, lst.name as 'lead_status', lst.value, 
    CONCAT(  c.first_name,' ',c.last_name) AS 'name', lst.color, 
    c.company, c.website,  
    IFNULL(i.name,'Undefined') AS 'industry',  
    IFNULL(ls.name,'Undefined') AS 'lead_source',   
    IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user' 

    FROM crm3_lead AS c  
    LEFT JOIN crm3_industry AS i ON i.id = c.id_industry 
    LEFT JOIN crm3_title AS t ON t.id = c.id_title 
    LEFT JOIN crm3_lead_source AS ls ON ls.id = c.id_lead_source 
    LEFT JOIN crm3_lead_status AS lst ON lst.id = c.id_lead_status 
    LEFT JOIN crm3_user AS u ON u.id = c.id_user 
    WHERE c.presence = 1 and c.convert = 0 
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
            array('db' => 'lead_status',  'dt' => 1),
            array('db' => 'name',  'dt' => 2),
            array('db' => 'company',  'dt' => 3),

            array('db' => 'website',  'dt' => 4),
            array('db' => 'industry',  'dt' => 5),
            array('db' => 'lead_source',  'dt' => 6),
            array('db' => 'user',  'dt' => 7),

            array('db' => 'color',  'dt' => 8),
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

        $id_user = $this->model->crm3('id_user', 'lead', 'id="' . $id . '" '.$this->model->user_group('id_user')['query']);

        if (!$this->model->allow_access_data($id_user) || !$id_user) {
            echo $this->model->error("Access data not allow");
             exit;
        }

        $data = [];
        $query = $this->db->query('select * from crm3_lead where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id"        => $row->id,
                "id_user"   => $row->id_user,
                "id_company_class"   => $row->id_company_class,
                "company_class"   => $this->model->select('name', 'crm3_company_class', 'id=' . $row->id_company_class),
                "id_company"   => $row->id_company,
                "id_lead_status" => $row->id_lead_status,
                "id_title" => $row->id_title,
                "id_lead_source" => $row->id_lead_source,
                "first_name"      =>  $row->first_name,
                "last_name"      =>   $row->last_name,

                "department"   => $row->department,
                "position"   => $row->position,
                "isDuplicate"   => $row->isDuplicate,
                "isNewCompany"   => $row->isDuplicate,

                "company"   =>  $row->company,
                "id_industry" => $row->id_industry,
                "website" =>   $row->website,
                "email" =>   $row->email,
                "phone" => $row->phone,
                "mobile" => $row->mobile,
                "fax" => $row->fax,
                "opportunity" => $row->opportunity,
                "amount" => $row->amount,

                "address_street" => $row->address_street,
                "address_city" => $row->address_city,
                "address_state" => $row->address_state,
                "address_code" =>  $row->address_code,
                "address_country" => $row->address_country,


                "lead_status" => array(
                    "id" => $row->id_lead_status,
                    "name" => $this->model->select('name', 'crm3_lead_status', 'id=' . $row->id_lead_status),
                    "color"  => $this->model->select('color', 'crm3_lead_status', 'id=' . $row->id_lead_status),
                ),

                "lead_status_name" => $this->model->select('name', 'crm3_lead_status', 'id=' . $row->id_lead_status),
                "lead_status_color"  => $this->model->select('color', 'crm3_lead_status', 'id=' . $row->id_lead_status),

                "opportunity" => $row->opportunity,
                "amount" => $row->amount,

                "sex" => $row->sex,
                "birthdate" => array(
                    "year"  => (int) date('Y', strtotime($row->birthdate)),
                    "month" => (int) date('m', strtotime($row->birthdate)),
                    "day"   => (int) date('d', strtotime($row->birthdate)),
                ),

                "currency" => $this->model->currency(),

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
        }


        $lead_status = [];
        $q = " select * from crm3_lead_status where presence = 1 order by value asc ";
        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id"    => $row->id,
                "name"  => $row->name,
                "value" => (int) $row->value,
                "color" => $row->color,
                "width" => 100 / $this->model->crm3('count(id)', 'lead_status', '1'),

            );
            array_push($lead_status, $temp);
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
        $query = $this->db->query('select * from crm3_user where presence = 1   ' . $this->model->user_group()['query'] . '  order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }

        $company = [];
        $query = $this->db->query('select * from crm3_company where presence = 1    order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company, $temp);
        }

        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "lead" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => str_replace('./', base_url(), $row->url),
                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }


        $product = [];
        $query = $this->db->query('select * from crm3_lead_product where id_lead = "' . $id . '" and presence = 1 order by input_date desc');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "id_product" => $row->id_product,
                "product" => $this->model->crm3('name', 'product', 'id=' . $row->id_product),
                "discount"  => $row->discount,
                "currency" => $this->model->currency(),
                "price"  => $row->price,
                "qty"  => (int) $row->qty,
                "subtotal"  => $row->price * $row->qty,
                "total"  => $row->total,
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
            array_push($product, $temp);
        }

        $company_class = [];
        $query = $this->db->query('select * from crm3_company_class where presence = 1 order by name desc');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company_class, $temp);
        }

        $opportunity_stage = [];
        $query = $this->db->query('select * from crm3_opportunity_stage where presence = 1 and ilock = 0 order by progress ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "progress" => $row->progress,
            );
            array_push($opportunity_stage, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "lead" => $data,
                "product" => $product,
                "company_class" => $company_class,
                "title" => $title,
                "lead_source"  => $lead_source,
                "attachment" => $attachment,
                "industry"  => $industry,
                "opportunity_stage"  => $opportunity_stage,
                "user"  => $user,
                "company" => $company,
                "lead_status" => $lead_status,
                "allow_access_data" =>  $this->model->allow_access_data($id_user) 
            ),
        );

        echo json_encode($json);
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

    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $id_lead = $this->model->number("lead");
            $insert = array(
                "id" => $id_lead,
                "id_user"      => $this->model->id_user(),
                "id_title"      => $post['id_title'],
                "first_name"      => ucwords( $post['first_name'] ),
                "last_name"      => ucwords( $post['last_name'] ),
                "email"      => $post['email'],
                "company"   => $post['company'],
                "id_company_class"   => $post['id_company_class'],

                "position"   => $post['position'],
                "department"   => $post['department'],

                "id_lead_source" => $post['id_lead_source'],
                "website" => $post['website'],
                "phone" => $post['phone'],
                "mobile" => $post['mobile'],
                "fax" => $post['fax'],

                "id_company" => $post['id_company'],
                "id_industry" => $post['id_industry'],
                "sex" => $post['sex'],
                "birthdate" => $post['birthdate']['year'] . '-' . $post['birthdate']['month'] . '-' . $post['birthdate']['day'],

                "address_street" => $post['address_street'],
                "address_city" => $post['address_city'],
                "address_state" => $post['address_state'],
                "address_code" => $post['address_code'],
                "address_country" => $post['address_country'],

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

    public function update()
    {

        $post = json_decode(file_get_contents('php://input'), true);
        if ($post) {

            $update = array(

                "id_user"      => $post['data']['id_user'],
                "id_title"      => $post['data']['id_title'],
                "first_name"      => ucwords( $post['data']['first_name']),
                "last_name"      => ucwords($post['data']['last_name']),
                "position"      => $post['data']['position'],
                "department"      => $post['data']['department'],
                "amount"      => $post['data']['amount'],
                "email"      => $post['data']['email'],
                "company"   => $post['data']['company'],
                "id_lead_source" => $post['data']['id_lead_source'],
                "id_company_class" => $post['data']['id_company_class'],
                "website" => $post['data']['website'],
                "fax" => $post['data']['fax'],

                "address_street" => $post['data']['address_street'],
                "address_city" => $post['data']['address_city'],
                "address_state" => $post['data']['address_state'],
                "address_code" => $post['data']['address_code'],
                "address_country" => $post['data']['address_country'],

                "sex" => $post['data']['sex'],
                "birthdate" => $post['data']['birthdate']['year'] . '-' . $post['data']['birthdate']['month'] . '-' . $post['data']['birthdate']['day'],


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


        $company_class = [];
        $query = $this->db->query('select * from crm3_company_class where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company_class, $temp);
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
        $query = $this->db->query('select * from crm3_user where presence = 1  ' . $this->model->user_group()['query'] . '  order by first_name ASC, last_name ASC');
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
                "company_class" => $company_class,
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

    public function selectContact($id = 0)
    {

        if ($id > 0) {
            $contacts = [];
            $query = $this->db->query('select * from crm3_contact where id_company = "' . $id . '" and presence = 1 order by first_name ASC, last_name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->first_name . ' ' . $row->last_name,
                );
                array_push($contacts, $temp);
            }

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "contacts" => $contacts,
                ),
            );
            echo json_encode($json);
        }
    }

    function requestCompanyData($id = 0)
    {
    }

    public function getCompany()
    {

        $data = [];
        if ($this->input->get('id')) {
            $q = " select *  from crm3_company where presence = 1 and id=" . $this->input->get('id') . " order by name ASC ";
            $query = $this->db->query($q);
            $data = $query->result();
        } else {
            $q = " select *  from crm3_company where presence = 1  order by name ASC ";
            $query = $this->db->query($q);
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($data, $temp);
            }
        }



        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
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
                    "presence"  => 0,
                );
                $this->db->update("crm3_lead", $update, 'id=' .  $row['id']);
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

    function fn_deleteProduct()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $update = array(
                "presence"  => 0,
            );
            $this->db->update("crm3_lead_product", $update, 'id=' . $post['id']);


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
            $this->db->update("crm3_lead", $update, 'id=' .  $post['id_lead']);


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
                $this->model->select('bill_street1', 'crm3_company', 'id=' . $row->id_company) . ' ' .
                //    $this->model->select('address_city', 'crm3_company', 'id=' . $row->id_company) . ' ' .
                //    $this->model->select('address_state', 'crm3_company', 'id=' . $row->id_company) . ' ' .
                $this->model->select('bill_street2', 'crm3_company', 'id=' . $row->id_company);

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

                //   "address_street" => $row->address_street,
                //   "address_city"  => $row->address_city,
                //   "address_state" =>  $row->address_state,
                //   "address_code"  => $row->address_code,

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

            if ($post['data']['isDuplicate'] == "0") {
                $id_company = $this->model->number("company");

                $insert = array(
                    "id"        => $id_company,
                    "name"      => $post['data']['newCompany'],
                    "website"   => $this->model->select('website', 'crm3_lead', 'id=' . $id_lead),
                    "phone"     =>  $this->model->select('phone', 'crm3_lead', 'id=' . $id_lead),
                    "fax"       => $this->model->select('fax', 'crm3_lead', 'id=' . $id_lead),
                    "email"     => $this->model->select('email', 'crm3_lead', 'id=' . $id_lead),

                    "id_industry" =>  $this->model->select('id_industry', 'crm3_lead', 'id=' . $id_lead),

                    "id_user" =>  $this->model->select('id_user', 'crm3_lead', 'id=' . $id_lead),
                    "id_company_class" =>  $this->model->select('id_company_class', 'crm3_lead', 'id=' . $id_lead),

                    "bill_street1" => $this->model->select('address_street', 'crm3_lead', 'id=' . $id_lead),
                    "bill_city" =>  $this->model->select('address_city', 'crm3_lead', 'id=' . $id_lead),
                    "bill_state" => $this->model->select('address_state', 'crm3_lead', 'id=' . $id_lead),
                    "bill_code" => $this->model->select('address_code', 'crm3_lead', 'id=' . $id_lead),
                    "bill_country" => $this->model->select('address_country', 'crm3_lead', 'id=' . $id_lead),

                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),

                );
                $this->db->insert("crm3_company", $insert);
            } else {
                $id_company = $post['data']['cl_id_company'];
            }

            $last_query =  $last_query . ';' . $this->db->last_query();


            $id_contact = $this->model->number("contact");
            $insert = array(
                "id"                => $id_contact,
                "id_company"      => $id_company,
                "first_name"      => $post['data']['lc_first_name'],
                "last_name"     =>  $post['data']['lc_last_name'],
                "id_lead_source" =>  $this->model->select('id_lead_source', 'crm3_lead', 'id=' . $id_lead),
                "id_title" =>  $this->model->select('id_title', 'crm3_lead', 'id=' . $id_lead),
                "position" =>  $this->model->select('position', 'crm3_lead', 'id=' . $id_lead),
                "id_title" => $this->model->select('id_title', 'crm3_lead', 'id=' . $id_lead),
                "email" =>   $this->model->select('email', 'crm3_lead', 'id=' . $id_lead),
                "mobile" =>  $this->model->select('mobile', 'crm3_lead', 'id=' . $id_lead),
                "phone" =>   $this->model->select('phone', 'crm3_lead', 'id=' . $id_lead),

                "department" =>   $this->model->select('department', 'crm3_lead', 'id=' . $id_lead),
                "position" =>   $this->model->select('position', 'crm3_lead', 'id=' . $id_lead),

                "sex" =>   $this->model->select('sex', 'crm3_lead', 'id=' . $id_lead),
                "birthdate" =>   $this->model->select('birthdate', 'crm3_lead', 'id=' . $id_lead),


                "id_user" =>  $post['data']['id_user'],
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
            );
            $this->db->insert("crm3_contact", $insert);
            $last_query =  $last_query . ';' . $this->db->last_query();

            $id_opportunity = false;
            if ($post['data']['opportunity'] != "") {

                $id_opportunity = $this->model->number("opportunity");
                $insert = array(
                    "id"                => $id_opportunity,
                    "id_user"           => $post['data']['id_user'],
                    "id_lead"           => $id_lead,
                    "id_contact"        => $id_contact,
                    "id_company"        => $id_company,
                    "id_currency"       => $this->model->id_currency(),
                    "id_stage"          => '100',

                    "name"              => $post['data']['opportunity'],
                    "expecting_closing_date"  => isset($post['data']['expecting_closing_date']['year']) ?   $post['data']['expecting_closing_date']['year'] . '-' . $post['data']['expecting_closing_date']['month'] . '-' . $post['data']['expecting_closing_date']['day'] : date('Y-m-d'),

                    "amount"            => isset($post['data']['amount']) ? $post['data']['amount'] : 0,
                    "id_lead_source"      =>   $this->model->select('id_lead_source', 'crm3_lead', 'id=' . $id_lead),
                    "start_date"  => date('Y-m-d'),
                    "input_date" => date('Y-m-d H:i:s'),
                    "update_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                );
                $this->db->insert("crm3_opportunity", $insert);
                $last_query =  $last_query . ';' . $this->db->last_query();


                /**
                 * PROUDUCT COPY
                 */
                $query = $this->db->query('select * from crm3_lead_product where id_lead = "' . $id_lead . '" and presence = 1 order by input_date desc');
                foreach ($query->result() as $row) {
                    $temp = array(
                        "id_opportunity" => $id_opportunity,
                        "id_product" => $row->id_product,
                        "discount"  => $row->discount,
                        "price"  => $row->price,
                        "qty"  => (int) $row->qty,
                        "total"  => $row->total,
                        "input_date" => $row->input_date,
                        "input_by" => $this->model->user($row->input_by),
                        "update_date" => $row->update_date,
                        "update_by" =>  $this->model->user($row->update_by),
                    );
                    $this->db->insert('crm3_opportunity_product', $temp);
                }
            }

            $id_lead_convert = $this->model->number("lead_convert");
            $insert = array(
                "id"                => $id_lead_convert,
                "id_user"           => $post['data']['id_user'],
                "id_lead"           => $id_lead,
                "id_contact"        => $id_contact,
                "id_company"        => $id_company,
                "id_opportunity"    => $id_opportunity,
                "convert_date"   => date('Y-m-d H:i:s'),
                "convert_by"     => $post['data']['id_user'],

                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
            );
            $this->db->insert("crm3_lead_convert", $insert);


            /**
             * Update ACTIVITY  ( with or without OPPORTUNITY)
             */
            $query = $this->db->query('select * from crm3_activity where id_person = "' . $id_lead . '" and id_module = 200 and presence = 1 order by input_date desc');
            foreach ($query->result() as $row) {
               // 500 = opportunity
              //  400 = companye

                $temp = array(
                    "id_person" =>  $id_contact,
                    "id_module"  =>  $post['data']['opportunity'] ? '500' : '400',
                    "id_company"  => $id_company,
                    "id_opportunity"  => $post['data']['opportunity'] ? $id_opportunity : 0, 

                    "update_date" => $row->update_date,
                    "update_by" =>  $this->model->user($row->update_by),
                );
                $this->db->update('crm3_activity', $temp, 'id = '.$row->id);
            //    $this->db->insert('crm3_activity', $temp);
            }


            /**
             * Update CRM3_ATTACHMENT
             */ 
            $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "lead" and id_module = "' . $post['id'] . '" order by input_date ASC');
            foreach ($query->result() as $row) {
                $attchData = array(
                    "module" => $post['data']['opportunity'] ? 'opportunity' : 'company',
                    "id_module" => $post['data']['opportunity'] ? $id_opportunity :  $id_company,
                );
                $this->db->update('crm3_attachment', $attchData, 'id = '.$row->id);
            }


            $this->db->trans_complete();
        }

        if ($this->db->trans_status() === FALSE) {
            $error = 666;
        }

        $json = array(
            "error"  => $error,
            "result" => array(
                "query" => $last_query,
                "id_lead_convert" => $id_lead_convert,
                "note"  => "convert done",
            ),
        );
        echo json_encode($json);
    }

    public function converted($id = "")
    {
        $data = [];

        $query = $this->db->query('select * from crm3_lead_convert  where id_lead = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id"                => $row->id,
                "user" => array(
                    "id"           => $row->id_user,
                    "name"           =>  $this->model->user($row->id_user),
                ),
                "lead" => array(
                    "id"           => $row->id_lead,
                    "name"           => $this->model->lead($row->id_lead),
                    "description" => "",
                ),
                "company" => array(
                    "id"        => $row->id_company,
                    "name"        => $this->model->company($row->id_company),
                    "description" => "",
                ),
                "contact" => array(
                    "id"        => $row->id_contact,
                    "description" => "",
                    "name"        => $this->model->contact($row->id_contact),
                ),
                "opportunity" => array(
                    "id"        => $row->id_opportunity,
                    "name"        => $this->model->opportunity($row->id_opportunity),
                    "description" => "",
                ),

                "convert" => array(
                    "date" => $row->convert_date,
                    "id_user" => $row->convert_by,
                    "user" =>  $this->model->user($row->convert_by),
                ),

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
