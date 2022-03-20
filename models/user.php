<?php

include_once "common/model.php";
include_once "common/store.php";

class User extends Model
{
    public string $email;
    public string $password;
    public string $full_name;
    public int $enabled;
    public int $administrator;

    function __construct()
    {
        parent::__construct();

        $this->email = "";
        $this->password = "";
        $this->full_name = "";
        $this->enabled = 0;
        $this->administrator = 0;
    }
}

class UserStore extends Store
{
    public static function fetchWithID(int $id): ?User
    {
        $dbh = parent::dataSource();
        $user = null;

        try {
            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										email,
										password,
										full_name, 
										enabled,
										administrator
									FROM users 
									WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $user = new User();
                    $user->setID($db_record->id);
                    $user->setNewStatus(false);
                    $user->setDateCreated($db_record->created_f);
                    $user->setDateUpdated($db_record->updated_f);
                    $user->email = $db_record->email;
                    $user->password = $db_record->password;
                    $user->full_name = $db_record->full_name;
                    $user->enabled = (int)$db_record->enabled;
                    $user->administrator = (int)$db_record->administrator;
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);;
            return null;
        }

        return $user;
    }

    public static function fetchWithUsername(string $username): ?User
    {
        $dbh = parent::dataSource();
        $user = null;

        try {
            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										email,
										password,
										full_name, 
										enabled,
										administrator
									FROM users 
									WHERE username = :username");
            $sql->bindParam(":username", $username);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $user = new User();
                    $user->setID($db_record->id);
                    $user->setNewStatus(false);
                    $user->setDateCreated($db_record->created_f);
                    $user->setDateUpdated($db_record->updated_f);
                    $user->email = $db_record->email;
                    $user->password = $db_record->password;
                    $user->full_name = $db_record->full_name;
                    $user->enabled = (int)$db_record->enabled;
                    $user->administrator = (int)$db_record->administrator;
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $user;
    }

    public static function fetchAll(): ?ArrayObject
    {
        $dbh = parent::dataSource();
        $users = new ArrayObject();

        try {
            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										email,
										password,
										full_name, 
										enabled,
										administrator
									FROM users 
									ORDER BY full_name ASC");

            $db_result = $sql->execute();

            if ($db_result) {
                while ($db_record = $sql->fetchObject()) {
                    $user = new User();
                    $user->setID($db_record->id);
                    $user->setNewStatus(false);
                    $user->setDateCreated($db_record->created_f);
                    $user->setDateUpdated($db_record->updated_f);
                    $user->email = $db_record->email;
                    $user->password = $db_record->password;
                    $user->full_name = $db_record->full_name;
                    $user->enabled = (int)$db_record->enabled;
                    $user->administrator = (int)$db_record->administrator;

                    $users->append($user);
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return null;
        }

        return $users;
    }

    public static function paginate(int $page, int $number_of_rows): ?StdClass
    {
        $dbh = parent::dataSource();

        $list = new ArrayObject();
        $page = $page - 1;
        $page = $page * $number_of_rows;

        try {
            $sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										email,
										full_name, 
										enabled,
										administrator
									FROM users 
									ORDER BY full_name ASC
									LIMIT $page, $number_of_rows");

            $db_result = $sql->execute();

            if (!$db_result) {
                return null;
            }

            while ($db_record = $sql->fetchObject()) {
                $user = new User();
                $user->setID($db_record->id);
                $user->setNewStatus(false);
                $user->setDateCreated($db_record->created_f);
                $user->setDateUpdated($db_record->updated_f);
                $user->email = $db_record->email;
                $user->full_name = $db_record->full_name;
                $user->enabled = (int)$db_record->enabled;
                $user->administrator = (int)$db_record->administrator;

                $list->append($user);
            }

            $sql = $dbh->prepare("SELECT COUNT(id) as total_count FROM users");

            $db_result = $sql->execute();

            if (!$db_result) {
                return null;
            }

            $page_count = $sql->fetchObject();

            $dbh = null;

        } catch (Exception $e) {
            error_log("#ERR004 " . $e->getMessage(), 0);
            return null;
        }

        $payload = new StdClass;
        $payload->items_list = $list;
        $payload->count = ceil(intval($page_count->total_count) / $number_of_rows);

        return $payload;
    }

    public static function persist(User $user): int
    {
        $user->updateTimeToNow();
        $return_id = 0;

        $sql_params = array(":created" => $user->getDateCreatedTimestamp(),
            ":updated" => $user->getDateUpdatedTimestamp(),
            ":email" => $user->email,
            ":full_name" => $user->full_name,
            ":enabled" => $user->enabled,
            ":administrator" => $user->administrator);

        if ($user->getNewStatus()):
            $sql_text = "INSERT INTO users (created, updated, email, full_name, 
											enabled, administrator)
										VALUES (FROM_UNIXTIME(:created), FROM_UNIXTIME(:updated), 
											:email, :full_name, :enabled, :administrator)";
        else:
            $sql_text = "UPDATE users SET
                                    created = FROM_UNIXTIME(:created),
                                    updated = FROM_UNIXTIME(:updated),
                                    email = :email,
                                    full_name = :full_name,
                                    enabled = :enabled,
                                    administrator = :administrator
								WHERE id = :id";

            $sql_params[":id"] = $user->getID();
        endif;

        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare($sql_text);
            $db_result = $sql->execute($sql_params);

            if (!$db_result) {
                error_log("Error while persisting user model.", 0);
                return -1;
            }

            if ($user->getNewStatus()) {
                $return_id = (int)$dbh->lastInsertId("id");
            } else {
                $return_id = $user->getID();
            }

        } catch (Exception $e) {
            error_log("#ERR005 " . $e->getMessage(), 0);
            return -1;
        }

        return $return_id;
    }

    public static function createInitialPassword(int $id, string $password): void
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("SELECT id,
										password
									FROM users 
									WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    if (!empty($db_record->password)) {
                        return;
                    }
                }
            }

            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = $dbh->prepare("UPDATE users SET password = :password WHERE id = :id");
            $sql->bindParam(":password", $new_password_hash);
            $sql->bindParam(":id", $id);
            $sql->execute();

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }

    public static function setPassword(int $id, string $new_password, string $old_password): void
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("SELECT id,
										password
									FROM users 
									WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $existing_password_hash = $db_record->password;

                    $password_correct = password_verify($old_password, $existing_password_hash);

                    if ($password_correct) {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                        $sql = $dbh->prepare("UPDATE users SET password = :password WHERE id = :id");
                        $sql->bindParam(":password", $new_password_hash);
                        $sql->bindParam(":id", $id);
                        $sql->execute();
                    }
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }

    public static function resetPassword(int $id, string $new_password): void
    {
        $dbh = parent::dataSource();

        try {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = $dbh->prepare("UPDATE users SET password = :password WHERE id = :id");
            $sql->bindParam(":password", $new_password_hash);
            $sql->bindParam(":id", $id);
            $sql->execute();

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }

    public static function verifyLogin(string $email, string $password): bool
    {
        $dbh = parent::dataSource();
        $password_correct = false;

        try {
            $sql = $dbh->prepare("SELECT email,
										password,
										enabled
									FROM users 
									WHERE email = :email");
            $sql->bindParam(":email", $email);

            $db_result = $sql->execute();

            if ($db_result) {
                if ($db_record = $sql->fetchObject()) {
                    $account_enabled = (bool)$db_record->enabled;

                    if ($account_enabled) {
                        $password_hash = $db_record->password;

                        $password_correct = password_verify($password, $password_hash);
                    }
                }
            }

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
            return $password_correct;
        }

        return $password_correct;
    }

    public static function deleteUser(int $id): bool
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("DELETE FROM users WHERE id = :id");
            $sql->bindParam(":id", $id);

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

    public static function assignAdminPrivileges(int $id): void
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("UPDATE users SET administrator = 1 WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }

    public static function removeAdminPrivileges(int $id): void
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("UPDATE users SET administrator = 0 WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }

    public static function setAccessEnabled(int $id): void
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("UPDATE users SET enabled = 1 WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }

    public static function setAccessDisabled(int $id): void
    {
        $dbh = parent::dataSource();

        try {
            $sql = $dbh->prepare("UPDATE users SET enabled = 0 WHERE id = :id");
            $sql->bindParam(":id", $id);

            $db_result = $sql->execute();

            $dbh = null;
        } catch (Exception $e) {
            error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
        }
    }
}
