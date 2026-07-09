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

        if ($id_module == '400') {
            // COMPANY
            $q = 'select * from crm3_activity where  presence = 1 and status = 1 and id_company = "' . $id . '" order by input_date DESC';
        } else if ($id_module == '500') {
            // OPPORTUNITY
            $q = 'select * from crm3_activity where  presence = 1 and status = 1 and id_opportunity = "' . $id . '" order by input_date DESC';
        } else if ($id_module == '600') {
            // ACTIVITY
            $filter = json_decode(base64_decode($this->input->get('f')), true);

            if ($filter == null) {
                $q = 'select * from crm3_activity where  presence = 1 and status = 1    ' . $this->model->user_group('id_user')['query'] . '    order by input_date DESC';
            } else {
                $id_user = $filter['id_user'] == 0 ? $this->model->user_group('id_user')['query'] : " and id_user = " . $filter['id_user'] . " ";

                $comments = $filter['comments'] ? " and (SELECT COUNT(id) FROM crm3_activity_comment AS c WHERE c.id_activity = a.id) > 0 ":"";
                 
                $id_activity_type = $filter['id_activity_type'] == 0 ? "" : " and id_activity_type = " . $filter['id_activity_type'] . " ";
                $date_select = $filter['date_select'] == 0 ? "" : " and due_date > CURDATE() - INTERVAL " . $filter['date_select'] . " day  ";

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
                 presence = 1 and  status = 1 ' . $id_user . $id_activity_type . $date_select . $related . $comments. '  order by input_date DESC';
            }
        } else if ($id_module == '200') {
            // LEAD
            $q = 'select * from crm3_activity where  presence = 1 and status = 1  and id_person = "' . $id . '"   ' . $this->model->user_group('id_user')['query'] . '    order by input_date DESC';
        } else {
            // CONTACT
            $q = 'select * from crm3_activity where  presence = 1 and status = 1 and id_module != "200" and id_person = "' . $id . '" order by input_date DESC';
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
                "q" => $q,
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























    public function getSelected($request = "", $id = 0)
    {
        $id_lead = 0;
        $id_contact = 0;
        $id_company = 0;
        $id_oppotynity = 0;
        $target = "";
        $id_target = 0;
        $where_id_company = false;

        $opportunity = array();
        $user = [];
        $q = " select *  from crm3_user where presence = 1 order by first_name ASC, last_name ASC ";
        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => $this->model->user($row->id),
            );
            array_push($user, $temp);
        }



        if ($request == 'lead') {
            $name = $this->model->lead($id);
            $id_lead = $id;

            $target = "lead";
            $id_target = $id;
        } else if ($request == 'contact') {
            $name = $this->model->contact($id);
            $id_contact = $id;

            $target = "contact";
            $id_target = $id;


            if ($this->model->select('id_company', 'crm3_contact', 'id = "' . $id . '"')) {
                $id_company = $this->model->select('id_company', 'crm3_contact', 'id = "' . $id . '"');
                $where_id_company = "and a.id_company = " . $id_company;
            }
        } else if ($request == 'company') {
            $name = [];
            $id_company = $id;
            $q = " select * from crm3_contact where presence = 1 and id_company= '$id' order by first_name ASC, last_name ASC ";
            $query = $this->db->query($q);
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $this->model->contact($row->id),
                );
                array_push($name, $temp);
            }

            $target = "company";
            $id_target = $id_company;
        } else if ($request == 'opportunity') {
            $id_oppotynity = $id;
            $name = [];

            $id_company = $this->model->select('id_company', 'crm3_opportunity', 'id=' . $id);

            $q = " select * from crm3_contact where presence = 1 and id_company= '$id_company' order by first_name ASC, last_name ASC ";
            $query = $this->db->query($q);
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => $this->model->contact($row->id),
                );
                array_push($name, $temp);
            }



            $opportunity = [];
            $q = "select * from crm3_opportunity where presence = 1 and id = $id order by input_date ASC";
            $query = $this->db->query($q);
            foreach ($query->result() as $row) {
                $temp = array(
                    "id" => $row->id,
                    "name" => ucwords($row->name),
                );
                array_push($opportunity, $temp);
            }


            $target = "contact";
            $id_target = $id_contact;
        }

        $activity_priority = [];
        $q = "select * from crm3_activity_priority where presence = 1  order by value ASC";
        $query = $this->db->query($q);
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name" => ucwords($row->name),
            );
            array_push($activity_priority, $temp);
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "user"          => $user,
                "request"       => $request,
                "id"            => $id,
                "name"          => $name,
                "activity_priority"      => $activity_priority,
                "opportunity"   => $opportunity,
                "data" => array(
                    "id_activity_priority" => $this->model->select('id', 'crm3_activity_priority', ' presence = 1  order by value ASC limit 1'),
                ),
            ),
        );
        echo json_encode($json);
    }


    public function widgetList($request = "", $id = 0)
    {
        $id_lead = 0;
        $id_contact = 0;
        $id_company = 0;
        $id_oppotynity = 0;
        $target = "";
        $id_target = 0;
        $where_id_company = false;

        $opportunity = array();

        if ($request == 'lead') {
            $name = $this->model->lead($id);
            $id_lead = $id;

            $target = "lead";
            $id_target = $id;
        } else if ($request == 'contact') {
            $name = $this->model->contact($id);
            $id_contact = $id;

            $target = "contact";
            $id_target = $id;


            if ($this->model->select('id_company', 'crm3_contact', 'id = "' . $id . '"')) {
                $id_company = $this->model->select('id_company', 'crm3_contact', 'id = "' . $id . '"');
                $where_id_company = "and a.id_company = " . $id_company;
            }
        } else if ($request == 'company') {
            $name = "";
            $id_company = $id;

            $target = "company";
            $id_target = $id_company;
        } else if ($request == 'opportunity') {
            $id_oppotynity = $id;
            $id_contact = $this->model->select('id_contact', 'crm3_opportunity', 'id= "' . $id . '" ');

            $target = "contact";
            $id_target = $id_contact;
        }



        $activity = [];
        if ($id_target) {
            $qa = "
            SELECT 
                a.id, a.id_lead, a.id_contact,  a.id_company,
                p.name,
                d.id as 'id_activty_detail', d.id_activity_type, d.id_user, d.id_company, d.id_opportunity, d.due_date, d.closed_date, d.closed, d.subject, d.id_priority, d.`comment`
            FROM crm3_activity AS a 
            LEFT join crm3_activity_detail  AS d    ON a.id = d.id_activity
            JOIN crm3_activity_priority     AS p    ON p.id = d.id_priority
            WHERE d.presence = 1 AND ( a.id_$target = '$id_target'  $where_id_company ) and d.closed = 0
            ORDER BY p.value DESC, d.due_date ASC
            ";

            $query = $this->db->query($qa);
            foreach ($query->result() as $row) {
                if ($request == 'company' || $request == 'opportunity') {
                    $name = $this->model->contact($row->id_contact);
                }
                $temp = array(

                    "activity_type" => array(
                        "id" => $row->id_activity_type,
                        "name" => $this->model->select('name', 'crm3_activity_type', 'id=' . $row->id_activity_type),

                    ),
                    "id" => $row->id_activty_detail,
                    "subject" => ucwords($row->subject),
                    "due_date" => $row->due_date,
                    "comment" => $row->comment,
                    "priority" => array(
                        "id" => $row->id_priority,
                        "color" => $this->model->select('color', 'crm3_activity_priority', 'id=' . $row->id_priority),
                        "name" => $this->model->select('name', 'crm3_activity_priority', 'id=' . $row->id_priority),
                    ),
                    "name" => $name,
                    "user" => array(
                        "id" => $row->id_user,
                        "name" => $this->model->user($row->id_user),
                    ),
                    "edit" => false

                );
                array_push($activity, $temp);
            }
        }


        $history = [];
        if ($id_target) {
            $qa = "
            SELECT 
                a.id, a.id_lead, a.id_contact,  a.id_company,
                p.name,
                d.id as 'id_activty_detail', d.id_activity_type, d.id_user, d.id_company, d.id_opportunity, d.due_date, d.closed_date, d.closed, d.subject, d.id_priority, d.`comment`, d.event
            FROM crm3_activity AS a 
            LEFT join crm3_activity_detail  AS d    ON a.id = d.id_activity
            JOIN crm3_activity_priority     AS p    ON p.id = d.id_priority
            WHERE d.presence = 1 AND ( a.id_$target = '$id_target'  $where_id_company ) and d.closed = 1 and d.event = 0
            ORDER BY  d.update_date DESC
            ";

            $query = $this->db->query($qa);
            foreach ($query->result() as $row) {
                if ($request == 'company' || $request == 'opportunity') {
                    $name = $this->model->contact($row->id_contact);
                }

                $temp = array(

                    "activity_type" => array(
                        "id" => $row->id_activity_type,
                        "name" => $this->model->select('name', 'crm3_activity_type', 'id=' . $row->id_activity_type),

                    ),
                    "id" => $row->id_activty_detail,
                    "subject" => ucwords($row->subject),
                    "due_date" => $row->due_date,
                    "closed_date" => $row->closed_date,
                    "comment" => $row->comment,
                    "priority" => array(
                        "id" => $row->id_priority,
                        "color" => $this->model->select('color', 'crm3_activity_priority', 'id=' . $row->id_priority),
                        "name" => $this->model->select('name', 'crm3_activity_priority', 'id=' . $row->id_priority),
                    ),
                    "name" =>   $name,
                    "user" => array(
                        "id" => $row->id_user,
                        "name" => $this->model->user($row->id_user),
                    ),
                    "edit" => false

                );
                array_push($history, $temp);
            }
        }

        $json = array(
            "error"  =>  0,
            "result" => array(
                "activity" => $activity,
                "history" => $history,
                "qa" => $qa,
            ),
        );
        echo json_encode($json);
    }


    public function detail($id = 0)
    {

        $query = $this->db->query('select * from crm3_contact where presence = 1 and id = ' . $id);
        foreach ($query->result() as $row) {
            $data = array(
                "id" => $row->id,
                "id_company" => $row->id_company,
                "name" => $row->first_name . ' ' . $row->last_name . ', ' . $this->model->title($row->id_title),
                "email" => $row->email,
                "phone" => $row->phone,
                "mobile" => $row->mobile,
                "lead_source" => $this->model->lead_source($row->id_lead_source),
                "user" => $this->model->user($row->id_user),
                "company" => $this->model->company($row->id_company),
                "input_date" => $row->input_date,
                "input_by" => $this->model->user($row->input_by),
                "update_date" => $row->update_date,
                "update_by" =>  $this->model->user($row->update_by),
            );
        }

        $opportunity = [];
        $query = $this->db->query('select * from crm3_opportunity where presence = 1 and id_contact = ' . $id . ' order by name ASC');
        foreach ($query->result() as $row) {
            $temp = array(
                "id" => $row->id,
                "name"  => $row->name,
                "stage" => $this->model->select('name', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "progress" => $this->model->select('progress', 'crm3_opportunity_stage', 'id= "' . $row->id_stage . '"'),
                "currency" => $this->model->currency("code"),
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

        $json = array(
            "error"  =>  0,
            "result" => array(
                "data" => $data,
                "opportunity" => $opportunity,
            ),
        );

        echo json_encode($json);
    }

    public function insert2()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        $id_lead = 0;
        $id_contact = 0;
        $id_company = 0;
        $id_target = 0;
        $newActivty = false;

        if ($post) {
            $param_id = $post['id'];
            $post = $post['data'];

            if ($post['request'] == 'lead') {

                $id_lead = $post['id'];
                $id_contact = 0;
                $id_company = 0;

                $id_target = $this->model->select('id', 'crm3_activity', 'id_lead = ' . $id_lead);
                if ($id_target) {
                    $id = $id_target;
                } else {
                    $id = $this->model->number("activity");
                    $newActivty = true;
                }
            } else if ($post['request'] == 'contact') {


                $id_contact = $post['id'];
                $id_company = $this->model->select('id_company', 'crm3_contact', 'id=' . $id_contact) ? $this->model->select('id_company', 'crm3_contact', 'id=' . $id_contact) : 0;

                $id_target = $this->model->select('id', 'crm3_activity', 'id_contact = ' . $id_contact . ' and id_company =' . $id_company);
                if ($id_target) {
                    $id = $id_target;
                } else {
                    $id = $this->model->number("activity");
                    $newActivty = true;
                }
            } else if ($post['request'] == 'company') {

                if ($post['id_activity_type'] == 400) {
                    $id_contact = 0;
                    $id_company =  $param_id;

                    $id_target = $this->model->select('id', 'crm3_activity', 'id_company  = "' . $id_company . '"');
                    if ($id_target) {
                        $id = $id_target;
                    } else {
                        $id = $this->model->number("activity");
                        $newActivty = true;
                    }
                } else {
                    $id_contact = $post['id_contact'];
                    $id_company =  $post['id'];

                    $id_target = $this->model->select('id', 'crm3_activity', 'id_contact = "' . $id_contact . '" and id_company  = "' . $id_company . '"');
                    if ($id_target) {
                        $id = $id_target;
                    } else {
                        $id = $this->model->select('id', 'crm3_activity', 'id_contact = ' . $id_contact . ' and id_company  = 0');
                        if ($id) {
                            $update = array(
                                "id_company" =>   $id_company,
                                "update_by" =>  $this->model->id_user(),
                                "update_date" => date('Y-m-d H:i:s'),
                            );
                            $this->db->update('crm3_activity', $update, 'id=' . $id);
                        } else {
                            $id = $this->model->number("activity");
                            $newActivty = true;
                        }
                    }
                }
            } else if ($post['request'] == 'opportunity') {
                $id_request = $post['id'];
                $id_contact =  $this->model->select('id_contact', 'crm3_opportunity', 'id=' . $id_request);
                $id_company =  $this->model->select('id_company', 'crm3_opportunity', 'id=' . $id_request);

                $id_target = $this->model->select('id', 'crm3_activity', 'id_contact = ' . $id_contact . ' and id_company =' . $id_company);
                if ($id_target) {
                    $id = $id_target;
                } else {
                    $id = $this->model->number("activity");
                    $newActivty = true;
                }
            }

            if ($newActivty == true) {
                $insert = array(
                    "id"            => $id,
                    "id_lead"       => $id_lead,
                    "id_contact"    => $id_contact,
                    "id_company"    => $id_company,

                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
                $this->db->insert("crm3_activity", $insert);
            }


            if ($post['id_activity_type'] == 400) {
                $insert = array(
                    "id_activity" => $id,
                    "id_activity_type" => '400',
                    "subject" => isset($post['subject']) ?  $post['subject'] : "",
                    "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : $this->model->id_user(),
                    "due_date" => date('Y-m-d'),
                    "closed" => '1',
                    "closed_date" => date('Y-m-d H:i:s'),
                    "id_priority" => isset($post['id_priority']) ?  $post['id_priority'] : "1",
                    "id_opportunity" => isset($post['id_opportunity']) ?  $post['id_opportunity'] : 0,
                    "comment" => isset($post['comment']) ?  $post['comment'] : "",
                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
            } else if ($post['id_activity_type'] == 300) {
                $insert = array(
                    "id_activity" => $id,
                    "id_activity_type" => '300',
                    "subject" => isset($post['subject']) ?  $post['subject'] : "",
                    "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : $this->model->id_user(),
                    "due_date" => date('Y-m-d'),
                    "closed" => '1',
                    "event" => '1',
                    "closed_date" => date('Y-m-d H:i:s'),
                    "id_priority" => isset($post['id_priority']) ?  $post['id_priority'] : "1",
                    "id_opportunity" => isset($post['id_opportunity']) ?  $post['id_opportunity'] : 0,
                    "comment" => isset($post['comment']) ?  $post['comment'] : "",

                    "from_date" => $post['start_date']['year'] . '-' . $post['start_date']['month'] . '-' . $post['start_date']['day'],
                    "to_date"   => $post['to_date']['year'] . '-' . $post['to_date']['month'] . '-' . $post['to_date']['day'],

                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
            } else {
                $insert = array(
                    "id_activity" => $id,
                    "id_activity_type" => isset($post['id_activity_type']) ?  $post['id_activity_type'] : '1',
                    "subject" => isset($post['subject']) ?  $post['subject'] : "",
                    "id_user" =>  isset($post['id_user']) ?  $post['id_user'] : $this->model->id_user(),
                    "due_date" => isset($post['date']) ?  $post['date']['year'] . '-' . $post['date']['month'] . '-' . $post['date']['day'] : date('Y-m-d'),
                    "closed" => $post['closed'] ? 1 : 0,
                    "closed_date" => date('Y-m-d H:i:s'),
                    "id_priority" => isset($post['id_priority']) ?  $post['id_priority'] : "1",
                    "id_opportunity" => isset($post['id_opportunity']) ?  $post['id_opportunity'] : 0,
                    "comment" => isset($post['comment']) ?  $post['comment'] : "",
                    "input_date" => date('Y-m-d H:i:s'),
                    "input_by" =>  $this->model->id_user(),
                    "update_date" => date('Y-m-d H:i:s'),
                );
            }

            $this->db->insert("crm3_activity_detail", $insert);


            $json = array(
                "error"  =>  0,
                "result" => array(
                    "id" => $id,
                    "query" => $this->db->last_query(),
                    "note"  => "Insert Activty done",
                    //  "post" => $post,
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
                "id_lead_source"     => isset($post['data']['id_lead_source']) ? $post['data']['id_lead_source'] : "0",
                "first_name"     => isset($post['data']['first_name']) ? $post['data']['first_name'] : "",
                "last_name"     => isset($post['data']['last_name']) ? $post['data']['last_name'] : "",

                "email"    => isset($post['data']['email']) ? $post['data']['email'] : "",
                "mobile"      => isset($post['data']['mobile']) ? $post['data']['mobile'] : "",
                "phone" => isset($post['data']['phone']) ? $post['data']['phone'] : "",

                "id_company" => isset($post['data']['id_company']) ? $post['data']['id_company'] : "0",

                "update_date" => date('Y-m-d H:i:s'),
                "update_by" =>  $this->model->id_user(),
            );
            $this->db->update("crm3_contact", $update, 'id=' . $post['id']);

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
                    "presence"  => 0,

                    "update_date" => date('Y-m-d H:i:s'),
                    "update_by" => $this->model->id_user(),
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

    public function onClosed()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "closed"  => 1,
                "closed_date" => date('Y-m-d H:i:s'),
                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),

            );
            $this->db->update("crm3_activity_detail", $update, 'id=' .  $post['id']);


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

    public function onComment()
    {
        $post = json_decode(file_get_contents('php://input'), true);

        if ($post) {

            $update = array(
                "comment"  => $post['comment'],

                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_activity_detail", $update, 'id=' .  $post['id']);


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

                "update_date" => date('Y-m-d H:i:s'),
                "update_by" => $this->model->id_user(),
            );
            $this->db->update("crm3_activity_detail", $update, 'id=' .  $post['id']);


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

        $query = $this->db->query('select * from crm3_contact where presence = 1 and id = ' . $id);
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
