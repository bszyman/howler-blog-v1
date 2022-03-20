<?php

include_once "common/model.php";
include_once "common/store.php";

class Site extends Model
{
    public string $site_name;
    public string $profile_pic;
    public string $bio_name;
    public string $bio;
    public string $location;
    public int $include_feed;
    public int $include_bookmarks;
    public int $include_following;
    public int $allow_rss_feed;
    public int $allow_atom_feed;
    public string $social_flickr;
    public string $social_instagram;
    public string $social_soundcloud;
    public string $social_unsplash;
    public string $social_vimeo;
    public string $social_website;
    public string $social_youtube;
    public string $social_bitbucket;
    public string $social_github;

    function __construct()
    {
        parent::__construct();

        $this->site_name = "";
        $this->profile_pic = "/images/profile-pic.jpg";
        $this->bio_name = "";
        $this->bio = "";
        $this->location = "";
        $this->include_feed = 1;
        $this->include_bookmarks = 1;
        $this->include_following = 1;
        $this->allow_rss_feed = 1;
        $this->allow_atom_feed = 1;
        $this->social_flickr = "";
        $this->social_instagram = "";
        $this->social_soundcloud = "";
        $this->social_unsplash = "";
        $this->social_vimeo = "";
        $this->social_website = "";
        $this->social_youtube = "";
        $this->social_bitbucket = "";
        $this->social_github = "";
    }
}

class SiteStore extends Store
{
    public static function checkIfInitialized(): bool
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("SELECT COUNT(created) as total_count FROM site");

            $db_result = $sql->execute();

            if ($db_result) {
                if ($page_count = $sql->fetchObject()) {
                    return ($page_count->total_count > 0);
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return false;
        }

        return false;
    }

    public static function fetchActiveConfig(): ?Site
    {
        $dbh = parent::dataSource();
        $site = null;

        try {
            $sql = $dbh->prepare("SELECT site_name, bio_name, bio,
                                        location,
                                        UNIX_TIMESTAMP(created) as created_f,
                                        UNIX_TIMESTAMP(updated) as updated_f,
                                        include_feed, include_bookmarks, include_following,
                                        allow_rss_feed, allow_atom_feed, social_flickr,
                                        social_instagram, social_soundcloud, social_unsplash,
                                        social_vimeo, social_website, social_youtube, 
                                        profile_pic, social_bitbucket, social_github
									FROM site
									ORDER BY created ASC
									LIMIT 0, 1");

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $site = new Site();
                    $site->setNewStatus(false);
                    $site->setDateCreated($db_record->created_f);
                    $site->setDateUpdated($db_record->updated_f);
                    $site->site_name = $db_record->site_name;
                    $site->bio_name = $db_record->bio_name;
                    $site->bio = $db_record->bio;
                    $site->location = $db_record->location;
                    $site->include_feed = $db_record->include_feed;
                    $site->include_bookmarks = $db_record->include_bookmarks;
                    $site->include_following = $db_record->include_following;
                    $site->allow_rss_feed = $db_record->allow_rss_feed;
                    $site->allow_atom_feed = $db_record->allow_atom_feed;
                    $site->social_flickr = $db_record->social_flickr;
                    $site->social_instagram = $db_record->social_instagram;
                    $site->social_soundcloud = $db_record->social_soundcloud;
                    $site->social_unsplash = $db_record->social_unsplash;
                    $site->social_vimeo = $db_record->social_vimeo;
                    $site->social_website = $db_record->social_website;
                    $site->social_youtube = $db_record->social_youtube;
                    $site->profile_pic = $db_record->profile_pic;
                    $site->social_bitbucket = $db_record->social_bitbucket;
                    $site->social_github = $db_record->social_github;
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $site;
    }

    public static function persist(Site $site): int
    {
        $site->updateTimeToNow();

        $sql_params = array(
            ":created" => $site->getDateCreatedTimestamp(),
            ":updated" => $site->getDateUpdatedTimestamp(),
            ":profile_pic" => $site->profile_pic,
            ":site_name" => $site->site_name,
            ":bio_name" => $site->bio_name,
            ":bio" => $site->bio,
            ":location" => $site->location,
            ":include_feed" => $site->include_feed,
            ":include_bookmarks" => $site->include_bookmarks,
            ":include_following" => $site->include_following,
            ":allow_rss_feed" => $site->allow_rss_feed,
            ":allow_atom_feed" => $site->allow_atom_feed,
            ":social_flickr" => $site->social_flickr,
            ":social_instagram" => $site->social_instagram,
            ":social_soundcloud" => $site->social_soundcloud,
            ":social_unsplash" => $site->social_unsplash,
            ":social_vimeo" => $site->social_vimeo,
            ":social_website" => $site->social_website,
            ":social_youtube" => $site->social_youtube,
            ":social_bitbucket" => $site->social_bitbucket,
            ":social_github" => $site->social_github
        );

        if ($site->getNewStatus()):
            $sql_text = "INSERT INTO site (
                           created, updated, site_name, bio_name, bio, location,
                           include_feed, include_bookmarks, include_following, 
                            allow_rss_feed, allow_atom_feed, social_flickr, 
                            social_instagram, social_soundcloud, social_unsplash, 
                            social_vimeo, social_website, social_youtube, profile_pic,
                            social_bitbucket, social_github
                       )
                        VALUES (
                                FROM_UNIXTIME(:created), FROM_UNIXTIME(:updated), 
                                :site_name, :bio_name, :bio, :location, :include_feed, 
                                :include_bookmarks, :include_following, :allow_rss_feed,
                                :allow_atom_feed, :social_flickr, :social_instagram, 
                                :social_soundcloud, :social_unsplash, :social_vimeo, 
                                :social_website, :social_youtube, :profile_pic,
                                :social_bitbucket, :social_github
                            )";
        else:
            $sql_text = "UPDATE site SET
                                    created = FROM_UNIXTIME(:created),
                                    updated = FROM_UNIXTIME(:updated),
                                    site_name = :site_name,
                                    bio_name = :bio_name,
                                    bio = :bio,
                                    location = :location,
                                    include_feed = :include_feed,
                                    include_bookmarks = :include_bookmarks,
                                    include_following = :include_following,
                                    allow_rss_feed = :allow_rss_feed,
                                    allow_atom_feed = :allow_atom_feed,
                                    social_flickr = :social_flickr, 
                                    social_instagram = :social_instagram, 
                                    social_soundcloud = :social_soundcloud,
                                    social_unsplash = :social_unsplash,
                                    social_vimeo = :social_vimeo,
                                    social_website = :social_website,
                                    social_youtube = :social_youtube,
                                    profile_pic = :profile_pic,
                                    social_bitbucket = :social_bitbucket,
                                    social_github = :social_github
								ORDER BY created ASC
								LIMIT 1";
        endif;

        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare($sql_text);
            $db_result = $sql->execute($sql_params);

            if (!$db_result) {
                error_log("Error while persisting site configuration.", 0);
                return -1;
            }

            if ($site->getNewStatus()) {
                $return_id = (int)$dbh->lastInsertId("id");
            } else {
                $return_id = $site->getID();
            }

        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return -1;
        }

        return $return_id;
    }

}


