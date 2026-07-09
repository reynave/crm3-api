<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Company extends CI_Controller
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

        if (!$this->model->header($this->openAPI) ) { 
            echo $this->model->error("Error auth"); 
            exit;
        }
    }

    public function indexDel()
    {

        $data = [];
        $i = 0;
        $query = $this->db->query('select * from crm3_company where presence = 1    order by name ASC limit 50');
        foreach ($query->result() as $row) {
            $temp = array(
                "id"        => $row->id,
                "name" => $row->name,
                "website" => $row->website,
                "phone" => $row->phone,
                "email" => $row->email,
                "fax" => $row->fax,
                "industry" => $this->model->industry($row->id_industry),
                "address" => $row->bill_street1 . ' ' . $row->bill_city . ' ' . $row->bill_state . ' ' . $row->bill_code,
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
                "total" => (int) $this->model->select('count(id)', 'crm3_company', 'presence = 1'),
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

        $table = <<<EOT
 (
    SELECT c.*, 
    IFNULL(i.name,'undefined')  AS 'industry', 
    IFNULL(cc.name,'undefined')  AS 'customer_class',
    IFNULL(concat(u.first_name,' ',u.last_name),'unsigned')  AS 'user'
    FROM crm3_company AS c
    LEFT  JOIN  crm3_industry AS i ON i.id = c.id_industry
    LEFT  JOIN crm3_company_class AS cc ON cc.id = c.id_company_class
    LEFT JOIN crm3_user AS u ON u.id = c.id_user
    WHERE c.presence = 1 
     
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
            array('db' => 'website',  'dt' => 2), 
            array('db' => 'code_number',  'dt' => 3), 
            array('db' => 'industry',  'dt' => 4), 
            array('db' => 'customer_class',  'dt' => 5), 
            array('db' => 'user',  'dt' => 6), 

           

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

        $id_user = $this->model->crm3('id_user','company','id="'.$id.'"');
 

        $data = [];
        $query = $this->db->query('select * from crm3_company where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id"        => $row->id,
                "id_company_class" => $row->id_company_class,
                "company_class" => $this->model->select('name','crm3_company_class','id = '.$row->id_company_class),
                "code_number" => $row->code_number,
                "name" => $row->name,
                "website" => $row->website,
                "phone" => $row->phone,
                "email" => $row->email, 
                "fax" => $row->fax,
                "industry" => $this->model->industry($row->id_industry),
                "id_industry" => $row->id_industry,
                "id_user" => $row->id_user,
                
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
                


                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
        }

        $contact = [];
        $query = $this->db->query('select * from crm3_contact where presence = 1 and id_company = '.$id.'  '.$this->model->user_group('id_user')['query'].'   order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name.' '.$row->last_name.', '.$this->model->title($row->id_title),
                "position" => $row->position,
                "department" => $row->department,
                
                "email" => $row->email,
                "department" => $row->department,
                "mobile" => $row->mobile,
                "user" => $this->model->user($row->id_user),

            );
            array_push($contact, $temp);
        }

        $branch = [];
        $query = $this->db->query('select * from crm3_branch where presence = 1 and id_company = '.$id.' order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "email" => $row->email,
                "phone" => $row->phone,
                "fax" => $row->fax,
                "address" => $row->bill_street1.' '.$row->bill_city.' '.$row->bill_state.' '.$row->bill_code,
                "code" => $row->code,
                "status" => $this->model->select('name','crm3_branch_status','id='.$row->id_branch_status),
            );
            array_push($branch, $temp);
        }

        $opportunity = [];
        $query = $this->db->query('select * from crm3_opportunity where presence = 1 and id_company = '.$id.' '.$this->model->user_group('id_user')['query'].' and closed = 0 order by name ASC');
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

        $deal = [];
        $query = $this->db->query('select * from crm3_opportunity where presence = 1 and id_company = '.$id.' '.$this->model->user_group('id_user')['query'].' and id_stage = "1000" and closed = 1 order by name ASC');
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
            );
            array_push($deal, $temp);
        }

        $archived = [];
        $query = $this->db->query('select * from crm3_opportunity where presence = 1 and id_company = '.$id.' '.$this->model->user_group('id_user')['query'].' and id_stage = "3000" and closed = 1 order by name ASC');
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
            );
            array_push($archived, $temp);
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


        $user = [];
        $query = $this->db->query('select * from crm3_user where presence = 1 '.$this->model->user_group()['query'].'   order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
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

        $priceList = [];
        $query = $this->db->query('select * from crm3_product where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
           
            $customer_price = $this->model->crm3('price','company_class_price_list','id_company_class = "'.$data['id_company_class'].'" and id_product = "'.$row->id.'" ');
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
                "sku" => $row->sku,
                "product_type" => $this->model->crm3('name','product_type','id='.$row->id_product_type),
                "price" =>  $customer_price > 0 ? $customer_price :$row->price,
                "customer_price" => $customer_price > 0 ? true : false,
                "currency" => $this->model->currency(),
                
            );
            array_push($priceList, $temp);
        }

        $purchased = [];
       
        
        $attachment = [];
        $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "company" and id_module = "' . $id . '" order by input_date ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "file_name" => $row->file_name,
                "url" => str_replace('./',base_url(), $row->url) , 
                "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',
            );
            array_push($attachment, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "contact" => $contact,
                "attachment" => $attachment,
                "industry" => $industry,
                "company_class" => $company_class,
                "user" => $user, 
                "opportunity" => $opportunity,
                "deal" => $deal,
                "archived" => $archived,
                "branch" => $branch,
                "priceList" => $priceList,
                "purchased" => $purchased,
                "allow_access_data" => $this->model->allow_access_data( $id_user),
            ),
        );

        echo json_encode($json);
    }
   
    public function insert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $code_number = $this->model->number("company");
            $insert = array(
                "code_number" => $code_number,
                "name"      => ucwords($post['name']),
                "website" => isset($post['website']) ?  $post['website'] : "",
                "phone" =>  isset($post['phone']) ?  $post['phone'] : "",
                "fax" =>  isset($post['fax']) ?  $post['fax'] : "",
                "email" =>  isset($post['email']) ?  $post['email'] : "",

                "id_industry" =>  isset($post['id_industry']) ?  $post['id_industry'] : "",
                "id_company_class" =>  isset($post['id_company_class']) ?  $post['id_company_class'] : "",
                "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : "",

                "bill_street1" =>  isset($post['bill_street']) ?  $post['bill_street'] : "",
                "bill_city" =>  isset($post['bill_city']) ?  $post['bill_city'] : "",
                "bill_state" =>  isset($post['bill_state']) ?  $post['bill_state'] : "",
                "bill_code" => isset($post['bill_code']) ?  $post['bill_code'] : "",
                "bill_country" => isset($post['bill_country']) ?  $post['bill_country'] : "",

                "ship_street1" =>  isset($post['ship_street']) ?  $post['ship_street'] : "",
                "ship_city" =>  isset($post['ship_city']) ?  $post['ship_city'] : "",
                "ship_state" =>  isset($post['ship_state']) ?  $post['ship_state'] : "",
                "ship_code" => isset($post['ship_code']) ?  $post['ship_code'] : "",
                "ship_country" => isset($post['ship_country']) ?  $post['ship_country'] : "",


                "input_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),

            );
            $this->db->insert("crm3_company", $insert);
            $id = $this->model->crm3('id','company','code_number = "'.$code_number.'" ');
           
            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id" => $id,
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
                "id_company_class"     => isset($post['data']['id_company_class']) ? $post['data']['id_company_class'] : "",
                "name"     => isset($post['data']['name']) ? ucwords($post['data']['name']) : "",
                "email"    => isset($post['data']['email']) ? $post['data']['email'] : "",
                "fax"      => isset($post['data']['fax']) ? $post['data']['fax'] : "",
                "website"  => isset($post['data']['website']) ? $post['data']['website'] : "",
                "code_number"  => isset($post['data']['code_number']) ? $post['data']['code_number'] : "",

                "id_user"  => isset($post['data']['id_user']) ? $post['data']['id_user'] : "",

                "bill_street1" => isset($post['data']['bill_street1']) ? $post['data']['bill_street1'] : "",
                "bill_street2" => isset($post['data']['bill_street1']) ? $post['data']['bill_street1'] : "",
                "bill_city" => isset($post['data']['bill_city']) ? $post['data']['bill_city']: "",
                "bill_state" => isset($post['data']['bill_state']) ? $post['data']['bill_state'] : "",
                "bill_code" => isset($post['data']['bill_code']) ? $post['data']['bill_code'] : "",
                "bill_country" => isset($post['data']['bill_country']) ? $post['data']['bill_country'] : "",

                "ship_street1" => isset($post['data']['ship_street1']) ? $post['data']['ship_street1'] : "",
                "ship_street2" => isset($post['data']['ship_street2']) ? $post['data']['ship_street2'] : "",
                "ship_city" => isset($post['data']['ship_city']) ? $post['data']['ship_city']: "",
                "ship_state" => isset($post['data']['ship_state']) ? $post['data']['ship_state'] : "",
                "ship_code" => isset($post['data']['ship_code']) ? $post['data']['ship_code'] : "",
                "ship_country" => isset($post['data']['ship_country']) ? $post['data']['ship_country'] : "", 
                
                "phone" => isset($post['data']['phone']) ? $post['data']['phone'] : "",
                "id_industry" => isset($post['data']['id_industry']) ? $post['data']['id_industry'] : "0",
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_company", $update, 'id=' . $post['id']);

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
       
        $industry = [];
        $query = $this->db->query('select * from crm3_industry where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($industry, $temp);
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
                "name" => $row->name ,
            );
            array_push($title, $temp);
        }

        $lead_source = [];
        $query = $this->db->query('select * from crm3_lead_source where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name ,
            );
            array_push($lead_source, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "industry"  => $industry,
                "title"  => $title,
                "lead_source" => $lead_source,
                "id_user"  => $this->model->id_user(),
                "user"  => $user,
                "company_class" => $company_class,
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
                $this->db->update("crm3_company", $update, 'id=' .  $row['id']);
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
            $this->db->update("crm3_company", $update, 'id=' .  $post['id']);


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
      
        $query = $this->db->query('select * from crm3_company where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
      
            $data = array(
                "id"        => $row->id,
                "name" => $row->name,
                "website" => $row->website,
                "phone" => $row->phone,
                "email" => $row->email,
                "fax" => $row->fax,
                "id_industry" => $row->id_industry,

                "bill_street1" => $row->bill_street1,
                "bill_city" => $row->bill_city,
                "bill_state" =>  $row->bill_state,
                "bill_code" => $row->bill_code,
                
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
