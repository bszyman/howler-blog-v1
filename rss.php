<?php

include_once "common/controller.php";
include_once "models/site.php";
include_once "models/post.php";

class RSSFeedController extends Controller
{
    function __construct()
    {
        parent::__construct();
        $this->render();
    }

    function render(): void
    {
        if (!$this->site->allow_rss_feed)
        {
            http_response_code(404);
            exit;
        }

        $posts = PostStore::mostRecent();

        if ($this->site->site_name) {
            $feed_title = $this->site->site_name;
        } else {
            $bio_name = $this->site->bio_name;
            $feed_title = "$bio_name\'s Microblog";
        }

        if ($this->site->bio) {
            $feed_description = $this->site->bio;
        } else {
            $bio_name = $this->site->bio_name;
            $feed_description = "$bio_name\'s Microblog";
        }

        $params = array(
            "posts" => $posts,
            "feed_title" => $feed_title,
            "feed_description" => $feed_description,
            "site_config" => $this->site
        );

        $template = parent::getTemplate("rss.phtml");
        $template->display($params);
    }
}

$page = new RSSFeedController();
