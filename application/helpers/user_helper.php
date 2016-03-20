<?php
defined('BASEPATH') OR exit('No direct script access allowed');


function usrfn_getFullUserInfoFromID($id, $userController) {
	$userController->load->model('Mdl_Users');

	$user = $userController->Mdl_Users->getEx($id);

	unset($user->password);
	unset($user->user);

	return $user;
}


function usrfn_getUserInfoFromID($id, $userController) {
	$userController->load->model('Mdl_Users');

	$user = $userController->Mdl_Users->get($id);

	unset($user->password);

	return $user;
}


?>