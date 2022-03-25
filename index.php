<?php

include_once "common/controller.php";
include_once "common/paginator.php";
include_once "common/csrf_protect.php";
include_once "models/post.php";

class IndexController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $action = parent::getAction();

        switch ($action)
        {
            case "savePost": {
                if (parent::userIsLoggedIn()) {
                    $this->createPost();
                }
            }
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        $page_number = parent::getSafePageNumber();

        if (parent::userIsLoggedIn()) {
            $paginated_posts = PostStore::paginate($page_number, 20, true);
            $csrf_token = CSRFProtect::generateToken("blog_index");
        } else {
            if (!$this->site->include_feed)
            {
                http_response_code(404);
                exit;
            }

            $paginated_posts = PostStore::paginate($page_number, 20);
            $csrf_token = "";
        }

        if (!$paginated_posts) {
            http_response_code(500);
            exit;
        }

        $_SESSION["browse_index_page"] = $page_number;

        $paginator = new Paginator();
        $paginator->setCurrentPage($page_number);
        $paginator->setMaxPage($paginated_posts->count);

        $page_list = $paginator->getPageRange();

        $params = array(
            "feed_nav_selected" => "navigationGroupActiveLink",
            "posts" => $paginated_posts->items_list,
            "current_page" => $page_number,
            "num_pages" => $paginated_posts->count,
            "page_list" => $page_list,
            "csrf_token" => $csrf_token,
            "user_logged_in" => parent::userIsLoggedIn(),
            "site_config" => $this->site
        );

        $template = parent::getTemplate("index.phtml");
        $template->display($params);
    }

    function createPost(): void
    {
        if (!parent::userIsLoggedIn()) {
            parent::redirectToLogin();
        } else {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("blog_index");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    $post = new Post();

                    if (!empty($_POST["post_text"])) {
                        $post->post_text = $_POST["post_text"];
                    }

                    $post->published = (int)!empty($_POST["published"]);
                    $post->public = (int)!empty($_POST["public"]);
                    $post->user = parent::loggedInUser()->id;

                    $res = PostStore::persist($post);

                    if ($res) {
                        header("Location: /");
                    }
                }
            }
        }
    }
}

$page = new IndexController();

