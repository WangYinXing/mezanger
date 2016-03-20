<?php

defined('BASEPATH') OR exit('No direct script access allowed');

Class Mdl_Profiles extends Mdl_Campus {

	function __construct() {
		parent::__construct();
		
		$this->table = 'profiles';
		$this->load->helper("utility");
	}

	public function update($arrValues) {
		$this->db->from($this->table);
		$this->db->where("user", $arrValues['user']);

		unset($arrValues['user']);

		$this->db->update($this->table, $arrValues);
	}

}

?>