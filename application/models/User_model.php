<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    protected $table = 'crm3_user';

    public function get_by_email($email)
    {
        return $this->db
            ->select('id, id_user_access, id_user_group, id_parent, folder, email, password, code, first_name, last_name, status')
            ->from($this->table)
            ->where('email', $email)
            ->limit(1)
            ->get()
            ->row();
    }
}