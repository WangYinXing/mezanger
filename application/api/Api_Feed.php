<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//  application/core/MY_Controller.php

class Api_Posts extends Api_Unit {
	public function __construct(){
    	parent::__construct();


    	$this->ctrlName = "Feed";
		$this->load->model('Mdl_Feeds', '', TRUE);
	}

	
}

?>