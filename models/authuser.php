<?php

include_once "common/model.php";
include_once "common/store.php";

class AuthUser extends Model
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
        $this->password = "";;
        $this->full_name = "";;
        $this->enabled = 0;
        $this->administrator = 0;
    }
}

class AuthUserStore extends Store
{	
	public static function fetchWithEmailAddress(string $emailAddress): ?AuthUser
    {
		$dbh = parent::dataSource();
		$user = null;
		
		try {
			$sql = $dbh->prepare("SELECT id,
										UNIX_TIMESTAMP(created) as created_f,
										UNIX_TIMESTAMP(updated) as updated_f,
										email,
										full_name, 
										enabled,
										administrator
									FROM users 
									WHERE email = :email");
			$sql->bindParam(":email", $emailAddress);
			
			$db_result = $sql->execute();
			
			if ($db_result) {
				if ($db_record = $sql->fetchObject()) {
					$user = new AuthUser();
					$user->setID($db_record->id);
					$user->setNewStatus(false);
					$user->setDateCreated($db_record->created_f);
					$user->setDateUpdated($db_record->updated_f);
					$user->email = $db_record->email;
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
			
			if ($db_result) 
			{
				if ($db_record = $sql->fetchObject()) 
				{
					$account_enabled = (bool)$db_record->enabled;
					
					if ($account_enabled) 
					{
						$password_hash = $db_record->password;
						$password_correct = password_verify($password, $password_hash);
					}
				}
			}
			
			$dbh = null;
		} catch (Exception $e) {
			error_log(get_class() .  "@" . __FUNCTION__ . ": " . $e->getMessage(), 0);
			return false;
		}
				
		return $password_correct;
	}
}
	
?>