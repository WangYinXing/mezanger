<?php

//  application/core/MY_Controller.php
class Api_User extends Api_Unit {

  public function __construct(){
    parent::__construct();

    $this->ctrlName = 'Users';

    $this->load->model('Mdl_Users', '', TRUE);
    $this->load->library('Qbhelper');
    
    $this->load->helper("email");
    $this->load->helper("utility");
    $this->load->helper("user");

    date_default_timezone_set('America/Los_Angeles');
  }


/*########################################################################################################################################################
  API Entries
########################################################################################################################################################*/

  /*--------------------------------------------------------------------------------------------------------
  User list for admin panel...
  _________________________________________________________________________________________________________*/
  public function api_entry_list() {
      parent::validateParams(array("rp", "page", "query", "qtype", "sortname", "sortorder"));

      if ($_POST['page'] <= 0) 
        parent::returnWithErr("Page number should be larger than 0.");
      //if ($_POST["qtype"] != "" && $_POST["qtype"] != "username" && $_POST["qtype"] != "email")
      //  parent::returnWithErr("Unknown qtype. it should be email or username.");
      
        $data = $this->Mdl_Users->get_list(
          $_POST['rp'],
          $_POST['page'],
          $_POST['query'],
          $_POST['qtype'],
          $_POST['sortname'],
          $_POST['sortorder']);

        parent::returnWithoutErr("Succeed to list.", array(
            'page'=>$_POST['page'],
            'total'=>$this->Mdl_Users->get_length(),
            'rows'=>$data,
    ));
  }

  public function api_entry_contacts() {
    parent::validateParams(array("user", "mode", "qtype", "query"));

    if ($_POST["mode"] != "friendsonly" && $_POST["mode"] != "nonfriendsonly") {
        parent::returnWithErr("Invalid mode. mode should be 'friendsonly' or 'nonfriendsonly'.");
    }

    $users = $this->Mdl_Users->getAll("id", $_POST["user"]);
    $contacts = $this->Mdl_Users->getAllEx([], [$_POST['qtype'] => $_POST['query']]);

    if (count($users) == 0)
        parent::returnWithErr("Invalid user id.");

    $user = $users[0];

    $arrFriends = json_decode($user->friends);

    $friends = [];


    if (count($contacts)) {
        foreach ($contacts as $contact) {
            if ($_POST["mode"] == "friendsonly") {
                if(count($arrFriends)) {
                    foreach ($arrFriends as $friendID) {
                        if ($contact->id == $friendID) {
                            $friends[] = $contact;
                        }
                    }
                }
            }
            else if ($_POST["mode"] == "nonfriendsonly") {
                $isFriend = false;

                if(count($arrFriends)) {
                    foreach ($arrFriends as $friendID) {
                        if ($contact->id == $friendID || $contact->id == $_POST["user"]) {
                            $isFriend = true;
                            break;
                        }
                    }
                }

                if (!$isFriend)
                    $friends[] = $contact;
            }
            
        }
    }

    parent::returnWithoutErr("Succeed to list.", array(
        'count' => count($friends),
        'friends' => $friends
        ));
}

  /*--------------------------------------------------------------------------------------------------------
    Sign up...
  _________________________________________________________________________________________________________*/
    public function api_entry_signup() {
        $arrFields = array("username", "email", "fullname", "password", "bday", "sex", "language", "mobile_number", "landline_number");

        $_arrFields = array("username", "email", "fullname", "password", "bday", "sex", "language", "preferred_language", "mobile_number", "landline_number");

        parent::validateParams($arrFields);

        $qbToken = $this->qbhelper->generateSession();

        if ($qbToken == null || $qbToken == "")             parent::returnWithErr("Generating QB session has been failed.");

        $qbSession = $this->qbhelper->signupUser(
          $qbToken,
          $_POST['username'],
          $_POST['email'],
          QB_DEFAULT_PASSWORD
        );
        
        if ($qbSession == null)
          parent::returnWithErr($this->qbhelper->latestErr);

        $arg = utfn_safeArray($_arrFields, $_POST);

        $newUser = $this->Mdl_Users->signup($arg, $qbSession);

        if ($newUser == null) {
          parent::returnWithErr($this->Mdl_Users->latestErr);
        }

        
        

        $newUser['token'] = $hash = hash('tiger192,3', $newUser['username'] . date("y-d-m-h-m-s"));
        $baseurl = $this->config->base_url();

        $this->load->model('Mdl_Tokens');
        $this->Mdl_Tokens->create(array(
          "token" => $hash,
          "user" =>  $newUser['id']
          ));
        
        $email = mh_loadVerificationEmailTemplate($this, $newUser);     
                  
        mh_sendViaMailgun([$newUser["email"]], "Please verify your account.", $email);
        //mh_send(["wangyinxing19@gmail.com"], "Please verify your account.", $email);
        
        /*
          Now we should register qb user at first.....
        */
        parent::returnWithoutErr("User has been created successfully. Please verfiy your account from verification email.", $newUser);
  }

