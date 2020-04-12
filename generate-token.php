<?php

require_once "config.php";

spl_autoload_register(function ($className) {
	$className = str_replace("Firebase\\JWT", "php-jwt", $className);
	$path = explode("\\", $className);
	array_unshift($path, __DIR__);

	require_once implode(DIRECTORY_SEPARATOR, $path) . ".php";
});

ini_set('session.use_cookies', '0');
if(isset($_GET["request"])) session_id($_GET["request"]);
session_start();

use \Firebase\JWT\JWT;

if(empty($_SESSION["token"])){
	$_SESSION["token"] = JWT::encode([
		"iss" => $issuer,
		"sub" => $user,
		"exp" => time() + $validity
	], $private_key, $sign_algo);
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta type="viewport" content="initial-scale=1.0">
		<title>Autentizace pro vzdálený překlad</title>
		<style>
			*{margin:0; padding:0; box-sizing:border-box;}

			body{
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
				font-size: 17px;
				background: #ecf0f1;
				min-height: 100vh;
				display: flex;
				flex-flow: column;
				align-items: center;
				justify-content: center;
				padding: 32px;
			}

			h1{ font-size:2em; font-weight:600; margin-bottom:48px; }

			.check-icon{ color:#27ae60; height:100px; margin-bottom:32px; }

			p{ margin-bottom:8px; }

			p.info{ opacity:.8; font-size:.8em; margin-top:40px; }

			@media(prefers-color-scheme: dark){
				body{ background:#222f3e; color: #fff; }
				.check-icon{ color:#2ecc71; }
			}
		</style>
	</head>
	<body>
		<svg class="check-icon" aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
		<h1>Autentizace pro překladový server</h1>
		<p>Autentizační token byl úspěšně vygenerován pro <b><?=$user ?></b>.</p>
		<p>Nyní můžete tuto stránku zavřít a vrátit se zpět do editoru.</p>
		<p class="info">Po návratu může trvat několik sekund než editor načte nový token.</p>
	</body>
</html>
