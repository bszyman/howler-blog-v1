<?php

include_once "common/controller.php";
include_once "common/csrf_protect.php";
include_once "models/authuser.php";

class AuthenticationLogoutController extends Controller
{
	function __construct()
	{
		parent::__construct();
        $action = parent::getAction();
		
		parent::logout();
		session_start();
		
		switch ($action) {
			default: {
				$this->render();
				break;
			}
		}
	}
	
	function render(): void
	{
		$csrf_token = CSRFProtect::generateToken("webapplogout");
		
		$params = array("csrf_token" => $csrf_token);
		
		$template = parent::getTemplate("logout.phtml");		
		$template->display($params);
	}
}

$page = new AuthenticationLogoutController();
