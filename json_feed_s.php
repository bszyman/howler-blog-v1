<?php

include_once "common/controller.php";
include_once "models/post.php";

class JsonFeedController extends Controller
{
    function __construct()
    {
        parent::__construct();

        if (!empty($_GET["page_size"])) {
            $page_size = (int)$_GET["page_size"];

            if ($page_size > 100) {
                $page_size = 100;
            } else if ($page_size < 1) {
                $page_size = 1;
            }
        } else {
            $page_size = 50;
        }

        $page_number = parent::getSafePageNumber();
        $post_set = PostStore::paginateForFeed2($page_number, $page_size);

        if ($post_set !== null) {
            header("Content-Type: application/json");
            echo json_encode($post_set->posts);
        } else {
            return http_response_code(500);
        }
    }
}

$page = new JsonFeedController();
