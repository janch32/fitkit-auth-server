<?php

ini_set('session.use_cookies', '0');
if(isset($_GET["request"])) session_id($_GET["request"]);
session_start();

if(isset($_GET["new"])){
	http_response_code(202);
	echo session_id();
	exit;
}

if(empty($_SESSION["token"])){
	http_response_code(204);
	exit;
}

echo $_SESSION["token"];

?>
