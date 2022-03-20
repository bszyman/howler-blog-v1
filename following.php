<?php

include_once "common/controller.php";
include_once "common/paginator.php";
include_once "common/csrf_protect.php";
include_once "models/friend.php";
include_once "common/OpenGraph.php";

class FollowingController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $action = parent::getAction();

        switch ($action)
        {
            case "add":
                $this->addFriend();
                break;
            case "remove":
                $this->removeFriend();
                break;
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        $page_number = parent::getSafePageNumber();

        if (parent::userIsLoggedIn()) {
            $paginated_friends = FriendStore::paginate($page_number, 20);
            $csrf_token = CSRFProtect::generateToken("following");
        } else {
            if ($this->site->include_following)
            {
                $paginated_friends = FriendStore::paginate($page_number, 20);
                $csrf_token = "";
            } else {
                http_response_code(404);
                exit;
            }
        }

        if (!$paginated_friends) {
            http_response_code(500);
            exit;
        }

        $_SESSION["browse_following_page"] = $page_number;

        $paginator = new Paginator();
        $paginator->setCurrentPage($page_number);
        $paginator->setMaxPage($paginated_friends->count);

        $page_list = $paginator->getPageRange();

        $params = array(
            "following_nav_selected" => "navigationGroupActiveLink",
            "friends" => $paginated_friends->items_list,
            "current_page" => $page_number,
            "num_pages" => $paginated_friends->count,
            "page_list" => $page_list,
            "csrf_token" => $csrf_token,
            "user_logged_in" => parent::userIsLoggedIn(),
            "site_config" => $this->site
        );

        $template = parent::getTemplate("following.phtml");
        $template->display($params);
    }

    function addFriend(): void
    {
        if (!parent::userIsLoggedIn())
        {
            http_response_code(503);
            exit;
        }

        $csrf_key = CSRFProtect::getTokenKeyForPageNamed("following");

        if (!empty($_COOKIE[$csrf_key]) &&
            !empty($_POST["csrf_token"]))
        {
            $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

            if ($csrf_check)
            {
                $friend_url = $_POST["url"];
                echo $friend_url;
                $friend = new Friend();

                $open_graph_info = OpenGraph::fetch($friend_url);

                if (!empty($open_graph_info->title)) {
                    $friend->name = $open_graph_info->title;
                }

                if (!empty($open_graph_info->url)) {
                    $friend->url = $open_graph_info->url;
                } else {
                    $friend->url = $friend_url;
                }

                $temporary_location = "images/friends/$friend->uuid";
                $fp = fopen($temporary_location, "w");

                if (!empty($open_graph_info->image))
                {
                    $ch = curl_init($open_graph_info->image);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    curl_exec($ch);
                    curl_close($ch);

                    fclose($fp);

                    $curl_filetype = curl_getinfo($ch,  CURLINFO_CONTENT_TYPE);
                    echo "file type - $curl_filetype";

                    if ($curl_filetype === "image/png") {
                        $file_extension = "png";
                    } else if ($curl_filetype === "image/jpg") {
                        $file_extension = "jpg";
                    } else if ($curl_filetype === "image/jpeg") {
                        $file_extension = "jpg";
                    } else {
                        $file_extension = "";
                    }

                    rename($temporary_location, "$temporary_location.$file_extension");

                    $friend->image_file = "/$temporary_location.$file_extension";
                }

                $result = FriendStore::persist($friend);

                header("Location: /following/");
            }
        }
    }

    function removeFriend(): void
    {
        if (!parent::userIsLoggedIn())
        {
            http_response_code(503);
            exit;
        }

        $csrf_key = CSRFProtect::getTokenKeyForPageNamed("following");

        if (!empty($_COOKIE[$csrf_key]) &&
            !empty($_POST["csrf_token"]))
        {
            $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

            if ($csrf_check)
            {
                $result = FriendStore::deleteWithID($_POST["friend_id"]);

                header("Location: /following/");
            }
        }
    }
}

$page = new FollowingController();
