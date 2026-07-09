<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Converted extends CI_Controller
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

    public function index()
    {

        $data = [];
        $i = 0;
        $q =  'select * from crm3_lead_convert where 1 '.$this->model->user_group('id_user')['query'].' order by input_date';
        $query = $this->db->query( $q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id"        => $row->id,
                "id_lead"        => $row->id_lead,
                "user"      => $this->model->user($row->id_user),
                "lead"      => $this->model->lead($row->id_lead),
                "company"   => $this->model->company($row->id_company),
                "contact"   => $this->model->contact($row->id_contact),
                "opportunity"   => $this->model->crm3('name','opportunity','id='.$row->id_opportunity),
                "convert_date"   =>  $row->convert_date,
                "convert_by"   => $this->model->user($row->convert_by), 

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
                "id_user" => $this->model->id_user(),
                "data" => $data,
                "total" => (int) $this->model->select('count(id)', 'crm3_lead', 'presence = 1  '. $this->model->user_group('id_user')['query']),
                "total_view" => $i,
                "pages" => 1,

            ),
        );

        echo json_encode($json);
    }

    public function detail($id = 0)
    {

        $id_user = $this->model->crm3('id_user','lead','id="'.$id.'"');

        if( !$this->model->allow_access_data( $id_user) ){
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

                "id_lead_status" => $row->id_lead_status,
                "id_title" => $row->id_title,
                "id_lead_source" => $row->id_lead_source,
                "first_name"      => $row->first_name,
                "last_name"      =>   $row->last_name,

                "department"   => $row->department,
                "position"   => $row->position,


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
                    "year"  => (int)date('Y',strtotime( $row->birthdate)),
                    "month" => (int)date('m',strtotime( $row->birthdate)),
                    "day"   => (int)date('d',strtotime( $row->birthdate)), 
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
        $query = $this->db->query('select * from crm3_user where presence = 1   '.$this->model->user_group()['query'].'  order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }

        $company = [];
        $query = $this->db->query('select * from crm3_company where presence = 1 order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $row->name,
            );
            array_push($company, $temp);
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
                "industry"  => $industry,
                "opportunity_stage"  => $opportunity_stage,
                "user"  => $user,
                "company" => $company,
                "lead_status" => $lead_status,
                "allow_access_data" =>  $this->model->allow_access_data( $id_user)
            ),
        );

        echo json_encode($json);
    }
 
}
