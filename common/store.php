<?php

// Base class for all repository classes

class Store {
	protected static function dataSource(): PDO {
		$ini_conf = parse_ini_file("app_settings.ini", true);
		$ds = $ini_conf["database_settings"];
		$host = $ds["host"];
		$dbname = $ds["dbname"];
		$username = $ds["username"];
		$password = $ds["password"];
		$port = $ds["port"];

		$dbh = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		return $dbh;
	}

}
