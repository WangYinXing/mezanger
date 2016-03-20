<?php

define('MZUSER_ROLES', serialize([
	// Normal user.. end-user who is unable to verify Translated-feed.
	"User",
	// User who is able to verify Translate-feed.
	"Translator",
	// Super user.
	"Administrator"
	]));


class Common {
	function __construct() {
				
	}
}

?>