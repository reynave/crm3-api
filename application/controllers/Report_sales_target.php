<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_sales_target extends CI_Controller
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
        
        $json = array(
            "error"  =>  0,
            "result" => array( 
                "year" => date('Y'),
                "data" => $this->model->user_recursive_target("","",date('Y')),
            ),
        );

        echo json_encode($json);
    }
       
}
