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
		parent::validateParams(array("rp", "page", "query", "qtype", "sortname", "sortorder"));

		$this->load->model("Mdl_Users");
		$this->load->model("Mdl_Feeds");
		$this->load->model("Mdl_TFeeds");


		$feeds = $this->Mdl_Feeds->get_list(
			$_POST['rp'],
			$_POST['page'],
			$_POST['query'],
			$_POST['qtype'],
			$_POST['sortname'],
			$_POST['sortorder']);


		foreach ($feeds as $key => $feed) {
			$feed->author = $this->Mdl_Users->get($feed->author);
			unset($feed->author->password);

			$feed->verified = false;
			
			$tFeedsVerified = $this->Mdl_TFeeds->getAllEx(["feed" => $feed->id, "verified" => 1]);

			if (count($tFeedsVerified)) {
				$feed->tFeeds = $tFeedsVerified;
				$feed->verified = true;
			}
			else {
				$tFeeds = $this->Mdl_TFeeds->getAll("feed", $feed->id);
				if (count($tFeeds) == 0)
					continue;

				foreach ($tFeeds as $key => $tFeed) {
					$user = $this->Mdl_Users->get($tFeed->author);

					$tFeed->author = array(
						'qbid' => $user->qbid,
						'id' => $user->id,
						'username' => $user->username,
						'email' => $user->email
						);
				}

				$feed->tFeeds = $tFeeds;
				$feed->tFeedsCnt = count($tFeeds);
			}
		}

		parent::returnWithoutErr("Request has been listed successfully.", array(
			'page'=>$_POST['page'],
			'total'=>$this->Mdl_Feeds->get_length(),
			'rows'=>$feeds,
		));
	}


	/*--------------------------------------------------------------------------------------------------------
		Create Request... 
		*** POST
	_________________________________________________________________________________________________________*/

	public function api_entry_create() {
		parent::validateParams(array("author", "content"));

		$feed = $this->Mdl_Feeds->create(utfn_safeArray(array('author', 'content', 'title', 'category'), $_POST));

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
		parent::validateParams(array("author", "feed", "content"));

		$this->load->model("Mdl_TFeeds");

		$tFeed = $this->Mdl_TFeeds->create(utfn_safeArray(array('author', 'feed', 'content', 'title', 'category'), $_POST));

		if ($tFeed == null)	parent::returnWithErr($this->Mdl_TFeeds->latestErr);

		/*
			Created successfully .... 
		*/
		parent::returnWithoutErr("Translation has been done successfully.", $tFeed);

	}
}

?>