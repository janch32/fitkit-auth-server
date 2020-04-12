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

if(isset($_SESSION["token"])){
	// Token je vygenerovaný, takže požadavek již proběhl úspěšně
}elseif(isset($_GET["reject"]) || isset($_SESSION["reject"])){
	// Požadavek zamítnut (již byl nebo teď je)
	$_SESSION["reject"] = true;
	unset($_SESSION["token"]);

}elseif(isset($_GET["nonce"]) && $_GET["nonce"] === $_SESSION["nonce"]){
	// Verifikační řetězec souhlasí, generujeme token
	$_SESSION["token"] = JWT::encode([
		"iss" => $issuer,
		"sub" => $user,
		"exp" => time() + $validity
	], $private_key, $sign_algo);
}

unset($_SESSION["nonce"]);

// Požadavek nebyl přijat ani odmítnut. Vytvoříme adresy pro tlačítka dotazu
if(!isset($_SESSION["reject"]) && !isset($_SESSION["token"])){
	// Nonce je kryptograficky náhodný řetězec pro ověření, zda požadavek
	// skutečně zadal uživatel. Předchází tomu, aby si aplikace bez potvrzení
	// uživatele zažádala o token
	$_SESSION["nonce"] = bin2hex(openssl_random_pseudo_bytes(12));
	$accept_query = $_GET;
	$accept_query["nonce"] = $_SESSION["nonce"];

	$reject_query = $_GET;
	$reject_query["reject"] = true;
}

// Následuje část s generováním HTML stránky

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta type="viewport" content="initial-scale=1.0">
		<title>Autentizace aplikace</title>
		<style>
			*{ margin:0; padding:0; box-sizing:border-box; }
			*:focus{ outline:none; }

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

			.icon{ margin-bottom:32px; }
			.icon.auth{ color:#2980b9; height:120px; }
			.icon.accept{ color:#27ae60; height:100px; }
			.icon.reject{ color:#c44569; height:100px; }

			h1{ font-size:2em; font-weight:600; margin-bottom:48px; }

			a{ text-decoration:none; color:#2980b9; font-weight:500; display:inline-block; }
			a:focus{ text-decoration:underline; }

			.button-container{ margin-top:48px; }
			.button{
				color: white;
				background: #2980b9;
				padding: 12px 28px;
				border-radius: 100px;
				transition: all 150ms;
				margin-left: 24px;
			}
			.button:focus,.button:active{ background:#3498db; text-decoration:none; }
			.button:active{ transform: scale(0.95); }

			p{ margin-bottom:8px; max-width:600px; text-align:center; }
			p.info{ opacity:.8; font-size:.8em; margin-top:32px; }

			/** Pro uživatele s tmavým režimem prohlížeče, aby neměli vypálené oči */
			@media(prefers-color-scheme: dark){
				body{ background:#222f3e; color: #fff; }
				.icon.auth{ color:#3498db; }
				.icon.accept{ color:#2ecc71; }
				.icon.reject{ color:#cf6a87; }
				a{ color:#3498db; }
			}
		</style>
	</head>
	<body>
		<?php if(isset($_SESSION["reject"])){ // Požadavek zamítnut ?>
		<svg class="icon reject" aria-hidden="true" focusable="false" data-prefix="far" data-icon="times-circle" class="svg-inline--fa fa-times-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm101.8-262.2L295.6 256l62.2 62.2c4.7 4.7 4.7 12.3 0 17l-22.6 22.6c-4.7 4.7-12.3 4.7-17 0L256 295.6l-62.2 62.2c-4.7 4.7-12.3 4.7-17 0l-22.6-22.6c-4.7-4.7-4.7-12.3 0-17l62.2-62.2-62.2-62.2c-4.7-4.7-4.7-12.3 0-17l22.6-22.6c4.7-4.7 12.3-4.7 17 0l62.2 62.2 62.2-62.2c4.7-4.7 12.3-4.7 17 0l22.6 22.6c4.7 4.7 4.7 12.3 0 17z"></path></svg>
		<h1>Požadavek zamítnut</h1>
		<p>Požadavek na autentizaci byl zamítnut uživatelem.</p>
		<p>Nyní můžete tuto stránku zavřít a vrátit se zpět do aplikace.</p>
		<p class="info">Změna aktuálního stavu může aplikaci několik sekund tvrat.</p>

		<?php }elseif(isset($_SESSION["token"])){ // Požadavek přijat ?>
		<svg class="icon accept" aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
		<h1>Autentizační token vytvořen</h1>
		<p>Autentizační token byl úspěšně vygenerován pro uživatele <b><?= $user ?></b>.</p>
		<p>Nyní můžete tuto stránku zavřít a vrátit se zpět do aplikace.</p>
		<p class="info">Potvrzení může aplikaci několik sekund trvat.</p>

		<?php }else{ // Nový požadavek, zobrazit dialog uživateli ?>
		<svg class="icon auth" aria-hidden="true" focusable="false" data-prefix="far" data-icon="id-badge" class="svg-inline--fa fa-id-badge fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M336 0H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48zm0 464H48V48h288v416zM144 112h96c8.8 0 16-7.2 16-16s-7.2-16-16-16h-96c-8.8 0-16 7.2-16 16s7.2 16 16 16zm48 176c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm-89.6 128h179.2c12.4 0 22.4-8.6 22.4-19.2v-19.2c0-31.8-30.1-57.6-67.2-57.6-10.8 0-18.7 8-44.8 8-26.9 0-33.4-8-44.8-8-37.1 0-67.2 25.8-67.2 57.6v19.2c0 10.6 10 19.2 22.4 19.2z"></path></svg>
		<h1>Požadavek aplikace na autentizaci</h1>
		<p>Aplikace <b><?= htmlspecialchars($_GET["appname"]) ?></b> vyžaduje autentizaci.</p>
		<p>Potvrzením získá aplikace autentizační token a bude moci pod vašim jménem navázat spojení s překladovým serverem FITkit projektů.</p>
		<p class="info">Tímto aplikace <b>nezíská</b> přístup k vašemu účtu, jediná předaná informace je vaše uživatelské jméno (<b><?= $user ?></b>)</p>
		<div class="button-container">
			<a href="?<?= http_build_query($reject_query) ?>">Zamítnout</a>
			<a class="button" href="?<?= http_build_query($accept_query) ?>">Povolit požadavek</a>
		</div>

		<?php } ?>
	</body>
</html>
