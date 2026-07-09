<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Activity extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->key = $this->db->key;
        $this->openAPI = $this->db->openAPI;
        header('Access-Control-Allow-Origin: *');
        //header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Access-Control-Allow-Headers: key, token,  Content-Type");

        header('Access-Control-Allow-Methods: GET, POST');
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

    function timenow()
    {
        echo date('d M Y H:i');
    }

    function index()
    {

        $user = [];
        $q = 'select * from crm3_user  where presence = 1 ' . $this->model->user_group()['query'] . ' and status = 1';
        $query = $this->db->query($q);
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
                "user" => $user,
            ),
        );

        echo json_encode($json);
    }

    function httpGet($module = "", $id = "")
    {

        $user = [];
        $person = [];
        $id_opporunty = 0;
        $id_company = 0;
        $query = $this->db->query('select * from crm3_user where presence = 1  ' . $this->model->user_group()['query'] . '  and status = 1 order by first_name ASC, last_name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" =>  $row->id,
                "name" => $row->first_name . ' ' . $row->last_name,
            );
            array_push($user, $temp);
        }


        if ($module == 'lead') {
            if ($id) {
                $temp = array(
                    "id" =>  $this->model->crm3('id', $module, 'id=' . $id),
                    "name" =>  $this->model->crm3('concat(first_name," ",last_name)', $module, 'id=' . $id),
                );
                array_push($person, $temp);
            } else {
                $person = [];
                $query = $this->db->query('select * from crm3_lead where presence = 1 and status = 1 order by first_name ASC, last_name ASC limit 200');
                foreach ($query->result() as $row) {
                    $temp = array(
                        "id" =>  $row->id,
                        "name" => $row->first_name . ' ' . $row->last_name,
                    );
                    array_push($person, $temp);
                }
            }
            $id_module = 200;
        } else if ($module == 'contact') {
            if ($id) {
                $temp = array(
                    "id" =>  $this->model->crm3('id', $module, 'id=' . $id),
                    "name" =>  $this->model->crm3('concat(first_name," ",last_name)', $module, 'id=' . $id),
                );
                array_push($person, $temp);
            } else {
                $person = [];
                $query = $this->db->query('select * from crm3_contact where presence = 1 and status = 1 order by first_name ASC, last_name ASC limit 200');
                foreach ($query->result() as $row) {
                    $temp = array(
                        "id" =>  $row->id,
                        "name" => $this->model->contact($row->id),
                    );
                    array_push($person, $temp);
                }
            }
            $id_module = 300;
        } else if ($module == 'company') {
            $person = [];

            $temp =  array(
                "id" => "0",
                "name" => "- None -",
            );
            array_push($person, $temp);

            $id_company =  $id;
            $query = $this->db->query('select * from crm3_contact where presence = 1 and status = 1 and id_company = "' . $id . '"  order by first_name ASC, last_name ASC limit 200');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" =>  $row->id,
                    "name" => $this->model->contact($row->id),
                );
                array_push($person, $temp);
            }
            $id_module = 400;
        } else if ($module == 'opportunity') {
            $person = [];
            $id_opporunty = $id;
            $id_company = $this->model->crm3('id_company', 'opportunity', 'id="' . $id . '" ');
            $query = $this->db->query('select * from crm3_contact where presence = 1 and status = 1 and id_company = "' . $id_company . '"  order by first_name ASC, last_name ASC limit 200');
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" =>  $row->id,
                    "name" => $this->model->contact($row->id),
                );
                array_push($person, $temp);
            }
            $id_module = 500;
        } else if ($module == 'activity') {
            $id_module = 600;
            $temp = array(
                "id" =>  "0",
                "name" =>  "",
            );
            array_push($person, $temp);
        }


        $json = array(
            "error" => 0,
            "result" => array(
                "data" => array(
                    "id_user" => $this->model->id_user(),
                    "person" =>  $person,
                    "id_module" => $id_module,
                    "id_company" => $id_company,
                    "id_opporunty" => $id_opporunty,

                ),
                "user" => $user,
                "id_user" => $this->model->id_user(),
            )
        );

        echo json_encode($json);
    }

    function httpHistory($module = "", $id = "")
    {

        $id_module = $this->model->crm3('id', 'module', 'name="' . $module . '"');
        $latest = [];
        $filter = [];
        $history = [];
        $limit = 100;
        if ($id_module == '400') {
            // COMPANY
            $q = 'select * from crm3_activity where  presence = 1 and status = 1 and id_company = "' . $id . '" order by input_date DESC';
        } else if ($id_module == '500') {
            // OPPORTUNITY
            $q = 'select * from crm3_activity where  presence = 1 and status = 1 and id_opportunity = "' . $id . '" order by input_date DESC limit ' . $limit;
        } else if ($id_module == '600') {
            // ACTIVITY
            $filter = json_decode(base64_decode($this->input->get('f')), true);

            if ($filter == null) {
                $q = 'select * from crm3_activity where closed = 0 and  closed = 0 and 
                 presence = 1 and status = 1    ' . $this->model->user_group('id_user')['query'] . '    order by input_date DESC';
            } else {
                $id_user = $filter['id_user'] == 0 ? $this->model->user_group('id_user')['query'] : " and id_user = " . $filter['id_user'] . " ";

                $comments = $filter['comments'] ? " and (SELECT COUNT(id) FROM crm3_activity_comment AS c WHERE c.id_activity = a.id) > 0 " : "";

                $id_activity_type = $filter['id_activity_type'] == 0 ? "" : " and id_activity_type = " . $filter['id_activity_type'] . " ";
               

                if ($filter['date_select'] == 0) {
                    $date_select  = " ";
                } else if ($filter['date_select'] == 1 ) {
                    $date_select  = " and DATE(due_date) = CURDATE() ";
                } else {
                    $date_select =  " and due_date > CURDATE() - INTERVAL " . $filter['date_select'] . " day  ";
                }


                if ($filter['id_related'] == '500') {
                    $related = "and id_module = 500 ";
                } else if ($filter['id_related'] == '200') {
                    $related = "and id_module = 200 ";
                } else {
                    $related = "";
                }
                $q = '
                SELECT a.*, 
                (SELECT COUNT(id) FROM crm3_activity_comment AS c WHERE c.id_activity = a.id) AS total_comments
                FROM crm3_activity AS a
                WHERE  
                 presence = 1 and   closed = 0 and  status = 1 ' . $id_user . $id_activity_type . $date_select . $related . $comments . '  order by input_date DESC';
            }
        } else if ($id_module == '200') {
            // LEAD
            $q = 'select * from crm3_activity where  
            presence = 1 and status = 1  and id_person = "' . $id . '"     order by input_date DESC  limit ' . $limit;

        } else {
            // CONTACT
            $q = 'select * from crm3_activity where  presence = 1 and status = 1 and id_module != "200" and id_person = "' . $id . '" order by input_date DESC  limit ' . $limit;
        }

        $query = $this->db->query($q);
        foreach ($query->result() as $row) {

            $temp = $this->model->activity_history($id_module, $id, json_decode(json_encode($row), true));

            if ($row->closed == 0) {
                array_push($latest, $temp);
            } else {
                array_push($history, $temp);
            }
        }



        $json = array(
            "error" => 0,
            "result" => array(
                "id_user" => $this->model->id_user(),
                "id_module" => $id_module,
                "filter" => $filter,
                "latest" => $latest,
                "history" => $history,
            )
        );
        echo json_encode($json);
    }


    function httpCheckIn()
    {

        $id_module = 600;
        $latest = [];
        $filter = [];
        $history = []; 
       
        $q = 'select * from crm3_activity where closed = 0  and (id_activity_type = 200 or id_activity_type = 300) and 
            presence = 1 and status = 1  and checkIn_by = 0  ' . $this->model->user_group('id_user')['query'] . '    order by input_date DESC';
         

        $query = $this->db->query($q);
        foreach ($query->result() as $row) {

            $temp = $this->model->activity_history($id_module, false, json_decode(json_encode($row), true));
 
            array_push($latest, $temp);
            
        }



        $json = array(
            "error" => 0,
            "result" => array(
                "id_user" => $this->model->id_user(), 
                "latest" => $latest, 
            )
        );
        echo json_encode($json);
    }

    
    function httpCheckOut()
    {

        $id_module = 600;
        $latest = [];
        $filter = [];
        $history = []; 
       
        $q = 'select * from crm3_activity where closed = 0  and (id_activity_type = 200 or id_activity_type = 300) and 
            presence = 1 and status = 1  and (checkIn_by != 0 and checkOut_by = 0) ' . $this->model->user_group('id_user')['query'] . '    order by input_date DESC';
         

        $query = $this->db->query($q);
        foreach ($query->result() as $row) {

            $temp = $this->model->activity_history($id_module, false, json_decode(json_encode($row), true));
 
            array_push($latest, $temp);
            
        }



        $json = array(
            "error" => 0,
            "result" => array(
                "id_user" => $this->model->id_user(), 
                "latest" => $latest, 
            )
        );
        echo json_encode($json);
    }

    function onInsert()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post && $post['data']['subject']) {
            $id = $post['id'];


            $log = "Created by " . $this->model->user($this->model->id_user());
            $insert = array(
                "id_user"           => $post['data']['id_user'],
                "id_activity_type"  => $post['data']['id_activity_type'],
                "id_company"        => $post['data']['id_company'],
                "id_person"         => $post['data']['id_person'],
                "id_module"         => $post['data']['id_module'],
                "id_opportunity"    => $post['data']['id_opportunity'],

                "amount"    => $post['data']['amount'],

                "subject"           => $post['data']['subject'],
                "due_date" => $post['data']['due_date']['year'] . '-' . $post['data']['due_date']['month'] . '-' . $post['data']['due_date']['day'],
                "log"               => $log,
                "start_date" => $post['data']['start_date']['year'] . '-' . $post['data']['start_date']['month'] . '-' . $post['data']['start_date']['day'],
                "end_date"   => $post['data']['end_date']['year'] . '-' . $post['data']['end_date']['month'] . '-' . $post['data']['end_date']['day'],

                "start_time"           => $post['data']['start_time'] . ":00",
                "end_time"           => $post['data']['end_time'] . ":00",

                "description"  => isset($post['data']['description']) ? $post['data']['description'] : "",

                "closed" =>  $this->model->crm3('auto_closed', 'activity_type', 'id=' . $post['data']['id_activity_type']),
                "input_date" => date('Y-m-d H:i:s'),
                "input_by" =>  $this->model->id_user(),
                "update_date" => date('Y-m-d H:i:s'),
            );
            $this->db->insert("crm3_activity", $insert);


            $id_module = $post['data']['id_module'];
            $newID =  $this->model->crm3('id', 'activity', 'id_module = "' . $id_module . '" order by input_date DESC ');


            if ($id_module  == '300') {
                $update = array(
                    "id_company"        =>  $this->model->crm3('id_company', 'contact', 'id=' . $post['data']['id_person']),

                );
                $this->db->update("crm3_activity", $update, " id = " . $newID);
            }


            if ($post['data']['id_activity_type'] == '100') {
                $update = array(
                    "due_date" => $post['data']['due_date']['year'] . '-' . $post['data']['due_date']['month'] . '-' . $post['data']['due_date']['day'] . " " . $post['data']['start_time'],
                );
                $this->db->update("crm3_activity", $update, " id = " . $newID);
            }
            if ($post['data']['id_activity_type'] == '200' || $post['data']['id_activity_type'] == '300') {
                $update = array(
                    "due_date" => $post['data']['start_date']['year'] . '-' . $post['data']['start_date']['month'] . '-' . $post['data']['start_date']['day'] . " " . $post['data']['start_time'],
                );
                $this->db->update("crm3_activity", $update, " id = " . $newID);
            }



            $latest = [];
            $history = [];
            $query = $this->db->query('select * from crm3_activity where id = "' . $newID . '" and  presence = 1 and status = 1 and id_module = "' . $id_module . '" order by input_date DESC');
            foreach ($query->result() as $row) {

                $temp = $this->model->activity_history($id_module, $id, json_decode(json_encode($row), true));

                if ($row->closed == 0) {
                    $latest  = $temp;
                } else {
                    $history = $temp;
                }
            }

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "latest" => $latest,
                    "history" => $history,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert Activty done",
                ),
            );
            echo json_encode($json);
        }
    }

    function fn_remove()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "presence"  => 0,
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_activity", $update, 'id=' .  $post['id']);


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

    function fn_closed()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "closed"  => 1,
                "comment" => $post['data']['comment'],
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_activity", $update, 'id=' .  $post['id']);


            $query = $this->db->query('select * from crm3_activity where id = "' . $post['id'] . '" ');
            foreach ($query->result() as $row) {
                $temp = $this->model->activity_history($row->id_module, $row->id_person, json_decode(json_encode($row), true));
            }


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "query" => $this->db->last_query(),
                    "data" =>  $temp,
                    "note"  => "close done",
                ),
            );
            echo json_encode($json);
        }
    }

    function fn_comments()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $insert = array(
                "id_activity" => $post['id_activity'],
                "comment" => $post['comments'],
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
                "input_by" => $this->model->id_user(),

            );
            $this->db->insert("crm3_activity_comment", $insert);



            $data = array(

                "comment" => '<b>' . $this->model->user($this->model->id_user()) . '</b> ' . $post['comments'],
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
                "input_date" => date('Y-m-d H:i:s'),
                "input_by" => $this->model->id_user(),

            );

            $json = array(
                "error"  =>  0,
                "result" => array(
                    "data"  => $data,
                    "query" => $this->db->last_query(),
                    "note"  => "Comment done",
                ),
            );
            echo json_encode($json);
        }
    }


    function checkInSubmit(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $update = array( 
                "checkIn_lat" => $post['lat'],  
                "checkIn_long" => $post['long'],  
                "checkIn_note" => $post['note'],  
                "checkIn_by" => $this->model->id_user(),
                "checkIn_datetime" => date('Y-m-d H:i:s')

            );
            $this->db->update("crm3_activity", $update, " id = ".$post['id']);
 
            $json = array(
                "error"  =>  0,
                "result" => array(
                    "data"  => $update, 
                ),
            );
            echo json_encode($json);
        }
    }

    function checkOutSubmit(){
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {
            $post = $post['data'];
            $update = array( 
                "checkOut_lat" => $post['lat'],  
                "checkOut_long" => $post['long'],  
                "checkOut_note" => $post['note'],  
                "checkOut_by" => $this->model->id_user(),
                "checkOut_datetime" => date('Y-m-d H:i:s') 
            );
            $this->db->update("crm3_activity", $update, " id = ".$post['id']);
 
            $json = array(
                "error"  =>  0,
                "result" => array(
                    "data"  => $update, 
                ),
            );
            echo json_encode($json);
        }
    }
}
