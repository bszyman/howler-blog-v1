<?php

include_once "common/model.php";
include_once "common/store.php";
include_once "models/user.php";

class Post extends Model
{
    public string $post_text;
    public int $user;
    public User $user_object;
    public StdClass $user_info;
    public int $in_reply_to;
    public string $quote_from;
    public string $quote_contents;
    public int $published;
    public int $public;

    function __construct()
    {
        parent::__construct();

        $this->post_text = "";
        $this->user = 0;
        $this->user_object = new User();
        $this->user_info = new StdClass;
        $this->in_reply_to = 0;
        $this->quote_from = "";
        $this->quote_contents = "";
        $this->published = 0;
        $this->public = 0;
    }

    public function summary(): string {
        if (strlen($this->post_text) > 100) {
            return substr($this->post_text, 0, 97) . "...";
        } else {
            return $this->post_text;
        }
    }

    public function getDateCreatedAs2822(): string { return $this->created->format(DateTime::RFC2822); }
}

class PostStore extends Store
{
    public static function fetchWithID(int $id): ?Post
    {
        $dbh = parent::dataSource();
        $post = null;

        try {
            $sql = $dbh->prepare("SELECT id,
                                        UNIX_TIMESTAMP(created) as created_f,
                                        UNIX_TIMESTAMP(updated) as updated_f,
                                        post_text, user, in_reply_to, quote_from,
                                        quote_contents, published, public
									FROM posts
									WHERE id = :id");

            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $post = new Post();
                    $post->setID($db_record->id);
                    $post->setNewStatus(false);
                    $post->setDateCreated($db_record->created_f);
                    $post->setDateUpdated($db_record->updated_f);
                    $post->post_text = $db_record->post_text;
                    $post->user = $db_record->user;
                    $post->in_reply_to = $db_record->in_reply_to;
                    $post->quote_from = $db_record->quote_from;
                    $post->quote_contents = $db_record->quote_contents;
                    $post->published = $db_record->published;
                    $post->public = $db_record->public;

                    $user = UserStore::fetchWithID($db_record->user);
                    if ($user) {
                        $post->user_object = $user;
                    }
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $post;
    }

    public static function persist(Post $post): int
    {
        $post->updateTimeToNow();

        $sql_params = array(
            ":created" => $post->getDateCreatedTimestamp(),
            ":updated" => $post->getDateUpdatedTimestamp(),
            ":post_text" => $post->post_text,
            ":user" => $post->user,
            ":in_reply_to" => $post->in_reply_to,
            ":quote_from" => $post->quote_from,
            ":quote_contents" => $post->quote_contents,
            ":published" => $post->published,
            ":public" => $post->public
        );

        if ($post->getNewStatus()):
            $sql_text = "INSERT INTO posts (
                           created, updated, post_text, user, in_reply_to,
                           quote_from, quote_contents, published, public
                       )
                        VALUES (
                                FROM_UNIXTIME(:created), FROM_UNIXTIME(:updated), 
                                :post_text, :user, :in_reply_to, :quote_from, :quote_contents, 
                                :published, :public
                            )";
        else:
            $sql_text = "UPDATE posts SET
                                    created = FROM_UNIXTIME(:created),
                                    updated = FROM_UNIXTIME(:updated),
                                    post_text = :post_text,
                                    user = :user,
                                    in_reply_to = :in_reply_to,
                                    quote_from = :quote_from,
                                    quote_contents = :quote_contents,
                                    published = :published,
                                    public = :public
								WHERE id = :id";

            $sql_params[":id"] = $post->getID();
        endif;

        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare($sql_text);
            $db_result = $sql->execute($sql_params);

            if (!$db_result) {
                error_log("Error while persisting post.", 0);
                return -1;
            }

            if ($post->getNewStatus()) {
                $return_id = (int)$dbh->lastInsertId("id");
            } else {
                $return_id = $post->getID();
            }

        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return -1;
        }

        return $return_id;
    }

