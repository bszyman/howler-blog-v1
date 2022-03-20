<?php

include_once "common/controller.php";
include_once "common/csrf_protect.php";
include_once "models/site.php";
include_once "models/user.php";

class InitializationController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $action = parent::getAction();

        switch ($action)
        {
            case "initialize":
                $this->initializeSite();
                break;
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        $csrf_token = CSRFProtect::generateToken("initialize");

        $params = array(
            "csrf_token" => $csrf_token,
        );

        $template = parent::getTemplate("initialize.phtml");
        $template->display($params);
    }

    function initializeSite(): void
    {
        $csrf_key = CSRFProtect::getTokenKeyForPageNamed("initialize");

        if (!empty($_COOKIE[$csrf_key]) &&
            !empty($_POST["csrf_token"]))
        {
            $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

            if ($csrf_check)
            {
                if (parent::siteIsInitialized()) {
                    echo "This site has already been initialized.";
                    return;
                }

                $site = new Site();
                $user = new User();

                # Site Information
                if (!empty($_POST["siteName"])) {
                    $site->site_name = $_POST["siteName"];
                }

                # About You
                if (!empty($_POST["bioName"])) {
                    $site->bio_name = $_POST["bioName"];
                }

                if (!empty($_POST["bio"])) {
                    $site->bio = $_POST["bio"];
                }

                if (!empty($_POST["location"])) {
                    $site->location = $_POST["location"];
                }

                # Features
                $site->include_feed = (int)!empty($_POST["useMicroblog"]);
                $site->allow_rss_feed = (int)!empty($_POST["allowRSSFeeds"]);
                $site->allow_atom_feed = (int)!empty($_POST["allowAtomFeeds"]);
                $site->include_bookmarks = (int)!empty($_POST["useBookmarks"]);
                $site->include_following = (int)!empty($_POST["useFollowing"]);

                $created_site = SiteStore::persist($site);

                # Create Account
                if (!empty($_POST["emailAddress"])) {
                    $user->email = $_POST["emailAddress"];
                }

                if (!empty($_POST["fullName"])) {
                    $user->full_name = $_POST["fullName"];
                }

                $user->enabled = 1;
                $user->administrator = 1;

                $user_id = UserStore::persist($user);
                UserStore::createInitialPassword($user_id, $_POST["password"]);

                if ($created_site > -1 && $user_id > -1) {
                    header("Location: /");
                }
            } else {
                echo "Malformed Request";
            }
        }
    }
}

$page = new InitializationController();

