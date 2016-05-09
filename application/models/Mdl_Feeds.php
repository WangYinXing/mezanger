<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Mdl_Feeds extends Mdl_Campus {


	function __construct() {
		parent::__construct();
		
		$this->table = 'feeds';
	}


	public function create($feed, $arrReceivers) {
		$this->load->model("Mdl_Users");
		$user = $this->Mdl_Users->get($feed['sender']);

		if ($user == null) {
			$this->latestErr = "Sender id is not valid.";
			return;
		}


		$arrLanguages = [];

		foreach ($arrReceivers as $receiverID) {
			// Get receiver's record...
			$receiver = $this->Mdl_Users->getEx($receiverID);

			if ($receiver == null) {
				continue;
			}

			$arrLanguages[] = $receiver->language;
		}

		$arrLanguages = array_unique($arrLanguages);

		if ($arrLanguages[0] == $feed['language']) {
			$this->latestErr = "No need to translate. sender and receivers are the same people.";
			return;
		}

		$engContent = null;

		/*
			If sender sent message as English...
		*/
		if (strtolower($feed['language']) == "english") {
			$engContent = $feed['content'];
		}

		$this->db->insert($this->table, $feed);
		$feedID = $this->db->insert_id();


		if ($feedID == 0) {
			$this->latestErr = "Failed to excute sql with : " . json_encode($arg);
		}
		else {
			$this->latestErr = "";
		}
		

		foreach ($arrLanguages as $language) {
			// Receiver's language is English and sender sent message as English. don't need to translate....
			if (strtolower($language) == "english" && strtolower($feed['language']) == "english") {
				continue;
			}

			// They are same people.
			if ($language == $feed['language']) {
				continue;
			}

			$this->db->insert('tfeeds', [
					'feed' => $feedID,
					'language' => $feed['language'],
					'target_language' => $language,
					'eng_content' => $engContent
				]);
			$tfeedID = $this->db->insert_id();
		}

		$feed['id'] = $feedID;

		return $feed;
	}

	public function createDraftFeed($draftFeed) {
		$this->db->insert('draft_feeds', $draftFeed);
		$draftFeed['id'] = $this->db->insert_id();

		return $draftFeed;
	}

	public function getEx($id, $type) {
		$this->db->select("*");
		$this->db->from($type);
		$this->db->where("id = $id");

		$users = $this->db->get();

		if ($users->num_rows() == 1) {
			return $users->result()[0];
		}

		return;
	}

	public function getLatest($duration) {
		$this->db->select('*');
		$this->db->from('feeds');
		$this->db->where("date_sub(now(), INTERVAL $duration MINUTE) < updated_time");

		$feeds = $this->db->get()->result();

		return $feeds;
	}
}

?>