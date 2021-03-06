<?php

defined('BASEPATH') OR exit('No direct script access allowed');

function utfn_gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,
        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function utfn_buildBaseParam($arrRecord, $user) {
	$arrRecord['addedby'] = $user;
	$arrRecord['changedby'] = $user;

	$date = date_create();

	$arrRecord['added'] = date_format($date, 'Y-m-d H:i:s');
	$arrRecord['changed'] = date_format($date, 'Y-m-d H:i:s');

	return $arrRecord;
}

function utfn_safeArray($argNames, $argSrc) {
    $safeArgs = array();

    foreach($argNames as $val) {
      if (isset($argSrc[$val]))
        $safeArgs[$val] = $argSrc[$val];
    }

    return $safeArgs;
}

function utfn_validteRole($role) {
    $arrRoles = unserialize(MZUSER_ROLES);

    foreach ($arrRoles as $validRole) {
        if ($role == $validRole)
            return true;
    }

    return false;
}

?>