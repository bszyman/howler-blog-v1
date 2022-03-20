<?php

include_once "common/controller.php";
include_once "common/csrf_protect.php";
include_once "models/site.php";
include_once "models/user.php";

class SiteSettingsController extends Controller
{
    function __construct()
    {
        parent::__construct();

        $action = parent::getAction();

        switch ($action)
        {
            case "update":
                $this->updateSite();
                break;
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        if (parent::userIsLoggedIn())
        {
            $site = SiteStore::fetchActiveConfig();
            $session_user = parent::loggedInUser();
            $user = UserStore::fetchWithID($session_user->id);

            if ($site !== null)
            {
                $csrf_token = CSRFProtect::generateToken("settings");
                $params = array(
                    "settings_nav_selected" => "navigationGroupActiveLink",
                    "site" => $site,
                    "user" => $user,
                    "csrf_token" => $csrf_token,
                    "user_logged_in" => parent::userIsLoggedIn(),
                    "site_config" => parent::siteConfig()
                );

                $template = parent::getTemplate("settings.phtml");
                $template->display($params);
            }
        } else
        {
            http_response_code(503);
            exit;
        }
    }

    function updateSite(): void
    {
        if (parent::userIsLoggedIn())
        {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("settings");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    $site = SiteStore::fetchActiveConfig();

                    if ($site !== null)
                    {
                        if (!empty($_POST["siteName"])) {
                            $site->site_name = $_POST["siteName"];
                        }

                        if (!empty($_FILES["profilePic"])) {
                            $filename = "profile-pic";
                            $extension = pathinfo($_FILES["profilePic"]["name"], PATHINFO_EXTENSION);
                            $generated_filename = "$filename.$extension";

                            $source = $_FILES["profilePic"]["tmp_name"];
                            $destination = $_SERVER['DOCUMENT_ROOT'] . "/images/$generated_filename";

                            move_uploaded_file($source, $destination);

                            $site->profile_pic = "/images/$generated_filename";
                        }

                        if (!empty($_POST["bioName"])) {
                            $site->bio_name = $_POST["bioName"];
                        }

                        if (!empty($_POST["bio"])) {
                            $site->bio = $_POST["bio"];
                        }

                        if (!empty($_POST["location"])) {
                            $site->location = $_POST["location"];
                        }

                        $site->include_feed = (int)!empty($_POST["useMicroblog"]);
                        $site->allow_rss_feed = (int)!empty($_POST["allowRSSFeeds"]);
                        $site->allow_atom_feed = (int)!empty($_POST["allowAtomFeeds"]);
                        $site->include_bookmarks = (int)!empty($_POST["useBookmarks"]);
                        $site->include_following = (int)!empty($_POST["useFollowing"]);

                        if (!empty($_POST["bitbucket"])) {
                            $site->social_bitbucket = $_POST["bitbucket"];
                        }

                        if (!empty($_POST["flickr"])) {
                            $site->social_flickr = $_POST["flickr"];
                        }

                        if (!empty($_POST["github"])) {
                            $site->social_github = $_POST["github"];
                        }

                        if (!empty($_POST["instagram"])) {
                            $site->social_instagram = $_POST["instagram"];
                        }

                        if (!empty($_POST["soundcloud"])) {
                            $site->social_soundcloud = $_POST["soundcloud"];
                        }

                        if (!empty($_POST["unsplash"])) {
                            $site->social_unsplash = $_POST["unsplash"];
                        }

                        if (!empty($_POST["vimeo"])) {
                            $site->social_vimeo = $_POST["vimeo"];
                        }

                        if (!empty($_POST["website"])) {
                            $site->social_website = $_POST["website"];
                        }

                        if (!empty($_POST["youtube"])) {
                            $site->social_youtube = $_POST["youtube"];
                        }

                        $res = SiteStore::persist($site);
                    }

                    $account = UserStore::fetchWithID(parent::loggedInUser()->id);

                    if ($account !== null)
                    {
                        if (!empty($_POST["fullName"])) {
                            $account->full_name = $_POST["fullName"];
                        }

                        if (!empty($_POST["emailAddress"])) {
                            $account->email = $_POST["emailAddress"];
                        }

                        $res = UserStore::persist($account);
                    }

                    header("Location: /settings/");
                }
            }
        } else {
            parent::redirectToLogin();
        }
    }

}

$page = new SiteSettingsController();