  /*--------------------------------------------------------------------------------------------------------
    Sign in...
  _________________________________________________________________________________________________________*/
  public function api_entry_signin() {
    //parent::returnWithErr("Opps. ipray service is expired... sorry.");
    parent::validateParams(array('email', 'password'));

    $users = $this->Mdl_Users->getAll("email", $_POST["email"]);

    if (count($users) == 0) parent::returnWithErr("User not found.");

    $user = $users[0];

    if (!$user->verified)                               parent::returnWithErr("This account is not verified yet.");
    if ($user->suspended)                               parent::returnWithErr("This account is under suspension.");
    if ($user->password != md5($_POST["password"]))     parent::returnWithErr("Invalid password.");

    $user->password = '';

    parent::returnWithoutErr("Signin succeed.", $user);
  }

  public function api_entry_authqb() {
    parent::validateParams(array('token', 'qbid'));

    $users = $this->Mdl_Users->getAll("qbid", $_POST["qbid"]);

    $user = $this->Mdl_Users->signin($_POST["qbid"], $_POST["token"]);

    if ($user == null)
        parent::returnWithErr("Authentication failed.");

    parent::returnWithoutErr("Authenticated in QB.", $users[0]);
  }

  /*--------------------------------------------------------------------------------------------------------
    Sign out...
  _________________________________________________________________________________________________________*/
  public function api_entry_signout() {
    parent::validateParams(array('user'));

    if (!$this->Mdl_Users->get($_POST["user"])) parent::returnWithErr("User id is not valid.");
    
    $this->Mdl_Users->signout($_POST["user"]);

    parent::returnWithoutErr("Signout succeed.");
  }

