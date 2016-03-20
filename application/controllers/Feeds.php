<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feeds extends Api_Feeds{
	function __construct() {
		parent::__construct();
		$this->load->helper('url');
	}
}

?>