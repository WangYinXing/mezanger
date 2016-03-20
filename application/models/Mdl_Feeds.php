<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Mdl_Feeds extends Mdl_Campus {


	function __construct() {
		parent::__construct();
		
		$this->table = 'feeds';
	}


	public function create($feed) {
		$this->load->model("Mdl_Users");
		$user = $this->Mdl_Users->get($feed['author']);

		if ($user == null) {
			$this->latestErr = "Author id is not valid.";

			return;
		}

		$this->db->insert($this->table, $feed);
		$feed_id = $this->db->insert_id();


		if ($feed_id == 0) {
			$this->latestErr = "Failed to excute sql with : " . json_encode($arg);
		}
		else {
			$this->latestErr = "";
		}

		$feed['id'] = $feed_id;

		return $feed;
	}
}

?>