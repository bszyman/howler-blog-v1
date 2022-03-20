<?php

include_once "common/model.php";
include_once "common/store.php";

class Bookmark extends Model
{
    public string $uuid;
    public string $title;
    public string $description;
    public string $url;
    public string $image_file;
    public int $public;

    function __construct()
    {
        parent::__construct();

        $this->uuid = uniqid();
        $this->title = "New Bookmark";
        $this->description = "";
        $this->url = "";
        $this->image_file = "";
        $this->public = 1;
    }
}

class BookmarkStore extends Store
{
    public static function fetchWithID(int $id): ?Bookmark
    {
        $dbh = parent::dataSource();
        $bookmark = null;

        try {
            $sql = $dbh->prepare("SELECT id,
                                        UNIX_TIMESTAMP(created) as created_f,
                                        UNIX_TIMESTAMP(updated) as updated_f,
                                        uuid, title, description, url, public, image_file
									FROM bookmarks
									WHERE id = :id");

            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $bookmark = new Bookmark();
                    $bookmark->setID($db_record->id);
                    $bookmark->setNewStatus(false);
                    $bookmark->setDateCreated($db_record->created_f);
                    $bookmark->setDateUpdated($db_record->updated_f);
                    $bookmark->uuid = $db_record->uuid;
                    $bookmark->title = $db_record->title;
                    $bookmark->description = $db_record->description;
                    $bookmark->url = $db_record->url;
                    $bookmark->image_file = $db_record->image_file;
                    $bookmark->public = $db_record->public;
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $bookmark;
    }

    public static function persist(Bookmark $bookmark): int
    {
        $bookmark->updateTimeToNow();

        $sql_params = array(
            ":created" => $bookmark->getDateCreatedTimestamp(),
            ":updated" => $bookmark->getDateUpdatedTimestamp(),
            ":uuid" => $bookmark->uuid,
            ":title" => $bookmark->title,
            ":description" => $bookmark->description,
            ":url" => $bookmark->url,
            ":image_file" => $bookmark->image_file,
            ":public" => $bookmark->public
        );

        if ($bookmark->getNewStatus()):
            $sql_text = "INSERT INTO bookmarks (
                           created, updated, uuid, title, description, url, image_file, public
                       )
                        VALUES (
                                FROM_UNIXTIME(:created), FROM_UNIXTIME(:updated), 
                                :uuid, :title, :description, :url, :image_file, :public
                            )";
        else:
            $sql_text = "UPDATE bookmarks SET
                                    created = FROM_UNIXTIME(:created),
                                    updated = FROM_UNIXTIME(:updated),
                                    uuid = :uuid,
                                    title = :title,
                                    description = :description,
                                    url = :url,
                                    image_file = :image_file,
                                    public = :public
								WHERE id = :id";

            $sql_params[":id"] = $bookmark->getID();
        endif;

        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare($sql_text);
            $db_result = $sql->execute($sql_params);

            if (!$db_result) {
                error_log("Error while persisting bookmark.", 0);
                return -1;
            }

            if ($bookmark->getNewStatus()) {
                $return_id = (int)$dbh->lastInsertId("id");
            } else {
                $return_id = $bookmark->getID();
            }

        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return -1;
        }

        return $return_id;
    }

    public static function deleteWithID(int $id): bool
    {
        $bookmark_to_delete = BookmarkStore::fetchWithID($id);

        if ($bookmark_to_delete == null) {
            return false;
        }

        $dbh = parent::dataSource();

        try {
            $bookmark_id = $bookmark_to_delete->getID();

            $sql = $dbh->prepare("DELETE FROM bookmarks WHERE id = :id");
            $sql->bindParam(":id", $bookmark_id);

            $db_result = $sql->execute();

            if (!$db_result) {
                return false;
            }
        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return false;
        }

        return true;
    }

    public static function paginate(int $page, int $number_of_rows, bool $include_private = false): ?StdClass
    {
        $dbh = parent::dataSource();

        $list = new ArrayObject();
        $page -= 1;
        $page = $page * $number_of_rows;

        try {
            if (!$include_private) {
                $private_filter = "WHERE public = 1";
            } else {
                $private_filter = "";
            }

            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										uuid, title, description, url, public, image_file
									FROM bookmarks
                                    $private_filter
									ORDER BY created DESC
									LIMIT $page, $number_of_rows");

            $db_result = $sql->execute();

            while ($db_record = $sql->fetchObject()) {
                $bookmark = new Bookmark();
                $bookmark->setID($db_record->id);
                $bookmark->setNewStatus(false);
                $bookmark->setDateCreated($db_record->created_f);
                $bookmark->setDateUpdated($db_record->updated_f);
                $bookmark->uuid = $db_record->uuid;
                $bookmark->title = $db_record->title;
                $bookmark->description = $db_record->description;
                $bookmark->url = $db_record->url;
                $bookmark->image_file = $db_record->image_file;
                $bookmark->public = $db_record->public;

                $list->append($bookmark);
            }

            $sql = $dbh->prepare("SELECT COUNT(id) as total_count FROM bookmarks $private_filter");

            $db_result = $sql->execute();
            if ($db_result) {
                $page_count = $sql->fetchObject()->total_count;
            } else {
                $page_count = 1;
            }

            $dbh = null;

        } catch (Exception $e) {
            error_log(get_class() . "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        $payload = new StdClass;
        $payload->items_list = $list;
        $payload->count = ceil(intval($page_count) / $number_of_rows);

        return $payload;
    }
}