    public static function deleteWithID(int $id): bool
    {
        $post_to_delete = PostStore::fetchWithID($id);

        if ($post_to_delete == null) {
            return false;
        }

        $dbh = parent::dataSource();

        try {
            $post_id = $post_to_delete->getID();

            $sql = $dbh->prepare("DELETE FROM posts WHERE id = :id");
            $sql->bindParam(":id", $post_id);

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
										post_text, user, in_reply_to, quote_from, quote_contents,
                                           published, public
									FROM posts
                                    $private_filter
									ORDER BY created DESC
									LIMIT $page, $number_of_rows");

            $db_result = $sql->execute();

            while ($db_record = $sql->fetchObject()) {
                $post = new Post();
                $post->setID($db_record->id);
                $post->setNewStatus(false);
                $post->setDateCreated($db_record->created_f);
                $post->setDateUpdated($db_record->updated_f);
                $post->post_text = $db_record->post_text;
                $post->user = $db_record->user;
                $post->in_reply_to = $db_record->in_reply_to;
                $post->quote_from = $db_record->quote_from;
                $post->quote_contents = $db_record->quote_contents;
                $post->published = $db_record->published;
                $post->public = $db_record->public;

                $user = UserStore::fetchWithID($db_record->user);
                if ($user) {
                    $post->user_object = $user;
                }

                $list->append($post);
            }

            $sql = $dbh->prepare("SELECT COUNT(id) as total_count FROM posts $private_filter");

            $db_result = $sql->execute();
            if ($db_result) {
                $page_count = $sql->fetchObject()->total_count;
            } else {
                $page_count = 1;
            }

            $dbh = null;

        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        $payload = new StdClass;
        $payload->items_list = $list;
        $payload->count = ceil(intval($page_count) / $number_of_rows);

        return $payload;
    }

    public static function mostRecent(): ?ArrayObject
    {
        $dbh = parent::dataSource();
        $list = new ArrayObject();

        try {
            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										post_text, user, in_reply_to, quote_from, quote_contents,
                                           published, public
									FROM posts
                                    WHERE public = 1
									ORDER BY created DESC
									LIMIT 0, 100");

            $db_result = $sql->execute();

            while ($db_record = $sql->fetchObject()) {
                $post = new Post();
                $post->setID($db_record->id);
                $post->setNewStatus(false);
                $post->setDateCreated($db_record->created_f);
                $post->setDateUpdated($db_record->updated_f);
                $post->post_text = $db_record->post_text;
                $post->in_reply_to = $db_record->in_reply_to;
                $post->quote_from = $db_record->quote_from;
                $post->quote_contents = $db_record->quote_contents;
                $post->published = $db_record->published;
                $post->public = $db_record->public;

                $user = UserStore::fetchWithID($db_record->user);
                if ($user) {
                    $post->user_object = $user;
                }

                $list->append($post);
            }

            $dbh = null;

        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $list;
    }

    public static function paginateForFeed(int $page, int $page_size): ?StdClass
    {
        $dbh = parent::dataSource();

        $list = array();
        $page -= 1;
        $page = $page * $page_size;

        try {
            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										post_text, user, in_reply_to, quote_from, quote_contents,
                                           published, public
									FROM posts
                                    WHERE public = 1
                                        AND published = 1
									ORDER BY created DESC
									LIMIT $page, $page_size");

            $db_result = $sql->execute();

            while ($db_record = $sql->fetchObject()) {
                $user = UserStore::fetchWithID($db_record->user);
                $slimmed_user = new StdClass;
                if ($user) {
                    $slimmed_user->name = $user->full_name;
                }

                $post = new Post();
                $post->setID($db_record->id);
                $post->setNewStatus(false);
                $post->setDateCreated($db_record->created_f);
                $post->setDateUpdated($db_record->updated_f);
                $post->post_text = $db_record->post_text;
                $post->user = $db_record->user;
                $post->user_info = $slimmed_user;
                $post->in_reply_to = $db_record->in_reply_to;
                $post->quote_from = $db_record->quote_from;
                $post->quote_contents = $db_record->quote_contents;
                $post->published = $db_record->published;
                $post->public = $db_record->public;
                array_push($list, $post);
                //$list->append($post);
            }

            $sql = $dbh->prepare("SELECT COUNT(id) as total_count FROM posts WHERE public = 1 AND published = 1");

            $db_result = $sql->execute();
            $total_count = $sql->fetchObject()->total_count;

            if ($db_result) {
                $has_next = (($page+1) * $page_size) < $total_count;
                $has_previous = ((($page+1) * $page_size) - $page_size) > 0;
            } else {
                $has_next = false;
                $has_previous = false;
            }

            $dbh = null;

        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        $payload = new StdClass;
        $payload->posts = $list;
        $payload->hasNext = $has_next;
        $payload->hasPrevious = $has_previous;

        return $payload;
    }
}


