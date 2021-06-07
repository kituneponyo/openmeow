<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2018/10/18
 * Time: 1:01
 */

class MyDB extends CI_DB_mysqli_driver
{
    public function __construct() {
        parent::__construct();
        $this->load->library('twig');
    }
}