  /*--------------------------------------------------------------------------------------------------------
    Sign out...
  _________________________________________________________________________________________________________*/
  public function api_entry_forgotpassword() {
    parent::validateParams(array('email'));

    $users = $this->Mdl_Users->getAll("email", $_POST["email"]);

    if (!($user = $users[0])) parent::returnWithErr("User email is not valid.");

    /*
    $hash = hash('tiger192,3', $user->username . date("y-d-m-h-m-s"));
    $baseurl = $this->config->base_url();

    $this->load->model('Mdl_Tokens');
    $this->Mdl_Tokens->create(array(
      "token" => $hash,
      "user" => $user->id
      ));
    */
    $newPassword = $this->Mdl_Users->resetPassword($_POST["email"]);

    if ($newPassword == null) {
        parent::returnWithErr("Failed to reset password.");
    }


    $content = '
        <html></html>
        <body>
            <div>You password has been reset.</div>
            <b>' . $newPassword . '</b>
            <div>Thanks.</div>
        </body>
        ';


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:key-061710f7633b3b2e2971afade78b48ea');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, 
          'https://api.mailgun.net/v3/sandboxa8b6f44a159048db93fd39fc8acbd3fa.mailgun.org/messages');
    curl_setopt($ch, CURLOPT_POSTFIELDS, 
            array('from' => 'noreply@mezanger.com <postmaster@mezanger.com>',
                  'to' => $user->username . ' <' . $user->email . '>',
                  'subject' => "You have forgot your passowrd.",
                  'html' => $content));
    $result = curl_exec($ch);
    curl_close($ch);
  }

  /*--------------------------------------------------------------------------------------------------------
    Submit device token, udid
  _________________________________________________________________________________________________________*/
  public function api_entry_subscribeAPN() {
    parent::validateParams(array('user', 'devicetoken', 'platform'));

    if ($_POST['platform'] != "android" && $_POST['platform'] != "ios") {
        parent::returnWithErr("Unknown platform.");
    }

    $users = $this->Mdl_Users->get($_POST["user"]);

    if (!$this->Mdl_Users->get($_POST["user"]))     parent::returnWithErr("User id is not valid.");

    $user = $this->Mdl_Users->update(array(
      'id' => $_POST["user"],
      'devicetoken' => $_POST["devicetoken"],
      'platform' => $_POST['platform']
      ));

    parent::returnWithoutErr("Subscription has been done successfully.", $user);
  }

  /*--------------------------------------------------------------------------------------------------------
    Make device token to void, udid
  _________________________________________________________________________________________________________*/
  public function api_entry_unsubscribeAPN() {
    parent::validateParams(array('user' ));

    $users = $this->Mdl_Users->get($_POST["user"]);

    if (!$this->Mdl_Users->get($_POST["user"]))     parent::returnWithErr("User id is not valid.");

    $user = $this->Mdl_Users->update(array(
      'id' => $_POST["user"],
      'devicetoken' => ''
      ));

    parent::returnWithoutErr("Unsubscription has been done successfully.", $user);
  }


  /*--------------------------------------------------------------------------------------------------------
    Get profile ..
  _________________________________________________________________________________________________________*/
  public function api_entry_getprofilefromqbid() {
    parent::validateParams(array('qbid'));

    $user = $this->Mdl_Users->getAll("qbid", $_POST["qbid"]);

    if (count($user) == 0  || $user[0] == null)
      parent::returnWithErr("QBID is not valid.");

    unset($user[0]->password);

    parent::returnWithoutErr("User profile fetched successfully.", $user[0]);
  }

  /*--------------------------------------------------------------------------------------------------------
    Get profile from qbid ..
  _________________________________________________________________________________________________________*/
  public function api_entry_getprofile() {
    parent::validateParams(array('user'));

    $user = usrfn_getFullUserInfoFromID($_POST['user'], $this);

    if ($user == null)
      parent::returnWithErr("User id is not valid.");
    

    parent::returnWithoutErr("User profile fetched successfully.", $user);
  }

  public function api_entry_getUserFromQBIDs() {
    parent::validateParams(array('users'));

    $users = explode(',', $_POST['users']);

    if ($users[0] == null)
        parent::returnWithErr("Users are missing.");

    $arrQBIDs = [];

    foreach ($users as $qbid) {
        $user = $this->Mdl_Users->getAll('qbid', $qbid);

        $userID = '';

        if ($user[0] != null) {
            $userID = $user[0];
        }

        $arrQBIDs[] = ['userid' => $userID, 'qbid' => $qbid];
    }

    parent::returnWithoutErr("Successfully fetched.", $arrQBIDs);
  }

  /*--------------------------------------------------------------------------------------------------------
    Set profile ..
  _________________________________________________________________________________________________________*/
  public function api_entry_setprofile() {
    parent::validateParams(array('user'));

    $user = $this->Mdl_Users->get($_POST["user"]);

    if ($user == null)
      parent::returnWithErr("User id is not valid.");


    /*
        Update basic user info...
    */
    $user = utfn_safeArray(array('username', 'email', 'fullname', 'password'), $_POST);

    $user['id'] = $_POST['user'];

    if (count($user) < 2)
      parent::returnWithErr("You should pass 1 profile entry at least to be updated.");

    $user = $this->Mdl_Users->update($user);

    /*
        Update profile....
    */
    $profile = utfn_safeArray(array('avatar', 'bday', 'sex', 'country', 'language', 'preferred_language', 'mobile_number','landline_number', 'role'), $_POST);

    $profile['user'] = $user->id;


    // Validate role.
    if (isset($profile['role'])) {
        if (!utfn_validteRole($profile['role']))
            parent::returnWithErr("Unknown role." . $profile['role']);
    }

    $this->load->model('Mdl_Profiles');

    $this->Mdl_Profiles->update($profile);

    // 
    $user = usrfn_getFullUserInfoFromID($user->id, $this);


    if ($user == null)
      parent::returnWithErr("Profile has not been updated.");

    parent::returnWithoutErr("Profile has been updated successfully.", $user);
  }


  /*--------------------------------------------------------------------------------------------------------
    Make friends ...
  _________________________________________________________________________________________________________*/

  public function api_entry_sendPN() {
    parent::validateParams(array('receiver', 'payload', 'pemFileName'));

    $receiver = $_POST['receiver'];
    $payload = $_POST['payload'];
    $pemFileName = $_POST['pemFileName'];

    $this->qbhelper->sendAPN($receiver, $payload, $pemFileName);
  }

  public function api_entry_sendnotification() {

    parent::validateParams(array('sender', 'receiver', 'subject'));

    if(!$this->Mdl_Users->get($_POST['sender']))    parent::returnWithErr("Sender is not valid");
    if(!$this->Mdl_Users->get($_POST['receiver']))    parent::returnWithErr("Receiver is not valid");

    $sender = $this->Mdl_Users->get($_POST['sender']);
    $receiver = $this->Mdl_Users->get($_POST['receiver']);

    unset($sender->password);
    unset($receiver->password);

    if    ($_POST['subject'] == "sendinvitation") {
      $msg = $sender->username . " has invited you.";
    }
    else if ($_POST['subject'] == "acceptinvitation") {
      $msg = $sender->username . " has accepted your invitation.";

      // sender ---> receiver 
      $this->Mdl_Users->makeFriends($_POST["sender"], $_POST["receiver"]);
    }
    else if ($_POST['subject'] == "rejectinvitation") {
      $msg = $sender->username . " has rejected your invitation.";
    }
    else if ($_POST['subject'] == 'sendprayrequest') {
      parent::validateParams(array('request'));
    }
    else if ($_POST['subject'] == 'acceptprayrequest') {
      parent::validateParams(array('request'));
    }
    else if ($_POST['subject'] == 'rejectprayrequest') {
      parent::validateParams(array('request'));
    }
    else {
      parent::returnWithErr("Unknown subject is requested.");
    }

    if (!isset($receiver->devicetoken) || $receiver->devicetoken == "")
      parent::returnWithErr("User is not available at this moment. Please try again later.");

    // Create notification record and get id for sending pushnotification.
    $this->load->model('Mdl_Notifications');
    
    $noti = $this->Mdl_Notifications->create(array(
        'subject' => $_POST['subject'],
        'message' => $msg,
        'sender' => $sender->id,
        'receiver' => $receiver->id
        ));


    $payloadForiOS = array(
      'sound' => "default",
      'subject' => $_POST['subject'],
      'alert' => $msg,
      'sender' => $sender,
      'receiver' => $receiver,
      'id' => $noti['id']
      );

    $payloadForAndroid = array(
            'message'   => $msg,
            //'title'     => $_POST['subject'],
            'subject'   => $_POST['subject'],
            'subtitle'  => '',
            'tickerText'    => '',
            'vibrate'   => 1,
            'sound'     => 1,
            'sender' => $sender,
            'receiver' => $receiver,
            'id' => $noti['id']
            //'largeIcon' => 'large_icon',
            //'smallIcon' => 'small_icon'
        );

    
    if ($receiver->platform == "ios") {
        if (($failedCnt = $this->qbhelper->sendAPN($receiver->devicetoken, json_encode($payloadForiOS))) == 0) {
          parent::returnWithoutErr("Contact request has been sent successfully.");
        }
        else {
          parent::returnWithErr($failedCnt . " requests have not been sent.");
        }    
    }
    else if ($receiver->platform == "android") {
        $result = $this->qbhelper->sendGCM($receiver->devicetoken, $payloadForAndroid);

        parent::returnWithoutErr($result);
    }
    
    
  }

  /*--------------------------------------------------------------------------------------------------------
    Pray ...
  _________________________________________________________________________________________________________*/
  public function api_entry_pray() {
    parent::validateParams(array('prayer', 'subject', 'request'));

    $this->load->model('Mdl_Requests');
    $this->load->model('Mdl_Prays');


    if(!($prayer = $this->Mdl_Users->get($_POST['prayer'])))      parent::returnWithErr("Prayer is not valid");
    if(!($request = $this->Mdl_Requests->get($_POST['request'])))   parent::returnWithErr("Request id is not valid");
    if(!($host = $this->Mdl_Users->get($request->host)))        parent::returnWithErr("Unknown request host.");

    if ($request->type != "REQ_COMMON")                 parent::returnWithErr("Invalid request type. " . $request->type);

    unset($prayer->password);
    unset($host->password);

    if ($host->id == $prayer->id)
      parent::returnWithErr("You can't pray for yourself.");

    if    ($_POST['subject'] == 'ipray_sendprayrequest') {
      $msg = $prayer->username . " would like to pray for you.";

      $sender = $prayer;
      $receiver = $host;
      $status = 0;
    }
    else if ($_POST['subject'] == 'ipray_answerprayrequest') {
      $msg = $host->username . " accepted your pray request.";

      $sender = $host;
      $receiver = $prayer;
      $status = 1;
    }
    else {
      parent::returnWithErr("Unknown subject is requested.");
    }

    if ($receiver->devicetoken == "" || !isset($receiver->devicetoken))
      parent::returnWithErr("User didn't subscribe.");

    $pray = $this->Mdl_Prays->create(array(
        'request' => $request->id,
        'prayer' => $prayer->id,
        'status' => $status
        ));


    $this->load->model('Mdl_Notifications');
    $noti = $this->Mdl_Notifications->create(array(
        'subject' => $_POST['subject'],
        'message' => $msg,
        'sender' => $sender->id,
        'receiver' => $receiver->id,
        'meta' => json_encode(array('request' => $request))
    ));


    $payload = array(
      'sound' => "default",
      'subject' => $_POST['subject'],
      'alert' => $msg,
      'sender' => $sender,
      'receiver' => $receiver,
      'request' => $request,
      'pray_id' => $pray['id'],
      'id' => $noti['id'],
      'meta' => json_encode(array('request' => $request))
      );



    if (($failedCnt = $this->qbhelper->sendPN($receiver->devicetoken, json_encode($payload))) == 0) {
      parent::returnWithoutErr("Contact request has been sent successfully.");
    }
    else {
      parent::returnWithErr($failedCnt . " requests have not been sent.");
    }
    
  }

}
?>