<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Contact extends CI_Controller
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

    function select()
    {

        $companies = [];
        $query = $this->db->query('select * from crm3_company where presence = 1   order by name ASC limit 5000');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"   => $row->id,
                "name" => $row->name,
            );
            array_push($companies, $temp);
        }


        $json = array(
            "error"  =>  0,
            "result" => array(
                "companies" => $companies,
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
    SELECT c.id, CONCAT(c.first_name,' ',c.last_name) AS 'name',
    c.position, c.department,
    IFNULL(com.name,'')  AS 'company', c.email, c.phone, c.mobile,
    IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user'

    FROM crm3_contact AS c
    LEFT JOIN crm3_user AS u ON u.id = c.id_user
    LEFT JOIN crm3_company AS com ON com.id = c.id_company
    LEFT JOIN crm3_title AS t ON t.id = c.id_title
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
            array('db' => 'name',  'dt' => 1),
            array('db' => 'company',  'dt' => 2),

            array('db' => 'position',  'dt' => 3),
            array('db' => 'department',  'dt' => 4), 
            array('db' => 'user',  'dt' => 5),
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

        $id_user = $this->model->crm3('id_user', 'contact', 'id="' . $id . '" '.$this->model->user_group('id_user')['query']);
        
        if (!$this->model->allow_access_data($id_user) || !$id_user) {
            echo $this->model->error("Access data not allow");
            exit;
        }

        $query = $this->db->query('select * from crm3_contact where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id" => $row->id,
                "id_company" => $row->id_company,
                "name" => $row->first_name . ' ' . $row->last_name . ', ' . $this->model->title($row->id_title),

                "id_title" => $row->id_title,
                "first_name" => $row->first_name,
                "last_name" => $row->last_name,
                "position" => $row->position,
                "department" => $row->department,
                "sex" => $row->sex,
                "birthdate" => array(
                    "year"  => (int) date('Y', strtotime($row->birthdate)),
                    "month" => (int) date('m', strtotime($row->birthdate)),
                    "day"   => (int) date('d', strtotime($row->birthdate)),
                ),

                "email" => $row->email,
                "phone" => $row->phone,
                "mobile" => $row->mobile,
                "id_lead_source" => $row->id_lead_source,
                "id_user" =>  $row->id_user,

                "user" =>   $this->model->user($row->id_user),
                "company" => $this->model->company($row->id_company),
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
        }

        $opportunity = [];
        $qo = 'select * from crm3_opportunity where presence = 1 and id_contact = ' . $id . ' ' .$this->model->user_group('id_user')['query'].'  order by name ASC';
        $query = $this->db->query( $qo );
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name"  => $row->name,
                "stage" => $this->model->select('name', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "progress" => $this->model->select('progress', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "currency" => $this->model->currency(),
                "amount" => (int) $row->amount,
                "company" => $this->model->company($row->id_company),
                "contact" => $this->model->contact($row->id_contact),

                "user" => $this->model->user($row->id_user),

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
            array_push($opportunity, $temp);
        }


        $title = [];
        $query = $this->db->query('select * from crm3_title where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name"  => $row->name,
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


        $company = [];
        $query = $this->db->query('select * from crm3_company where presence = 1   order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company, $temp);
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1  ' .$this->model->user_group()['query'].'   order by first_name ASC, last_name ASC');
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


        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "contact" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => str_replace('./', base_url(), $row->url),
                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }

        $json = array(
            "error"  =>  0,
            
            "result" => array(
                "data" => $data,
                "company" => $company,
                "lead_source" => $lead_source,
                "title" => $title,
                "user"  => $user,
                "stage"  => $stage,
                "attachment" => $attachment,
                "opportunity" => $opportunity,
                "allow_access_data" =>  $this->model->allow_access_data($id_user)
            ),
        );

        echo json_encode($json);
    }

    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $id = $this->model->number("contact");
            $insert = array(
                "id" => $id,
                "id_company"      => $post['id_company'],
                "first_name"      => ucwords($post['first_name']),
                "last_name" => isset($post['last_name']) ?  ucwords($post['last_name']) : "",
                "id_title" =>  isset($post['id_title']) ?  $post['id_title'] : "0",
                "email" =>  isset($post['email']) ?  $post['email'] : "",
                "mobile" =>  isset($post['mobile']) ?  $post['mobile'] : "",
                "sex" =>  isset($post['sex']) ?  $post['sex'] : "",

                "position" =>  isset($post['position']) ?  $post['position'] : "",
                "department" =>  isset($post['department']) ?  $post['department'] : "",
                "id_lead_source" =>  isset($post['id_lead_source']) ?  $post['id_lead_source'] : "",
                "phone" =>  isset($post['phone']) ?  $post['phone'] : "",
                "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_contact", $insert);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id" => $id,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert Contact done",
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

                "id_title"     => isset($post['data']['id_title']) ? $post['data']['id_title'] : "0",
                "position"     => isset($post['data']['position']) ? $post['data']['position'] : "",
                "department"     => isset($post['data']['department']) ? $post['data']['department'] : "",

                "id_lead_source"     => isset($post['data']['id_lead_source']) ? $post['data']['id_lead_source'] : "0",
                "first_name"     => isset($post['data']['first_name']) ? ucwords($post['data']['first_name']) : "",
                "last_name"     => isset($post['data']['last_name']) ? ucwords($post['data']['last_name']) : "",
                "sex" =>  isset($post['data']['sex']) ? $post['data']['sex'] : "",
                "birthdate" => $post['data']['birthdate']['year'] . '-' . $post['data']['birthdate']['month'] . '-' . $post['data']['birthdate']['day'],

                "email"    => isset($post['data']['email']) ? $post['data']['email'] : "",
                "mobile"      => isset($post['data']['mobile']) ? $post['data']['mobile'] : "",
                "phone" => isset($post['data']['phone']) ? $post['data']['phone'] : "",

                "id_company" => isset($post['data']['id_company']) ? $post['data']['id_company'] : "0",
                "id_user" => isset($post['data']['id_user']) ? $post['data']['id_user'] : $this->model->id_user(),

                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_contact", $update, 'id=' . $post['id']);

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "note"  => "Update done",
                    "post" => $post,
                ),
            );
            echo json_encode($json);
        }
    }

    public function selected()
    {

        $company = [];
        $query = $this->db->query('select * from crm3_company where presence = 1    order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company, $temp);
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1  '.$this->model->user_group()['query'].'   order by first_name ASC, last_name ASC');
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

    public function widget_new_contact($module = "", $id = 0)
    {

        $data = [];

        if ($module == 'contact') {

            $company = [];
            $query = $this->db->query('select * from crm3_company where presence = 1  order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }

            $data = array(
                "id_company" => 0,
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

            $data = array(
                "id_company" => $id_company,
                "id_user"   => $this->model->id_user(),
            );
        } else if ($module == 'opportunity') {

            $id_company =  $this->model->crm3('id_company', 'opportunity', 'id="' . $id . '"');
            $company = [];
            $query = $this->db->query('select * from crm3_company where id = ' . $id_company . ' and presence = 1 order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }

            $data = array(
                "id_company" => $id_company,
                "id_user"   => $this->model->id_user(),
            );
        } else {
            $company = [];
            $query = $this->db->query('select * from crm3_company where presence = 1   order by name ASC');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $row->name,
                );
                array_push($company, $temp);
            }
        }

        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1 ' . $this->model->user_group()['query'] . '  order by first_name ASC, last_name ASC');
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
                "data"  => $data,
                "company" => $company,
                "user"  => $user,
                "title"  => $title,
                "lead_source" => $lead_source,
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

    public function edit($id = 0)
    {

        $query = $this->db->query('select * from crm3_contact where presence = 1 and id = "' . $id . '"');
        foreach ($query->result() as $row) {

            $data = array(
                "id"        => $row->id,
                "id_title" => $row->id_title,
                "id_lead_source" => $row->id_lead_source,
                "id_company" => $row->id_company,

                "first_name" => $row->first_name,
                "last_name" => $row->last_name,
                "name" => $row->first_name . ' ' . $row->last_name . ', ' . $this->model->title($row->id_title),

                "email" => $row->email,
                "mobile" => $row->mobile,
                "phone" => $row->phone,
                "id_user" => $row->id_user,

                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),

            );
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
                "data" => $data,
                "company"  => $company,
                "title"  => $title,
                "lead_source" => $lead_source,
                "user"  => $user,
            ),
        );

        echo json_encode($json);
    }
}
