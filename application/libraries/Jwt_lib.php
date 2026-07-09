<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Jwt_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('jwt');
    }

    public function generate_token($payload_data)
    {
        $secret = $this->CI->config->item('jwt_secret');
        $issuer = $this->CI->config->item('jwt_issuer');
        $expire = $this->CI->config->item('jwt_expire');

        $issued_at = time();

        $payload = array(
            'iss'  => $issuer,
            'iat'  => $issued_at,
            'exp'  => $issued_at + $expire,
            'data' => $payload_data
        );

        return JWT::encode($payload, $secret, 'HS256');
    }

    public function validate_token($token)
    {
        $secret = $this->CI->config->item('jwt_secret');

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}