<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//  application/core/MY_Controller.php

class Api_Feeds extends Api_Unit {
	public function __construct(){
    	parent::__construct();


    	$this->ctrlName = "Feed";
		$this->load->model('Mdl_Feeds', '', TRUE);

		$this->load->helper('utility');
	}

/*########################################################################################################################################################

	API Entries

########################################################################################################################################################*/

	public function api_entry_list() {
		parent::validateParams(array("rp", "page", "user", "duration"));

		$this->load->model('Mdl_Users');
		$this->load->model('Mdl_Feeds');
		$this->load->model('Mdl_TFeeds');

		if ($_POST['duration'] == "")
			$_POST['duration'] = 1000;

		$user = $this->Mdl_Users->getFirst('id', $_POST['user']);

		if ($user == null) {
			parent::returnWithErr("User is not valid.");
		}

		$feeds = $this->Mdl_Feeds->getLatest($_POST['duration']);
		$filteredFeeds = [];

		foreach ($feeds as $feed) {
			if ($feed->sender == $_POST['user'] || $feed->receiver == $_POST['user']) {
				continue;
			}

			$sender = $this->Mdl_Users->getFirst('id', $feed->sender);
			$receiver = $this->Mdl_Users->getFirst('id', $feed->receiver);

			if ($sender == null || $receiver == null)
				continue;

			if ($sender->language == $receiver->language)
				continue;

			if (($user->language == $sender->language && $user->preferred_language == $receiver->language) || 
				($user->language == $receiver->language && $user->preferred_language == $sender->language)) {

				$tFeedsVerified = $this->Mdl_TFeeds->getAllEx(["feed" => $feed->id, "verified" => 1]);
				$feed->tFeeds = [];

				if (count($tFeedsVerified)) {
					$feed->tFeeds = $tFeedsVerified;
					$feed->verified = true;

					foreach ($feed->tFeeds as $key => $tFeed) {
						$user = $this->Mdl_Users->getFirst('id', $tFeed->author);

						unset($user->password);

						$tFeed->author = $user;
					}
				}
				else {
					$tFeeds = $this->Mdl_TFeeds->getAll("feed", $feed->id);

					if (count($tFeeds)) {
						foreach ($tFeeds as $key => $tFeed) {
							$user = $this->Mdl_Users->getFirst('id', $tFeed->author);

							unset($user->password);

							$tFeed->author = $user;
						}

						$feed->tFeeds = $tFeeds;
					}

					
					$feed->tFeedsCnt = count($tFeeds);
				}

				$feed->sender = $this->Mdl_Users->getFirst('id', $feed->sender);
				$feed->receiver = $this->Mdl_Users->getFirst('id', $feed->receiver);

				$filteredFeeds[] = $feed;
			}
		}


		parent::returnWithoutErr("Request has been listed successfully.", array(
			'cnt' => count($filteredFeeds),
			'feeds' => $filteredFeeds
		));
	}


	/*--------------------------------------------------------------------------------------------------------
		Create Request... 
		*** POST
	_________________________________________________________________________________________________________*/

	public function api_entry_create() {
		parent::validateParams(array("sender", "receiver", "content"));

		$feed = $this->Mdl_Feeds->create(utfn_safeArray(array('sender', 'receiver','content', 'title', 'category'), $_POST));

		if ($feed == null)	parent::returnWithErr($this->Mdl_Feeds->latestErr);

		/*
			Created successfully .... 
		*/
		parent::returnWithoutErr("Feed has been created successfully.", $feed);

	}

	/*--------------------------------------------------------------------------------------------------------
		Create Request... 
		*** POST
	_________________________________________________________________________________________________________*/

	public function api_entry_translate() {
		parent::validateParams(array("author", "feed", "content", "verified"));

		$this->load->model('Mdl_Users');
		$this->load->model('Mdl_Feeds');
		$this->load->model('Mdl_TFeeds');

		if ($_POST["verified"] != 1 && $_POST["verified"] != 0) {
			parent::returnWithErr('Verified field should be 0 or 1');
		}

		$author = $this->Mdl_Users->getFirst('id', $_POST['author']);

		if ($author == null) {
			parent::returnWithErr('author is not valid.');
		}

		$verifiedTFeed = $_POST["verified_tfeed"];

		if ($author->role == "User") {
			if ($_POST["verified"] == 1 || $verifiedTFeed)
				parent::returnWithErr('author have not permission to verify.');
		}

		if ($verifiedTFeed) {

			$tFeed = $this->Mdl_TFeeds->get($verifiedTFeed);

			if ($tFeed == null)
				parent::returnWithErr('verifiedTFeed is not valid.');

			$this->Mdl_TFeeds->updateEx($verifiedTFeed, ['verified' => 1]);

			

			parent::returnWithoutErr("Translation has been done successfully.", $tFeed);
		}

		$this->load->model("Mdl_TFeeds");

		$tFeed = $this->Mdl_TFeeds->create(utfn_safeArray(array('author', 'verified', 'feed', 'content', 'title', 'category'), $_POST));

		if ($tFeed == null)	parent::returnWithErr($this->Mdl_TFeeds->latestErr);

		/*
			Created successfully .... 
		*/
		parent::returnWithoutErr("Translation has been done successfully.", $tFeed);

	}
}

?>