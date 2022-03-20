<?php

include_once "common/model.php";
include_once "common/store.php";

class Friend extends Model
{
    public string $uuid;
    public string $name;
    public string $url;
    public string $image_file;

    function __construct()
    {
        parent::__construct();

        $this->uuid = uniqid();
        $this->name = "";
        $this->url = "";
        $this->image_file = "";
    }
}

class FriendStore extends Store
{
    public static function fetchWithID(int $id): ?Friend
    {
        $dbh = parent::dataSource();
        $friend = null;

        try {
            $sql = $dbh->prepare("SELECT id,
                                        UNIX_TIMESTAMP(created) as created_f,
                                        UNIX_TIMESTAMP(updated) as updated_f,
                                        uuid, name, url, image_file
									FROM friends
									WHERE id = :id");

            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $friend = new Friend();
                    $friend->setID($db_record->id);
                    $friend->setNewStatus(false);
                    $friend->setDateCreated($db_record->created_f);
                    $friend->setDateUpdated($db_record->updated_f);
                    $friend->uuid = $db_record->uuid;
                    $friend->name = $db_record->name;
                    $friend->url = $db_record->url;
                    $friend->image_file = $db_record->image_file;
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $friend;
    }

    public static function persist(Friend $friend): int
    {
        $friend->updateTimeToNow();

        $sql_params = array(":created" => $friend->getDateCreatedTimestamp(),
            ":updated" => $friend->getDateUpdatedTimestamp(),
            ":uuid" => $friend->uuid,
            ":name" => $friend->name,
            ":url" => $friend->url,
            ":image_file" => $friend->image_file
        );

        if ($friend->getNewStatus()):
            $sql_text = "INSERT INTO friends (created, updated, uuid, name, url, image_file)
                        VALUES (
                                FROM_UNIXTIME(:created), FROM_UNIXTIME(:updated), 
                                :uuid, :name, :url, :image_file
                            )";
        else:
            $sql_text = "UPDATE friends SET
                                    created = FROM_UNIXTIME(:created),
                                    updated = FROM_UNIXTIME(:updated),
                                    uuid = :uuid,
                                    name = :name,
                                    url = :url,
                                    image_file = :image_file
								WHERE id = :id";

            $sql_params[":id"] = $friend->getID();
        endif;

        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare($sql_text);
            $db_result = $sql->execute($sql_params);

            if (!$db_result) {
                error_log("Error while persisting friend.", 0);
                return -1;
            }

            if ($friend->getNewStatus()) {
                $return_id = (int)$dbh->lastInsertId("id");
            } else {
                $return_id = $friend->getID();
            }

        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return -1;
        }

        return $return_id;
    }

    public static function deleteWithID(int $id): bool
    {
        $friend_to_delete = FriendStore::fetchWithID($id);

        if ($friend_to_delete == null) {
            return false;
        }

        $dbh = parent::dataSource();

        try {
            $friend_id = $friend_to_delete->getID();

            $sql = $dbh->prepare("DELETE FROM friends WHERE id = :id");
            $sql->bindParam(":id", $friend_id);

            $db_result = $sql->execute();

            if (!$db_result) {
                return false;
            }
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return false;
        }

        return true;
    }

    public static function paginate(int $page, int $number_of_rows): ?StdClass
    {
        $dbh = parent::dataSource();

        $list = new ArrayObject();
        $page -= 1;
        $page = $page * $number_of_rows;

        try {

            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										uuid, name, url, image_file
									FROM friends
									ORDER BY name ASC
									LIMIT $page, $number_of_rows");

            $db_result = $sql->execute();

            while ($db_record = $sql->fetchObject()) {
                $friend = new Friend();
                $friend->setID($db_record->id);
                $friend->setNewStatus(false);
                $friend->setDateCreated($db_record->created_f);
                $friend->setDateUpdated($db_record->updated_f);
                $friend->uuid = $db_record->uuid;
                $friend->name = $db_record->name;
                $friend->url = $db_record->url;
                $friend->image_file = $db_record->image_file;

                $list->append($friend);
            }

            $sql = $dbh->prepare("SELECT COUNT(id) as total_count FROM friends");

            $db_result = $sql->execute();
            $page_count = $sql->fetchObject();

            $dbh = null;

        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        $payload = new StdClass;
        $payload->items_list = $list;
        $payload->count = ceil(intval($page_count->total_count) / $number_of_rows);

        return $payload;
    }
}


