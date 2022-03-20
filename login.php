<?php

include_once "common/controller.php";
include_once "common/csrf_protect.php";
include_once "models/user.php";

class AuthenticationLoginController extends Controller
{
	function __construct()
	{	
		parent::__construct();
        $action = parent::getAction();
		
		switch ($action) {
			case "tryLogin": {
				$this->tryLogin();
				break;
			}
			case "tryLoginFromLogout": {
				$this->tryLoginFromLogout();
				break;
			}
			default: {
				$this->render();
				break;
			}
		}
	}
	
	function render(): void
	{
		$csrf_token = CSRFProtect::generateToken("webapplogin");
		
		$params = array("csrf_token" => $csrf_token,
						"form_errors" => $this->form_errors);
		
		$template = parent::getTemplate("login.phtml");		
		$template->display($params);
	}
	
	function performLogin(): void
	{
		if (!empty($_POST["username"]) && 
			!empty($_POST["password"]))
		{
			$username = $_POST["username"];
			$password = $_POST["password"];
			
			$login_result = AuthUserStore::verifyLogin($username, $password);
			
			if ($login_result) {
				$user = AuthUserStore::fetchWithEmailAddress($username);
                if ($user) {
                    parent::setLoginUser($user);
                    header("Location: /");
                } else {
                    parent::redirectToLogin();
                }
			}
		}
	}
	
	function tryLogin(): void
	{
		$csrf_key = CSRFProtect::getTokenKeyForPageNamed("webapplogin");
		$this->form_errors->incorrect_login = true;
		
		if (!empty($_COOKIE[$csrf_key]) &&
			!empty($_POST["csrf_token"]))
		{
			$csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);
			
			if ($csrf_check) 
			{
				$this->performLogin();
			}
		}
		
		$this->render();
	}
	
	function tryLoginFromLogout(): void
	{
		$csrf_key = CSRFProtect::getTokenKeyForPageNamed("webapplogout");
		$this->form_errors->incorrect_login = true;
		
		if (!empty($_COOKIE[$csrf_key]) &&
			!empty($_POST["csrf_token"]))
		{
			$csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);
			
			if ($csrf_check) 
			{
				$this->performLogin();
			}
		}
		
		$this->render();
	}
	
}

$page = new AuthenticationLoginController();
