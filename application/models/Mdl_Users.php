<?php

defined('BASEPATH') OR exit('No direct script access allowed');

Class Mdl_Users extends Mdl_Campus {

	function __construct() {
		parent::__construct();

		$this->table = 'users';
		$this->load->helper("utility");
	}

	public function online_usercnt() {
		$this->db->select("id");
		$this->db->from($this->table);
		$this->db->where('token != "" AND devicetoken != ""');

		return $this->db->get()->num_rows();
	}


	public function get_list($rp, $page, $query, $qtype, $sortname, $sortorder, $count = false) {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->join('profiles', 'users.id = profiles.user', 'left');
		$this->db->order_by($sortname, $sortorder);

		if ($query != "" && $qtype != "") {
			$this->db->like($qtype, $query);
		}

		if ($count)
			return $this->db->count_all_results();

		$this->db->limit($rp, $rp * ($page - 1));

		return $this->db->get()->result();
	}

	public function getAll($field = "", $val = "") {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->join('profiles', 'users.id = profiles.user', 'left');

		if ($field != "" && $val != "")
			$this->db->where($field, $val);

		$users = $this->db->get();

		if ($users->num_rows() == 0)
			return;

		return $users->result();
	}

	public function getEx($id) {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->join('profiles',  "users.id = profiles.user");
		$this->db->where("id = $id");

		$users = $this->db->get();

		if ($users->num_rows() == 1) {
			return $users->result()[0];
		}

		return null;
	}

	public function signup($args, $qbuser) {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->where('email', $args['email']);

		if ($this->db->get()->num_rows() != 0) {
			$this->latestErr = "email is already used by another user.";
			return null;
		}

		$args['qbid'] = $qbuser->id;
		$args['password'] = md5($args['password']);

		$user = utfn_safeArray(array('qbid', 'username', 'email', 'fullname', 'password'), $args);

		$this->db->insert($this->table, $user);
		$args['id'] = $userid = $this->db->insert_id();

		if (!$userid) {
			$this->latestErr = "Failed to create excute sql with : " . json_encode($args);
			return;
		}

		$profile = utfn_safeArray(array('bday', 'country', 'preferred_language', 'mobile_number','landline_number'), $args);

		// We set role as User first....
		$profile['role'] = 'User';
		$profile['user'] = $userid;


		if (!$this->db->insert('profiles', $profile)) {
			$this->latestErr = "Failed to create excute sql with : " . json_encode($profile);
			return;
		}

		unset($args['password']);
		return $args;
	}



	public function signin($qbid, $token) {
		$this->db->select("*");
		$this->db->from($this->table);

		$this->db->where("qbid", $qbid);

		$users = $this->db->get()->result();

		if (count($users) == 0)
			return null;

		$user = $users[0];

		$this->db->select("*");
		$this->db->where("qbid", $qbid);


		if (!$this->db->update($this->table, array('token'=> $token))) {
			return;
		}

		unset($user->password);
		//unset($user->updated_time);

		$user->token = $token;

		return $user;
	}



	public function signout($user) {
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->where("id", $user);

		if (!$this->db->update($this->table, array('token'=> '', 'devicetoken' => '', 'udid' => ''))) {
			return;
		}

		//unset($user->password);
		//unset($user->updated_time);


		//$user['token'] = '';
		//return $user;
	}



	public function update($arg) {
		$id = $arg['id'];

		unset($arg['id']);

		$this->db->select("*");
		$this->db->from($this->table);

		$this->db->where("id", $id);

		if (!$this->db->update($this->table, $arg)) {
			return;
		}

		$this->db->from($this->table);
		$this->db->where("id", $id);

		return $this->db->get()->result()[0];
	}



	public function makeFriends($a, $b) {
		$this->latestErr = "";
		$userA = $this->getEx($a);

		if ($userA == null) {
			$this->latestErr = "UserA is not valid...";
			return;
		}

		$ret = $this->addToStrArray($b, $userA->friends);

		if (!$ret['succeed']) {
			$this->latestErr = "This user added already.";
			return;
		}

		
		$this->db->from('profiles');
		$this->db->where('user', $a);
		$this->db->update('profiles', array('friends'=> json_encode($ret['array'])));


		$userB = $this->getEx($b);

		if ($userB == null) {
			$this->latestErr = "UserB is not valid...";
			return;
		}

		$ret = $this->addToStrArray($a, $userB->friends);

		if (!$ret['succeed']) {
			$this->latestErr = "This user added already.";
			return;
		}

		$this->db->from('profiles');
		$this->db->where('user', $b);
		$this->db->update('profiles', array('friends'=> json_encode($ret['array'])));
	}
}

?>