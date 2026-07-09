<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['jwt_secret'] = 'GANTI_DENGAN_SECRET_KEY_YANG_PANJANG_DAN_RANDOM';
$config['jwt_issuer'] = 'crm3-api';
$config['jwt_expire'] = 3600 * 8; // 8 jam, sesuaikan kebutuhan