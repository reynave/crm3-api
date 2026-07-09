<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Output extends CI_Controller
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
    }


    function requestToken()
    {
        $data = array(
            "error" => 0,
            "result" => array(
                "token" => md5('masihtesting'),
            ),
        );
        echo json_encode($data);
    }

    public function quote($id = 0)
    {

        $version = 0;

        $data = array(
            "discount" => 0,
            "tax" => 0,
            "shipping" => 0,
        );
        $query = $this->db->query('select * from crm3_quote where presence = 1 and id = "' . $id . '" order by input_date DESC ');
        foreach ($query->result() as $row) {
            $id_company =  $this->model->select('id_company', 'crm3_opportunity', 'id=' . $row->id_opportunity);
            $id_opportunity = $row->id_opportunity;
            $version =  $row->version;
            $data = array(
                "id" => $row->id,

                "status" => array(
                    "id"    =>  $row->id_quote_status,
                    "note"  =>  $this->model->crm3('name', 'quote_status', 'id=' . $row->id_quote_status),
                ),
                "id_opportunity" => $row->id_opportunity,
                "version" => $row->version,

                "opportunity" => $this->model->select('name', 'crm3_opportunity', 'id=' . $row->id_opportunity),

                "company" => $this->model->select('name', 'crm3_company', 'id=' . $id_company),
                "contact" => array(
                    "id" => $row->id_contact,
                    "name" => $this->model->contact($row->id_contact),
                ),
                "expired_date" => $row->expired_date,
                "currency" => $this->model->currency(),
                "id_user" => $row->id_user,
                "quotes_number" => $row->quotes_number,
                "name"    => $row->name,
               
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



        $pricing =  $this->model->calculation($id);


        $json = array(
            "data" => $data,
            "quote_item" => $quote_item,
            "pricing" => $pricing['pricing'],

        );

        if ($this->input->get('json') == true) {
            header('Content-Type: application/javascript');
            echo json_encode($json);
        } else {
           $version  =  $version + 1;

            $update= array(
                "version" => $version,
            );
            $this->db->update('crm3_quote',$update,'id='.$id);

           $file = './upload/quote/'.url_title( strtolower( $data['quotes_number'])).'-'. $version .".html";
           fopen( $file, "w"); 
           $current  = $this->load->view('output/quote', $json,TRUE); 
           file_put_contents($file, $current); 

           
            $insert = array( 
                "full_path" => $file,
                "module" => "quote",
                "id_module"=> $id,
                "version"  =>  $version, 
                "file_name" => url_title( strtolower( $data['quotes_number'])),
                
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
            $this->db->insert('crm3_output',$insert);

            $json = array(
                "error" => 0,
                "url" =>  str_replace('./',base_url(),$file), 
            );
            echo json_encode($json);
        }
    }
}
