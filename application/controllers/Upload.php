<?php

defined('BASEPATH') or exit('No direct script access allowed');



class Upload extends CI_Controller

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



        if (!$this->input->post('token')) {

            echo $this->model->error("Error auth");

            exit;

        }

    }



    public function index()

    {

    }



    public function quotes_attachment($id = 0)

    {



        if ($this->model->crm3('id_user', 'user_auth', 'token="' . $this->input->post('token') . '"')) {

            $id_user = $this->model->crm3('id_user', 'user_auth', 'token="' . $this->input->post('token') . '"');



            $folder = $this->model->crm3('folder', 'user', 'id="' . $id_user . '" ');



            $config['upload_path']          = './../api-v1/upload' . $folder;;

            $config['allowed_types']        = '*';

            $config['max_size']             = 10240;



            $this->model->folder($config['upload_path']);





            $this->load->library('upload', $config);



            if (!$this->upload->do_upload('files')) {

                $data = array('error' => strip_tags($this->upload->display_errors()));

            } else {

                $data = array(

                    'upload_data' => $this->upload->data(),

                );

                $insert = array(



                    "file_name"     => $data['upload_data']['file_name'],

                    "full_path"    =>  $data['upload_data']['full_path'],

                    "url"    =>  $config['upload_path'] . '/' . $data['upload_data']['file_name'],



                    "file_size" => $data['upload_data']['file_size'],



                    "module"     => 'quote',

                    "id_module"   => $this->input->post('id_module'),



                    "input_date" => date('Y-m-d H:i:s'),

                    "input_by" => $id_user,

                    "update_date" => date('Y-m-d H:i:s'),

                );

                $this->db->insert('crm3_attachment', $insert);

                $id = $this->input->post('id_module');

                $attachment = []; 

                $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "quote" and id_module = "' . $id . '" order by input_date ASC');

                foreach ($query->result() as $row) {

                    $temp = array(

                        "id" => $row->id,

                        "file_name" => $row->file_name,

                        "url" => $row->url,

                        "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',

                    );

                    array_push($attachment, $temp);

                }





                $data = array(

                    'error' => 0,

                    "result" => array(

                        "attachment" => $attachment,

                        'upload_data' => $this->upload->data(),

                        'update' => $this->db->last_query(),

                    )



                );

            }

        }

        echo json_encode($data);

    }



    public function priceList_attachment($id = 0)

    {



        if ($this->model->crm3('id_user', 'user_auth', 'token="' . $this->input->post('token') . '"')) {

            $id_user = $this->model->crm3('id_user', 'user_auth', 'token="' . $this->input->post('token') . '"');



            $folder = $this->model->crm3('folder', 'user', 'id="' . $id_user . '" ');



            $config['upload_path']          = './upload/' . $folder;

            $config['allowed_types']        = '*';

            $config['max_size']             = 10240;



            $this->model->folder($config['upload_path']);





            $this->load->library('upload', $config);



            if (!$this->upload->do_upload('files')) {

                $data = array('error' => strip_tags($this->upload->display_errors()));

            } else {

                $data = array(

                    'upload_data' => $this->upload->data(),

                );

                $insert = array(



                    "file_name"     => $data['upload_data']['file_name'],

                    "full_path"    =>  $data['upload_data']['full_path'],

                    "url"    =>  $config['upload_path'] . '/' . $data['upload_data']['file_name'],



                    "file_size" => $data['upload_data']['file_size'],



                    "module"     => 'priceList',

                    "id_module"   => $this->input->post('id_module'),



                    "input_date" => date('Y-m-d H:i:s'),

                    "input_by" => $id_user,

                    "update_date" => date('Y-m-d H:i:s'),

                );

                $this->db->insert('crm3_attachment', $insert);

                $id = $this->input->post('id_module');

                $attachment = []; 

                $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "priceList" and id_module = "' . $id . '" order by input_date ASC');

                foreach ($query->result() as $row) {

                    $temp = array(

                        "id" => $row->id,

                        "file_name" => $row->file_name,

                        "url" => $row->url,

                        "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',

                    );

                    array_push($attachment, $temp);

                }





                $data = array(

                    'error' => 0,

                    "result" => array(

                        "attachment" => $attachment,

                        'upload_data' => $this->upload->data(),

                        'update' => $this->db->last_query(),

                    )



                );

            }

        }

        echo json_encode($data);

    }





    public function  attachment()

    {



        if ($this->model->crm3('id_user', 'user_auth', 'token="' . $this->input->post('token') . '"')) {

            $id_user = $this->model->crm3('id_user', 'user_auth', 'token="' . $this->input->post('token') . '"');



            $folder = $this->model->crm3('folder', 'user', 'id="' . $id_user . '" ');



            $config['upload_path']          = './upload/' . $folder;

            $config['allowed_types']        = '*';

            $config['max_size']             = 10240;



            $this->model->folder($config['upload_path']);





            $this->load->library('upload', $config);



            if (!$this->upload->do_upload('files')) {

                $data = array('error' => strip_tags($this->upload->display_errors()));

            } else {

                $data = array(

                    'upload_data' => $this->upload->data(),

                );

                $insert = array(



                    "file_name"     => $data['upload_data']['file_name'],

                    "full_path"    =>  $data['upload_data']['full_path'],

                    "url"    =>  $config['upload_path'] . '/' . $data['upload_data']['file_name'],



                    "file_size" => $data['upload_data']['file_size'],



                    "module"     => $this->input->post('module'),

                    "id_module"   => $this->input->post('id'),



                    "input_date" => date('Y-m-d H:i:s'),

                    "input_by" => $id_user,

                    "update_date" => date('Y-m-d H:i:s'),

                );

                $this->db->insert('crm3_attachment', $insert);

                $id = $this->input->post('id_module');

                $attachment = []; 

                $query = $this->db->query('select * from crm3_attachment where presence = 1 and module = "'.$this->input->post('module').'" and id_module = "' . $id . '" order by input_date ASC');

                foreach ($query->result() as $row) {

                    $temp = array(

                        "id" => $row->id,

                        "file_name" => $row->file_name,

                        "url" => $row->url,

                        "size" => (float) $row->file_size / 1024 < 0 ? ((float) $row->file_size / 1024) . ' Mb' : $row->file_size . ' Kb',

                    );

                    array_push($attachment, $temp);

                }





                $data = array(

                    'error' => 0,

                    "result" => array(

                        "attachment" => $attachment,

                        'upload_data' => $this->upload->data(),

                        'update' => $this->db->last_query(),

                    )



                );

            }

        }

        echo json_encode($data);

    }

}

