<?php

class Mdl_Campus extends CI_Model {

	function __construct() {
		parent::__construct();

		$this->latestErr = "";
	}

	public function getLatestError() {
		return $this->latestErr;
	}

	public function get($id) {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->where("id", $id);

		$users = $this->db->get();

		if ($users->num_rows() == 1) {
			return $users->result()[0];
		}

		return null;
	}

	public function getAll($field = "", $val = "") {
		$this->db->select("*");
		$this->db->from($this->table);

		if ($field != "" && $val != "")
			$this->db->where($field, $val);

		$users = $this->db->get();

		if ($users->num_rows() == 0)
			return;

		return $users->result();
	}

	public function getAllEx($whereConditions, $likeConditions = []) {
		$this->db->select("*");
		$this->db->from($this->table);

		if (count($whereConditions)) {
			$this->db->where($whereConditions);
		}

		if (count($likeConditions)) {
			$this->db->like($likeConditions);
		}

		$users = $this->db->get();

		if ($users->num_rows() == 0)
			return;

		return $users->result();
	}



	public function get_list($rp, $page, $query, $qtype, $sortname, $sortorder, $count = false) {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->order_by($sortname, $sortorder);
		
		$queries = explode(',', $query);
		$qtypes = explode(',', $qtype);
		
		$cnt = MIN(count($queries), count($qtypes));
		
		$likes = [];
		
		
		for($i=0; $i<$cnt; $i++) {
			$likes[$qtypes[$i]] = $queries[$i];
		}

		try {
			if ($cnt) {
				$this->db->like($likes);
			}

			if ($count)
				return $this->db->count_all_results();

			$this->db->limit($rp, $rp * ($page - 1));

			$ret = $this->db->get()->result();


		}
		catch (Exception $e) {
			$this->latestErr = $e->getMessage();
			return null;
		}

		

		return $ret;
	}

	public function get_length() {
		$this->db->select("id");
		$this->db->from($this->table);

		return $this->db->get()->num_rows();
	}

	public function remove($id) {
		$this->db->delete($this->table, array('id' => $id));
	}

	public function updateEx($id, $arrValues) {
		$this->db->from($this->table);
		$this->db->where("id", $id);

		$this->db->update($this->table, $arrValues);
	}

	public function addToStrArray($val, $strArray) {
		$result = array();

		if ($strArray == null) {
			array_push($result, $val);

			return array("array" => $result, "succeed" => true);
		}

		$result = json_decode($strArray);

		$key = array_search($val, $result);


		if (in_array($val, $result)) {
			return array("array" => $result, "succeed" => false);
		}

		array_push($result, $val);

		return array("array" => $result, "succeed" => true);
	}


	public function removeFromStrArray($val, $strArray) {
		$result = array();

		if ($strArray == null) {
			return array("array" => $result, "succeed" => false);
		}

		$result = json_decode($strArray);


		if (!in_array($val, $result)) {
			return array("array" => $result, "succeed" => false);
		}

		$key = array_search($val, $result);

		unset($result[$key]);

		return array("array" => $result, "succeed" => true);
	}
}

?>