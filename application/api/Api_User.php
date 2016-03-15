<?php

//  application/core/MY_Controller.php
class Api_User extends Api_Unit {

  public function __construct(){
    parent::__construct();

    $this->ctrlName = 'Users';

    $this->load->model('Mdl_Users', '', TRUE);
    $this->load->library('Qbhelper');
	
	$this->load->helper("email");
  }


/*########################################################################################################################################################
  API Entries
########################################################################################################################################################*/

  /*--------------------------------------------------------------------------------------------------------
  User list for admin panel...
  _________________________________________________________________________________________________________*/
  public function api_entry_list() {
	  parent::validateParams(array("rp", "page", "query", "qtype", "sortname", "sortorder"));
	  
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
/*
	echo json_encode(array(
      'page'=>$_POST['page'],
      'total'=>$this->Mdl_Users->get_length(),
      'rows'=>$data,
    ));
*/
  }

  /*--------------------------------------------------------------------------------------------------------
    Sign up...
  _________________________________________________________________________________________________________*/
	public function api_entry_signup() {
		parent::validateParams(array("username", "email", "fullname", "password", "bday", "sex"));

		$qbToken = $this->qbhelper->generateSession();

		if ($qbToken == null || $qbToken == "")             parent::returnWithErr("Generating QB session has been failed.");

		$qbSession = $this->qbhelper->signupUser(
		  $qbToken,
		  $_POST['username'],
		  $_POST['email'],
		  QB_DEFAULT_PASSWORD
		);
		/*

		*/
		if ($qbSession == null)
		  parent::returnWithErr($this->qbhelper->latestErr);

		$newUser = $this->Mdl_Users->signup(
		  $_POST['username'],
		  $_POST['email'],
		  md5($_POST['password']),
		  $_POST['fullname'],
		  $_POST['bday'],
		  $_POST['sex'],
		  $qbSession
		);

		if ($newUser == null) {
		  parent::returnWithErr($this->qbhelper->latestErr);
		}

		$newUser['token'] = $hash = hash('tiger192,3', $newUser['username'] . date("y-d-m-h-m-s"));
		$baseurl = $this->config->base_url();

		$this->load->model('Mdl_Tokens');
		$this->Mdl_Tokens->create(array(
		  "token" => $hash,
		  "user" =>  $newUser['id']
		  ));
		
		$email = mh_loadVerificationEmailTemplate($this, $newUser);
				  
				  
				  
		mh_send([$newUser["username"]], "Please verify your account.", $email);
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

    parent::returnWithoutErr("Signin succeed.", $user);
  }

  public function api_entry_authqb() {
    parent::validateParams(array('token', 'qbid'));

    $users = $this->Mdl_Users->getAll("qbid", $_POST["qbid"]);

    $user = $this->Mdl_Users->signin($_POST["qbid"], $_POST["token"]);

    parent::returnWithoutErr("Authenticated in QB.", $user);
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

    $hash = hash('tiger192,3', $user->username . date("y-d-m-h-m-s"));
    $baseurl = $this->config->base_url();

    $this->load->model('Mdl_Tokens');
    $this->Mdl_Tokens->create(array(
      "token" => $hash,
      "user" => $user->id
      ));


    $content = '
    <html><head><base target="_blank">
        <style type="text/css">
        ::-webkit-scrollbar{ display: none; }
        </style>
        <style id="cloudAttachStyle" type="text/css">
        #divNeteaseBigAttach, #divNeteaseBigAttach_bak{display:none;}
        </style>
                    <style type="text/css">
                        img {
                            border: 0;
                            height: auto;
                            outline: none;
                            text-decoration: none;
                        }

                        body {
                            height: 100% !important;
                            margin: 0;
                            padding: 0;
                            width: 100% !important;
                        }

                        img {
                            -ms-interpolation-mode: bicubic;
                        }

                        .ReadMsgBody {
                            width: 100%;
                        }

                        .ExternalClass {
                            width: 100%;
                        }

                        body {
                            -ms-text-size-adjust: 100%;
                            -webkit-text-size-adjust: 100%;
                        }

                        .ExternalClass {
                            line-height: 100%;
                        }

                        img {
                            max-width: 100%;
                        }

                        body {
                            -webkit-font-smoothing: antialiased;
                            -webkit-text-size-adjust: none;
                            width: 100% !important;
                            height: 100%;
                            line-height: 1.6;
                        }

                        body {
                            background-color: #f3f3f3;
                        }

                        img {
                            border-radius: 12px;
                        }

                        img {
                            width: 100%;
                        }

                        _media screen and (min-width: 768px) {
                            [class="emailContainer"] {
                                width: 585px !important;
                            }

                            #emailLogo {
                                max-width: 200px;
                            }

                            #emailPreview {
                                max-width: 440px;
                            }

