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
		parent::validateParams(array("user", "duration"));

		$this->load->model('Mdl_Users');
		$this->load->model('Mdl_Feeds');
		$this->load->model('Mdl_TFeeds');

		if ($_POST['duration'] == "")
			$_POST['duration'] = 1000;

		$user = null;

		if ($_POST['user'] != null)
			$user = $this->Mdl_Users->getFirst('id', $_POST['user']);

		$feeds = $this->Mdl_Feeds->getLatest($_POST['duration']);
		
		foreach ($feeds as $feed) {
			$feed->tfeeds = $this->Mdl_Feeds->getAllFromTable('tfeeds', 'feed = ' . $feed->id);

			foreach ($feed->tfeeds as $key => $tfeed) {
				if ($user != null) {
					if ($tfeed->language != $user->language && $tfeed->target_language != $user->language) {
						
						unset($feed->tfeeds[$key]);

						continue;
					}
				}

				$tfeed->draftFeeds = $this->Mdl_Feeds->getAllFromTable('draft_feeds', 'tfeed = ' . $tfeed->id);;
			}
		}

		$filteredFeeds = [];

		foreach($feeds as $feed) {
			if ($feed->tfeeds != null || count($feed->tfeeds) != 0)
				array_push($filteredFeeds, $feed);
		}


		parent::returnWithoutErr("Request has been listed successfully.", array(
			'cnt' => count($filteredFeeds),
			'feeds' => $filteredFeeds
		));
	}

	public function api_entry_get() {
		parent::validateParams(array('duration', "qbmsgid", "qbdlgid"));

		$this->load->model('Mdl_Feeds');
		$this->load->model('Mdl_TFeeds');
		$this->load->model('Mdl_Users');

		$_feeds = $this->Mdl_Feeds->getLatest($_POST['duration']);
		$feeds = [];
		$filteredFeeds = [];

		foreach ($_feeds as $key=>$feed) {
			if ($feed->qbmsgid == $_POST['qbmsgid'] && $feed->qbdlgid == $_POST['qbdlgid']) {
				$feeds[] = $feed;
			}
		}

		foreach ($feeds as $feed) {
			$feed->tfeeds = $this->Mdl_Feeds->getAllFromTable('tfeeds', 'feed = ' . $feed->id);

			foreach ($feed->tfeeds as $key => $tfeed) {
				//$tfeed->draftFeeds = $this->Mdl_Feeds->getAllFromTable('draft_feeds', 'tfeed = ' . $tfeed->id);



				if ($tfeed->draftFeeds != null && count($tfeed->draftFeeds)) {
					foreach ($tfeed->draftFeeds as $dfeed) {
						$dfeed->author = $this->Mdl_Users->get($dfeed->author);
					}
				}
			}

			$filteredFeeds[] = $feed;
		}

		parent::returnWithoutErr("Request has been listed successfully.", $filteredFeeds);
	}


	/*--------------------------------------------------------------------------------------------------------
		Create Request... 
		*** POST
	_________________________________________________________________________________________________________*/

	public function api_entry_create() {
		parent::validateParams(array("qbmsgid", "qbdlgid", 'language', 'sender', 'receivers', 'content'), true);

		if (!$_POST["qbmsgid"])
			parent::returnWithErr("qbmsgid is not valid.");

		if (!$_POST["qbdlgid"])
			parent::returnWithErr("qbdlgid is not valid.");

		$arrReceivers = explode(',', $_POST['receivers']);

		if ($arrReceivers[0] == null)
			parent::returnWithErr("Receivers are missing.");

		$feed = $this->Mdl_Feeds->create(utfn_safeArray(array('qbmsgid', 'qbdlgid', 'sender', 'language', 'receivers', 'content', 'title', 'category'), $_POST), $arrReceivers);

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
		parent::validateParams(array('author', 'tfeed', 'language', 'content'));

		$this->load->model('Mdl_Users');
		$this->load->model('Mdl_Feeds');
		$this->load->model('Mdl_TFeeds');


		$author = $this->Mdl_Users->getFirst('id', $_POST['author']);

		if ($author == null) {
			parent::returnWithErr('Author is not valid.');
		}

		$tfeed = $this->Mdl_Feeds->getEx($_POST['tfeed'], 'tfeeds');

		if ($tfeed == null) {
			parent::returnWithErr('tFeed is not valid.');
		}

		$feed = $this->Mdl_Feeds->getEx($tfeed->feed, 'feeds');

		/*
			This traslatedFeed has been already translated to English. so this request will be ignored.
		*/
		$language = strtolower($_POST['language']);

		if ($language == 'english') {
			if ($feed->eng_content != null) {
				parent::returnWithErr('This feed has been translated and verified to English. your translation has been rejected.');
			}
		}
		/*
			This feed has been translated and verified to target language.
		*/
		else if ($language == strtolower($tfeed->target_language)) {
			if ($feed->eng_content == null) {
				parent::returnWithErr("This feed isn't translated to English yet. How did you translate this?");
			}
			if ($tfeed->trans_content != null ) {
				parent::returnWithErr("This feed has been translated and verified to " . $tfeed->target_language . ". your translation has been rejected.");
			}
		}
		else {
			parent::returnWithErr('Wrong language for this feed.');
		}

		$_POST['feed'] = $feed->id;


		$draftFeed = $this->Mdl_Feeds->createDraftFeed($_POST);

		/*
			Created successfully .... 
		*/
		parent::returnWithoutErr("Translation has been saved draftly.", $draftFeed);
	}

	public function api_entry_verify() {
		parent::validateParams(array('verifier', 'draftFeed'));

		$this->load->model('Mdl_Users');

		/*
		if ($_POST["verified"] != 1 && $_POST["verified"] != 0) {
			parent::returnWithErr('Verified field should be 0 or 1');
		}
		*/

		$verifier = $this->Mdl_Users->getFirst('id', $_POST['verifier']);

		if ($verifier->role == "User") {
			parent::returnWithErr("User doesn't have permission to verify feed translation.");
		}

		$draftFeed = $this->Mdl_Feeds->getEx($_POST['draftFeed'], 'draft_feeds');

		if ($draftFeed == null) {
			parent::returnWithErr('Draft feed id is not valid.');
		}

		$tfeed = $this->Mdl_Feeds->getEx($draftFeed->tfeed, 'tfeeds');

		if ($tfeed == null) {
			parent::returnWithErr('tFeed is not valid.');
		}

		$feed = $this->Mdl_Feeds->getEx($tfeed->feed, 'feeds');

		if ($feed == null) {
			parent::returnWithErr('Feed is not valid.');
		}

		/*
			If the draft feed is english then we will set this content as tfeed's eng_content.
		*/
		if (strtolower($draftFeed->language) == 'english') {
			if ($feed->eng_content != null) {
				parent::returnWithErr("This feed already has been verified. Verification request has been rejected.");
			}

			$this->Mdl_Feeds->updateWithTableEx(['id' => $draftFeed->feed], ['eng_content' => $draftFeed->content], 'feeds');
			$this->Mdl_Feeds->updateWithTableEx([
				'feed' => $draftFeed->feed,
				'target_language' => 'English'
				], ['trans_content' => $draftFeed->content], 'tfeeds');
		}
		else if (strtolower($draftFeed->language) == strtolower($tfeed->target_language)) {
			if ($tfeed->trans_content != null) {
				parent::returnWithErr("This feed already has been verified. Verification request has been rejected.");
			}

			if ($feed->eng_content == null) {
				parent::returnWithErr("This feed isn't translated to English yet. How did you translate this feed?");
			}

			$this->Mdl_Feeds->updateWithTable($draftFeed->tfeed, ['trans_content' => $draftFeed->content], 'tfeeds');
		}
		else {
			parent::returnWithErr("This draft feed doesn't match with original feed. How did you translate this feed?");
		}

		$this->Mdl_Feeds->updateWithTable($draftFeed->id, ['verified' => 1], 'draft_feeds');

		parent::returnWithoutErr("Translation has been verified successfully.", null);
	}
}

?>