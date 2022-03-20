<?php

session_start();

include_once "models/authuser.php";
include_once "models/site.php";
include_once "models/user.php";

class Controller 
{
	protected StdClass $form_errors;
    protected Site $site;
	
	function __construct()
	{
		$this->form_errors = new StdClass;
        $site = SiteStore::fetchActiveConfig();

        if (!SiteStore::checkIfInitialized()) {
            header("Location: /initialize/");
        }

        if (!$site) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . "Couldn't load site config.", 0);
            http_response_code(500);
            exit;
        } else {
            $this->site = $site;
        }
	}
	
	protected final function getTemplate(string $template_name)
	{
		require_once("vendor/autoload.php");
		$loader = new \Twig\Loader\FilesystemLoader("./templates");
		$twig = new \Twig\Environment($loader, []);
		
		return $twig->load($template_name);
	}
	
	protected final function getPageFromSession(string $page_key): string {
		return (!empty($_SESSION[$page_key])) ? $_SESSION[$page_key] : "1";
	}
	
	protected final function setLoginUser(AuthUser $user): void
	{
		// We can't just stash the $user object (of class AuthUser)
		// into the session, since PHP will not know how to
		// properly desearialize it. (...due to private fields
		// in the Model superclass)
		// Instead, we're going to take all important properties
		// and add them to a generic StdClass, then store it
		// into the session.
		
		$session_user = new StdClass;
		$session_user->id = $user->getID();
		$session_user->email = $user->email;
		$session_user->full_name = $user->full_name;
		$session_user->enabled = $user->enabled;
		$session_user->administrator = $user->administrator;
		
		$_SESSION["loggedin_user"] = $session_user;
	}
	
	protected final function loggedInUser(): StdClass
	{
		$refreshed_login_user = AuthUserStore::fetchWithEmailAddress($_SESSION["loggedin_user"]->email);
		if ($refreshed_login_user) {
            $this->setLoginUser($refreshed_login_user);
            return $_SESSION["loggedin_user"];
        } else {
            $this->redirectToLogin();
            exit;
        }
	}
	
	protected final function logout(): void
	{
		$_SESSION = array();
		
		if (ini_get("session.use_cookies")) {
		    $params = session_get_cookie_params();
		    setcookie(session_name(), '', time() - 42000,
		        $params["path"], $params["domain"],
		        $params["secure"], $params["httponly"]
		    );
		}
		
		session_destroy();
	}
	
	protected final function redirectToLogin(): void
	{
		$ini_conf = parse_ini_file("app_settings.ini", true);
		$auth_section = $ini_conf["authentication_settings"];
		$auth_url = $auth_section["auth_url"];
		
		header("Location: " . $auth_url);
	}

    protected final function redirectToInitialization(): void
    {
        header("Location: /initialize/");
    }
	
	protected final function displayNoAccess(): void
	{
		$params = array();
		$template = $this->getTemplate("no_access.phtml");
		$template->display($params);
	}
	
	protected final function userIsLoggedIn(): bool
	{
		if (!empty($_SESSION["loggedin_user"])) {
			// We are loggedInUser this here to make sure that the session
			// user object gets reloaded on each page load. This is 
			// so that permissions set in /system_users take immediate
			// effect.
			$this->loggedInUser();
			return true;
		} else {
			return false;
		}
	}

    protected final function siteIsInitialized(): bool
    {
        return SiteStore::checkIfInitialized();
    }

    protected final function siteConfig(): ?Site
    {
        return SiteStore::fetchActiveConfig();
    }
	
	protected final function userEnabled(): bool
	{
		$session_user = $this->loggedInUser();
		return $session_user->enabled;
	}
	
	protected final function hasAdministratorAccess(): bool
	{
		$session_user = $this->loggedInUser();	
		return $session_user->administrator;
	}

    protected final function getAction(): string
    {
        return !empty($_POST["action"]) ? $_POST["action"] : "nodef";
    }

    protected final function getSafePageNumber(): int
    {
        return !empty($_GET["page"]) ? (int)$_GET["page"] : 1;
    }
}