                            [class="flexibleColumn"] {
                                width: 50% !important;
                            }

                            [class="flexibleGrid"] {
                                width: 33% !important;
                            }
                        
                        }

                        _media screen and (max-width: 768px) {
                            [id="emailPreview"] {
                                max-width: 100% !important;
                                width: 100% !important;
                            }

                            [id="emailLogo"] {
                                max-width: 100% !important;
                                width: 100% !important;
                            }

                            [class="flexibleColumn"] {
                                max-width: 50% !important;
                                width: 100% !important;
                            }

                            [class="flexibleGrid"] {
                                max-width: 33% !important;
                            }

                            [id="bodyTable"] {
                                width: 100% !important;
                            }

                            [id="bodyCell"] {
                                width: 100% !important;
                            }

                            [class="emailContainer"] {
                                width: 100% !important;
                            }

                            [id="emailPreview"] {
                                max-width: 100% !important;
                                width: 100% !important;
                            }

                            [id="emailLogo"] {
                                max-width: 100% !important;
                                width: 100% !important;
                            }

                            [id="previewContent"] {
                                text-align: center !important;
                            }

                            [id="logoContent"] {
                                text-align: center !important;
                            }

                            [id="logo"] {
                                text-align: center !important;
                            }

                            [class="cta-blue"] {
                                padding: 0 !important;
                            }

                                [class="cta-blue"] a {
                                    padding: 10px 40px !important;
                                }

                            [class="cta-blue-gradient"] {
                                padding: 0 !important;
                            }

                                [class="cta-blue-gradient"] a {
                                    padding: 15px 60px !important;
                                }
                       span[class="spnText"] {display:block !important; word-wrap:break-word !important; width:245px !important; padding:0 7px !important; margin:0 auto !important;}
                            span[class="spnText1"] {display:block !important; word-wrap:break-word !important; width:255px !important; padding:0 2px !important;margin:0 auto !important;}
                            td[class="footer"] table {width: 320px !important; padding: 0 20px !important; }
                        }

                        _media only screen and (max-width: 480px) {
                            body {
                                width: 100% !important;
                                min-width: 100% !important;
                            }

                            [id="emailPreheader"] .emailContainer td {
                                padding-bottom: 0 !important;
                            }

                                [id="emailPreheader"] .emailContainer td.rightCol {
                                    padding: 10px 0 !important;
                                }

                            [class="flexibleColumn"] {
                                max-width: 100% !important;
                                width: 100% !important;
                            }

                                [class="flexibleColumn"] td {
                                    text-align: center !important;
                                    padding: 0 0 10px 0 !important;
                                }

                            [class="flexibleGrid"] {
                                max-width: 50% !important;
                            }

                            [class="footerContent"] br {
                                display: none !important;
                                line-height:10px !important;
                            }

                            [id="emailPreview"] {
                                display: none !important;
                                visibility: hidden !important;
                            }

                            [class="headerButton"] {
                                width: 50% !important;
                                padding-bottom: 15px !important;
                            }

                            [class="headerButtonContent"] {
                                font-size: 22px !important;
                                padding: 0 !important;
                            }

                                [class="headerButtonContent"] a {
                                    padding: 20px !important;
                                }

                            [id="emailGrid"] .emailContainer {
                                max-width: 80% !important;
                            }

                            [class="articleContent"] {
                                text-align: center !important;
                            }

                                [class="articleContent"] h3 {
                                    text-align: center !important;
                                }

                                [class="articleContent"] h5 {
                                    text-align: center !important;
                                }

                            [class="articleButton"] {
                                margin: 0 auto !important;
                                width: 50% !important;
                            }

                            [class="articleButtonContent"] {
                                font-size: 22px !important;
                                padding: 0 !important;
                            }

                                [class="articleButtonContent"] a {
                                    padding: 20px !important;
                                }
                            span[class="spnText"] {display:block !important; word-wrap:break-word !important; width:245px !important; padding:0 7px !important; margin:0 auto !important;}
                            span[class="spnText1"] {display:block !important;  width:258px !important; padding:0 2px !important;margin:0 auto !important;}
                            td[class="foot