<?php

include_once "common/controller.php";
include_once "common/csrf_protect.php";
include_once "models/post.php";
include_once "models/site.php";

class PostDetailController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $action = parent::getAction();

        switch ($action)
        {
            case "update":
                $this->updatePost();
                break;
            case "delete":
                $this->deletePost();
                break;
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        if (!$this->site->include_feed)
        {
            http_response_code(404);
            exit;
        }

        $post_id_param = (!empty($_GET["post_id"])) ? (int)$_GET["post_id"] : -1;

        if ($post_id_param > -1)
        {
            $post = PostStore::fetchWithID($post_id_param);

            if ($post !== null)
            {
                if (!parent::userIsLoggedIn()) {
                    if (!$post->published) {
                        http_response_code(404);
                        exit;
                    }
                }

                if (parent::userIsLoggedIn()) {
                    $csrf_token = CSRFProtect::generateToken("post_detail");
                } else {
                    $csrf_token = "";
                }

                $params = array(
                    "feed_nav_selected" => "navigationGroupActiveLink",
                    "post" => $post,
                    "csrf_token" => $csrf_token,
                    "user_logged_in" => parent::userIsLoggedIn(),
                    "site_config" => $this->site
                );

                $template = parent::getTemplate("post.phtml");
                $template->display($params);
            }
        }
    }

    function updatePost(): void
    {
        if (!parent::userIsLoggedIn()) {
            parent::redirectToLogin();
        } else {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("post_detail");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    $post = PostStore::fetchWithID($_POST["post_id"]);

                    if ($post !== null)
                    {
                        if (!empty($_POST["post_text"])) {
                            $post->post_text = $_POST["post_text"];
                        }

                        $post->published = (int)!empty($_POST["published"]);
                        $post->public = (int)!empty($_POST["public"]);

                        $res = PostStore::persist($post);

                        header("Location: /post/" . $_GET["post_id"] . "/");
                    }
                }
            }
        }
    }

    function deletePost(): void
    {
        if (parent::userIsLoggedIn()) {
            parent::redirectToLogin();
        } else {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("post_detail");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    $result = PostStore::deleteWithID($_POST["post_id"]);

                    if ($result) {
                        header("Location: /");
                    } else {
                        header("Location: /post/" . $_GET["post_id"] . "/");
                    }
                }
            }
        }
    }
}

$page = new PostDetailController();

