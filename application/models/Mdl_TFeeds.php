<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Mdl_TFeeds extends Mdl_Campus {


	function __construct() {
		parent::__construct();
		
		$this->table = 'tfeeds';
	}

	public function create($tFeed) {
		$this->load->model("Mdl_Users");
		$user = $this->Mdl_Users->get($tFeed['author']);

		if ($user == null) {
			$this->latestErr = "Author id is not valid.";

			return;
		}

		$this->db->insert($this->table, $tFeed);
		$tFeed_id = $this->db->insert_id();


		if ($tFeed_id == 0) {
			$this->latestErr = "Failed to excute sql with : " . json_encode($arg);
		}
		else {
			$this->latestErr = "";
		}

		$tfeed['id'] = $tFeed_id;

		return $tFeed;
	}

	public function getVerified($feed) {
		$this->db->select('*');
		$this->db->from("tfeeds");
		$this->db->where(["feed" => $feed, "verified" => 1]);

		$tFeeds = $this->db->get()->result();

		return $tFeeds;
	}
}

?>