<?php

include_once "common/controller.php";
include_once "common/paginator.php";
include_once "common/csrf_protect.php";
include_once "models/bookmark.php";
include_once "models/site.php";
include_once "common/OpenGraph.php";

class BookmarksController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $action = parent::getAction();

        switch ($action)
        {
            case "saveBookmark": {
                $this->createBookmark();
            }
            default: {
                $this->render();
            }
        }
    }

    function render(): void
    {
        $page_number = parent::getSafePageNumber();;

        if (parent::userIsLoggedIn()) {
            $paginated_bookmarks = BookmarkStore::paginate($page_number, 20, true);
            $csrf_token = CSRFProtect::generateToken("bookmarks");
        } else {
            if ($this->site->include_bookmarks)
            {
                $paginated_bookmarks = BookmarkStore::paginate($page_number, 20);
                $csrf_token = "";
            } else {
                http_response_code(404);
                exit;
            }
        }

        if (!$paginated_bookmarks) {
            http_response_code(500);
            exit;
        }

        $_SESSION["browse_bookmarks_page"] = $page_number;

        $paginator = new Paginator();
        $paginator->setCurrentPage($page_number);
        $paginator->setMaxPage(ceil($paginated_bookmarks->count/20));

        $page_list = $paginator->getPageRange();

        $params = array(
            "bookmarks_nav_selected" => "navigationGroupActiveLink",
            "bookmarks" => $paginated_bookmarks->items_list,
            "current_page" => $page_number,
            "num_pages" => $paginated_bookmarks->count,
            "page_list" => $page_list,
            "csrf_token" => $csrf_token,
            "user_logged_in" => parent::userIsLoggedIn(),
            "site_config" => $this->site
        );

        $template = parent::getTemplate("bookmarks.phtml");
        $template->display($params);
    }

    function createBookmark(): void
    {
        if (!parent::userIsLoggedIn()) {
            parent::redirectToLogin();
        } else {
            $csrf_key = CSRFProtect::getTokenKeyForPageNamed("bookmarks");

            if (!empty($_COOKIE[$csrf_key]) &&
                !empty($_POST["csrf_token"]))
            {
                $csrf_check = CSRFProtect::verifyToken($_COOKIE[$csrf_key], $_POST["csrf_token"]);

                if ($csrf_check)
                {
                    if (!empty($_POST["url"]))
                    {
                        $bookmark = new Bookmark();
                        $bookmark_url = $_POST["url"];

                        $open_graph_info = OpenGraph::fetch($bookmark_url);

                        if (!empty($open_graph_info->title)) {
                            $bookmark->title = $open_graph_info->title;
                        }

                        if (!empty($open_graph_info->description)) {
                            $bookmark->description = $open_graph_info->description;
                        }

                        if (!empty($open_graph_info->url)) {
                            $bookmark->url = $open_graph_info->url;
                        } else {
                            $bookmark->url = $bookmark_url;
                        }

                        $bookmark->public = (int)!empty($_POST["public"]);

                        $temporary_location = "images/bookmarks/$bookmark->uuid";
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

                            $bookmark->image_file = "/$temporary_location.$file_extension";
                        }

                        $res = BookmarkStore::persist($bookmark);

                        if ($res) {
                            header("Location: /bookmarks/");
                        }
                    }
                }
            }
        }
    }
}

$page = new BookmarksController();
