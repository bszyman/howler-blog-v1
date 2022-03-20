<?php

class CSRFProtect {
	
	public static function generateToken(string $page_name): string {
		$csrf_token = base64_encode(openssl_random_pseudo_bytes(32));
		setcookie("csrf_token" . $page_name, $csrf_token);
		
		return $csrf_token;
	}
	
	public static function verifyToken(string $cookie_token, string $form_token): bool {
		if (strcmp($cookie_token, $form_token) == 0) {
			return true;
		}
		
		return false;
	}
	
	public static function getTokenKeyForPageNamed(string $page_name): string {
		return "csrf_token" . $page_name;
	}
	
}

?>