<?php

defined('BASEPATH') OR exit('No direct script access allowed');



Class Mdl_Notifications extends Mdl_Campus {



	function __construct() {
		parent::__construct();
		$this->table = 'notifications';

	}



	public function create($arg) {
		$this->db->insert($this->table, $arg);
		$id = $this->db->insert_id();

		if ($id == 0) {
			$this->latestErr = "Failed to create excute sql with : " . json_encode($arg);
		}
		else {
			$this->latestErr = "";
		}

		$arg['id'] = $id;

		return $arg;
	}



	public function fetch($user) {

		$this->db->select("*");

		$this->db->from($this->table);

		

		//$this->db->where('sender', $user);

		$this->db->where('receiver', $user);



		return $this->db->get()->result();

	}

}



?>