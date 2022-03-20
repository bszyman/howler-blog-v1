<?php

include_once "common/controller.php";
include_once "common/csrf_protect.php";
include_once "models/bookmark.php";
include_once "models/site.php";

class BookmarkDetailController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $action = parent::getAction();

        switch ($action)
        {
            case "update":
                $this->updateBookmark();
                break;
            case "delete":
                $this->deleteBookmark();
                break;
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        if (!$this->site->include_bookmarks)
        {
            http_response_code(404);
            exit;
        }

        $id_param = (!empty($_GET["id"])) ? (int)$_GET["id"] : -1;

        if ($id_param > -1)
        {
            $bookmark = BookmarkStore::fetchWithID($id_param);

            if ($bookmark !== null)
            {
                if (!parent::userIsLoggedIn()) {
                    if (!$bookmark->public) {
                        http_response_code(404);
                        exit;
                    }
                }

                if (parent::userIsLoggedIn()) {
                    $csrf_token = CSRFProtect::generateToken("bookmark_detail");
                } else {
                    $csrf_token = "";
                }

                $params = array(
                    "bookmarks_nav_selected" => "navigationGroupActiveLink",
                    "bookmark" => $bookmark,
                    "csrf_token" => $csrf_token,
                    "user_logged_in" => parent::userIsLoggedIn(),
                    "site_config" => $this->site
                );

                $template = parent::getTemplate("bookmark.phtml");
                $template->display($params);
            }
        }
    }

    function updateBookmark(): void
    {
        if (!parent::userIsLoggedIn()) {
            parent::redirectToLogin();
        } else {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("bookmark_detail");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    $bookmark = BookmarkStore::fetchWithID($_POST["bookmark_id"]);

                    if ($bookmark !== null)
                    {
                        if (!empty($_POST["title"])) {
                            $bookmark->title = $_POST["title"];
                        }

                        if (!empty($_POST["description"])) {
                            $bookmark->description = $_POST["description"];
                        }

                        if (!empty($_POST["url"])) {
                            $bookmark->url = $_POST["url"];
                        }

                        $bookmark->public = (int)!empty($_POST["public"]);

                        $res = BookmarkStore::persist($bookmark);

                        header("Location: /bookmark/" . $_POST["bookmark_id"] . "/");
                    }
                }
            }
        }
    }

    function deleteBookmark(): void
    {
        if (!parent::userIsLoggedIn()) {
            parent::redirectToLogin();
        } else {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("bookmark_detail");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    $result = BookmarkStore::deleteWithID($_POST["bookmark_id"]);

                    if ($result) {
                        header("Location: /bookmarks/");
                    } else {
                        header("Location: /");
                    }
                }
            }
        }
    }
}

$page = new BookmarkDetailController();

